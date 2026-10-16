<?php

declare(strict_types=1);

namespace App\Domain\Post;

interface PostRepository
{
    public function find(int $id): ?Post;
    public function findByAuthor(int $id, int $authorId): ?Post;
    public function findPublishedBySlug(string $slug): ?Post;
    /** @return list<Post> */
    public function listPublished(int $limit, int $offset): array;
    /** @return list<Post> */
    public function listByAuthor(int $authorId, int $limit, int $offset): array;
    public function countByAuthor(int $authorId): int;
    public function countPublished(): int;
    public function create(Post $post): Post;
    public function update(Post $post): Post;
    public function delete(int $id): void;
}
