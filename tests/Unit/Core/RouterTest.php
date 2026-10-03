<?php

declare(strict_types=1);

namespace Assessment\Tests\Unit\Core;

use Assessment\Core\Request;
use Assessment\Core\Response;
use Assessment\Core\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    public function testDispatchesRouteParameters(): void
    {
        $router = new Router();
        $router->get('/items/{id}', static function (Request $request, array $params): Response {
            return Response::json(array('id' => $params['id']));
        });

        $response = $router->dispatch(new Request('GET', '/items/42'));

        self::assertSame(200, $response->status());
        self::assertSame('{"id":"42"}', $response->body());
    }

    public function testReturnsNotFoundForUnknownRoute(): void
    {
        $response = (new Router())->dispatch(new Request('GET', '/missing'));

        self::assertSame(404, $response->status());
    }
}

