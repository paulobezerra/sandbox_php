<?php
declare(strict_types=1);

namespace App\Domain\Page;

use DateTimeImmutable;

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
