<?php

namespace App\Entity;

use App\Repository\BikeRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BikeRepository::class)]
class Bike
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'bikes')]
    private ?Client $client = null;

    #[ORM\ManyToOne(inversedBy: 'bikes')]
    private ?BikeBrand $make = null;

    #[ORM\Column(length: 100)]
    private ?string $model = null;

    #[ORM\Column]
    private ?int $year = null;

    #[ORM\Column]
    private ?int $cc = null;

    #[ORM\Column(length: 50)]
    private ?string $plate = null;

    #[ORM\Column]
    private ?int $mileage = null;

    #[ORM\Column(length: 40)]
    private ?string $vin = null;

    #[ORM\Column]
    private ?\DateTime $createdAt = null;

    #[ORM\Column(length: 255)]
    private ?string $uniqueID = null;

    /**
     * @var Collection<int, BikeReport>
     */
    #[ORM\OneToMany(targetEntity: BikeReport::class, mappedBy: 'bike')]
    private Collection $bikeReports;

    /**
     * @var Collection<int, Maintenance>
     */
    #[ORM\OneToMany(targetEntity: Maintenance::class, mappedBy: 'bike', cascade: ['remove'])]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private Collection $maintenances;

    public function __construct()
    {
        $this->uniqueID = uniqid();
        $this->createdAt = new DateTime();
        
        $this->bikeReports = new ArrayCollection();
        $this->maintenances = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): static
    {
        $this->client = $client;

        return $this;
    }

    public function getMake(): ?BikeBrand
    {
        return $this->make;
    }

    public function setMake(?BikeBrand $make): static
    {
        $this->make = $make;

        return $this;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function setModel(string $model): static
    {
        $this->model = $model;

        return $this;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(int $year): static
    {
        $this->year = $year;

        return $this;
    }

    public function getCc(): ?int
    {
        return $this->cc;
    }

    public function setCc(int $cc): static
    {
        $this->cc = $cc;

        return $this;
    }

    public function getPlate(): ?string
    {
        return $this->plate;
    }

    public function setPlate(string $plate): static
    {
        $this->plate = $plate;

        return $this;
    }

    public function getMileage(): ?int
    {
        return $this->mileage;
    }

    public function setMileage(int $mileage): static
    {
        $this->mileage = $mileage;

        return $this;
    }

    public function getVin(): ?string
    {
        return $this->vin;
    }

    public function setVin(string $vin): static
    {
        $this->vin = $vin;

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

    public function getUniqueID(): ?string
    {
        return $this->uniqueID;
    }

    public function setUniqueID(string $uniqueID): static
    {
        $this->uniqueID = $uniqueID;

        return $this;
    }

    /**
     * @return Collection<int, BikeReport>
     */
    public function getBikeReports(): Collection
    {
        return $this->bikeReports;
    }

    public function addBikeReport(BikeReport $bikeReport): static
    {
        if (!$this->bikeReports->contains($bikeReport)) {
            $this->bikeReports->add($bikeReport);
            $bikeReport->setBike($this);
        }

        return $this;
    }

    public function removeBikeReport(BikeReport $bikeReport): static
    {
        if ($this->bikeReports->removeElement($bikeReport)) {
            // set the owning side to null (unless already changed)
            if ($bikeReport->getBike() === $this) {
                $bikeReport->setBike(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Maintenance>
     */
    public function getMaintenances(): Collection
    {
        return $this->maintenances;
    }

    public function addMaintenance(Maintenance $maintenance): static
    {
        if (!$this->maintenances->contains($maintenance)) {
            $this->maintenances->add($maintenance);
            $maintenance->setBike($this);
        }

        return $this;
    }

    public function removeMaintenance(Maintenance $maintenance): static
    {
        if ($this->maintenances->removeElement($maintenance)) {
            if ($maintenance->getBike() === $this) {
                $maintenance->setBike(null);
            }
        }

        return $this;
    }
}
