<?php
declare(strict_types=1);

namespace Engine;

/**
 * Tells the rest of the kernel where it is running: development or production,
 * plain HTTP or HTTPS, and which directory may hold credentials and logs.
 *
 * Settings come from config/app.php. Everything there is optional - with an
 * empty config the kernel still picks sane values on its own.
 */
class Environment
{
    private const DEVELOPMENT = 'dev';
    private const PRODUCTION  = 'prod';

    /** Hostnames that mean "a developer machine", never a live site. */
    private const LOCAL_HOSTS = ['localhost', '127.0.0.1', '::1', '[::1]'];

    private string $name;
    private bool $secure;
    private bool $hsts;
    private string $privateDir;

    public function __construct(array $server, array $config = [])
    {
        $this->name       = $this->resolveName($server, $config['env'] ?? null);
        $this->secure     = $this->resolveSecure($server, (bool) ($config['trusted_proxy'] ?? true));
        $this->hsts       = (bool) ($config['hsts'] ?? false);
        $this->privateDir = $this->resolvePrivateDir($config['private_dir'] ?? null);
    }

    /** Reads config/app.php when it is there, so a missing file is not an error. */
    public static function create(array $server): static
    {
        $file = BASE_PATH . '/config/app.php';

        return new static($server, is_file($file) ? (array) require $file : []);
    }

    public function isDevelopment(): bool
    {
        return $this->name === self::DEVELOPMENT;
    }

    public function isSecure(): bool
    {
        return $this->secure;
    }

    /** HSTS only makes sense on a site that is already served over HTTPS. */
    public function sendsHsts(): bool
    {
        return $this->hsts && $this->secure;
    }

    /**
     * Config file to load. A copy in the private directory wins over the one
     * in config/, which lets a deployment move credentials out of the web root
     * without touching any code.
     */
    public function configFile(string $name): string
    {
        $private = $this->privateDir . '/' . $name;

        return is_file($private) ? $private : BASE_PATH . '/config/' . $name;
    }

    /**
     * Error log. The .php extension is deliberate: if someone requests the file
     * over HTTP, PHP runs its first line and exits instead of printing the log
     * (see ErrorHandler). That holds even when .htaccess is ignored.
     */
    public function logFile(): string
    {
        return $this->privateDir . '/log/error-log.php';
    }

    public function privateDir(): string
    {
        return $this->privateDir;
    }

    // -------------------------------------------------------------------------
    // Private
    // -------------------------------------------------------------------------

    private function resolveName(array $server, ?string $configured): string
    {
        if ($configured !== null) {
            return $configured === self::DEVELOPMENT ? self::DEVELOPMENT : self::PRODUCTION;
        }

        // The built-in server is only ever used by a developer.
        if (PHP_SAPI === 'cli-server' || PHP_SAPI === 'cli') {
            return self::DEVELOPMENT;
        }

        $host = $this->host($server);

        if (in_array($host, self::LOCAL_HOSTS, true)) {
            return self::DEVELOPMENT;
        }

        // Reserved for local development, so they can never be a live site.
        return str_ends_with($host, '.localhost') || str_ends_with($host, '.test')
            ? self::DEVELOPMENT
            : self::PRODUCTION;
    }

    /**
     * On shared hosting HTTPS is often terminated by a proxy or CDN, so
     * $_SERVER['HTTPS'] stays empty while the visitor is on HTTPS. The forwarded
     * headers answer that, but a client can send them too - hence the switch.
     */
    private function resolveSecure(array $server, bool $trustedProxy): bool
    {
        if (!empty($server['HTTPS']) && strtolower((string) $server['HTTPS']) !== 'off') {
            return true;
        }

        if ((int) ($server['SERVER_PORT'] ?? 0) === 443) {
            return true;
        }

        if (!$trustedProxy) {
            return false;
        }

        // The first value is the one the visitor's browser spoke to.
        $proto = strtolower(trim(explode(',', (string) ($server['HTTP_X_FORWARDED_PROTO'] ?? ''))[0]));

        if ($proto === 'https') {
            return true;
        }

        return strtolower((string) ($server['HTTP_X_FORWARDED_SSL'] ?? '')) === 'on'
            || strtolower((string) ($server['HTTP_FRONT_END_HTTPS'] ?? '')) === 'on';
    }

    /**
     * Directory for things no visitor may read. A directory above the web root
     * is the safest spot, but not every hosting account has one, so the
     * fallback is var/ inside the project.
     */
    private function resolvePrivateDir(?string $configured): string
    {
        $candidates = [
            $configured,
            getenv('KERNEL_PRIVATE_DIR') ?: null,
            dirname(BASE_PATH) . '/kernel-private',
        ];

        foreach ($candidates as $candidate) {
            if ($candidate !== null && is_dir($candidate)) {
                return rtrim($candidate, '/\\');
            }
        }

        return BASE_PATH . '/var';
    }

    private function host(array $server): string
    {
        $host = (string) ($server['HTTP_HOST'] ?? $server['SERVER_NAME'] ?? '');

        // Strip the port: "localhost:8000" is still localhost.
        return strtolower(preg_replace('/:\d+$/', '', $host) ?? $host);
    }
}
