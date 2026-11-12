<?php

namespace App\Entity;

use App\Enum\TicketStatus;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Ticket
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'tickets')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Order $order = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?PerformanceSeat $performanceSeat = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Tariff $tariff = null;

    #[ORM\Column]
    private int $pricePaidCents = 0;

    #[ORM\Column(enumType: TicketStatus::class, length: 20)]
    private TicketStatus $status = TicketStatus::PENDING;

    #[ORM\Column(length: 64, unique: true)]
    private string $qrToken;

    public function __construct()
    {
        $this->qrToken = bin2hex(random_bytes(16));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPricePaidCents(): ?int
    {
        return $this->pricePaidCents;
    }

    public function setPricePaidCents(int $pricePaidCents): static
    {
        $this->pricePaidCents = $pricePaidCents;

        return $this;
    }

    public function getStatus(): ?TicketStatus
    {
        return $this->status;
    }

    public function setStatus(TicketStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getQrToken(): ?string
    {
        return $this->qrToken;
    }

    public function setQrToken(string $qrToken): static
    {
        $this->qrToken = $qrToken;

        return $this;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): static
    {
        $this->order = $order;

        return $this;
    }

    public function getPerformanceSeat(): ?PerformanceSeat
    {
        return $this->performanceSeat;
    }

    public function setPerformanceSeat(?PerformanceSeat $performanceSeat): static
    {
        $this->performanceSeat = $performanceSeat;

        return $this;
    }

    public function getTariff(): ?Tariff
    {
        return $this->tariff;
    }

    public function setTariff(?Tariff $tariff): static
    {
        $this->tariff = $tariff;

        return $this;
    }
}