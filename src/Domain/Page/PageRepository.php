<?php

declare(strict_types=1);

namespace App\Domain\Page;

interface PageRepository
{
    public function find(int $id): ?Page;
    public function findPublishedBySlug(string $slug): ?Page;
    /** @return list<Page> */
    public function listAdmin(int $limit, int $offset): array;
    /** @return list<Page> */
    public function listPublished(int $limit, int $offset): array;
    public function countAdmin(): int;
    public function countPublished(): int;
    public function create(Page $page): Page;
    public function update(Page $page): Page;
    public function delete(int $id): void;
}
