<?php
/** Point d'entrée unique : tout passe par ici, à l'exception des fichiers de public/. */
declare(strict_types=1);

// Le serveur intégré PHP n'applique pas public/.htaccess : servir les fichiers réels de public/.
if (PHP_SAPI === 'cli-server') {
    $uri = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
    $cible = realpath(__DIR__ . $uri);
    $racine = realpath(__DIR__);
    if ($cible !== false && $racine !== false
        && str_starts_with($cible, $racine . DIRECTORY_SEPARATOR) && is_file($cible)) {
        return false;
    }
}

require __DIR__ . '/../DAL/Config.php';

set_error_handler(
    static function (int $niveau, string $message, string $fichier, int $ligne): bool
    {
        if ((error_reporting() & $niveau) === 0) {
            return false;
        }
        throw new ErrorException($message, 0, $niveau, $fichier, $ligne);
    }
);

Session::demarrer();

$contexte = [];

// Langue : choix explicite (?langue=ar), puis session, puis français.
$choixLangue = Http::get('langue');
if (in_array($choixLangue, ['fr', 'ar'], true)) {
    Session::ecrire('tz_langue', $choixLangue);
}
$langue = (string) Session::lire('tz_langue', 'fr');
I18n::definir($langue);
$contexte['langue'] = $langue;

// Enseigne de la vitrine visitée (préfixe /boutique/{slug} ou ?enseigne=).
$chemin = Http::chemin();
if (preg_match('#^/boutique/([a-z0-9\-]+)$#i', $chemin, $m)) {
    $enseigne = Db::un("SELECT id, slug FROM tz_enseigne WHERE slug = ? AND etat = 'active'", [$m[1]]);
    if ($enseigne !== null) {
        $contexte['enseigne_id'] = (int) $enseigne['id'];
        $contexte['enseigne_slug'] = (string) $enseigne['slug'];
    }
}

ParametresApp::demarrer($contexte);

$routeur = require TZ_ROOT . '/routes.php';

try {
    $routeur->dispatcher($chemin, Http::methode());
} catch (Throwable $e) {
    tz_log('Erreur', get_class($e) . ': ' . $e->getMessage(), [
        'chemin'  => $chemin,
        'fichier' => $e->getFile(),
        'ligne'   => $e->getLine(),
    ]);
    if (str_contains(Http::enTete('Accept'), 'application/json')) {
        Http::json(['ok' => false, 'erreur' => 'incident interne'], 500);
    }
    $detail = (bool) ParametresApp::reglure('afficher_detail_erreur', true);
    Erreur::page(500, $detail
        ? $e->getMessage() . ' — ' . basename($e->getFile()) . ':' . $e->getLine()
        : 'Le service est momentanément indisponible.');
}
