<?php
/**
 * Socle applicatif — configuration et chargement des classes.
 * Hors docroot : seul public/ est servi par Apache.
 */
declare(strict_types=1);

define('TZ_ROOT',        dirname(__DIR__));
define('TZ_APP',         TZ_ROOT);
define('TZ_PUBLIC',      TZ_ROOT . '/public');
define('TZ_VIEWS',       TZ_ROOT . '/views');
define('TZ_VAR',         TZ_ROOT . '/var');
define('TZ_DATA',        TZ_ROOT . '/data');
define('TZ_UPLOAD_DIR',  TZ_PUBLIC . '/uploads');
define('TZ_UPLOAD_URL',  '/uploads');

/* ---------- Base de données ---------- */
define('TZ_DB_HOST', '127.0.0.1');
define('TZ_DB_PORT', 3306);
define('TZ_DB_USER', 'root');
define('TZ_DB_PASS', 'secure');
define('TZ_DB_NAME', 'tozeur_shop');

/* ---------- Identité du site ---------- */
define('TZ_SITE_NAME', 'Tozeur Souk');
define('TZ_SITE_CITY', 'Tozeur, Tunisie');
define('TZ_CURRENCY', 'TND');
define('TZ_CURRENCY_ISO', '788');
define('TZ_DECIMALS', 3);              // le dinar se compte en millimes
define('TZ_TVA_STANDARD', 19000);      // 19 %  -> 19 000 millimes pour 100 000
define('TZ_TVA_ALIMENTAIRE', 7000);    // 7 %

define('TZ_LOGISTICS_FRAIS_BASE', 5000);   // 5,000 DT livraison Tozeur
define('TZ_LOGISTICS_GRATUITE_A_PART', 150000); // franchise à 150,000 DT

/* ---------- Paiement : plateforme GPG Checkout ---------- */
define('TZ_GPG_ENABLED', true);
define('TZ_GPG_URL', 'https://www.gpgcheckoutlocal.com/Paiement/Validation_paiement.php');
define('TZ_GPG_NUMSITE', 'TZ0001');                    // gpg_site.num_client
define('TZ_GPG_PASSWORD', 'changez-moi');              // envoyé haché en md5
define('TZ_GPG_LANGAGE', 'FR');
define('TZ_GPG_SIMULE', true);      // true = on ne part pas vers la vraie page de paiement
define('TZ_GPG_URL_PROD', 'https://www.gpgcheckout.com/Paiement/Validation_paiement.php');

/* ---------- Session / runtime ---------- */
define('TZ_SESSION_NAME', 'tzshop');
define('TZ_ADMIN_SESSION_KEY', 'tz_admin');
define('TZ_CLIENT_SESSION_KEY', 'tz_client');
define('TZ_CART_SESSION_KEY', 'tz_panier');
define('TZ_BCRYPT_COST', 11);
define('TZ_TIMEZONE', 'Africa/Tunis');

date_default_timezone_set(TZ_TIMEZONE);
mb_internal_encoding('UTF-8');

/**
 * Le chargeur de la maison : classe simple, sans namespace, fichée dans
 * Core/, DAL/, BL/ ou WS/ sous le nom <Classe>.class.php.
 */
function chargerClasse(string $classe): void
{
    static $dossiers = ['Core/', 'Controller/', 'DAL/', 'BL/', 'WS/'];
    foreach ($dossiers as $dossier) {
        $fichier = TZ_ROOT . '/' . $dossier . $classe . '.class.php';
        if (is_file($fichier)) {
            require_once $fichier;
            return;
        }
    }
}

spl_autoload_register('chargerClasse');

/** Écrit dans data/log, en reprenant le double json + lisible de la maison. */
function tz_log(string $canal, string $message, array $contexte = []): void
{
    $repertoire = TZ_DATA . '/log';
    if (!is_dir($repertoire)) {
        @mkdir($repertoire, 0775, true);
    }
    $fichier = sprintf('%s/%s%s.txt', $repertoire, $canal, date('Ymd'));
    $ligne = sprintf(
        "[%s] %s\n%s\n",
        date('Y-m-d H:i:s'),
        $message,
        json_encode($contexte, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    );
    @file_put_contents($fichier, $ligne, FILE_APPEND | LOCK_EX);
}

/** Lecture d'un paramètre de contexte ( Enseigne courante, devise, langue ). */
function tz_param(string $cle, mixed $defaut = null): mixed
{
    return ParametresApp::voyageur($cle, $defaut);
}
