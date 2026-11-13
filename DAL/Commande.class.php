<?php
/**
 * Commandes : persistance, filtres du back-office, suivi public.
 * Les montants sont déjà figés par le moteur de prix ; ici on ne recalcule rien.
 */
class Commande
{
    /** Colonne de date remplie quand la commande atteint ce statut. */
    private const HORODATAGES = [
        'payee'        => 'validee_le',
        'preparation'  => 'preparee_le',
        'controle_qualite' => 'preparee_le',
        'expediee'     => 'expediee_le',
        'livree'       => 'livree_le',
        'annulee'      => 'annulee_le',
        'remboursee'   => 'annulee_le',
        'rembourse_partiel' => 'annulee_le',
    ];

    public static function parId(int $id): ?array
    {
        return Db::un('SELECT * FROM tz_commande WHERE id = ?', [$id]);
    }

    public static function parReference(string $reference): ?array
    {
        return Db::un('SELECT * FROM tz_commande WHERE reference = ?', [$reference]);
    }

    /** Nouvelle référence, format TZaammjj-NNNN, unique par tirages successifs. */
    public static function nouvelleReference(): string
    {
        for ($essai = 0; $essai < 25; $essai++) {
            $proposition = sprintf('TZ%s-%04d', date('ymd'), random_int(1, 9999));
            if (Db::compter('tz_commande', 'reference = ?', [$proposition]) === 0) {
                return $proposition;
            }
        }
        return sprintf('TZ%s-%04d%s', date('ymd'), random_int(1, 9999), chr(65 + random_int(0, 25)));
    }

    /**
     * Insert la commande puis ses lignes dans une seule transaction.
     *
     * @param array<string, mixed> $commande colonnes tz_commande déjà préparées
     * @param array<int, array<string, mixed>> $lignes sorties de GestionPanier::tarifer()['lignes']
     */
    public static function creer(array $commande, array $lignes): int
    {
        return Db::transaction(static function () use ($commande, $lignes): int {
            $commandeId = Db::inserer('tz_commande', $commande);
            foreach ($lignes as $ligne) {
                Db::inserer('tz_commande_ligne', $ligne + ['commande_id' => $commandeId]);
            }
            return $commandeId;
        });
    }

    /** @return array<int, array<string, mixed>> */
    public static function lignes(int $commandeId): array
    {
        return Db::tous(
            'SELECT l.*, e.nom enseigne_nom, e.slug enseigne_slug, e.couleur enseigne_couleur,
                    p.slug produit_slug, v.sku sku_vivant
               FROM tz_commande_ligne l
               JOIN tz_enseigne e ON e.id = l.enseigne_id
               LEFT JOIN tz_produit p ON p.id = l.produit_id
               LEFT JOIN tz_variante v ON v.id = l.variante_id
              WHERE l.commande_id = ?
           ORDER BY l.id',
            [$commandeId]
        );
    }

    /** Total par maison engagé sur la commande (le partage est figé dans les lignes). */
    public static function repartition(int $commandeId): array
    {
        return Db::tous(
            "SELECT l.enseigne_id, e.nom, e.slug, e.commission_bpo,
                    COUNT(*) articles,
                    SUM(l.total_ttc) net,
                    SUM(l.cout_achat) revient,
                    SUM(l.total_ht) ht
               FROM tz_commande_ligne l
               JOIN tz_enseigne e ON e.id = l.enseigne_id
              WHERE l.commande_id = ?
           GROUP BY l.enseigne_id, e.nom, e.slug, e.commission_bpo
           ORDER BY net DESC",
            [$commandeId]
        );
    }

    /** Historique public : la référence seule ne suffit pas, on exige l'e-mail du destinataire. */
    public static function suivre(string $reference, string $email): ?array
    {
        return Db::un('SELECT * FROM tz_commande WHERE reference = ? AND LOWER(email) = LOWER(?)',
            [$reference, $email]);
    }

    /**
     * Liste filtrée du back-office.
     *
     * @param array<string, mixed> $f
     * @return array{ lignes: array<int, array<string, mixed>>, total: int, pages: int, page: int }
     */
    public static function administrer(array $f = [], int $page = 1, int $parPage = 25): array
    {
        $conditions = [];
        $params = [];
        if (($f['statut'] ?? '') !== '') {
            $conditions[] = 'c.statut = ?';
            $params[] = $f['statut'];
        }
        if (($f['paiement'] ?? '') !== '') {
            $conditions[] = 'c.statut_paiement = ?';
            $params[] = $f['paiement'];
        }
        if (!empty($f['enseigne_id'])) {
            $conditions[] = 'FIND_IN_SET(?, c.enseigne_ids)';
            $params[] = (string) (int) $f['enseigne_id'];
        }
        if (!empty($f['mode_paiement'])) {
            $conditions[] = 'c.mode_paiement = ?';
            $params[] = $f['mode_paiement'];
        }
        if (($f['du'] ?? '') !== '') {
            $conditions[] = 'c.created_at >= ?';
            $params[] = $f['du'] . ' 00:00:00';
        }
        if (($f['au'] ?? '') !== '') {
            $conditions[] = 'c.created_at <= ?';
            $params[] = $f['au'] . ' 23:59:59';
        }
        if (($f['q'] ?? '') !== '') {
            $conditions[] = '(c.reference LIKE ? OR c.email LIKE ? OR cl.nom LIKE ? OR cl.prenom LIKE ?
                             OR EXISTS (SELECT 1 FROM tz_commande_ligne l2
                                         JOIN tz_produit p2 ON p2.id = l2.produit_id
                                        WHERE l2.commande_id = c.id AND p2.nom LIKE ?))';
            $motif = '%' . $f['q'] . '%';
            array_push($params, $motif, $motif, $motif, $motif, $motif);
        }
        if (($f['min'] ?? '') !== '') {
            $conditions[] = 'c.total_ttc >= ?';
            $params[] = (int) $f['min'];
        }
        $where = $conditions === [] ? '1' : implode(' AND ', $conditions);

        $base = "FROM tz_commande c
                 LEFT JOIN tz_client cl ON cl.id = c.client_id
                 LEFT JOIN tz_transporteur t ON t.id = c.transporteur_id
                 WHERE $where";

        $total = (int) Db::valeur("SELECT COUNT(*) $base", $params);
        $pages = max(1, (int) ceil($total / $parPage));
        $page = min(max(1, $page), $pages);
        $depart = ($page - 1) * $parPage;

        $lignes = Db::tous(
            "SELECT c.*, cl.nom client_nom, cl.prenom client_prenom, cl.email client_email, cl.telephone client_tel,
                    t.nom transporteur_nom,
                    (SELECT COUNT(*) FROM tz_commande_ligne l WHERE l.commande_id = c.id) nb_lignes,
                    (SELECT SUM(l.quantite) FROM tz_commande_ligne l WHERE l.commande_id = c.id) nb_articles,
                    (SELECT GROUP_CONCAT(DISTINCT e.nom SEPARATOR ', ')
                       FROM tz_commande_ligne l JOIN tz_enseigne e ON e.id = l.enseigne_id
                      WHERE l.commande_id = c.id) maisons
               $base
              ORDER BY c.created_at DESC, c.id DESC
              LIMIT $parPage OFFSET $depart",
            $params
        );

        return ['lignes' => $lignes, 'total' => $total, 'pages' => $pages, 'page' => $page];
    }

    /** Commandes récentes pour le tableau de bord. */
    public static function recentes(int $limite = 8): array
    {
        $limite = max(1, $limite);
        return Db::tous(
            "SELECT c.id, c.reference, c.statut, c.total_ttc, c.mode_paiement, c.statut_paiement, c.created_at,
                    cl.nom client_nom, cl.prenom client_prenom
               FROM tz_commande c LEFT JOIN tz_client cl ON cl.id = c.client_id
              WHERE c.statut <> 'panier_abandonne'
           ORDER BY c.created_at DESC LIMIT $limite"
        );
    }

    public static function changerStatut(int $id, string $statut, ?string $note = null): bool
    {
        $autorises = ['attente_validation', 'en_attente', 'payee', 'preparation', 'controle_qualite',
                      'expediee', 'livree', 'annulee', 'rembourse_partiel', 'remboursee', 'litige'];
        if (!in_array($statut, $autorises, true)) {
            return false;
        }
        $donnees = ['statut' => $statut];
        $colonne = self::HORODATAGES[$statut] ?? null;
        if ($colonne !== null) {
            $donnees[$colonne] = date('Y-m-d H:i:s');
        }
        if ($note !== null && trim($note) !== '') {
            $ancienne = self::parId($id);
            $donnees['note_interne'] = trim(($ancienne['note_interne'] ?? '') . "\n" . $note);
        }
        Db::modifier('tz_commande', $donnees, 'id = ?', [$id]);
        return true;
    }

    public static function majPaiement(int $id, string $statutPaiement): void
    {
        Db::modifier('tz_commande', ['statut_paiement' => $statutPaiement], 'id = ?', [$id]);
    }

    public static function suiviNumero(int $id, ?string $numero, ?int $transporteurId = null): void
    {
        $donnees = ['suivi_numero' => $numero];
        if ($transporteurId !== null) {
            $donnees['transporteur_id'] = $transporteurId;
        }
        Db::modifier('tz_commande', $donnees, 'id = ?', [$id]);
    }

    /**
     * Agrégats du tableau de bord.
     *
     * @return array{ en_cours: int, a_traiter: int, jour: array, semaine: array, mois: array,
     *                par_statut: array<string,int>, panier_moyen: int, remboursement: int }
     */
    public static function statistiques(): array
    {
        $parStatut = [];
        foreach (Db::tous('SELECT statut, COUNT(*) n FROM tz_commande GROUP BY statut') as $ligne) {
            $parStatut[$ligne['statut']] = (int) $ligne['n'];
        }

        $periodes = ['jour' => date('Y-m-d 00:00:00'), 'semaine' => date('Y-m-d 00:00:00', strtotime('-6 days')),
                     'mois' => date('Y-m-01 00:00:00')];
        $chiffres = [];
        foreach ($periodes as $nom => $depuis) {
            $chiffres[$nom] = Db::un(
                "SELECT COUNT(*) commandes, COALESCE(SUM(total_ttc), 0) brut,
                        COALESCE(SUM(total_ht - cout_achat), 0) marge
                   FROM tz_commande
                  WHERE created_at >= ? AND statut IN ('payee','preparation','controle_qualite','expediee','livree','rembourse_partiel')",
                [$depuis]
            ) ?? ['commandes' => 0, 'brut' => 0, 'marge' => 0];
        }

        return [
            'en_cours'     => $parStatut['en_attente'] ?? 0,
            'a_traiter'    => ($parStatut['payee'] ?? 0) + ($parStatut['preparation'] ?? 0)
                            + ($parStatut['controle_qualite'] ?? 0),
            'jour'         => $chiffres['jour'],
            'semaine'      => $chiffres['semaine'],
            'mois'         => $chiffres['mois'],
            'par_statut'   => $parStatut,
            'panier_moyen' => (int) (Db::valeur(
                "SELECT COALESCE(AVG(total_ttc), 0) FROM tz_commande
                  WHERE statut IN ('payee','preparation','controle_qualite','expediee','livree')") ?? 0),
            'remboursement' => (int) (Db::valeur(
                "SELECT COALESCE(SUM(montant), 0) FROM tz_remboursement WHERE statut = 'execute'") ?? 0),
        ];
    }

    /** Ventes par jour pour les courbes d'admin. */
    public static function serie(int $jours = 30): array
    {
        $jours = max(1, min(365, $jours));
        return Db::tous(
            "SELECT DATE(created_at) jour, COUNT(*) commandes, COALESCE(SUM(total_ttc), 0) brut
               FROM tz_commande
              WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL $jours DAY)
                AND statut IN ('payee','preparation','controle_qualite','expediee','livree')
           GROUP BY DATE(created_at)
           ORDER BY jour"
        );
    }

    /** Chiffres par maison, pour l'écran « enseignes » et le reversement des commissions. */
    public static function parEnseigne(string $depuis, string $jusqu): array
    {
        return Db::tous(
            "SELECT e.id, e.nom, e.slug, e.commission_bpo,
                    COUNT(DISTINCT c.id) commandes,
                    SUM(l.quantite) articles,
                    SUM(l.total_ttc) net,
                    SUM(l.total_ht) ht,
                    SUM(l.cout_achat) revient,
                    ROUND(SUM(l.total_ttc * e.commission_bpo) / 100000) commission
               FROM tz_commande_ligne l
               JOIN tz_commande c ON c.id = l.commande_id
               JOIN tz_enseigne e ON e.id = l.enseigne_id
              WHERE c.created_at BETWEEN ? AND ?
                AND c.statut IN ('payee','preparation','controle_qualite','expediee','livree')
           GROUP BY e.id, e.nom, e.slug, e.commission_bpo
           ORDER BY net DESC",
            [$depuis . ' 00:00:00', $jusqu . ' 23:59:59']
        );
    }

    public static function compterClient(int $clientId): int
    {
        return (int) Db::compter(
            'tz_commande',
            "client_id = ? AND statut IN ('payee','preparation','controle_qualite','expediee','livree')",
            [$clientId]
        );
    }

    public static function premiereCommande(int $clientId): bool
    {
        return self::compterClient($clientId) === 0;
    }

    /** @return array<int, array<string, mixed>> */
    public static function parClient(int $clientId, int $limite = 20): array
    {
        $limite = max(1, $limite);
        return Db::tous(
            "SELECT c.*, (SELECT SUM(l.quantite) FROM tz_commande_ligne l WHERE l.commande_id = c.id) articles
               FROM tz_commande c
              WHERE c.client_id = ?
           ORDER BY c.created_at DESC LIMIT $limite",
            [$clientId]
        );
    }

    /** Snapshot de l'adresse stocké en JSON au moment de la commande. */
    public static function adresse(array $commande, string $cle = 'adresse_destinataire'): array
    {
        $brut = $commande[$cle] ?? '';
        $decode = is_string($brut) ? json_decode($brut, true) : $brut;
        return is_array($decode) ? $decode : [];
    }

    public static function annuler(int $id, ?int $utilisateurId = null, string $motif = ''): void
    {
        $commande = self::parId($id);
        if ($commande === null) {
            return;
        }
        self::changerStatut($id, 'annulee', $motif === '' ? null : "Annulation : $motif");
        ActionJournal::noter('commande_annulee', 'commande', $id,
            ['reference' => $commande['reference'], 'motif' => $motif], 'admin', $utilisateurId);
    }
}
