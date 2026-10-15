<?php
declare(strict_types=1);

namespace App\Tests\Unit\Application;

use App\Application\Page\PageService;
use App\Domain\Page\PageRepository;
use DomainException;
use PHPUnit\Framework\TestCase;

final class PageServiceTest extends TestCase
{
    public function testApplicationReservedSlugCannotBeCreatedAsPage(): void
    {
        $repository = $this->createMock(PageRepository::class);
        $service = new PageService($repository, \App\Bootstrap\ProjectModules::reservedPageSlugs());
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('reservado');
        $service->create(['title' => 'Admin', 'slug' => 'admin', 'content' => 'Conteúdo']);
    }

    public function testInstitutionalSiteRoutesCannotBeReplacedByPages(): void
    {
        $repository = $this->createMock(PageRepository::class);
        $service = new PageService($repository, \App\Bootstrap\ProjectModules::reservedPageSlugs());

        foreach (['features', 'architecture', 'guide', 'login', 'register', 'forgot-password', 'reset-password'] as $slug) {
            try {
                $service->create(['title' => ucfirst($slug), 'slug' => $slug, 'content' => 'Conteúdo']);
                self::fail(sprintf('Expected reserved slug "%s" to be rejected.', $slug));
            } catch (DomainException $exception) {
                self::assertStringContainsString('reservado', $exception->getMessage());
            }
        }
    }

    public function testExplicitInvalidSlugIsRejectedInsteadOfSilentlyNormalized(): void
    {
        $repository = $this->createMock(PageRepository::class);
        $repository->expects(self::never())->method('create');
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Slug inválido.');
        (new PageService($repository, \App\Bootstrap\ProjectModules::reservedPageSlugs()))->create(['title' => 'Minha Página', 'slug' => 'Slug Inválido!', 'content' => 'Conteúdo']);
    }
}
