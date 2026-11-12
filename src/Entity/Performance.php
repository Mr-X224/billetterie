<?php

namespace App\Entity;

use App\Enum\PerformanceStatus;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Performance
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'performances')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Event $event = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Venue $venue = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $startsAt = null;

    #[ORM\Column(enumType: PerformanceStatus::class, length: 20)]
    private PerformanceStatus $status = PerformanceStatus::SCHEDULED;

    /** @var Collection<int, PerformanceSeat> */
    #[ORM\OneToMany(mappedBy: 'performance', targetEntity: PerformanceSeat::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $performanceSeats;

    /** @var Collection<int, Price> */
    #[ORM\OneToMany(mappedBy: 'performance', targetEntity: Price::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $prices;

    public function __construct()
    {
        $this->performanceSeats = new ArrayCollection();
        $this->prices = new ArrayCollection();
    }

    public function __toString(): string
    {
        return ($this->event?->getTitle() ?? 'Représentation') . ' - ' . ($this->startsAt?->format('d/m/Y H:i') ?? '');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStartsAt(): ?\DateTimeImmutable
    {
        return $this->startsAt;
    }

    public function setStartsAt(\DateTimeImmutable $startsAt): static
    {
        $this->startsAt = $startsAt;

        return $this;
    }

    public function getStatus(): ?PerformanceStatus
    {
        return $this->status;
    }

    public function setStatus(PerformanceStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): static
    {
        $this->event = $event;

        return $this;
    }

    public function getVenue(): ?Venue
    {
        return $this->venue;
    }

    public function setVenue(?Venue $venue): static
    {
        $this->venue = $venue;

        return $this;
    }

    /**
     * @return Collection<int, PerformanceSeat>
     */
    public function getPerformanceSeats(): Collection
    {
        return $this->performanceSeats;
    }

    public function addPerformanceSeat(PerformanceSeat $performanceSeat): static
    {
        if (!$this->performanceSeats->contains($performanceSeat)) {
            $this->performanceSeats->add($performanceSeat);
            $performanceSeat->setPerformance($this);
        }

        return $this;
    }

    public function removePerformanceSeat(PerformanceSeat $performanceSeat): static
    {
        if ($this->performanceSeats->removeElement($performanceSeat)) {
            // set the owning side to null (unless already changed)
            if ($performanceSeat->getPerformance() === $this) {
                $performanceSeat->setPerformance(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Price>
     */
    public function getPrices(): Collection
    {
        return $this->prices;
    }

    public function addPrice(Price $price): static
    {
        if (!$this->prices->contains($price)) {
            $this->prices->add($price);
            $price->setPerformance($this);
        }

        return $this;
    }

    public function removePrice(Price $price): static
    {
        if ($this->prices->removeElement($price)) {
            // set the owning side to null (unless already changed)
            if ($price->getPerformance() === $this) {
                $price->setPerformance(null);
            }
        }

        return $this;
    }
}