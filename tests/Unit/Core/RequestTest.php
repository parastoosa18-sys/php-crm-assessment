<?php

declare(strict_types=1);

namespace Assessment\Tests\Unit\Core;

use Assessment\Core\Request;
use PHPUnit\Framework\TestCase;

final class RequestTest extends TestCase
{
    public function testNormalizesMethodPathAndHeaders(): void
    {
        $request = new Request('get', '/customers/', array(), array('X-User-Id' => '2'));

        self::assertSame('GET', $request->method());
        self::assertSame('/customers', $request->path());
        self::assertSame('2', $request->header('x-user-id'));
    }
}

