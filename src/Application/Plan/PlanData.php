<?php
declare(strict_types=1);

namespace App\Application\Plan;

use App\Domain\Plan\Money;
use InvalidArgumentException;

readonly class PlanData
{
    public function __construct(
        public string $name,
        public Money $price,
        public ?string $description = null
    ) {
    }

    /**
     * @param array{name: string, price: float|int|string, description?: ?string} $data
     */
    public static function fromArray(array $data): self
    {
        if (!isset($data['price']) || !is_numeric($data['price'])) {
            throw new InvalidArgumentException('O preço do plano deve ser numérico.');
        }

        if (!isset($data['name'])) {
            throw new InvalidArgumentException('O nome do plano é obrigatório.');
        }

        $price = Money::fromString((string) $data['price']);

        return new self(
            name: (string) $data['name'],
            price: $price,
            description: isset($data['description']) ? (string) $data['description'] : null
        );
    }
}
