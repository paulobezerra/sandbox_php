<?php

declare(strict_types=1);

namespace App\Domain\Post;

use DateTimeImmutable;
use InvalidArgumentException;

final readonly class Post
{
    public function __construct(
        public int $id,
        public string $title,
        public string $slug,
        public string $content,
        public ?string $excerpt,
        public string $status,
        public int $authorId,
        public ?DateTimeImmutable $publishedAt,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {
        if ($id < 0 || $authorId < 1 || trim($title) === '' || mb_strlen($title) > 200
            || $content === '' || mb_strlen($content) > 60000
            || preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) !== 1 || mb_strlen($slug) > 200
            || !in_array($status, ['DRAFT', 'PUBLISHED'], true)
            || ($excerpt !== null && mb_strlen($excerpt) > 1000)
            || ($status === 'PUBLISHED' && $publishedAt === null)
            || ($status === 'DRAFT' && $publishedAt !== null)
            || $updatedAt < $createdAt) {
            throw new InvalidArgumentException('Post has an invalid state.');
        }
    }

    /** @return array<string, mixed> */
    public function toArray(bool $public = false): array
    {
        $data = [
            'id' => $this->id, 'title' => $this->title, 'slug' => $this->slug,
            'content' => $this->content, 'excerpt' => $this->excerpt, 'status' => $this->status,
            'authorId' => $this->authorId, 'publishedAt' => $this->publishedAt?->format(DATE_ATOM),
            'createdAt' => $this->createdAt->format(DATE_ATOM), 'updatedAt' => $this->updatedAt->format(DATE_ATOM),
        ];
        if ($public) {
            unset($data['authorId'], $data['updatedAt']);
        }
        return $data;
    }
}
