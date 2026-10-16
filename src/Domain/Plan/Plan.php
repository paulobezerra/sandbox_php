<?php

declare(strict_types=1);

namespace App\Domain\Plan;

class Plan
{
    private ?int $id;
    private string $name;
    private Money $price;
    private ?string $description;
    private ?string $createdAt;

    public function __construct(
        ?int $id,
        string $name,
        Money $price,
        ?string $description = null,
        ?string $createdAt = null
    ) {
        $this->id = $id;
        $this->setName($name);
        $this->price = $price;
        $this->setDescription($description);
        $this->createdAt = $createdAt;
    }

    public static function create(string $name, Money $price, ?string $description = null): self
    {
        return new self(null, $name, $price, $description);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $trimmed = trim($name);
        $length = mb_strlen($trimmed);

        if ($trimmed === '') {
            throw new PlanValidationException(['name' => 'O nome do plano é obrigatório.']);
        }

        if ($length < 2 || $length > 100) {
            throw new PlanValidationException(['name' => 'O nome deve ter entre 2 e 100 caracteres.']);
        }

        $this->name = $trimmed;
    }

    public function getPrice(): Money
    {
        return $this->price;
    }

    public function setPrice(Money $price): void
    {
        $this->price = $price;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        if ($description === null) {
            $this->description = null;
            return;
        }

        $trimmed = trim($description);
        $this->description = $trimmed === '' ? null : $trimmed;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function update(string $name, Money $price, ?string $description): void
    {
        $this->setName($name);
        $this->setPrice($price);
        $this->setDescription($description);
    }

    /**
     * @return array{id: ?int, name: string, price: float, description: ?string, created_at: ?string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price->toFloat(),
            'description' => $this->description,
            'created_at' => $this->createdAt,
        ];
    }
}
