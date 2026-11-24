<?php
declare(strict_types=1);

namespace Engine;

use Engine\Translation\Translator;

class Kernel
{
    /** Methods that only read, so they need no CSRF token. */
    private const SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];

    private ServiceContainer $container;
    private Router $router;

    public function __construct(
        private Environment $environment
    ) {
        $this->container = new ServiceContainer();
        $this->router    = new Router();
    }

    public function boot(): void
    {
        $this->registerCoreServices();
        $this->loadTranslations();
        $this->registerRoutes();
    }

    public function createRequest(): Request
    {
        return new Request();
    }

    public function handle(Request $request): Response
    {
        $route = $this->router->match($request);

        if ($route === null) {
            return Response::notFound($this->translate('error.not_found'));
        }

        // Checked here, not in the controller: a new write action stays protected
        // even when whoever wrote it never thought about CSRF.
        if (!$this->csrfSatisfied($request, $route)) {
            return Response::forbidden($this->translate('error.csrf'));
        }

        $controllerClass = $route['controller'];
        $action          = $route['action'];

        if (!class_exists($controllerClass)) {
            return Response::notFound($this->translate('error.not_found'));
        }

        $controller = new $controllerClass($this->container);

        // method_exists is not enough: it also sees private and protected methods,
        // and calling one of those from outside ends in a 500 instead of a clean 404.
        if (!$this->isPublicAction($controller, $action)) {
            return Response::notFound($this->translate('error.not_found'));
        }

        $params = $this->router->getParams($route['path'], $request->uri);

        return $controller->$action($request, $params);
    }

    public function getContainer(): ServiceContainer
    {
        return $this->container;
    }

    // -------------------------------------------------------------------------
    // Private
    // -------------------------------------------------------------------------

    /** A write request needs a valid token unless its route opts out. */
    private function csrfSatisfied(Request $request, array $route): bool
    {
        if (in_array($request->method, self::SAFE_METHODS, true)) {
            return true;
        }

        if (($route['csrf'] ?? true) === false) {
            return true;
        }

        return Csrf::verify($request->string(Csrf::FIELD));
    }

    private function translate(string $key): string
    {
        /** @var Translator $translator */
        $translator = $this->container->make('translator');

        return $translator->trans($key);
    }

    private function isPublicAction(object $controller, string $action): bool
    {
        if (!method_exists($controller, $action)) {
            return false;
        }

        return (new \ReflectionMethod($controller, $action))->isPublic();
    }

    private function registerCoreServices(): void
    {
        // singleton, not bind: the connection is opened once per request and only
        // on first use, so a page without queries never opens one at all.
        $this->container->singleton('db', function () {
            // The file may sit outside the web root, so Environment resolves it.
            $config = require $this->environment->configFile('database.php');

            return new Database($config);
        });

        $this->container->singleton('translator', function () {
            // An unknown cookie value falls back to the default locale (see Translator).
            return new Translator((string) ($_COOKIE['locale'] ?? 'en'));
        });
    }

    private function loadTranslations(): void
    {
        /** @var Translator $translator */
        $translator = $this->container->make('translator');
        $translator->load(BASE_PATH . '/src');
    }

    private function registerRoutes(): void
    {
        $module = require BASE_PATH . '/config/module.php';

        foreach ($module['routes'] as $route) {
            $this->addRoute($route);
        }

        // Optional: src/routes.php adds application routes so config/module.php stays small.
        $appRoutes = BASE_PATH . '/src/routes.php';

        if (is_file($appRoutes)) {
            foreach (require $appRoutes as $route) {
                $this->addRoute($route);
            }
        }
    }

    private function addRoute(array $route): void
    {
        $this->router->add(
            $route['method'],
            $route['path'],
            $route['controller'],
            $route['action'],
            $route['csrf'] ?? true
        );
    }
}
