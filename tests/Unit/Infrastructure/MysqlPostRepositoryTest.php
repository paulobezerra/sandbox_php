<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure;

use App\Domain\Post\Post;
use App\Infrastructure\Database\Repository\MysqlPostRepository;
use DateTimeImmutable;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class MysqlPostRepositoryTest extends TestCase
{
    public function testFindMethodsMapRowsAndReturnNullForMissingRecords(): void
    {
        $repository = new MysqlPostRepository($this->pdo([
            false,
            $this->row(publishedAt: '2026-01-02 10:00:00'),
            $this->row(),
        ]));

        self::assertNull($repository->find(1));
        self::assertSame(12, $repository->findByAuthor(12, 4)?->id);
        self::assertNull($repository->findPublishedBySlug('draft-post')?->publishedAt);
    }

    public function testListMethodsBindLimitsAndMapRows(): void
    {
        $repository = new MysqlPostRepository($this->pdo([
            [$this->row(publishedAt: '2026-01-02 10:00:00')],
            [$this->row(), $this->row(id: 13, excerpt: 'A note')],
        ]));

        self::assertCount(1, $repository->listPublished(10, 20));
        $authorsPosts = $repository->listByAuthor(4, 5, 15);
        self::assertCount(2, $authorsPosts);
        self::assertSame('A note', $authorsPosts[1]->excerpt);
    }

    public function testCountMethodsReturnIntegerTotals(): void
    {
        $repository = new MysqlPostRepository($this->pdo([['scalar' => '3'], ['scalar' => '8']]));

        self::assertSame(3, $repository->countByAuthor(4));
        self::assertSame(8, $repository->countPublished());
    }

    public function testCreateAndUpdateReturnReadBackPostOrThrow(): void
    {
        $post = $this->post();
        $repository = new MysqlPostRepository($this->pdo([false, $this->row()]));
        self::assertSame(12, $repository->create($post)->id);

        $repository = new MysqlPostRepository($this->pdo([false]));
        try {
            $repository->create($post);
            self::fail('A missing inserted row must throw.');
        } catch (RuntimeException $exception) {
            self::assertSame('Post was not persisted.', $exception->getMessage());
        }

        $repository = new MysqlPostRepository($this->pdo([false, $this->row(id: 12, publishedAt: '2026-01-02 10:00:00')]));
        self::assertSame(12, $repository->update($this->post(status: 'PUBLISHED', publishedAt: '2026-01-02 10:00:00'))->id);

        $repository = new MysqlPostRepository($this->pdo([false, false]));
        try {
            $repository->update($post);
            self::fail('A missing updated row must throw.');
        } catch (RuntimeException $exception) {
            self::assertSame('Post was not found after update.', $exception->getMessage());
        }
    }

    public function testDeleteExecutesPreparedStatement(): void
    {
        $repository = new MysqlPostRepository($this->pdo([false]));
        $repository->delete(12);
        self::assertTrue(true);
    }

    /** @param list<array<string, mixed>|list<array<string, mixed>>|false> $responses */
    private function pdo(array $responses): PDO
    {
        return new class ($responses) extends PDO {
            /** @param list<array<string, mixed>|list<array<string, mixed>>|false> $responses */
            public function __construct(private array $responses)
            {
            }

            public function prepare(string $query, array $options = []): PDOStatement|false
            {
                return $this->statement();
            }

            public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs): PDOStatement|false
            {
                return $this->statement();
            }

            public function lastInsertId(?string $name = null): string|false
            {
                return '12';
            }

            private function statement(): PDOStatement
            {
                $response = array_shift($this->responses) ?: false;
                return new class ($response) extends PDOStatement {
                    public function __construct(private array|false $response)
                    {
                    }

                    public function bindValue(string|int $param, mixed $value, int $type = PDO::PARAM_STR): bool
                    {
                        return true;
                    }

                    public function execute(?array $params = null): bool
                    {
                        return true;
                    }

                    public function fetch(int $mode = PDO::FETCH_DEFAULT, int $cursorOrientation = PDO::FETCH_ORI_NEXT, int $cursorOffset = 0): mixed
                    {
                        return $this->response;
                    }

                    public function fetchAll(int $mode = PDO::FETCH_DEFAULT, mixed ...$args): array
                    {
                        return is_array($this->response) && array_is_list($this->response) ? $this->response : [];
                    }

                    public function fetchColumn(int $column = 0): mixed
                    {
                        return is_array($this->response) ? ($this->response['scalar'] ?? false) : false;
                    }
                };
            }
        };
    }

    /** @return array<string, string|null> */
    private function row(int $id = 12, ?string $excerpt = null, ?string $publishedAt = null): array
    {
        return [
            'id' => (string) $id,
            'title' => 'A title',
            'slug' => 'a-title',
            'content' => 'Body',
            'excerpt' => $excerpt,
            'status' => $publishedAt === null ? 'DRAFT' : 'PUBLISHED',
            'author_id' => '4',
            'published_at' => $publishedAt,
            'created_at' => '2026-01-01 10:00:00',
            'updated_at' => '2026-01-01 10:00:00',
        ];
    }

    private function post(string $status = 'DRAFT', ?string $publishedAt = null): Post
    {
        $createdAt = new DateTimeImmutable('2026-01-01 10:00:00');
        return new Post(12, 'A title', 'a-title', 'Body', null, $status, 4, $publishedAt === null ? null : new DateTimeImmutable($publishedAt), $createdAt, $createdAt);
    }
}
