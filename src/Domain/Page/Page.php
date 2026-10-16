<?php

declare(strict_types=1);

namespace App\Domain\Page;

use DateTimeImmutable;
use InvalidArgumentException;

final readonly class Page
{
    public function __construct(
        public int $id,
        public string $title,
        public string $slug,
        public string $content,
        public string $status,
        public ?DateTimeImmutable $publishedAt,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {
        if ($id < 0 || trim($title) === '' || mb_strlen($title) > 200
            || $content === '' || mb_strlen($content) > 60000
            || preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) !== 1 || mb_strlen($slug) > 200
            || !in_array($status, ['DRAFT', 'PUBLISHED'], true)
            || ($status === 'PUBLISHED' && $publishedAt === null)
            || ($status === 'DRAFT' && $publishedAt !== null)
            || $updatedAt < $createdAt) {
            throw new InvalidArgumentException('Página contém estado inválido.');
        }
    }

    /** @return array<string, mixed> */
    public function toArray(bool $public = false): array
    {
        $data = [
            'id' => $this->id, 'title' => $this->title, 'slug' => $this->slug,
            'content' => $this->content, 'status' => $this->status,
            'publishedAt' => $this->publishedAt?->format(DATE_ATOM),
            'createdAt' => $this->createdAt->format(DATE_ATOM), 'updatedAt' => $this->updatedAt->format(DATE_ATOM),
        ];
        if ($public) {
            unset($data['updatedAt']);
        }
        return $data;
    }
}
