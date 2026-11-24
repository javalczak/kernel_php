<?php
declare(strict_types=1);

namespace Engine;

use Engine\Translation\Translator;

abstract class Controller
{
    public function __construct(
        protected ServiceContainer $container
    ) {}

    /**
     * The template is given relative to templates/, without the extension:
     * 'example/index'. Keys of $data become plain variables in the template.
     */
    protected function render(string $template, array $data = []): Response
    {
        // A template name never comes from the visitor, but one careless change
        // in a controller and we would have "../config/database" - hence the check.
        if (preg_match('#^[A-Za-z0-9_/-]+$#', $template) !== 1 || str_contains($template, '..')) {
            throw new \InvalidArgumentException("Invalid template name: {$template}");
        }

        $file = BASE_PATH . '/templates/' . $template . '.php';

        if (!is_file($file)) {
            return Response::notFound("Missing template: {$template}");
        }

        $data['translator'] = $this->container->make('translator');

        extract($data);

        // Buffer: the template echoes, and we want that as a string in Response.
        ob_start();
        require $file;

        return (new Response())->setContent((string) ob_get_clean());
    }

    protected function redirect(string $url, int $code = 302): Response
    {
        return Response::redirect($url, $code);
    }

    protected function json(array $data, int $code = 200): Response
    {
        return (new Response())
            ->setStatusCode($code)
            ->addHeader('Content-Type', 'application/json; charset=utf-8')
            ->setContent(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    protected function db(): Database
    {
        return $this->container->make('db');
    }

    protected function trans(string $key, array $params = []): string
    {
        /** @var Translator $translator */
        $translator = $this->container->make('translator');

        return $translator->trans($key, $params);
    }
}
