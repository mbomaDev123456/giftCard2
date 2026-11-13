<?php
/** Pages d'erreur homogènes pour la vitrine et l'administration. */
class Erreur
{
    public static function page(int $code, string $message, ?string $miseEnPage = 'store'): void
    {
        Http::code($code);
        $titres = [
            403 => 'Accès refusé',
            404 => 'Page introuvable',
            405 => 'Méthode non permise',
            410 => 'Adresse retirée',
            500 => 'Incident interne',
            503 => 'Maintenance',
        ];
        View::page('store/erreur', [
            'code'    => $code,
            'titre'   => $titres[$code] ?? 'Erreur',
            'message' => $message,
        ], $miseEnPage);
    }

    public static function admin(int $code, string $message): void
    {
        self::page($code, $message, 'admin');
    }
}
