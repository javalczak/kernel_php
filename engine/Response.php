<?php
declare(strict_types=1);

namespace Engine;

class Response
{
    private string $content = '';
    private int $statusCode = 200;

    // Default content type. Without charset=utf-8 the browser guesses the
    // encoding and accented characters fall apart - with nosniff it is a must.
    private array $headers = ['Content-Type' => 'text/html; charset=utf-8'];

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function setStatusCode(int $code): static
    {
        $this->statusCode = $code;

        return $this;
    }

    public function addHeader(string $name, string $value): static
    {
        $this->headers[$name] = $value;

        return $this;
    }

    public function send(): void
    {
        http_response_code($this->statusCode);

        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }

        echo $this->content;
    }

    public static function notFound(string $message = 'Not found'): static
    {
        return (new static())
            ->setStatusCode(404)
            ->setContent($message);
    }

    public static function forbidden(string $message = 'Forbidden'): static
    {
        return (new static())
            ->setStatusCode(403)
            ->setContent($message);
    }

    public static function redirect(string $url, int $code = 302): static
    {
        return (new static())
            ->setStatusCode($code)
            ->addHeader('Location', $url);
    }
}
