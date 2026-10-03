<?php

declare(strict_types=1);

namespace Assessment\Controller;

use Assessment\Core\Response;

final class HealthController
{
    public function __invoke(): Response
    {
        return Response::json(array('status' => 'ok'));
    }
}

