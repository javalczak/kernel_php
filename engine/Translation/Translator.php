<?php
declare(strict_types=1);

namespace Engine\Translation;

class Translator
{
    // The locale comes from a cookie and ends up in require below. Without this
    // list, locale=../../config/database would execute someone else's file.
    private const AVAILABLE = ['pl', 'en'];

    private const FALLBACK = 'en';

    private array $translations = [];
    private string $locale;

    public function __construct(string $locale = self::FALLBACK)
    {
        $this->locale = self::clean($locale);
    }

    /** Loads translations/{locale}.php. Can be called more than once, later keys win. */
    public function load(string $path): void
    {
        $file = $path . '/translations/' . $this->locale . '.php';

        if (!is_file($file)) {
            return;
        }

        $translations = require $file;

        if (is_array($translations)) {
            $this->translations = array_merge($this->translations, $translations);
        }
    }

    public function trans(string $key, array $params = []): string
    {
        // Missing key returns the key itself, so the gap is visible on the page.
        $message = $this->translations[$key] ?? $key;

        foreach ($params as $placeholder => $value) {
            $message = str_replace('%' . $placeholder . '%', (string) $value, $message);
        }

        return $message;
    }

    public function setLocale(string $locale): void
    {
        $this->locale = self::clean($locale);
        $this->translations = []; // reset, load again
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    /** An unknown locale falls back instead of blowing up. */
    private static function clean(string $locale): string
    {
        return in_array($locale, self::AVAILABLE, true) ? $locale : self::FALLBACK;
    }
}
