<?php
declare(strict_types=1);

namespace Engine;

/**
 * Last line of defense for uncaught exceptions and PHP errors.
 *
 * In production the visitor gets a plain 500 page and the details go to the log.
 * In development the details stay on screen, because that is what they are for.
 */
class ErrorHandler
{
    /** Errors PHP reports only after the script is already dead. */
    private const FATAL = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];

    public function __construct(
        private Environment $environment
    ) {}

    public function register(): void
    {
        error_reporting(E_ALL);

        // Nothing is printed by PHP itself - every path goes through this class.
        ini_set('display_errors', '0');
        ini_set('display_startup_errors', '0');
        ini_set('log_errors', '0');

        set_error_handler($this->handleError(...));
        set_exception_handler($this->handleException(...));
        register_shutdown_function($this->handleShutdown(...));
    }

    /**
     * Turns warnings and notices into exceptions, so a single code path covers
     * everything. Returning false would hand the error back to PHP, which would
     * print it - exactly what we are avoiding.
     */
    public function handleError(int $severity, string $message, string $file = '', int $line = 0): bool
    {
        if ((error_reporting() & $severity) === 0) {
            return true;
        }

        throw new \ErrorException($message, 0, $severity, $file, $line);
    }

    public function handleException(\Throwable $exception): void
    {
        $this->log($this->describe($exception));
        $this->respond($this->environment->isDevelopment() ? $this->describe($exception) : null);
    }

    public function handleShutdown(): void
    {
        $error = error_get_last();

        if ($error === null || !in_array($error['type'], self::FATAL, true)) {
            return;
        }

        $details = sprintf('%s in %s:%d', $error['message'], $error['file'], $error['line']);

        $this->log($details);
        $this->respond($this->environment->isDevelopment() ? $details : null);
    }

    // -------------------------------------------------------------------------
    // Private
    // -------------------------------------------------------------------------

    /** Full story for the log: message, class, file, line and the whole chain. */
    private function describe(\Throwable $exception): string
    {
        $parts = [];

        for ($current = $exception; $current !== null; $current = $current->getPrevious()) {
            $parts[] = sprintf(
                "%s: %s in %s:%d\n%s",
                $current::class,
                $current->getMessage(),
                $current->getFile(),
                $current->getLine(),
                $current->getTraceAsString()
            );
        }

        return implode("\n\nCaused by: ", $parts);
    }

    private function log(string $details): void
    {
        $file = $this->environment->logFile();
        $dir  = dirname($file);

        if (!is_dir($dir) && !@mkdir($dir, 0o750, true) && !is_dir($dir)) {
            return;
        }

        // First line makes PHP exit before printing anything, so the log stays
        // unreadable through a browser even without .htaccess.
        if (!is_file($file)) {
            @file_put_contents($file, "<?php exit; ?>\n");
        }

        $entry = sprintf(
            "[%s] %s %s\n%s\n\n",
            date('Y-m-d H:i:s'),
            $_SERVER['REQUEST_METHOD'] ?? 'CLI',
            $_SERVER['REQUEST_URI'] ?? '-',
            $details
        );

        @file_put_contents($file, $entry, FILE_APPEND | LOCK_EX);
    }

    /** @param string|null $details Shown only in development. */
    private function respond(?string $details): void
    {
        // A half-rendered template must not end up above the error page.
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=utf-8');
        }

        $body = $details === null
            ? '<h1>500</h1><p>Something went wrong. Please try again later.</p>'
            : '<h1>500</h1><pre>' . htmlspecialchars($details) . '</pre>';

        // No inline styles here on purpose - the content security policy would block them.
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>500</title></head>'
            . '<body>' . $body . '</body></html>';
    }
}
