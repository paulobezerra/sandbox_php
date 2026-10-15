<?php
declare(strict_types=1);

namespace App\Infrastructure\Database\Repository;

use App\Domain\Post\{Post, PostRepository};
use DateTimeImmutable;
use PDO;

final readonly class MysqlPostRepository implements PostRepository
{
    public function __construct(private PDO $pdo) {}
    public function find(int $id): ?Post { return $this->one('SELECT * FROM posts WHERE id = ?', [$id]); }
    public function findPublishedBySlug(string $slug): ?Post { return $this->one("SELECT * FROM posts WHERE slug = ? AND status = 'PUBLISHED' AND published_at <= NOW()", [$slug]); }
    public function listAdmin(int $limit,int $offset): array { return $this->many('SELECT * FROM posts ORDER BY created_at DESC,id DESC LIMIT ? OFFSET ?', $limit,$offset); }
    public function listPublished(int $limit,int $offset): array { return $this->many("SELECT * FROM posts WHERE status='PUBLISHED' AND published_at <= NOW() ORDER BY published_at DESC,id DESC LIMIT ? OFFSET ?",$limit,$offset); }
    public function countAdmin(): int { return (int)$this->pdo->query('SELECT COUNT(*) FROM posts')->fetchColumn(); }
    public function countPublished(): int { return (int)$this->pdo->query("SELECT COUNT(*) FROM posts WHERE status='PUBLISHED' AND published_at <= NOW()")->fetchColumn(); }
    public function create(Post $p): Post
    {
        $s=$this->pdo->prepare('INSERT INTO posts(title,slug,content,excerpt,status,author_id,published_at,created_at,updated_at) VALUES(?,?,?,?,?,?,NULL,?,?)');
        $now=$p->createdAt->format('Y-m-d H:i:s'); $s->execute([$p->title,$p->slug,$p->content,$p->excerpt,$p->status,$p->authorId,$now,$now]);
        return $this->find((int)$this->pdo->lastInsertId()) ?? throw new \RuntimeException('Post não persistido.');
    }
    public function update(Post $p): Post
    {
        $s=$this->pdo->prepare('UPDATE posts SET title=?,slug=?,content=?,excerpt=?,status=?,published_at=?,updated_at=? WHERE id=?');
        $s->execute([$p->title,$p->slug,$p->content,$p->excerpt,$p->status,$p->publishedAt?->format('Y-m-d H:i:s'),$p->updatedAt->format('Y-m-d H:i:s'),$p->id]);
        return $this->find($p->id) ?? throw new \RuntimeException('Post não encontrado após atualização.');
    }
    public function delete(int $id): void { $s=$this->pdo->prepare('DELETE FROM posts WHERE id=?'); $s->execute([$id]); }
    /** @return list<Post> */ private function many(string $sql,int $limit,int $offset): array { $s=$this->pdo->prepare($sql); $s->bindValue(1,$limit,PDO::PARAM_INT); $s->bindValue(2,$offset,PDO::PARAM_INT); $s->execute(); return array_map($this->map(...),$s->fetchAll(PDO::FETCH_ASSOC)); }
    private function one(string $sql,array $params): ?Post { $s=$this->pdo->prepare($sql); $s->execute($params); $r=$s->fetch(PDO::FETCH_ASSOC); return $r?$this->map($r):null; }
    private function map(array $r): Post { return new Post((int)$r['id'],$r['title'],$r['slug'],$r['content'],$r['excerpt']!==null?$r['excerpt']:null,$r['status'],(int)$r['author_id'],$r['published_at']?new DateTimeImmutable($r['published_at']):null,new DateTimeImmutable($r['created_at']),new DateTimeImmutable($r['updated_at'])); }
}
