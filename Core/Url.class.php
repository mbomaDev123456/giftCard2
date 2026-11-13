<?php
/** Génération d'URLs internes, y compris en installation sous-dossier. */
class Url
{
    public static function to(string $chemin, array $params = []): string
    {
        $base = Http::racine();
        $chemin = '/' . ltrim($chemin, '/');
        $url = $base . ($chemin === '/' ? '/' : rtrim($chemin, '/'));
        if ($url === '') {
            $url = '/';
        }
        if ($params !== []) {
            $url .= '?' . http_build_query($params, '', '&');
        }
        return $url === '' ? '/' : $url;
    }

    public static function asset(string $chemin): string
    {
        return Http::racine() . '/assets/' . ltrim($chemin, '/');
    }

    public static function upload(?string $chemin): string
    {
        if ($chemin === '' || $chemin === null) {
            return '';
        }
        if (preg_match('#^https?://#', $chemin)) {
            return $chemin;
        }
        return Http::racine() . TZ_UPLOAD_URL . '/' . ltrim($chemin, '/');
    }
}
