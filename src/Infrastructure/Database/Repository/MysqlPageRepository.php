<?php

declare(strict_types=1);

namespace App\Infrastructure\Database\Repository;

use App\Domain\Page\{Page, PageRepository};
use DateTimeImmutable;
use PDO;

final readonly class MysqlPageRepository implements PageRepository
{
    public function __construct(private PDO $pdo)
    {
    }
    public function find(int $id): ?Page
    {
        return $this->one('SELECT * FROM pages WHERE id = ?', [$id]);
    }
    public function findPublishedBySlug(string $slug): ?Page
    {
        return $this->one("SELECT * FROM pages WHERE slug = ? AND status='PUBLISHED' AND published_at <= NOW()", [$slug]);
    }
    public function listAdmin(int $limit, int $offset): array
    {
        return $this->many('SELECT * FROM pages ORDER BY created_at DESC,id DESC LIMIT ? OFFSET ?', $limit, $offset);
    }
    public function listPublished(int $limit, int $offset): array
    {
        return $this->many("SELECT * FROM pages WHERE status='PUBLISHED' AND published_at <= NOW() ORDER BY published_at DESC,id DESC LIMIT ? OFFSET ?", $limit, $offset);
    }
    public function countAdmin(): int
    {
        return (int)$this->pdo->query('SELECT COUNT(*) FROM pages')->fetchColumn();
    }
    public function countPublished(): int
    {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM pages WHERE status='PUBLISHED' AND published_at <= NOW()")->fetchColumn();
    }
    public function create(Page $p): Page
    {
        $now = $p->createdAt->format('Y-m-d H:i:s');
        $s = $this->pdo->prepare('INSERT INTO pages(title,slug,content,status,published_at,created_at,updated_at) VALUES(?,?,?, ?,NULL,?,?)');
        $s->execute([$p->title,$p->slug,$p->content,$p->status,$now,$now]);
        return $this->find((int)$this->pdo->lastInsertId()) ?? throw new \RuntimeException('Página não persistida.');
    }
    public function update(Page $p): Page
    {
        $s = $this->pdo->prepare('UPDATE pages SET title=?,slug=?,content=?,status=?,published_at=?,updated_at=? WHERE id=?');
        $s->execute([$p->title,$p->slug,$p->content,$p->status,$p->publishedAt?->format('Y-m-d H:i:s'),$p->updatedAt->format('Y-m-d H:i:s'),$p->id]);
        return $this->find($p->id) ?? throw new \RuntimeException('Página não encontrada após atualização.');
    }
    public function delete(int $id): void
    {
        $s = $this->pdo->prepare('DELETE FROM pages WHERE id=?');
        $s->execute([$id]);
    }
    /** @return list<Page> */ private function many(string $sql, int $limit, int $offset): array
    {
        $s = $this->pdo->prepare($sql);
        $s->bindValue(1, $limit, PDO::PARAM_INT);
        $s->bindValue(2, $offset, PDO::PARAM_INT);
        $s->execute();
        return array_map($this->map(...), $s->fetchAll(PDO::FETCH_ASSOC));
    }
    /** @param array<array-key, int|string> $params */
    private function one(string $sql, array $params): ?Page
    {
        $s = $this->pdo->prepare($sql);
        $s->execute($params);
        $r = $s->fetch(PDO::FETCH_ASSOC);
        return $r ? $this->map($r) : null;
    }
    /** @param array<string, mixed> $r */
    private function map(array $r): Page
    {
        return new Page((int)$r['id'], $r['title'], $r['slug'], $r['content'], $r['status'], $r['published_at'] ? new DateTimeImmutable($r['published_at']) : null, new DateTimeImmutable($r['created_at']), new DateTimeImmutable($r['updated_at']));
    }
}
