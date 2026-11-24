<?php
declare(strict_types=1);

namespace Engine;

class Request
{
    public readonly string $method;
    public readonly string $uri;
    public readonly array $query;
    public readonly array $post;
    public readonly array $server;
    public readonly array $cookies;
    public readonly array $files;

    public function __construct()
    {
        $this->method  = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        // strtok cuts the query string off: /items?page=2 -> /items
        $this->uri     = (string) (strtok($_SERVER['REQUEST_URI'] ?? '/', '?') ?: '/');
        $this->query   = $_GET;
        $this->post    = $_POST;
        $this->server  = $_SERVER;
        $this->cookies = $_COOKIE;
        $this->files   = $_FILES;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $default;
    }

    /**
     * A form field as a string, and only as a string.
     *
     * A field named "title[]" arrives in $_POST as an array, and casting an
     * array to (string) is a PHP 8 error, so a 500 instead of a validation
     * message. Anything that is not a scalar counts as no value at all.
     */
    public function string(string $key, string $default = ''): string
    {
        $value = $this->post[$key] ?? $this->query[$key] ?? null;

        return is_scalar($value) ? (string) $value : $default;
    }

    public function int(string $key, int $default = 0): int
    {
        $value = $this->string($key);

        return is_numeric($value) ? (int) $value : $default;
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function isGet(): bool
    {
        return $this->method === 'GET';
    }
}
