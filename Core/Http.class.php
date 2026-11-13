<?php
/** Accès à la requête courante, sans dépendre d'un framework. */
class Http
{
    public static function chemin(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $chemin = parse_url($uri, PHP_URL_PATH) ?: '/';
        $chemin = rawurldecode($chemin);
        $racine = self::racine();
        if ($racine !== '' && str_starts_with($chemin, $racine)) {
            $chemin = substr($chemin, strlen($racine));
        }
        $chemin = '/' . trim($chemin, '/');
        return $chemin === '/' ? '/' : rtrim($chemin, '/');
    }

    /** Sous-dossier d'installation éventuel (en vhost racine : ''). */
    public static function racine(): string
    {
        $base = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        return $base === '/' || $base === '\\' ? '' : rtrim(str_replace('\\', '/', $base), '/');
    }

    public static function methode(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public static function estPost(): bool
    {
        return self::methode() === 'POST';
    }

    public static function post(string $cle, mixed $defaut = null): mixed
    {
        $valeur = $_POST[$cle] ?? $defaut;
        return is_string($valeur) ? trim($valeur) : $valeur;
    }

    public static function get(string $cle, mixed $defaut = null): mixed
    {
        $valeur = $_GET[$cle] ?? $defaut;
        return is_string($valeur) ? trim($valeur) : $valeur;
    }

    public static function chaine(string $cle, mixed $defaut = ''): string
    {
        $valeur = $_POST[$cle] ?? $_GET[$cle] ?? $defaut;
        return is_scalar($valeur) ? trim((string) $valeur) : (string) $defaut;
    }

    public static function entier(string $cle, int $defaut = 0): int
    {
        $valeur = $_POST[$cle] ?? $_GET[$cle] ?? null;
        return $valeur === null || $valeur === '' ? $defaut : (int) $valeur;
    }

    public static function postTableau(string $cle): array
    {
        $valeur = $_POST[$cle] ?? [];
        return is_array($valeur) ? $valeur : [];
    }

    public static function enTete(string $cle, string $defaut = ''): string
    {
        $clef = 'HTTP_' . strtoupper(str_replace('-', '_', $cle));
        return (string) ($_SERVER[$clef] ?? $defaut);
    }

    public static function ip(): string
    {
        return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    public static function urlOrigine(): string
    {
        $https = self::enTete('X-Forwarded-Proto') === 'https'
            || (($_SERVER['HTTPS'] ?? '') !== '' && $_SERVER['HTTPS'] !== 'off');
        $hote = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return ($https ? 'https' : 'http') . '://' . $hote;
    }

    public static function redirection(string $url): never
    {
        header('Location: ' . $url, true, 302);
        exit;
    }

    public static function json(mixed $donnee, int $code = 200): never
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($donnee, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function code(int $code): void
    {
        http_response_code($code);
    }
}
