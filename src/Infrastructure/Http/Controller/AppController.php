<?php
declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Infrastructure\View\ViewRenderer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AppController
{
    public function __construct(
        private readonly ViewRenderer $view
    ) {
    }

    public function index(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        return $this->view->render($response, 'index.phtml');
    }
}
