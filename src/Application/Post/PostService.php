<?php

declare(strict_types=1);

namespace App\Application\Post;

use App\Domain\Post\{Post, PostRepository};
use DateTimeImmutable;

final readonly class PostService
{
    public function __construct(private PostRepository $posts)
    {
    }

    /** @return array{items:list<Post>,total:int,page:int,perPage:int} */
    public function listMine(int $authorId, int $page, int $perPage): array
    {
        [$page, $perPage, $offset] = $this->pagination($page, $perPage);
        return ['items' => $this->posts->listByAuthor($authorId, $perPage, $offset), 'total' => $this->posts->countByAuthor($authorId), 'page' => $page, 'perPage' => $perPage];
    }


    /** @return array{items:list<Post>,total:int,page:int,perPage:int} */
    public function listPublished(int $page, int $perPage): array
    {
        [$page, $perPage, $offset] = $this->pagination($page, $perPage);
        return ['items' => $this->posts->listPublished($perPage, $offset), 'total' => $this->posts->countPublished(), 'page' => $page, 'perPage' => $perPage];
    }

    public function findMine(int $id, int $authorId): Post
    {
        return $this->posts->findByAuthor($id, $authorId) ?? throw new PostException('post.notFound');
    }
    public function findPublished(string $slug): Post
    {
        return $this->posts->findPublishedBySlug($slug) ?? throw new PostException('post.notFound');
    }

    /** @param array<string, mixed> $data */
    public function create(array $data, int $authorId): Post
    {
        [$title, $slug, $content, $excerpt] = $this->validate($data);
        $now = new DateTimeImmutable();
        return $this->posts->create(new Post(0, $title, $slug, $content, $excerpt, 'DRAFT', $authorId, null, $now, $now));
    }

    /** @param array<string, mixed> $data */
    public function updateMine(int $id, int $authorId, array $data): Post
    {
        $existing = $this->findMine($id, $authorId);
        [$title, $slug, $content, $excerpt] = $this->validate($data);
        return $this->posts->update(new Post($id, $title, $slug, $content, $excerpt, $existing->status, $existing->authorId, $existing->publishedAt, $existing->createdAt, new DateTimeImmutable()));
    }

    public function publishMine(int $id, int $authorId): Post
    {
        $post = $this->findMine($id, $authorId);
        $now = new DateTimeImmutable();
        return $this->posts->update(new Post($post->id, $post->title, $post->slug, $post->content, $post->excerpt, 'PUBLISHED', $post->authorId, $post->publishedAt ?? $now, $post->createdAt, $now));
    }

    public function unpublishMine(int $id, int $authorId): Post
    {
        $post = $this->findMine($id, $authorId);
        return $this->posts->update(new Post($post->id, $post->title, $post->slug, $post->content, $post->excerpt, 'DRAFT', $post->authorId, null, $post->createdAt, new DateTimeImmutable()));
    }

    public function deleteMine(int $id, int $authorId): void
    {
        $this->findMine($id, $authorId);
        $this->posts->delete($id);
    }

    /**
     * @param array<string, mixed> $data
     * @return array{string,string,string,?string}
     */
    private function validate(array $data): array
    {
        if (array_key_exists('title', $data) && !is_string($data['title'])) {
            throw new PostException('post.titleText');
        }
        if (array_key_exists('content', $data) && !is_string($data['content'])) {
            throw new PostException('post.contentText');
        }
        $title = trim($data['title'] ?? '');
        $content = $data['content'] ?? '';
        if (array_key_exists('slug', $data) && !is_string($data['slug'])) {
            throw new PostException('post.invalidSlug');
        }
        $rawSlug = trim($data['slug'] ?? '');
        $slug = $rawSlug === '' ? $this->slugify($title) : $rawSlug;
        $excerpt = $data['excerpt'] ?? null;
        if ($excerpt !== null && !is_string($excerpt)) {
            throw new PostException('post.invalidExcerpt');
        }
        $excerpt = is_string($excerpt) ? trim($excerpt) : null;
        if ($title === '' || mb_strlen($title) > 200) {
            throw new PostException('post.titleRequired');
        }
        if ($content === '' || mb_strlen($content) > 60000) {
            throw new PostException('post.contentRequired');
        }
        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) !== 1 || mb_strlen($slug) > 200) {
            throw new PostException('post.invalidSlug');
        }
        if ($excerpt !== null && mb_strlen($excerpt) > 1000) {
            throw new PostException('post.excerptTooLong');
        }
        return [$title, $slug, $content, $excerpt];
    }

    private function slugify(string $value): string
    {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        $slug = strtolower(trim((string)preg_replace('/[^a-zA-Z0-9]+/', '-', $ascii), '-'));
        return $slug;
    }

    /** @return array{int,int,int} */
    private function pagination(int $page, int $perPage): array
    {
        if ($page < 1 || $perPage < 1 || $perPage > 100) {
            throw new PostException('post.invalidPagination');
        }
        return [$page, $perPage, ($page - 1) * $perPage];
    }
}
