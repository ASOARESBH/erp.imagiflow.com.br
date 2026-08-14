<?php

declare(strict_types=1);

namespace App\Core;

use DateTimeImmutable;
use DateTimeInterface;
use Throwable;

/**
 * Motor leve de internacionalização do ERP IMAGINIFLOW.
 *
 * Os catálogos são arrays PHP separados por módulo em app/Lang/{locale}/.
 * Fallback obrigatório: locale ativo -> pt_BR -> chave solicitada.
 */
final class Lang
{
    private const FALLBACK_LOCALE = 'pt_BR';

    /** @var array<string, string> */
    private const SUPPORTED_LOCALES = [
        'pt_BR' => 'Português',
        'en' => 'English',
        'es' => 'Español',
    ];

    private static ?self $instance = null;

    private string $locale = self::FALLBACK_LOCALE;

    /** @var array<string, array<string, array<string, mixed>>> */
    private array $catalogues = [];

    private function __construct()
    {
    }

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function resolveCurrent(): string
    {
        $candidate = $_SESSION['locale'] ?? $_COOKIE['app_locale'] ?? self::FALLBACK_LOCALE;
        $this->locale = $this->normalize((string) $candidate) ?? self::FALLBACK_LOCALE;

        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['locale'] = $this->locale;
        }

        return $this->locale;
    }

    public function current(): string
    {
        return $this->locale;
    }

    public function htmlLocale(): string
    {
        return $this->locale === 'pt_BR' ? 'pt-BR' : $this->locale;
    }

    /** @return array<string, string> */
    public function supported(): array
    {
        return self::SUPPORTED_LOCALES;
    }

    public function isSupported(string $locale): bool
    {
        return $this->normalize($locale) !== null;
    }

    public function setLocale(string $locale, bool $persistCookie = true): bool
    {
        $normalized = $this->normalize($locale);
        if ($normalized === null) {
            return false;
        }

        $this->locale = $normalized;

        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['locale'] = $normalized;
        }

        if ($persistCookie && !headers_sent()) {
            $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || ((int) ($_SERVER['SERVER_PORT'] ?? 80) === 443);

            setcookie('app_locale', $normalized, [
                'expires' => time() + 31536000,
                'path' => '/',
                'secure' => $isSecure,
                'httponly' => false,
                'samesite' => 'Lax',
            ]);
        }

        return true;
    }

    /**
     * @param array<string, scalar|null> $replacements
     */
    public function get(string $key, array $replacements = []): string
    {
        [$module, $segments] = $this->splitKey($key);
        $value = $this->find($this->locale, $module, $segments)
            ?? $this->find(self::FALLBACK_LOCALE, $module, $segments)
            ?? $key;

        if (!is_string($value)) {
            return $key;
        }

        foreach ($replacements as $name => $replacement) {
            $replacement = (string) ($replacement ?? '');
            $value = str_replace(['{' . $name . '}', ':' . $name], $replacement, $value);
        }

        return $value;
    }

    public function formatCurrency(float|int|string $value): string
    {
        $amount = (float) $value;
        $formatted = $this->locale === 'en'
            ? number_format($amount, 2, '.', ',')
            : number_format($amount, 2, ',', '.');

        return 'R$ ' . $formatted;
    }

    public function formatNumber(float|int|string $value, int $decimals = 2): string
    {
        $number = (float) $value;

        return $this->locale === 'en'
            ? number_format($number, $decimals, '.', ',')
            : number_format($number, $decimals, ',', '.');
    }

    public function formatDate(DateTimeInterface|string|null $date, bool $withTime = false): string
    {
        if ($date === null || $date === '') {
            return '';
        }

        try {
            $dateTime = $date instanceof DateTimeInterface
                ? $date
                : new DateTimeImmutable((string) $date);
        } catch (Throwable) {
            return (string) $date;
        }

        $format = $this->locale === 'en' ? 'm/d/Y' : 'd/m/Y';
        if ($withTime) {
            $format .= ' H:i';
        }

        return $dateTime->format($format);
    }

    private function normalize(string $locale): ?string
    {
        $locale = str_replace('-', '_', trim($locale));
        $locale = strtolower($locale);

        return match ($locale) {
            'pt', 'pt_br' => 'pt_BR',
            'en', 'en_us', 'en_gb' => 'en',
            'es', 'es_es', 'es_419' => 'es',
            default => null,
        };
    }

    /** @return array{0: string, 1: array<int, string>} */
    private function splitKey(string $key): array
    {
        $parts = array_values(array_filter(explode('.', trim($key)), static fn (string $part): bool => $part !== ''));
        if ($parts === []) {
            return ['common', []];
        }

        $knownModules = ['common', 'auth', 'portal', 'clientes', 'financeiro', 'faturamento', 'crm', 'estoque', 'manutencao', 'rdv', 'hub_ia', 'configuracoes'];
        $module = in_array($parts[0], $knownModules, true) ? array_shift($parts) : 'common';

        return [$module, $parts];
    }

    /** @param array<int, string> $segments */
    private function find(string $locale, string $module, array $segments): mixed
    {
        $catalogue = $this->load($locale, $module);
        $value = $catalogue;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return null;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    /** @return array<string, mixed> */
    private function load(string $locale, string $module): array
    {
        if (isset($this->catalogues[$locale][$module])) {
            return $this->catalogues[$locale][$module];
        }

        $file = dirname(__DIR__) . '/Lang/' . $locale . '/' . $module . '.php';
        $catalogue = is_file($file) ? require $file : [];

        if (!is_array($catalogue)) {
            $catalogue = [];
        }

        $this->catalogues[$locale][$module] = $catalogue;

        return $catalogue;
    }
}
