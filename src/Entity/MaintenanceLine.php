<?php

namespace App\Entity;

use App\Repository\MaintenanceLineRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: MaintenanceLineRepository::class)]
class MaintenanceLine
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'lines')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Maintenance $maintenance = null;

    /** A line is either an action (labour) or a product (part sold) — exactly one of the two is set. */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?MaintenanceAction $action = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Product $product = null;

    /** Snapshot of the action/product label, so old invoices stay unchanged if it is renamed/deleted. */
    #[ORM\Column(length: 150)]
    private ?string $label = null;

    #[ORM\Column]
    #[Assert\Positive]
    private ?int $quantity = 1;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 3)]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private ?string $price = '0.000';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMaintenance(): ?Maintenance
    {
        return $this->maintenance;
    }

    public function setMaintenance(?Maintenance $maintenance): static
    {
        $this->maintenance = $maintenance;

        return $this;
    }

    public function getAction(): ?MaintenanceAction
    {
        return $this->action;
    }

    public function setAction(?MaintenanceAction $action): static
    {
        $this->action = $action;
        if ($action !== null) {
            $this->label = $action->getLabel();
        }

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;
        if ($product !== null) {
            $this->label = mb_substr((string) $product->getLabel(), 0, 150);
        }

        return $this;
    }

    public function isProduct(): bool
    {
        return $this->product !== null;
    }

    /** 'product' or 'action' — handy for templates. */
    public function getType(): string
    {
        return $this->isProduct() ? 'product' : 'action';
    }

    #[Assert\Callback]
    public function validateActionOrProduct(ExecutionContextInterface $context): void
    {
        if ($this->action === null && $this->product === null) {
            $context->buildViolation('Please choose an action or a product.')
                ->atPath('action')
                ->addViolation();
        } elseif ($this->action !== null && $this->product !== null) {
            $context->buildViolation('A line must be either an action or a product, not both.')
                ->atPath('product')
                ->addViolation();
        }
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(?int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string|float|int|null $price): static
    {
        $this->price = $price === null ? null : (string) $price;

        return $this;
    }

    public function getTotal(): float
    {
        return round((float) $this->price * (int) $this->quantity, 3);
    }
}
