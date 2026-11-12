<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Order;
use App\Entity\Performance;
use App\Entity\PerformanceSeat;
use App\Entity\Price;
use App\Entity\Tariff;
use App\Entity\Ticket;
use App\Entity\User;
use App\Enum\OrderChannel;
use App\Enum\OrderStatus;
use App\Enum\PerformanceStatus;
use App\Enum\SeatStatus;
use App\Enum\TicketStatus;
use App\Exception\ReservationException;
use App\Exception\SeatUnavailableException;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;

/**
 * Règles métier de la billetterie : réservation temporaire, paiement,
 * annulation et remboursement. Toutes les méthodes qui modifient des places
 * s'exécutent dans une transaction avec verrou pessimiste (SELECT ... FOR UPDATE).
 */
class ReservationService
{
    public const MAX_SEATS_PER_ORDER = 20;
    public const CANCELLATION_DEADLINE_HOURS = 24;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly int $holdMinutes = 10,
    ) {
    }

    /**
     * Réserve temporairement des places et crée une commande PENDING.
     *
     * @param array<int, string> $seatTariffs [id PerformanceSeat => code tarif]
     *                                        Le canal BOX_OFFICE doit être réservé par l'appelant au personnel
     *                                        (guichetier ou administrateur).
     *
     * @throws SeatUnavailableException si une place n'est pas disponible
     * @throws ReservationException     si une règle métier est violée
     */
    public function hold(
        Performance $performance,
        array $seatTariffs,
        ?User $customer,
        OrderChannel $channel = OrderChannel::WEB,
        ?User $seller = null,
        ?\DateTimeImmutable $now = null,
    ): Order {
        $now ??= new \DateTimeImmutable();
        $count = \count($seatTariffs);

        if ($count < 1 || $count > self::MAX_SEATS_PER_ORDER) {
            throw new ReservationException(sprintf('Une commande doit contenir entre 1 et %d places.', self::MAX_SEATS_PER_ORDER));
        }

        $tariffs = $this->loadTariffs(array_values(array_unique($seatTariffs)), $count, $channel === OrderChannel::BOX_OFFICE);

        // Libère d'abord les réservations expirées (transaction séparée).
        $this->releaseExpired($now);

        return $this->em->wrapInTransaction(function () use ($performance, $seatTariffs, $customer, $channel, $seller, $now, $tariffs): Order {
            // Verrou partagé : une annulation de la représentation attend la fin des réservations en cours.
            $this->em->refresh($performance, LockMode::PESSIMISTIC_READ);

            if ($performance->getStatus() !== PerformanceStatus::SCHEDULED) {
                throw new ReservationException('Cette représentation est annulée.');
            }
            if ($performance->getStartsAt() === null || $performance->getStartsAt() <= $now) {
                throw new ReservationException('Cette représentation a déjà commencé.');
            }

            $ids = array_keys($seatTariffs);
            sort($ids);

            $seats = $this->lockSeats('ps.performance = :perf AND ps.id IN (:ids)', ['perf' => $performance, 'ids' => $ids]);
            if (\count($seats) !== \count($ids)) {
                throw new ReservationException('Une des places demandées n\'existe pas pour cette représentation.');
            }

            $order = (new Order())
                ->setChannel($channel)
                ->setCustomer($customer)
                ->setSeller($seller)
                ->setStatus(OrderStatus::PENDING)
                ->setCreatedAt($now);
            $this->em->persist($order);

            $holdUntil = $now->modify(sprintf('+%d minutes', $this->holdMinutes));
            $total = 0;

            foreach ($seats as $ps) {
                if ($ps->getStatus() !== SeatStatus::AVAILABLE) {
                    throw new SeatUnavailableException(sprintf('La place %s n\'est plus disponible.', (string) $ps->getSeat()));
                }

                $tariff = $tariffs[$seatTariffs[$ps->getId()]];
                $price = $this->em->getRepository(Price::class)->findOneBy([
                    'performance' => $performance,
                    'zone' => $ps->getSeat()?->getZone(),
                    'tariff' => $tariff,
                ]);
                if ($price === null) {
                    throw new ReservationException(sprintf('Aucun prix défini pour la place %s avec le tarif « %s ».', (string) $ps->getSeat(), $tariff->getName()));
                }

                $ps->setStatus(SeatStatus::HELD)->setHeldUntil($holdUntil)->setCurrentOrder($order);

                $ticket = (new Ticket())
                    ->setOrder($order)
                    ->setPerformanceSeat($ps)
                    ->setTariff($tariff)
                    ->setPricePaidCents($price->getAmountCents()) // prix figé
                    ->setStatus(TicketStatus::PENDING);
                $this->em->persist($ticket);

                $total += $price->getAmountCents();
            }

            $order->setTotalCents($total);

            return $order;
        });
    }

    /**
     * Confirme le paiement : places vendues, billets valides.
     *
     * @throws ReservationException si la commande n'est plus payable ou si la réservation a expiré
     */
    public function pay(Order $order, ?\DateTimeImmutable $now = null): void
    {
        $now ??= new \DateTimeImmutable();

        $paid = $this->em->wrapInTransaction(function () use ($order, $now): bool {
            $this->em->refresh($order, LockMode::PESSIMISTIC_WRITE);
            $this->assertPending($order, 'payée');

            $seats = $this->lockSeats('ps.currentOrder = :o', ['o' => $order]);

            if ($seats === []) {
                $this->releaseOrder($order, OrderStatus::EXPIRED);

                return false;
            }
            foreach ($seats as $ps) {
                if ($ps->getStatus() !== SeatStatus::HELD || $ps->getHeldUntil() === null || $ps->getHeldUntil() <= $now) {
                    $this->releaseOrder($order, OrderStatus::EXPIRED);

                    return false;
                }
            }

            foreach ($seats as $ps) {
                $ps->setStatus(SeatStatus::SOLD)->setHeldUntil(null);
            }
            foreach ($this->em->getRepository(Ticket::class)->findBy(['order' => $order]) as $ticket) {
                $ticket->setStatus(TicketStatus::VALID);
            }
            $order->setStatus(OrderStatus::PAID)->setPaidAt($now);

            return true;
        });

        if (!$paid) {
            throw new ReservationException('Votre réservation a expiré : les places ont été libérées.');
        }
    }

    /** Paiement refusé : la commande est FAILED et les places sont libérées immédiatement. */
    public function failPayment(Order $order): void
    {
        $this->em->wrapInTransaction(function () use ($order): void {
            $this->em->refresh($order, LockMode::PESSIMISTIC_WRITE);
            $this->assertPending($order, 'marquée comme échouée');
            $this->releaseOrder($order, OrderStatus::FAILED);
        });
    }

    /**
     * Libère les réservations expirées. Retourne le nombre de commandes expirées.
     * Appelable par la commande app:release-expired-holds (planifiable).
     */
    public function releaseExpired(?\DateTimeImmutable $now = null): int
    {
        $now ??= new \DateTimeImmutable();

        return $this->em->wrapInTransaction(function () use ($now): int {
            $expired = $this->lockSeats(
                'ps.status = :held AND ps.heldUntil <= :now',
                ['held' => SeatStatus::HELD, 'now' => $now],
            );

            $orders = [];
            foreach ($expired as $ps) {
                $order = $ps->getCurrentOrder();
                if ($order !== null) {
                    $orders[$order->getId()] = $order;
                }
            }

            $count = 0;
            foreach ($orders as $order) {
                if ($order->getStatus() === OrderStatus::PENDING) {
                    $this->releaseOrder($order, OrderStatus::EXPIRED);
                    ++$count;
                }
            }

            // Place expirée dont la commande n'est plus en attente (cas anormal) : on la libère quand même.
            foreach ($expired as $ps) {
                if ($ps->getStatus() === SeatStatus::HELD) {
                    $this->freeSeat($ps);
                }
            }

            return $count;
        });
    }

    /**
     * Annule une commande payée et la rembourse intégralement.
     * Client : au moins 24 h avant la représentation. Personnel : $bypassDeadline = true.
     *
     * @throws ReservationException
     */
    public function cancelOrder(Order $order, bool $bypassDeadline = false, ?\DateTimeImmutable $now = null): void
    {
        $now ??= new \DateTimeImmutable();

        $this->em->wrapInTransaction(function () use ($order, $bypassDeadline, $now): void {
            $this->em->refresh($order, LockMode::PESSIMISTIC_WRITE);

            if ($order->getStatus() !== OrderStatus::PAID) {
                throw new ReservationException('Seule une commande payée peut être annulée et remboursée.');
            }

            if (!$bypassDeadline) {
                $startsAt = $this->startsAtOf($order);
                $limit = $now->modify(sprintf('+%d hours', self::CANCELLATION_DEADLINE_HOURS));
                if ($startsAt === null || $startsAt <= $limit) {
                    throw new ReservationException(sprintf('Annulation impossible moins de %d h avant la représentation.', self::CANCELLATION_DEADLINE_HOURS));
                }
            }

            $this->refundOrder($order, $now);
        });
    }

    /**
     * Annule une représentation : commandes payées remboursées, réservations en cours libérées.
     * Retourne le nombre de commandes remboursées.
     */
    public function cancelPerformance(Performance $performance, ?\DateTimeImmutable $now = null): int
    {
        $now ??= new \DateTimeImmutable();

        return $this->em->wrapInTransaction(function () use ($performance, $now): int {
            $this->em->refresh($performance, LockMode::PESSIMISTIC_WRITE);

            if ($performance->getStatus() === PerformanceStatus::CANCELLED) {
                throw new ReservationException('Cette représentation est déjà annulée.');
            }
            $performance->setStatus(PerformanceStatus::CANCELLED);

            /** @var list<Ticket> $tickets */
            $tickets = $this->em->createQueryBuilder()
                ->select('t')
                ->from(Ticket::class, 't')
                ->join('t.performanceSeat', 'ps')
                ->where('ps.performance = :p')
                ->setParameter('p', $performance)
                ->getQuery()
                ->getResult();

            $orders = [];
            foreach ($tickets as $ticket) {
                $order = $ticket->getOrder();
                $orders[$order->getId()] = $order;
            }

            $refunded = 0;
            foreach ($orders as $order) {
                if ($order->getStatus() === OrderStatus::PAID) {
                    $this->refundOrder($order, $now);
                    ++$refunded;
                } elseif ($order->getStatus() === OrderStatus::PENDING) {
                    $this->releaseOrder($order, OrderStatus::CANCELLED);
                }
            }

            return $refunded;
        });
    }

    /**
     * Charge les places en les verrouillant (SELECT ... FOR UPDATE), triées par id,
     * en rafraîchissant les données depuis la base.
     *
     * @param array<string, mixed> $params
     *
     * @return list<PerformanceSeat>
     */
    private function lockSeats(string $where, array $params): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('ps')
            ->from(PerformanceSeat::class, 'ps')
            ->where($where)
            ->orderBy('ps.id', 'ASC');
        foreach ($params as $name => $value) {
            $qb->setParameter($name, $value);
        }

        $query = $qb->getQuery();
        $query->setLockMode(LockMode::PESSIMISTIC_WRITE);
        $query->setHint(Query::HINT_REFRESH, true);

        return $query->getResult();
    }

    /**
     * @param list<string> $codes
     *
     * @return array<string, Tariff>
     */
    private function loadTariffs(array $codes, int $quantity, bool $isStaff): array
    {
        $byCode = [];
        foreach ($this->em->getRepository(Tariff::class)->findBy(['code' => $codes]) as $tariff) {
            $byCode[$tariff->getCode()] = $tariff;
        }

        foreach ($codes as $code) {
            $tariff = $byCode[$code] ?? throw new ReservationException(sprintf('Tarif inconnu : %s.', $code));

            if ($tariff->isStaffOnly() && !$isStaff) {
                throw new ReservationException(sprintf('Le tarif « %s » est réservé au guichet.', $tariff->getName()));
            }
            if ($quantity < $tariff->getMinQuantity()) {
                throw new ReservationException(sprintf('Le tarif « %s » exige au moins %d places dans la commande.', $tariff->getName(), $tariff->getMinQuantity()));
            }
        }

        return $byCode;
    }

    private function assertPending(Order $order, string $action): void
    {
        if ($order->getStatus() !== OrderStatus::PENDING) {
            throw new ReservationException(sprintf('La commande %s ne peut pas être %s (statut : %s).', $order->getReference(), $action, $order->getStatus()->value));
        }
    }

    /** Annule les billets, libère les places de la commande et fixe son statut. À appeler dans une transaction. */
    private function releaseOrder(Order $order, OrderStatus $status): void
    {
        foreach ($this->em->getRepository(Ticket::class)->findBy(['order' => $order]) as $ticket) {
            $ticket->setStatus(TicketStatus::CANCELLED);
        }
        foreach ($this->lockSeats('ps.currentOrder = :o', ['o' => $order]) as $ps) {
            $this->freeSeat($ps);
        }
        $order->setStatus($status);
    }

    private function refundOrder(Order $order, \DateTimeImmutable $now): void
    {
        $this->releaseOrder($order, OrderStatus::REFUNDED);
        $order->setRefundedCents($order->getTotalCents())->setCancelledAt($now);
    }

    private function freeSeat(PerformanceSeat $ps): void
    {
        $ps->setStatus(SeatStatus::AVAILABLE)->setHeldUntil(null)->setCurrentOrder(null);
    }

    private function startsAtOf(Order $order): ?\DateTimeImmutable
    {
        $ticket = $this->em->getRepository(Ticket::class)->findOneBy(['order' => $order]);

        return $ticket?->getPerformanceSeat()?->getPerformance()?->getStartsAt();
    }
}