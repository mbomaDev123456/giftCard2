<?php
/**
 * Contexte de la requête (enseigne visitée, langue) + réglages stockés en base.
 * Les réglages sont lus une fois puis relus si var/cache/parametres.php change.
 */
class ParametresApp
{
    private static array $contexte = [];
    private static ?array $reglures = null;

    public static function demarrer(array $contexte = []): void
    {
        self::$contexte = $contexte;
    }

    public static function voyageur(string $cle, mixed $defaut = null): mixed
    {
        return self::$contexte[$cle] ?? $defaut;
    }

    public static function definir(string $cle, mixed $valeur): void
    {
        self::$contexte[$cle] = $valeur;
    }

    /** Enseigne courante (id) si on est sous une vitrine de marque. */
    public static function enseigneCourante(): ?int
    {
        $id = self::voyageur('enseigne_id');
        return $id === null ? null : (int) $id;
    }

    /** Enseignes réellement visibles, dans l'ordre de la maison. */
    public static function enseignesDisponibles(): array
    {
        return Db::tous(
            'SELECT id, nom, slug, slogan, logo, couleur, etat
               FROM tz_enseigne
              WHERE etat = "active"
              ORDER BY position, nom'
        );
    }

    public static function reglure(string $cle, mixed $defaut = null): mixed
    {
        $toutes = self::toutesReglures();
        return $toutes[$cle] ?? $defaut;
    }

    public static function toutesReglures(): array
    {
        if (self::$reglures !== null) {
            return self::$reglures;
        }
        $cache = TZ_VAR . '/cache/parametres.php';
        if (is_file($cache)) {
            $charge = @include $cache;
            if (is_array($charge)) {
                return self::$reglures = $charge;
            }
        }
        $reglures = [];
        try {
            foreach (Db::tous('SELECT cle, valeur, type FROM tz_parametre') as $ligne) {
                $reglures[$ligne['cle']] = self::couler($ligne['valeur'], $ligne['type']);
            }
        } catch (Throwable $e) {
            // Base indisponible au premier lancement : on travaille avec les constantes.
            tz_log('Parametres', 'base indisponible: ' . $e->getMessage());
        }
        return self::$reglures = $reglures + [
            'livraison_frais'        => TZ_LOGISTICS_FRAIS_BASE,
            'livraison_gratuite_a'   => TZ_LOGISTICS_GRATUITE_A_PART,
            'payment_simule'         => TZ_GPG_SIMULE,
            'tva_standard'           => TZ_TVA_STANDARD,
            'tva_alimentaire'        => TZ_TVA_ALIMENTAIRE,
        ];
    }

    public static function ecrireReglure(string $cle, mixed $valeur, string $type = 'texte'): void
    {
        $existant = Db::un('SELECT cle FROM tz_parametre WHERE cle = ?', [$cle]);
        if ($existant === null) {
            Db::inserer('tz_parametre', [
                'cle'    => $cle,
                'valeur' => (string) $valeur,
                'type'   => $type,
            ]);
        } else {
            Db::modifier('tz_parametre', ['valeur' => (string) $valeur], 'cle = ?', [$cle]);
        }
        self::$reglures = null;
        self::viderCache();
    }

    public static function rafraichirCache(): void
    {
        $reglures = [];
        foreach (Db::tous('SELECT cle, valeur, type FROM tz_parametre') as $ligne) {
            $reglures[$ligne['cle']] = self::couler($ligne['valeur'], $ligne['type']);
        }
        $fichier = TZ_VAR . '/cache/parametres.php';
        if (!is_dir(dirname($fichier))) {
            @mkdir(dirname($fichier), 0775, true);
        }
        file_put_contents(
            $fichier,
            "<?php return " . var_export($reglures, true) . ";"
        );
        self::$reglures = null;
    }

    private static function viderCache(): void
    {
        @unlink(TZ_VAR . '/cache/parametres.php');
    }

    private static function couler(?string $valeur, string $type): mixed
    {
        return match ($type) {
            'entier' => (int) $valeur,
            'reel'   => (float) $valeur,
            'booleen' => in_array($valeur, ['1', 'true', 'oui', 'on'], true),
            'json'   => json_decode((string) $valeur, true) ?? [],
            default  => (string) ($valeur ?? ''),
        };
    }
}
