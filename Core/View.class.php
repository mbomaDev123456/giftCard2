<?php
/** Rendu de vues : partiel + layout, échappement automatique par e(). */
class View
{
    public static function page(string $vue, array $donnees = [], ?string $miseEnPage = 'store'): void
    {
        $corps = self::corps($vue, $donnees);
        if ($miseEnPage === null) {
            echo $corps;
            return;
        }
        $GLOBALS['tz_contenu'] = $corps;
        echo self::corps('layouts/' . $miseEnPage, $donnees + ['contenu' => $corps]);
    }

    public static function corps(string $vue, array $donnees = []): string
    {
        $fichier = TZ_VIEWS . '/' . $vue . '.php';
        if (!is_file($fichier)) {
            throw new RuntimeException('Vue absente : ' . $vue);
        }
        $donnees['langue'] ??= I18n::langue();
        $donnees['sens'] ??= I18n::dir();
        extract($donnees, EXTR_SKIP);
        ob_start();
        include $fichier;
        return (string) ob_get_clean();
    }

    /** Échappement pour attribut HTML et texte : usage unique dans les vues. */
    public static function e(mixed $valeur): string
    {
        return htmlspecialchars((string) ($valeur ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('e')) {
    function e(mixed $valeur): string
    {
        return View::e($valeur);
    }
}

if (!function_exists('t')) {
    function t(string $clef, array $remplacements = []): string
    {
        return I18n::t($clef, $remplacements);
    }
}

if (!function_exists('dt')) {
    /** Raccourci d'affichage monétaire dans les vues. */
    function dt(?int $millimes, bool $suffixe = true): string
    {
        return Money::formater((int) $millimes, $suffixe);
    }
}

if (!function_exists('filtre_url')) {
    /**
     * Lien de facette : on repart des filtres courants, on applique la
     * modification, et une valeur vide retire la clef. La page saute à 1.
     */
    function filtre_url(array $courants, array $modification, string $chemin = '/catalogue'): string
    {
        $params = $courants;
        unset($params['page']);
        foreach ($modification as $clef => $valeur) {
            if ($valeur === '' || $valeur === null || $valeur === false) {
                unset($params[$clef]);
                continue;
            }
            $params[$clef] = $valeur;
        }
        return Url::to($chemin, $params);
    }
}

if (!function_exists('couleur_sure')) {
    /** Une couleur saisie en régie ne doit jamais déborder dans la feuille de style. */
    function couleur_sure(mixed $valeur, string $repli): string
    {
        return preg_match('/^#[0-9a-fA-F]{6}$/', (string) $valeur) === 1 ? (string) $valeur : $repli;
    }
}

if (!function_exists('imageexiste')) {
    /** Vrai si le fichier est vraiment posé dans les uploads (ou si l'URL est externe). */
    function imageexiste(?string $chemin): bool
    {
        $chemin = trim((string) $chemin);
        if ($chemin === '') {
            return false;
        }
        if (preg_match('#^https?://#', $chemin) === 1) {
            return true;
        }
        return is_file(TZ_UPLOAD_DIR . '/' . ltrim(str_replace('\\', '/', $chemin), '/'));
    }
}

if (!function_exists('nom_produit')) {
    /** Nom affiché, la langue arabe prenant le relais quand elle est remplie. */
    function nom_produit(array $ligne, string $clef = 'nom'): string
    {
        if (I18n::estArabe() && !empty($ligne[$clef . '_ar'])) {
            return (string) $ligne[$clef . '_ar'];
        }
        return (string) ($ligne[$clef] ?? '');
    }
}
