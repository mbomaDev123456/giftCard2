<?php
/** Démarrage de session isolé dans var/sessions. */
class Session
{
    public static function demarrer(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $repertoire = TZ_VAR . '/sessions';
        if (!is_dir($repertoire)) {
            @mkdir($repertoire, 0775, true);
        }
        session_name(TZ_SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_save_path($repertoire);
        session_start();
    }

    public static function lire(string $cle, mixed $defaut = null): mixed
    {
        return $_SESSION[$cle] ?? $defaut;
    }

    public static function ecrire(string $cle, mixed $valeur): void
    {
        $_SESSION[$cle] = $valeur;
    }

    public static function effacer(string $cle): void
    {
        unset($_SESSION[$cle]);
    }

    public static function renouveler(): void
    {
        session_regenerate_id(true);
    }

    /** Messages flash : empilés, consommés à l'affichage. */
    public static function flash(string $type, string $message): void
    {
        $_SESSION['tz_flash'][$type][] = $message;
    }

    public static function consommerFlash(): array
    {
        $flash = $_SESSION['tz_flash'] ?? [];
        unset($_SESSION['tz_flash']);
        return $flash;
    }
}
