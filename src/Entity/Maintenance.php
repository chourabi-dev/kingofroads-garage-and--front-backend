<?php

namespace App\Entity;

use App\Repository\MaintenanceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MaintenanceRepository::class)]
class Maintenance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'maintenances')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Bike $bike = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $note = null;

    #[ORM\Column]
    private ?\DateTime $createdAt = null;

    /**
     * @var Collection<int, MaintenanceLine>
     */
    #[ORM\OneToMany(targetEntity: MaintenanceLine::class, mappedBy: 'maintenance', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Assert\Count(min: 1, minMessage: 'Add at least one action or product line.')]
    #[Assert\Valid]
    private Collection $lines;

    #[ORM\Column]
    private ?float $odometer = null;

    public function __construct()
    {
        $this->lines = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBike(): ?Bike
    {
        return $this->bike;
    }

    public function setBike(?Bike $bike): static
    {
        $this->bike = $bike;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return Collection<int, MaintenanceLine>
     */
    public function getLines(): Collection
    {
        return $this->lines;
    }

    public function addLine(MaintenanceLine $line): static
    {
        if (!$this->lines->contains($line)) {
            $this->lines->add($line);
            $line->setMaintenance($this);
        }

        return $this;
    }

    public function removeLine(MaintenanceLine $line): static
    {
        if ($this->lines->removeElement($line)) {
            if ($line->getMaintenance() === $this) {
                $line->setMaintenance(null);
            }
        }

        return $this;
    }

    public function getTotal(): float
    {
        $total = 0.0;
        foreach ($this->lines as $line) {
            $total += $line->getTotal();
        }

        return round($total, 3);
    }

    public function getInvoiceNumber(): string
    {
        return sprintf('INV-%s-%05d', $this->createdAt?->format('Y') ?? date('Y'), $this->id ?? 0);
    }

    public function getOdometer(): ?float
    {
        return $this->odometer;
    }

    public function setOdometer(float $odometer): static
    {
        $this->odometer = $odometer;

        return $this;
    }
}
