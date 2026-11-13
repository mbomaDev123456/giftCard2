<?php
/** Jeton anti-CSRF de session, champ caché nommé csrf comme dans la maison. */
class Csrf
{
    private const CLE = 'tz_csrf';

    public static function jeton(): string
    {
        $jeton = Session::lire(self::CLE);
        if (!is_string($jeton) || $jeton === '') {
            $jeton = bin2hex(random_bytes(32));
            Session::ecrire(self::CLE, $jeton);
        }
        return $jeton;
    }

    public static function champ(): string
    {
        return '<input type="hidden" name="csrf" value="' . View::e(self::jeton()) . '">';
    }

    public static function valider(?string $saisie): bool
    {
        $attendu = self::jeton();
        return is_string($saisie) && $saisie !== '' && hash_equals($attendu, $saisie);
    }

    /** Rotation après une connexion réussie (le jeton change de valeur). */
    public static function renouveler(): string
    {
        $jeton = bin2hex(random_bytes(32));
        Session::ecrire(self::CLE, $jeton);
        return $jeton;
    }

    public static function exiger(): void
    {
        if (!self::valider(Http::post('csrf'))) {
            Session::flash('erreur', 'Session expirée, merci de recommencer.');
            Http::redirection(Url::to(Http::chemin()));
        }
    }
}
