<?php
declare(strict_types=1);

namespace Engine;

class Router
{
    private array $routes = [];

    /**
     * Pass $csrf = false only for an endpoint that cannot carry a token, such as
     * a webhook called by another server. Then the action itself has to
     * authenticate the caller.
     */
    public function add(string $method, string $path, string $controller, string $action, bool $csrf = true): void
    {
        $this->routes[] = [
            'method'     => strtoupper($method),
            'path'       => $path,
            'controller' => $controller,
            'action'     => $action,
            'csrf'       => $csrf,
        ];
    }

    public function get(string $path, string $controller, string $action): void
    {
        $this->add('GET', $path, $controller, $action);
    }

    public function post(string $path, string $controller, string $action): void
    {
        $this->add('POST', $path, $controller, $action);
    }

    /** The first match wins, so put concrete paths before the ones with a {parameter}. */
    public function match(Request $request): ?array
    {
        foreach ($this->routes as $route) {
            if ($route['method'] === $request->method && preg_match($this->pattern($route['path']), $request->uri) === 1) {
                return $route;
            }
        }

        return null;
    }

    /** @return array<string, string> parameter name => value from the URL */
    public function getParams(string $routePath, string $requestUri): array
    {
        preg_match_all('/\{([^}]+)\}/', $routePath, $names);

        if ($names[1] === [] || preg_match($this->pattern($routePath), $requestUri, $values) !== 1) {
            return [];
        }

        array_shift($values); // [0] is the whole match, not a parameter

        // rawurldecode, because a space arrives as %20 and the slug would not line up
        $values = array_map(rawurldecode(...), $values);

        return array_combine($names[1], $values);
    }

    /**
     * Route path to a regular expression. preg_quote is the important part:
     * without it the dot in /file.json would mean "any character".
     */
    private function pattern(string $routePath): string
    {
        $parts   = preg_split('/(\{[^}]+\})/', $routePath, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];
        $pattern = '';

        foreach ($parts as $part) {
            $pattern .= str_starts_with($part, '{') ? '([^/]+)' : preg_quote($part, '#');
        }

        return '#^' . $pattern . '$#';
    }
}
