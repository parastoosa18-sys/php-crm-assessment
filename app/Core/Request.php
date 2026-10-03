<?php

declare(strict_types=1);

namespace Assessment\Core;

final class Request
{
    private string $method;
    private string $path;
    private array $query;
    private array $headers;
    private array $body;

    public function __construct(string $method, string $path, array $query = array(), array $headers = array(), array $body = array())
    {
        $this->method = strtoupper($method);
        $this->path = '/' . trim($path, '/');
        $this->query = $query;
        $this->headers = array_change_key_case($headers, CASE_LOWER);
        $this->body = $body;
    }

    public static function fromGlobals(): self
    {
        $rawBody = file_get_contents('php://input');
        $decoded = json_decode($rawBody ?: '', true);
        $body = is_array($decoded) ? $decoded : $_POST;

        return new self(
            $_SERVER['REQUEST_METHOD'] ?? 'GET',
            parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/',
            $_GET,
            function_exists('getallheaders') ? (getallheaders() ?: array()) : array(),
            $body
        );
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function query(string $key, $default = null)
    {
        return $this->query[$key] ?? $default;
    }

    public function header(string $key, $default = null)
    {
        return $this->headers[strtolower($key)] ?? $default;
    }

    public function body(): array
    {
        return $this->body;
    }
}

