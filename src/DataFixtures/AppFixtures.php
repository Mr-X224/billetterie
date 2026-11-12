<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Event;
use App\Entity\Order;
use App\Entity\Performance;
use App\Entity\PerformanceSeat;
use App\Entity\Price;
use App\Entity\Seat;
use App\Entity\Tariff;
use App\Entity\Ticket;
use App\Entity\User;
use App\Entity\Venue;
use App\Entity\Zone;
use App\Enum\EventType;
use App\Enum\OrderChannel;
use App\Enum\OrderStatus;
use App\Enum\PerformanceStatus;
use App\Enum\SeatStatus;
use App\Enum\TicketStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Données de démonstration : salle de 400 places (deux configurations),
 * 6 événements, 12 représentations, tarifs, prix, utilisateurs et ventes.
 * Les données sont reproductibles (graine aléatoire fixe).
 */
final class AppFixtures extends Fixture
{
    private const SEATED_LAYOUT = [
        ['name' => 'Orchestre', 'color' => '#c0392b', 'rows' => 10, 'perRow' => 20], // 200
        ['name' => 'Balcon', 'color' => '#2980b9', 'rows' => 8, 'perRow' => 15],     // 120
        ['name' => 'Loge', 'color' => '#8e44ad', 'rows' => 4, 'perRow' => 20],       // 80
    ];

    private const STANDING_LAYOUT = [
        ['name' => 'Fosse debout', 'color' => '#e67e22', 'rows' => 10, 'perRow' => 20], // 200
        ['name' => 'Balcon', 'color' => '#2980b9', 'rows' => 8, 'perRow' => 15],        // 120
        ['name' => 'Loge', 'color' => '#8e44ad', 'rows' => 4, 'perRow' => 20],          // 80
    ];

    /** factor = proportion du plein tarif */
    private const TARIFFS = [
        'PLEIN' => ['name' => 'Plein tarif', 'staffOnly' => false, 'minQuantity' => 1, 'factor' => 1.0],
        'REDUIT' => ['name' => 'Tarif réduit', 'staffOnly' => false, 'minQuantity' => 1, 'factor' => 0.75],
        'GROUPE' => ['name' => 'Tarif groupe (10 places et plus)', 'staffOnly' => false, 'minQuantity' => 10, 'factor' => 0.8],
        'INVITATION' => ['name' => 'Invitation', 'staffOnly' => true, 'minQuantity' => 1, 'factor' => 0.0],
    ];

    /** Prix plein tarif en dirhams, par zone */
    private const EVENTS = [
        [
            'title' => 'Rock en Fosse - Tournée anniversaire',
            'type' => EventType::CONCERT,
            'duration' => 120,
            'standing' => true,
            'time' => '20:30',
            'days' => [6, 13],
            'prices' => ['Fosse debout' => 250, 'Balcon' => 180, 'Loge' => 300],
            'description' => 'Concert rock en fosse debout : une soirée à haute énergie avec les groupes de la scène locale.',
        ],
        [
            'title' => 'Concert symphonique de printemps',
            'type' => EventType::CONCERT,
            'duration' => 105,
            'standing' => false,
            'time' => '20:00',
            'days' => [8, 15, 22],
            'prices' => ['Orchestre' => 300, 'Balcon' => 200, 'Loge' => 400],
            'description' => 'L\'orchestre philharmonique interprète un programme de pièces romantiques.',
        ],
        [
            'title' => 'Le Malade imaginaire',
            'type' => EventType::THEATRE,
            'duration' => 110,
            'standing' => false,
            'time' => '20:00',
            'days' => [7, 14],
            'prices' => ['Orchestre' => 200, 'Balcon' => 140, 'Loge' => 260],
            'description' => 'La comédie de Molière dans une mise en scène dynamique et colorée.',
        ],
        [
            'title' => 'Hamlet - Mise en scène contemporaine',
            'type' => EventType::THEATRE,
            'duration' => 135,
            'standing' => false,
            'time' => '19:30',
            'days' => [10, 17],
            'prices' => ['Orchestre' => 220, 'Balcon' => 150, 'Loge' => 280],
            'description' => 'Une relecture moderne du drame de Shakespeare.',
        ],
        [
            'title' => 'Conférence : l\'IA au service de l\'entreprise',
            'type' => EventType::CONFERENCE,
            'duration' => 90,
            'standing' => false,
            'time' => '18:30',
            'days' => [9],
            'prices' => ['Orchestre' => 80, 'Balcon' => 60, 'Loge' => 100],
            'description' => 'Des experts présentent des cas concrets d\'intelligence artificielle en entreprise.',
        ],
        [
            'title' => 'Soirée Chaâbi',
            'type' => EventType::CONCERT,
            'duration' => 150,
            'standing' => false,
            'time' => '21:00',
            'days' => [11, 18],
            'prices' => ['Orchestre' => 250, 'Balcon' => 170, 'Loge' => 320],
            'description' => 'Une grande soirée de musique populaire marocaine.',
        ],
    ];

    private const CUSTOMER_NAMES = [
        'Yassine Benali', 'Salma El Idrissi', 'Omar Tazi', 'Khadija Amrani',
        'Youssef Berrada', 'Imane Lahlou', 'Hamza Chraibi', 'Meryem Alaoui',
    ];

    public function __construct(private readonly UserPasswordHasherInterface $hasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        mt_srand(42);

        [$boxOffice, $customers] = $this->createUsers($manager);
        $tariffs = $this->createTariffs($manager);

        $venues = [
            'seated' => $this->createVenue($manager, 'Salle Atlas - placement assis', self::SEATED_LAYOUT),
            'standing' => $this->createVenue($manager, 'Salle Atlas - fosse debout', self::STANDING_LAYOUT),
        ];

        $first = true;
        foreach (self::EVENTS as $data) {
            $event = (new Event())
                ->setTitle($data['title'])
                ->setType($data['type'])
                ->setDescription($data['description'])
                ->setDurationMinutes($data['duration']);
            $manager->persist($event);

            $venueData = $venues[$data['standing'] ? 'standing' : 'seated'];

            foreach ($data['days'] as $day) {
                $performance = (new Performance())
                    ->setEvent($event)
                    ->setVenue($venueData['venue'])
                    ->setStartsAt(new \DateTimeImmutable(sprintf('+%d days %s', $day, $data['time'])))
                    ->setStatus(PerformanceStatus::SCHEDULED);
                $manager->persist($performance);

                $priceMap = $this->createPrices($manager, $performance, $venueData, $data['prices'], $tariffs);
                $items = $this->createPerformanceSeats($manager, $performance, $venueData);
                $remaining = $this->sell($manager, $items, $priceMap, $tariffs, $customers, $boxOffice);

                if ($first) {
                    $this->addRefundedOrders($manager, $remaining, $priceMap, $tariffs, $customers[0]);
                    $first = false;
                }

                $manager->flush();
            }
        }
    }

    /** @return array{0: User, 1: list<User>} */
    private function createUsers(ObjectManager $m): array
    {
        $this->createUser($m, 'admin@billetterie.test', 'Administrateur Atlas', ['ROLE_ADMIN']);
        $boxOffice = $this->createUser($m, 'guichet@billetterie.test', 'Guichetier Atlas', ['ROLE_BOX_OFFICE']);

        $customers = [];
        foreach (self::CUSTOMER_NAMES as $i => $name) {
            $customers[] = $this->createUser($m, sprintf('client%d@billetterie.test', $i + 1), $name, []);
        }

        return [$boxOffice, $customers];
    }

    /** @param list<string> $roles */
    private function createUser(ObjectManager $m, string $email, string $fullName, array $roles): User
    {
        $user = (new User())->setEmail($email)->setFullName($fullName)->setRoles($roles);
        $user->setPassword($this->hasher->hashPassword($user, 'password'));
        $m->persist($user);

        return $user;
    }

    /** @return array<string, Tariff> */
    private function createTariffs(ObjectManager $m): array
    {
        $tariffs = [];
        foreach (self::TARIFFS as $code => $t) {
            $tariff = (new Tariff())
                ->setCode($code)
                ->setName($t['name'])
                ->setStaffOnly($t['staffOnly'])
                ->setMinQuantity($t['minQuantity']);
            $m->persist($tariff);
            $tariffs[$code] = $tariff;
        }

        return $tariffs;
    }

    /**
     * @param list<array{name: string, color: string, rows: int, perRow: int}> $layout
     *
     * @return array{venue: Venue, zones: list<array{zone: Zone, name: string, seats: list<Seat>}>}
     */
    private function createVenue(ObjectManager $m, string $name, array $layout): array
    {
        $venue = (new Venue())->setName($name)->setAddress('Salle Atlas, avenue Hassan II, Agadir');
        $m->persist($venue);

        $zones = [];
        foreach ($layout as $i => $z) {
            $zone = (new Zone())
                ->setVenue($venue)
                ->setName($z['name'])
                ->setColor($z['color'])
                ->setDisplayOrder($i + 1);
            $m->persist($zone);

            $seats = [];
            for ($r = 0; $r < $z['rows']; $r++) {
                for ($n = 1; $n <= $z['perRow']; $n++) {
                    $seat = (new Seat())->setZone($zone)->setRowLabel(chr(65 + $r))->setNumber($n);
                    $m->persist($seat);
                    $seats[] = $seat;
                }
            }
            $zones[] = ['zone' => $zone, 'name' => $z['name'], 'seats' => $seats];
        }

        return ['venue' => $venue, 'zones' => $zones];
    }

    /**
     * @param array<string, int>    $pleinPrices prix plein tarif en dirhams, par zone
     * @param array<string, Tariff> $tariffs
     *
     * @return array<string, array<string, int>> montants en centimes [zone][code tarif]
     */
    private function createPrices(ObjectManager $m, Performance $performance, array $venueData, array $pleinPrices, array $tariffs): array
    {
        $map = [];
        foreach ($venueData['zones'] as $z) {
            foreach (self::TARIFFS as $code => $t) {
                $cents = $this->priceCents($pleinPrices[$z['name']], $t['factor']);
                $m->persist(
                    (new Price())
                        ->setPerformance($performance)
                        ->setZone($z['zone'])
                        ->setTariff($tariffs[$code])
                        ->setAmountCents($cents)
                );
                $map[$z['name']][$code] = $cents;
            }
        }

        return $map;
    }

    /** Arrondi au multiple de 5 dirhams, retourné en centimes. */
    private function priceCents(int $pleinMad, float $factor): int
    {
        return ((int) round($pleinMad * $factor / 5)) * 5 * 100;
    }

    /** @return list<array{ps: PerformanceSeat, zone: string}> */
    private function createPerformanceSeats(ObjectManager $m, Performance $performance, array $venueData): array
    {
        $items = [];
        foreach ($venueData['zones'] as $z) {
            foreach ($z['seats'] as $seat) {
                $ps = (new PerformanceSeat())
                    ->setPerformance($performance)
                    ->setSeat($seat)
                    ->setStatus(SeatStatus::AVAILABLE);
                $m->persist($ps);
                $items[] = ['ps' => $ps, 'zone' => $z['name']];
            }
        }

        return $items;
    }

    /**
     * Vend une partie des places (25 à 85 %) par commandes de 1 à 4 billets.
     *
     * @param list<array{ps: PerformanceSeat, zone: string}> $items
     * @param list<User>                                     $customers
     *
     * @return list<array{ps: PerformanceSeat, zone: string}> places restées disponibles
     */
    private function sell(ObjectManager $m, array $items, array $priceMap, array $tariffs, array $customers, User $boxOffice): array
    {
        shuffle($items);
        $target = (int) round(count($items) * mt_rand(25, 85) / 100);
        $sold = 0;

        while ($sold < $target) {
            $qty = min(mt_rand(1, 4), $target - $sold);
            $slice = array_splice($items, 0, $qty);
            $isBox = mt_rand(1, 100) <= 30;
            $createdAt = new \DateTimeImmutable(sprintf('-%d days -%d hours', mt_rand(0, 6), mt_rand(0, 23)));

            $order = (new Order())
                ->setChannel($isBox ? OrderChannel::BOX_OFFICE : OrderChannel::WEB)
                ->setCustomer($isBox ? null : $customers[array_rand($customers)])
                ->setSeller($isBox ? $boxOffice : null)
                ->setStatus(OrderStatus::PAID)
                ->setCreatedAt($createdAt)
                ->setPaidAt($createdAt);

            $total = 0;
            foreach ($slice as $item) {
                $code = ($isBox && mt_rand(1, 100) <= 10) ? 'INVITATION' : (mt_rand(1, 100) <= 25 ? 'REDUIT' : 'PLEIN');
                $amount = $priceMap[$item['zone']][$code];

                $ticket = (new Ticket())
                    ->setOrder($order)
                    ->setPerformanceSeat($item['ps'])
                    ->setTariff($tariffs[$code])
                    ->setPricePaidCents($amount)
                    ->setStatus(TicketStatus::VALID);
                $item['ps']->setStatus(SeatStatus::SOLD)->setCurrentOrder($order);

                $total += $amount;
                $m->persist($ticket);
            }

            $order->setTotalCents($total);
            $m->persist($order);
            $sold += $qty;
        }

        return $items;
    }

    /**
     * Deux commandes remboursées : billets annulés, places revenues disponibles.
     *
     * @param list<array{ps: PerformanceSeat, zone: string}> $remaining
     */
    private function addRefundedOrders(ObjectManager $m, array $remaining, array $priceMap, array $tariffs, User $customer): void
    {
        foreach (array_slice($remaining, 0, 2) as $item) {
            $amount = $priceMap[$item['zone']]['PLEIN'];
            $createdAt = new \DateTimeImmutable('-3 days');

            $order = (new Order())
                ->setChannel(OrderChannel::WEB)
                ->setCustomer($customer)
                ->setStatus(OrderStatus::REFUNDED)
                ->setTotalCents($amount)
                ->setRefundedCents($amount)
                ->setCreatedAt($createdAt)
                ->setPaidAt($createdAt)
                ->setCancelledAt(new \DateTimeImmutable('-1 day'));

            $ticket = (new Ticket())
                ->setOrder($order)
                ->setPerformanceSeat($item['ps'])
                ->setTariff($tariffs['PLEIN'])
                ->setPricePaidCents($amount)
                ->setStatus(TicketStatus::CANCELLED);

            $m->persist($order);
            $m->persist($ticket);
        }
    }
}