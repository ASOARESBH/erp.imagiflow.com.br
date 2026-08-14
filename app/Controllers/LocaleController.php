<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Lang;
use App\Models\PortalCliente;
use App\Models\User;

final class LocaleController
{
    /**
     * Atualiza o idioma da sessão, do cookie e da credencial autenticada quando houver.
     * Esta rota é pública porque precisa funcionar na tela de login, mas exige CSRF.
     */
    public function update(): void
    {
        $token = (string) ($_POST['csrf_token'] ?? '');
        $sessionToken = (string) ($_SESSION['csrf_token'] ?? '');
        if ($token === '' || $sessionToken === '' || !hash_equals($sessionToken, $token)) {
            http_response_code(403);
            echo t('common.invalid_request');
            return;
        }

        $locale = (string) ($_POST['locale'] ?? '');
        $lang = Lang::instance();
        if (!$lang->setLocale($locale)) {
            http_response_code(422);
            echo t('common.invalid_language');
            return;
        }

        if (Auth::check()) {
            (new User())->updateLocale((int) $_SESSION['user_id'], $lang->current());
        } elseif (!empty($_SESSION['portal_cliente_id'])) {
            (new PortalCliente())->updateLocale((int) $_SESSION['portal_cliente_id'], $lang->current());
        }

        header('Location: ' . $this->safeRedirect((string) ($_POST['redirect'] ?? '')));
        exit();
    }

    private function safeRedirect(string $redirect): string
    {
        $fallback = Auth::check()
            ? '/dashboard'
            : (!empty($_SESSION['portal_cliente_id']) ? '/portal/dashboard' : '/login');

        $redirect = trim($redirect);
        if ($redirect === '') {
            return $fallback;
        }

        $parts = parse_url($redirect);
        if ($parts === false || isset($parts['scheme']) || isset($parts['host'])) {
            return $fallback;
        }

        $path = (string) ($parts['path'] ?? '');
        if ($path === '' || !str_starts_with($path, '/') || str_starts_with($path, '//')) {
            return $fallback;
        }

        return $path . (isset($parts['query']) ? '?' . $parts['query'] : '');
    }
}
