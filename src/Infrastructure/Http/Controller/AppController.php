<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Infrastructure\View\ViewRenderer;
use App\Infrastructure\I18n\Localization;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AppController
{
    public function __construct(
        private readonly ViewRenderer $view,
        private readonly Localization $localization
    ) {
    }

    public function index(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $locale = (string) $request->getAttribute('locale', $this->localization->fallbackLocale);
        $translations = [];
        foreach (['shell.loading', 'shell.loadError', 'shell.home', 'shell.blog', 'shell.login', 'shell.myPosts', 'shell.logout', 'shell.footer', 'shell.title', 'shell.description'] as $key) {
            $translations[$key] = $this->localization->translate($locale, $key);
        }
        return $this->view->render($response, 'index.phtml', ['locale' => $locale, 'translations' => $translations]);
    }
}
