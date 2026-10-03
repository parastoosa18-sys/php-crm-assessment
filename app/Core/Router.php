<?php

declare(strict_types=1);

namespace Assessment\Core;

use Throwable;

final class Router
{
    private array $routes = array();

    public function get(string $path, callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    public function put(string $path, callable $handler): void
    {
        $this->add('PUT', $path, $handler);
    }

    public function add(string $method, string $path, callable $handler): void
    {
        $this->routes[] = array($method, '/' . trim($path, '/'), $handler);
    }

    public function dispatch(Request $request): Response
    {
        foreach ($this->routes as $route) {
            list($method, $path, $handler) = $route;
            $pattern = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $path);

            if ($method === $request->method() && preg_match('#^' . $pattern . '$#', $request->path(), $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                try {
                    return $handler($request, $params);
                } catch (HttpException $exception) {
                    return Response::json(array('error' => $exception->getMessage()), $exception->status());
                } catch (Throwable $exception) {
                    if (Config::get('APP_DEBUG', 'false') === 'true') {
                        return Response::json(array('error' => $exception->getMessage()), 500);
                    }
                    return Response::json(array('error' => 'Internal server error'), 500);
                }
            }
        }

        return Response::json(array('error' => 'Not found'), 404);
    }
}

