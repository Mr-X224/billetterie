<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\UniqueConstraint(name: 'uniq_seat_position', columns: ['zone_id', 'row_label', 'number'])]
class Seat
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'seats')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Zone $zone = null;

    #[ORM\Column(length: 5)]
    private string $rowLabel = '';

    #[ORM\Column]
    private int $number = 0;

    public function __toString(): string
    {
        return $this->rowLabel . $this->number;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRowLabel(): ?string
    {
        return $this->rowLabel;
    }

    public function setRowLabel(string $rowLabel): static
    {
        $this->rowLabel = $rowLabel;

        return $this;
    }

    public function getNumber(): ?int
    {
        return $this->number;
    }

    public function setNumber(int $number): static
    {
        $this->number = $number;

        return $this;
    }

    public function getZone(): ?Zone
    {
        return $this->zone;
    }

    public function setZone(?Zone $zone): static
    {
        $this->zone = $zone;

        return $this;
    }
}