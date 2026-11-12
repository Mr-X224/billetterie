<?php

namespace App\Entity;

use App\Enum\SeatStatus;
use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity]
#[ORM\Index(name: 'idx_perfseat_status', columns: ['performance_id', 'status'])]
#[ORM\Index(name: 'idx_perfseat_held', columns: ['status', 'held_until'])]
#[ORM\UniqueConstraint(name: 'uniq_performance_seat', columns: ['performance_id', 'seat_id'])]
class PerformanceSeat
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'performanceSeats')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Performance $performance = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Seat $seat = null;

    #[ORM\Column(enumType: SeatStatus::class, length: 20)]
    private SeatStatus $status = SeatStatus::AVAILABLE;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $heldUntil = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Order $currentOrder = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): ?SeatStatus
    {
        return $this->status;
    }

    public function setStatus(SeatStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getHeldUntil(): ?\DateTimeImmutable
    {
        return $this->heldUntil;
    }

    public function setHeldUntil(?\DateTimeImmutable $heldUntil): static
    {
        $this->heldUntil = $heldUntil;

        return $this;
    }

    public function getPerformance(): ?Performance
    {
        return $this->performance;
    }

    public function setPerformance(?Performance $performance): static
    {
        $this->performance = $performance;

        return $this;
    }

    public function getSeat(): ?Seat
    {
        return $this->seat;
    }

    public function setSeat(?Seat $seat): static
    {
        $this->seat = $seat;

        return $this;
    }

    public function getCurrentOrder(): ?Order
    {
        return $this->currentOrder;
    }

    public function setCurrentOrder(?Order $currentOrder): static
    {
        $this->currentOrder = $currentOrder;

        return $this;
    }
}