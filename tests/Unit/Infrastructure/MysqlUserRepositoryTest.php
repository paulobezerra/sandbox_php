<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure;

use App\Infrastructure\Database\Repository\MysqlUserRepository;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class MysqlUserRepositoryTest extends TestCase
{
    public function testFindMethodsMapRowsAndReturnNullForMissingRows(): void
    {
        $repository = new MysqlUserRepository($this->pdo([
            false,
            ['id' => '7', 'name' => 'Author', 'email' => 'a@example.test', 'password_hash' => 'hash', 'created_at' => '2026-01-01 00:00:00'],
        ]));

        self::assertNull($repository->findByEmail('missing@example.test'));
        $user = $repository->findById(7);
        self::assertSame(7, $user?->id);
        self::assertSame('Author', $user?->name);
    }

    public function testCreateReturnsPersistedUserAndThrowsWhenReadBackFails(): void
    {
        $repository = new MysqlUserRepository($this->pdo([
            false,
            ['id' => '12', 'name' => 'Author', 'email' => 'a@example.test', 'password_hash' => 'hash', 'created_at' => '2026-01-01 00:00:00'],
        ]));
        self::assertSame(12, $repository->create('Author', 'a@example.test', 'hash')->id);

        $repository = new MysqlUserRepository($this->pdo([false, false]));
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('User was not persisted.');
        $repository->create('Author', 'a@example.test', 'hash');
    }

    /** @param list<array<string, string>|false> $rows */
    private function pdo(array $rows): PDO
    {
        return new class ($rows) extends PDO {
            /** @param list<array<string, string>|false> $rows */
            public function __construct(private array $rows)
            {
            }

            public function prepare(string $query, array $options = []): PDOStatement|false
            {
                $row = array_shift($this->rows) ?: false;
                return new class ($row) extends PDOStatement {
                    public function __construct(private array|false $row)
                    {
                    }

                    public function execute(?array $params = null): bool
                    {
                        return true;
                    }

                    public function fetch(int $mode = PDO::FETCH_DEFAULT, int $cursorOrientation = PDO::FETCH_ORI_NEXT, int $cursorOffset = 0): mixed
                    {
                        return $this->row;
                    }
                };
            }

            public function lastInsertId(?string $name = null): string|false
            {
                return '12';
            }
        };
    }
}
