<?php
declare(strict_types=1);

namespace App\Bootstrap;

final class ProjectModules
{
    /** @return list<BootstrapModule> */
    public static function enabled(): array
    {
        return [new CmsModule()];
    }

    /** Root frontend routes and infrastructure paths unavailable to CMS Pages. @return list<string> */
    public static function reservedPageSlugs(): array
    {
        return ['admin', 'api', 'login', 'logout', 'register', 'docs', 'blog', 'posts', 'pages', 'assets', 'health', 'about', 'features', 'architecture', 'guide', 'forgot-password', 'reset-password', 'account'];
    }
}
