<?php

namespace App\Entity;

use App\Repository\MaintenanceActionRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MaintenanceActionRepository::class)]
class MaintenanceAction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank]
    private ?string $label = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 3)]
    #[Assert\PositiveOrZero]
    private ?string $defaultPrice = '0.000';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getDefaultPrice(): ?string
    {
        return $this->defaultPrice;
    }

    public function setDefaultPrice(string|float|int|null $defaultPrice): static
    {
        $this->defaultPrice = (string) ($defaultPrice ?? '0');

        return $this;
    }

    public function __toString(): string
    {
        return (string) $this->label;
    }
}
