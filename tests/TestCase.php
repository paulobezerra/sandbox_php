<?php

declare(strict_types=1);

namespace App\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UriFactory;

abstract class TestCase extends BaseTestCase
{
    protected function getApp(): App
    {
        return \App\Bootstrap\App::create(modules: [new \App\Bootstrap\CmsModule(), new \App\Bootstrap\LegacyPlanModule()]);
    }

    protected function createRequest(
        string $method,
        string $path,
        array $headers = ['HTTP_ACCEPT' => 'application/json'],
        array $serverParams = []
    ): ServerRequestInterface {
        $uri = (new UriFactory())->createUri($path);
        $request = (new ServerRequestFactory())->createServerRequest($method, $uri, $serverParams);

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        return $request;
    }

    protected function createJsonRequest(
        string $method,
        string $path,
        array $data = [],
        array $headers = [],
        array $serverParams = []
    ): ServerRequestInterface {
        $request = $this->createRequest($method, $path, array_merge([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ], $headers), $serverParams);

        if (!empty($data)) {
            $stream = (new StreamFactory())->createStream(json_encode($data, JSON_THROW_ON_ERROR));
            $stream->rewind();
            $request = $request->withBody($stream);
        }

        return $request;
    }
}
