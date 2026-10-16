<?php

declare(strict_types=1);

namespace App\Tests\Architecture;

use App\Bootstrap\ProjectModules;
use PHPUnit\Framework\TestCase;

final class PageSlugRoutesTest extends TestCase
{
    public function testEveryStaticRootVueRouteReservesItsPageSlug(): void
    {
        $router = (string) file_get_contents(dirname(__DIR__, 2) . '/public/assets/js/router.js');
        preg_match_all("/path:\\s*'\\/([a-z][a-z0-9-]*)'/", $router, $matches);
        $reserved = ProjectModules::reservedPageSlugs();

        foreach ($matches[1] as $slug) {
            self::assertContains($slug, $reserved, "Root route /$slug must reserve its CMS Page slug.");
        }
    }
}
