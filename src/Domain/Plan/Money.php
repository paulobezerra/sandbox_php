<?php
declare(strict_types=1);

namespace App\Domain\Plan;

use InvalidArgumentException;

readonly class Money
{
    private int $cents;

    private function __construct(int $cents)
    {
        if ($cents < 0) {
            throw new InvalidArgumentException('O preço não pode ser negativo.');
        }

        $this->cents = $cents;
    }

    public static function fromCents(int $cents): self
    {
        return new self($cents);
    }

    public static function fromFloat(float $amount): self
    {
        return new self((int) round($amount * 100));
    }

    public static function fromString(string $amount): self
    {
        if (!is_numeric($amount)) {
            throw new InvalidArgumentException('O valor monetário deve ser numérico.');
        }

        return self::fromFloat((float) $amount);
    }

    public function toCents(): int
    {
        return $this->cents;
    }

    public function toFloat(): float
    {
        return $this->cents / 100;
    }

    public function toDecimal(): string
    {
        return number_format($this->cents / 100, 2, '.', '');
    }

    public function equals(self $other): bool
    {
        return $this->cents === $other->cents;
    }
}
