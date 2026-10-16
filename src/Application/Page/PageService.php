<?php

declare(strict_types=1);

namespace App\Application\Page;

use App\Domain\{ContentNotFound, Page\Page, Page\PageRepository};
use DateTimeImmutable;
use DomainException;

final readonly class PageService
{
    /** @param list<string> $reservedSlugs */
    public function __construct(private PageRepository $pages, private array $reservedSlugs = [])
    {
    }

    /** @return array{items:list<Page>,total:int,page:int,perPage:int} */
    public function listAdmin(int $page, int $perPage): array
    {
        return $this->listing($page, $perPage, false);
    }

    /** @return array{items:list<Page>,total:int,page:int,perPage:int} */
    public function listPublished(int $page, int $perPage): array
    {
        return $this->listing($page, $perPage, true);
    }

    public function find(int $id): Page
    {
        return $this->pages->find($id) ?? throw new ContentNotFound('Página não encontrada.');
    }

    public function findPublished(string $slug): Page
    {
        return $this->pages->findPublishedBySlug($slug) ?? throw new ContentNotFound('Página não encontrada.');
    }

    /** @param array<string,mixed> $data */
    public function create(array $data): Page
    {
        [$title, $slug, $content] = $this->validate($data);
        $now = new DateTimeImmutable();
        return $this->pages->create(new Page(0, $title, $slug, $content, 'DRAFT', null, $now, $now));
    }

    /** @param array<string,mixed> $data */
    public function update(int $id, array $data): Page
    {
        $p = $this->find($id);
        [$title, $slug, $content] = $this->validate($data);
        return $this->pages->update(new Page($id, $title, $slug, $content, $p->status, $p->publishedAt, $p->createdAt, new DateTimeImmutable()));
    }

    public function publish(int $id): Page
    {
        $p = $this->find($id);
        return $this->pages->update(new Page($p->id, $p->title, $p->slug, $p->content, 'PUBLISHED', $p->publishedAt ?? new DateTimeImmutable(), $p->createdAt, new DateTimeImmutable()));
    }

    public function unpublish(int $id): Page
    {
        $p = $this->find($id);
        return $this->pages->update(new Page($p->id, $p->title, $p->slug, $p->content, 'DRAFT', null, $p->createdAt, new DateTimeImmutable()));
    }

    public function delete(int $id): void
    {
        $this->find($id);
        $this->pages->delete($id);
    }

    /** @return array{items:list<Page>,total:int,page:int,perPage:int} */
    private function listing(int $page, int $perPage, bool $public): array
    {
        if ($page < 1 || $perPage < 1 || $perPage > 100) {
            throw new DomainException('Paginação inválida.');
        }
        $offset = ($page - 1) * $perPage;
        return ['items' => $public ? $this->pages->listPublished($perPage, $offset) : $this->pages->listAdmin($perPage, $offset), 'total' => $public ? $this->pages->countPublished() : $this->pages->countAdmin(), 'page' => $page, 'perPage' => $perPage];
    }

    /**
     * @param array<string,mixed> $data
     * @return array{string,string,string}
     */
    private function validate(array $data): array
    {
        if (array_key_exists('title', $data) && !is_string($data['title'])) {
            throw new DomainException('O título deve ser texto.');
        }
        if (array_key_exists('content', $data) && !is_string($data['content'])) {
            throw new DomainException('O conteúdo deve ser texto.');
        }
        $title = trim($data['title'] ?? '');
        $content = $data['content'] ?? '';
        if (array_key_exists('slug', $data) && !is_string($data['slug'])) {
            throw new DomainException('Slug inválido.');
        }
        $raw = trim($data['slug'] ?? '');
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $raw !== '' ? $raw : $title);
        $slug = $raw === ''
            ? strtolower(trim((string)preg_replace('/[^a-zA-Z0-9]+/', '-', (string)$ascii), '-'))
            : $raw;
        if ($title === '' || mb_strlen($title) > 200) {
            throw new DomainException('O título é obrigatório e deve ter até 200 caracteres.');
        }
        if ($content === '' || mb_strlen($content) > 60000) {
            throw new DomainException('O conteúdo é obrigatório e deve ter até 60000 caracteres.');
        }
        if ($slug === '' || mb_strlen($slug) > 200 || preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) !== 1) {
            throw new DomainException('Slug inválido.');
        }
        if (in_array($slug, $this->reservedSlugs, true)) {
            throw new DomainException('Esse slug é reservado pela aplicação.');
        }
        return [$title, $slug, $content];
    }
}
