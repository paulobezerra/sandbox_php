<?php
declare(strict_types=1);

namespace App\Infrastructure\Database\Repository;

use App\Domain\Plan\Money;
use App\Domain\Plan\Plan;
use App\Domain\Plan\PlanRepository;
use PDO;

class MysqlPlanRepository implements PlanRepository
{
    public function __construct(
        private readonly PDO $pdo
    ) {
    }

    /**
     * @return array<int, Plan>
     */
    public function findAll(): array
    {
        $stmt = $this->pdo->query('SELECT id, name, price, description, created_at FROM plans ORDER BY id DESC');
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $plans = [];
        foreach ($rows as $row) {
            $plans[] = $this->mapRowToPlan($row);
        }

        return $plans;
    }

    public function findById(int $id): ?Plan
    {
        $stmt = $this->pdo->prepare('SELECT id, name, price, description, created_at FROM plans WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return $this->mapRowToPlan($row);
    }

    public function save(Plan $plan): Plan
    {
        if ($plan->getId() === null) {
            $stmt = $this->pdo->prepare(
                'INSERT INTO plans (name, price, description) VALUES (:name, :price, :description)'
            );
            $stmt->execute([
                'name' => $plan->getName(),
                'price' => $plan->getPrice()->toDecimal(),
                'description' => $plan->getDescription(),
            ]);

            $id = (int) $this->pdo->lastInsertId();
            $plan->setId($id);

            return $this->findById($id) ?? $plan;
        }

        $stmt = $this->pdo->prepare(
            'UPDATE plans SET name = :name, price = :price, description = :description WHERE id = :id'
        );
        $stmt->execute([
            'id' => $plan->getId(),
            'name' => $plan->getName(),
            'price' => $plan->getPrice()->toDecimal(),
            'description' => $plan->getDescription(),
        ]);

        return $this->findById($plan->getId()) ?? $plan;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM plans WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function mapRowToPlan(array $row): Plan
    {
        return new Plan(
            id: (int) $row['id'],
            name: (string) $row['name'],
            price: Money::fromString((string) $row['price']),
            description: isset($row['description']) ? (string) $row['description'] : null,
            createdAt: isset($row['created_at']) ? (string) $row['created_at'] : null
        );
    }
}
