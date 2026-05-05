<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    public const TYPE_PLAT = 'plat';
    public const TYPE_DESSERT = 'dessert';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?int $price = null;

    #[ORM\Column(length: 20)]
    private ?string $type = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $availableDate = null;

    #[ORM\Column]
    private ?int $stock = null;

    #[ORM\OneToMany(mappedBy: 'product', targetEntity: CartItem::class)]
    private Collection $cartItems;

    public function __construct()
    {
        $this->cartItems = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setPrice(int $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getPriceFormatted(): string
    {
        return number_format($this->price / 100, 2, ',', '') . ' €';
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getAvailableDate(): ?\DateTimeImmutable
    {
        return $this->availableDate;
    }

    public function setAvailableDate(\DateTimeImmutable $availableDate): static
    {
        $this->availableDate = $availableDate;

        return $this;
    }

    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function setStock(int $stock): static
    {
        $this->stock = $stock;

        return $this;
    }

    public function isAvailableToday(): bool
    {
        $today = new \DateTimeImmutable('today');

        return $this->availableDate == $today;
    }

    /**
     * @return Collection<int, CartItem>
     */
    public function getCartItems(): Collection
    {
        return $this->cartItems;
    }
}
