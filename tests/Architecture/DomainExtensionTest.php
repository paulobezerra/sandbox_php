<?php
declare(strict_types=1);

namespace App\Tests\Architecture;

use App\Application\Notice\ListNoticesService;
use App\Domain\Notice\Notice;
use App\Domain\Notice\NoticeRepository;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class DomainExtensionTest extends TestCase
{
    public function testNewDomainUseCaseWorksWithRepositoryContractOnly(): void
    {
        $notice = new Notice('Manutenção', 'O sistema ficará indisponível às 22h.');
        $repository = new class($notice) implements NoticeRepository {
            public function __construct(private readonly Notice $notice)
            {
            }

            public function all(): array
            {
                return [$this->notice];
            }
        };

        self::assertSame([$notice], (new ListNoticesService($repository))->execute());
    }

    public function testNewDomainProtectsItsTitleInvariant(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Notice(' ', 'Uma mensagem válida.');
    }
}
