<?php
/** Promotions : codes, remises automatiques, paliers, quotas et fenêtres. */
class Promo
{
    public static function parId(int $id): ?array
    {
        return Db::un('SELECT * FROM tz_promo WHERE id = ?', [$id]);
    }

    public static function parCode(string $code): ?array
    {
        return Db::un(
            "SELECT * FROM tz_promo WHERE code = ? AND etat = 'active'",
            [mb_strtoupper(trim($code))]
        );
    }

    /** Fenêtre active : la promo est jouable maintenant, quota non épuisé. */
    public static function estFenetre(array $promo, ?string $quand = null): bool
    {
        if (($promo['etat'] ?? '') !== 'active') {
            return false;
        }
        $quand ??= date('Y-m-d H:i:s');
        if (strtotime((string) $promo['debut_at']) > strtotime($quand)) {
            return false;
        }
        if ($promo['fin_at'] !== null && strtotime((string) $promo['fin_at']) < strtotime($quand)) {
            return false;
        }
        if ((int) $promo['quota_total'] > 0 && (int) $promo['quota_utilise'] >= (int) $promo['quota_total']) {
            return false;
        }
        return true;
    }

    /** Remises automatiques (sans code) applicables maintenant. */
    public static function automatiques(): array
    {
        $maintenant = date('Y-m-d H:i:s');
        $lignes = Db::tous(
            "SELECT * FROM tz_promo
              WHERE code IS NULL AND etat = 'active'
                AND debut_at <= ? AND (fin_at IS NULL OR fin_at >= ?)
           ORDER BY type = 'port_gratuit', seuil_achat DESC, valeur DESC",
            [$maintenant, $maintenant]
        );
        return array_values(array_filter($lignes, static fn(array $p): bool => self::estFenetre($p, $maintenant)));
    }

    /** Codes affichés en vitrine, pour le bandeau « promotions ». */
    public static function vitrine(): array
    {
        return Db::tous(
            "SELECT p.*, e.nom enseigne_nom, e.slug enseigne_slug, e.couleur enseigne_couleur
               FROM tz_promo p LEFT JOIN tz_enseigne e ON e.id = p.enseigne_id
              WHERE p.etat = 'active' AND p.afficher_vitrine = 1
                AND p.debut_at <= NOW() AND (p.fin_at IS NULL OR p.fin_at >= NOW())
           ORDER BY p.fin_at IS NULL, p.fin_at, p.valeur DESC"
        );
    }

    public static function administrer(): array
    {
        return Db::tous(
            'SELECT p.*, e.nom enseigne_nom,
                    (SELECT COUNT(*) FROM tz_promo_utilisation u WHERE u.promo_id = p.id) utilisations,
                    (SELECT COALESCE(SUM(u.montant_applique),0) FROM tz_promo_utilisation u WHERE u.promo_id = p.id) remise_accordee
               FROM tz_promo p LEFT JOIN tz_enseigne e ON e.id = p.enseigne_id
              ORDER BY p.etat = "active" DESC, p.debut_at DESC, p.id DESC'
        );
    }

    /** Une promo déjà utilisée par ce client ? (règle « une fois par client ») */
    public static function dejaUtiliseePar(int $promoId, ?int $clientId): bool
    {
        if ($clientId === null) {
            return false;
        }
        return (int) Db::valeur(
            'SELECT COUNT(*) FROM tz_promo_utilisation WHERE promo_id = ? AND client_id = ?',
            [$promoId, $clientId]
        ) > 0;
    }

    /** Le client a-t-il déjà payé une commande ? (règle « première commande seule ») */
    public static function clientAPayee(?int $clientId): bool
    {
        if ($clientId === null) {
            return false;
        }
        return (int) Db::valeur(
            "SELECT COUNT(*) FROM tz_commande WHERE client_id = ? AND statut_paiement = 'accorde'",
            [$clientId]
        ) > 0;
    }

    /** Codes d'affichage des types, pour l'admin et les étiquettes. */
    public static function libelleType(string $type): string
    {
        return [
            'pourcentage'        => 'Pourcentage sur la cible',
            'montant'            => 'Montant fixe',
            'port_gratuit'       => 'Port offert',
            'palier_pourcentage' => 'Palier : pourcentage dès un seuil',
            'palier_montant'     => 'Palier : montant dès un seuil',
            'n_plus_gratuit'     => 'X achetés, Y offerts',
            'prix_impose'        => 'Prix imposé',
            'cadeau'             => 'Cadeau offert',
        ][$type] ?? $type;
    }

    public static function enregistrer(array $d, ?int $id = null): int
    {
        $champs = [];
        foreach (['libelle', 'libelle_ar', 'description', 'type', 'cible_type', 'cible_ids', 'exclusion_ids',
                  'visuel', 'etat'] as $cle) {
            if (array_key_exists($cle, $d)) {
                $valeur = is_string($d[$cle]) ? trim($d[$cle]) : $d[$cle];
                $champs[$cle] = ($valeur === '' ? null : $valeur);
            }
        }
        if (array_key_exists('code', $d)) {
            $code = mb_strtoupper(trim((string) $d['code']));
            $champs['code'] = $code === '' ? null : $code;
        }
        if (array_key_exists('enseigne_id', $d)) {
            $champs['enseigne_id'] = $d['enseigne_id'] === '' ? null : (int) $d['enseigne_id'];
        }
        $typeCourant = (string) ($champs['type'] ?? $d['type'] ?? '');
        foreach (['valeur', 'seuil_achat', 'plafond_remise'] as $cle) {
            if (!array_key_exists($cle, $d)) {
                continue;
            }
            $brut = (string) $d[$cle];
            if ($cle === 'valeur' && str_contains($typeCourant, 'pourcentage')) {
                // Un pourcentage se stocke en millièmes : 10 % -> 10000.
                $champs[$cle] = (int) round((float) str_replace(',', '.', $brut) * 1000);
            } else {
                $champs[$cle] = Money::analyser($brut);
            }
        }
        foreach (['quantite_declencheur', 'nombre_offert', 'quota_total'] as $cle) {
            if (array_key_exists($cle, $d)) {
                $champs[$cle] = (int) $d[$cle];
            }
        }
        foreach (['cumulable', 'une_fois_par_client', 'premiere_commande_seule', 'afficher_vitrine'] as $cle) {
            if (array_key_exists($cle, $d)) {
                $champs[$cle] = $d[$cle] ? 1 : 0;
            }
        }
        foreach (['debut_at', 'fin_at'] as $cle) {
            if (array_key_exists($cle, $d)) {
                $champs[$cle] = self::normaliserDate((string) $d[$cle], $cle === 'fin_at');
            }
        }
        if ($id === null) {
            $champs['created_at'] = date('Y-m-d H:i:s');
            return Db::inserer('tz_promo', $champs);
        }
        Db::modifier('tz_promo', $champs, 'id = ?', [$id]);
        return $id;
    }

    private static function normaliserDate(string $valeur, bool $estFin): ?string
    {
        $valeur = trim($valeur);
        if ($valeur === '') {
            return null;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $valeur)) {
            return $valeur . ($estFin ? ' 23:59:59' : ' 00:00:00');
        }
        $timestamp = strtotime($valeur);
        return $timestamp === false ? null : date('Y-m-d H:i:s', $timestamp);
    }

    /** Comptabilise la consommation d'une promo sur une commande. */
    public static function consommer(int $promoId, int $commandeId, ?int $clientId, int $montant): void
    {
        Db::inserer('tz_promo_utilisation', [
            'promo_id'         => $promoId,
            'commande_id'      => $commandeId,
            'client_id'        => $clientId,
            'montant_applique' => $montant,
        ]);
        Db::modifier('tz_promo', ['quota_utilise' => 'quota_utilise + 1'], 'id = ?', [$promoId], brutes: ['quota_utilise']);
    }

    public static function rendre(int $promoId, int $commandeId): void
    {
        Db::supprimer('tz_promo_utilisation', 'promo_id = ? AND commande_id = ?', [$promoId, $commandeId]);
        Db::modifier('tz_promo', ['quota_utilise' => 'GREATEST(quota_utilise - 1, 0)'], 'id = ?', [$promoId],
            brutes: ['quota_utilise']);
    }
}
