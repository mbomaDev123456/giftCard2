<?php
/**
 * Traçabilité des tentatives de paiement GPG Checkout.
 * La signature et le mapping TransStatus vivent dans GestionPaiement ; ici, la persistance seule.
 * Rien ici n'accepte de numéro de carte : seules carte_type et carte_fin sont stockées.
 */
class Paiement
{
    public const FOURNISSEUR = 'gpg_checkout';

    public static function parId(int $id): ?array
    {
        return Db::un('SELECT * FROM tz_paiement WHERE id = ?', [$id]);
    }

    /** Le order_id est unique par fournisseur : c'est la clé de retour de GPG. */
    public static function parOrderId(string $orderId, ?string $fournisseur = null): ?array
    {
        if ($fournisseur === null) {
            return Db::un('SELECT * FROM tz_paiement WHERE order_id = ? ORDER BY id DESC LIMIT 1', [$orderId]);
        }
        return Db::un('SELECT * FROM tz_paiement WHERE order_id = ? AND fournisseur = ?',
            [$orderId, $fournisseur]);
    }

    public static function parCommande(int $commandeId): array
    {
        return Db::tous('SELECT * FROM tz_paiement WHERE commande_id = ? ORDER BY id', [$commandeId]);
    }

    public static function dernierPourCommande(int $commandeId): ?array
    {
        return Db::un('SELECT * FROM tz_paiement WHERE commande_id = ? ORDER BY id DESC LIMIT 1',
            [$commandeId]);
    }

    /** Un paiement non soldé depuis trop longtemps est considéré comme abandonné. */
    public static function enSuspend(int $minutes = 45): array
    {
        $minutes = max(5, $minutes);
        return Db::tous(
            "SELECT p.*, c.reference, c.total_ttc
               FROM tz_paiement p JOIN tz_commande c ON c.id = p.commande_id
              WHERE p.statut IN ('initialise','en_attente_3ds')
                AND p.initie_le < DATE_SUB(NOW(), INTERVAL ? MINUTE)
           ORDER BY p.initie_le",
            [$minutes]
        );
    }

    public static function creer(array $donnees): int
    {
        return Db::inserer('tz_paiement', self::prepares($donnees));
    }

    /**
     * Mise à jour d'une tentative existante. `reponse_brute` est ajoutée au journal
     * déjà stocké plutôt que remplacée, pour garder la trace des allers-retours.
     */
    public static function maj(int $id, array $donnees): void
    {
        $champs = self::prepares($donnees);
        if (isset($donnees['reponse_brute'])) {
            $ancienne = (string) (Db::valeur('SELECT reponse_brute FROM tz_paiement WHERE id = ?', [$id]) ?? '');
            $champs['reponse_brute'] = $ancienne === ''
                ? $donnees['reponse_brute']
                : $ancienne . "\n----\n" . $donnees['reponse_brute'];
        }
        Db::modifier('tz_paiement', $champs, 'id = ?', [$id]);
    }

    public static function solde(int $id, string $statut): void
    {
        Db::modifier('tz_paiement',
            ['statut' => $statut, 'solde_le' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
    }

    /** @param array<string, mixed> $f */
    public static function administrer(array $f = [], int $page = 1, int $parPage = 30): array
    {
        $conditions = [];
        $params = [];
        if (($f['statut'] ?? '') !== '') {
            $conditions[] = 'p.statut = ?';
            $params[] = $f['statut'];
        }
        if (($f['q'] ?? '') !== '') {
            $conditions[] = '(p.order_id LIKE ? OR c.reference LIKE ? OR p.id_transaction_gpg LIKE ?)';
            $motif = '%' . $f['q'] . '%';
            array_push($params, $motif, $motif, $motif);
        }
        if (($f['du'] ?? '') !== '') {
            $conditions[] = 'p.initie_le >= ?';
            $params[] = $f['du'] . ' 00:00:00';
        }
        if (($f['au'] ?? '') !== '') {
            $conditions[] = 'p.initie_le <= ?';
            $params[] = $f['au'] . ' 23:59:59';
        }
        $where = $conditions === [] ? '1' : implode(' AND ', $conditions);
        $base = "FROM tz_paiement p
                 JOIN tz_commande c ON c.id = p.commande_id
                 LEFT JOIN tz_client cl ON cl.id = c.client_id
                 WHERE $where";

        $total = (int) Db::valeur("SELECT COUNT(*) $base", $params);
        $pages = max(1, (int) ceil($total / $parPage));
        $page = min(max(1, $page), $pages);
        $depart = ($page - 1) * $parPage;

        return [
            'lignes' => Db::tous(
                "SELECT p.*, c.reference, c.total_ttc, c.mode_paiement, c.statut commande_statut,
                        cl.nom client_nom, cl.prenom client_prenom
                   $base
               ORDER BY p.initie_le DESC, p.id DESC
               LIMIT $parPage OFFSET $depart",
                $params
            ),
            'total' => $total, 'pages' => $pages, 'page' => $page,
        ];
    }

    /** Revenus encaissés par jour, pour le suivi du paiement. */
    public static function serie(int $jours = 30): array
    {
        $jours = max(1, min(365, $jours));
        return Db::tous(
            "SELECT DATE(p.initie_le) jour,
                    COUNT(*) tentatives,
                    SUM(p.statut = 'accorde') accordes,
                    SUM(p.statut = 'refuse') refuses,
                    COALESCE(SUM(CASE WHEN p.statut = 'accorde' THEN p.montant ELSE 0 END), 0) encaisse
               FROM tz_paiement p
              WHERE p.fournisseur IN ('gpg_checkout','sandbox')
                AND p.initie_le >= DATE_SUB(CURDATE(), INTERVAL $jours DAY)
           GROUP BY DATE(p.initie_le) ORDER BY jour"
        );
    }

    public static function transactions(int $limite = 20): array
    {
        $limite = max(1, min(200, $limite));
        return Db::tous(
            "SELECT p.*, c.reference, c.total_ttc
               FROM tz_paiement p JOIN tz_commande c ON c.id = p.commande_id
           ORDER BY p.initie_le DESC LIMIT $limite"
        );
    }

    /** Champs acceptés, nullifies si vides. Toute autre clé est ignorée. */
    private static function prepares(array $d): array
    {
        $liste = ['commande_id', 'fournisseur', 'order_id', 'num_site', 'montant', 'devise', 'statut',
                  'code_autorisation', 'ref_checkout', 'id_transaction_gpg', 'dernier_trans_status',
                  'signature_recue', 'session_marchande', 'carte_type', 'carte_fin',
                  'erreur_message', 'ip_client'];
        $sorties = [];
        foreach ($liste as $cle) {
            if (array_key_exists($cle, $d)) {
                $valeur = $d[$cle];
                $sorties[$cle] = ($valeur === '' || $valeur === null) ? null : $valeur;
            }
        }
        foreach (['commande_id', 'montant'] as $entier) {
            if (array_key_exists($entier, $sorties)) {
                $sorties[$entier] = (int) $sorties[$entier];
            }
        }
        if (array_key_exists('solde_le', $d) && $d['solde_le'] !== '') {
            $sorties['solde_le'] = $d['solde_le'];
        }
        return $sorties;
    }
}
