<?php
/**
 * Facturation : factures, avoirs, proformas, acomptes.
 * Les totaux sont figés à l'émission ; une facture ne se recalcule jamais.
 */
class Facture
{
    private const PREFIXES = [
        'facture'          => 'FS',
        'avoir'            => 'AV',
        'proforma'         => 'PR',
        'facture_acompte'  => 'AC',
    ];

    public static function parId(int $id): ?array
    {
        return Db::un(
            'SELECT f.*, c.reference commande_reference, cl.nom client_nom, cl.prenom client_prenom,
                    cl.email client_email, cl.adresse_l1, cl.adresse_l2, cl.ville, cl.code_postal,
                    cl.telephone client_tel, cl.patente, cl.societe, e.nom enseigne_nom, e.slug enseigne_slug,
                    e.ville enseigne_ville, e.telephone enseigne_tel, e.email enseigne_email,
                    e.patente enseigne_patente, e.rib enseigne_rib, e.couleur enseigne_couleur
               FROM tz_facture f
               LEFT JOIN tz_commande c ON c.id = f.commande_id
               LEFT JOIN tz_client cl ON cl.id = f.client_id
               LEFT JOIN tz_enseigne e ON e.id = f.enseigne_id
              WHERE f.id = ?',
            [$id]
        );
    }

    public static function parNumero(string $numero): ?array
    {
        return Db::un('SELECT * FROM tz_facture WHERE numero = ?', [$numero]);
    }

    /** Prochaine numéro de la série de l'année : FS-2026-000102. */
    public static function prochaineNumero(string $type = 'facture', ?string $date = null): string
    {
        $prefixe = self::PREFIXES[$type] ?? 'FS';
        $annee = date('Y', strtotime($date ?: date('Y-m-d')));
        $debut = "$prefixe-$annee-";
        $dernier = Db::valeur(
            'SELECT MAX(numero) FROM tz_facture WHERE numero LIKE ? FOR UPDATE',
            [$debut . '%']
        );
        $suivant = $dernier === null ? 1 : ((int) substr((string) $dernier, strlen($debut))) + 1;
        return $debut . str_pad((string) $suivant, 6, '0', STR_PAD_LEFT);
    }

    /**
     * @param array<string, mixed> $facture
     * @param array<int, array<string, mixed>> $lignes
     */
    public static function creer(array $facture, array $lignes = []): int
    {
        return Db::transaction(static function () use ($facture, $lignes): int {
            // Le numéro est pris dans la transaction : deux émissions simultanées ne se télescopent pas.
            if (trim((string) ($facture['numero'] ?? '')) === '') {
                $facture['numero'] = self::prochaineNumero(
                    (string) ($facture['type'] ?? 'facture'),
                    (string) ($facture['date_emission'] ?? date('Y-m-d'))
                );
            }
            $id = Db::inserer('tz_facture', self::prepare($facture));
            $position = 0;
            foreach ($lignes as $ligne) {
                $position++;
                Db::inserer('tz_facture_ligne', self::prepareLigne($ligne, $id, $position));
            }
            return $id;
        });
    }

    public static function modifier(int $id, array $facture, array $lignes = []): int
    {
        return Db::transaction(static function () use ($id, $facture, $lignes): int {
            Db::modifier('tz_facture', self::prepare($facture), 'id = ?', [$id]);
            if ($lignes !== []) {
                Db::supprimer('tz_facture_ligne', 'facture_id = ?', [$id]);
                $position = 0;
                foreach ($lignes as $ligne) {
                    $position++;
                    Db::inserer('tz_facture_ligne', self::prepareLigne($ligne, $id, $position));
                }
            }
            return $id;
        });
    }

    public static function lignes(int $factureId): array
    {
        return Db::tous('SELECT * FROM tz_facture_ligne WHERE facture_id = ? ORDER BY position, id', [$factureId]);
    }

    /** En-tête + lignes + total, prêt pour l'impression PDF ou HTML. */
    public static function dossier(int $factureId): ?array
    {
        $facture = self::parId($factureId);
        if ($facture === null) {
            return null;
        }
        $facture['lignes'] = self::lignes($factureId);
        $facture['encaissements'] = Encaissement::pourFacture($factureId);
        $facture['restant'] = max(0, (int) $facture['total_ttc'] - (int) $facture['regle']);
        return $facture;
    }

    /** @return array{ lignes: array<int, array<string, mixed>>, total: int, pages: int, page: int, creances: int } */
    public static function administrer(array $f = [], int $page = 1, int $parPage = 25): array
    {
        $conditions = [];
        $params = [];
        if (($f['type'] ?? '') !== '') {
            $conditions[] = 'f.type = ?';
            $params[] = $f['type'];
        }
        if (($f['statut'] ?? '') !== '') {
            $conditions[] = 'f.statut = ?';
            $params[] = $f['statut'];
        }
        if (!empty($f['enseigne_id'])) {
            $conditions[] = 'f.enseigne_id = ?';
            $params[] = (int) $f['enseigne_id'];
        }
        if (($f['du'] ?? '') !== '') {
            $conditions[] = 'f.date_emission >= ?';
            $params[] = $f['du'];
        }
        if (($f['au'] ?? '') !== '') {
            $conditions[] = 'f.date_emission <= ?';
            $params[] = $f['au'];
        }
        if (($f['q'] ?? '') !== '') {
            $conditions[] = '(f.numero LIKE ? OR f.objet LIKE ? OR cl.nom LIKE ? OR cl.prenom LIKE ? OR cl.email LIKE ?)';
            $motif = '%' . $f['q'] . '%';
            array_push($params, $motif, $motif, $motif, $motif, $motif);
        }
        $where = $conditions === [] ? '1' : implode(' AND ', $conditions);
        $base = "FROM tz_facture f
                 LEFT JOIN tz_client cl ON cl.id = f.client_id
                 LEFT JOIN tz_enseigne e ON e.id = f.enseigne_id
                 LEFT JOIN tz_commande c ON c.id = f.commande_id
                 WHERE $where";

        $total = (int) Db::valeur("SELECT COUNT(*) $base", $params);
        $pages = max(1, (int) ceil($total / $parPage));
        $page = min(max(1, $page), $pages);
        $depart = ($page - 1) * $parPage;

        $creances = (int) (Db::valeur(
            "SELECT COALESCE(SUM(f.total_ttc - f.regle), 0) $base
              AND f.statut IN ('emise','partielle','recouvre')",
            $params
        ) ?? 0);

        return [
            'lignes' => Db::tous(
                "SELECT f.*, cl.nom client_nom, cl.prenom client_prenom, cl.email client_email,
                        e.nom enseigne_nom, c.reference commande_reference,
                        (f.total_ttc - f.regle) restant,
                        GREATEST(DATEDIFF(CURDATE(), f.date_echeance), 0) retard
                   $base
               ORDER BY f.date_emission DESC, f.id DESC
               LIMIT $parPage OFFSET $depart",
                $params
            ),
            'total' => $total, 'pages' => $pages, 'page' => $page, 'creances' => $creances,
        ];
    }

    /** Balance âgée : ce qui reste dû, tranché par ancienneté d'échéance. */
    public static function balanceAgee(): array
    {
        return Db::tous(
            "SELECT CASE
                        WHEN f.date_echeance IS NULL OR f.date_echeance >= CURDATE() THEN 'a_echoir'
                        WHEN DATEDIFF(CURDATE(), f.date_echeance) <= 30 THEN 'j30'
                        WHEN DATEDIFF(CURDATE(), f.date_echeance) <= 60 THEN 'j60'
                        WHEN DATEDIFF(CURDATE(), f.date_echeance) <= 90 THEN 'j90'
                        ELSE 'au_delà'
                    END tranche,
                    COUNT(*) factures,
                    COALESCE(SUM(f.total_ttc - f.regle), 0) du
               FROM tz_facture f
              WHERE f.statut IN ('emise','partielle','recouvre')
           GROUP BY tranche
           ORDER BY FIELD(tranche, 'a_echoir','j30','j60','j90','au_delà')"
        );
    }

    public static function parClient(int $clientId): array
    {
        return Db::tous(
            'SELECT * FROM tz_facture WHERE client_id = ? ORDER BY date_emission DESC, id DESC',
            [$clientId]
        );
    }

    public static function pourCommande(int $commandeId): array
    {
        return Db::tous('SELECT * FROM tz_facture WHERE commande_id = ? ORDER BY id', [$commandeId]);
    }

    public static function statut(int $id, string $statut): bool
    {
        if (!in_array($statut, ['brouillon', 'emise', 'partielle', 'payee', 'annulee', 'recouvre'], true)) {
            return false;
        }
        Db::modifier('tz_facture', ['statut' => $statut], 'id = ?', [$id]);
        return true;
    }

    /**
     * Encaissement enregistré : met à jour `regle` et le statut qui en découle.
     * Le montant doit rester dans la limite du restant dû.
     */
    public static function encaisser(int $id, int $montant): string
    {
        $facture = self::parId($id);
        if ($facture === null) {
            throw new RuntimeException('Facture introuvable : #' . $id);
        }
        $restant = (int) $facture['total_ttc'] - (int) $facture['regle'];
        if ($montant > $restant) {
            throw new RuntimeException(sprintf(
                'Règlement trop élevé : %s DT pour un restant de %s DT.',
                Money::formater($montant, false),
                Money::formater(max(0, $restant), false)
            ));
        }
        $nouveau = (int) $facture['regle'] + $montant;
        $statut = $nouveau >= (int) $facture['total_ttc'] ? 'payee' : 'partielle';
        Db::modifier('tz_facture',
            ['regle' => $nouveau, 'statut' => $statut, 'date_paiement' => date('Y-m-d')], 'id = ?', [$id]);
        return $statut;
    }

    public static function marquerEcrite(int $id): void
    {
        Db::modifier('tz_facture', ['ecrite' => 1], 'id = ?', [$id]);
    }

    /** Brouillons jamais passés en comptabilité, à traiter. */
    public static function aEcrire(int $limite = 50): array
    {
        $limite = max(1, min(200, $limite));
        return Db::tous(
            "SELECT f.*, cl.nom client_nom, cl.prenom client_prenom
               FROM tz_facture f LEFT JOIN tz_client cl ON cl.id = f.client_id
              WHERE f.ecrite = 0 AND f.statut IN ('emise','partielle','payee')
           ORDER BY f.date_emission LIMIT $limite"
        );
    }

    /** Chiffres d'affaires facturés par mois, HT puis TVA, pour la trésorerie. */
    public static function serie(int $mois = 12): array
    {
        $mois = max(1, min(36, $mois));
        return Db::tous(
            "SELECT DATE_FORMAT(date_emission, '%Y-%m') periode,
                    COUNT(*) factures,
                    COALESCE(SUM(total_ht), 0) ht,
                    COALESCE(SUM(total_tva), 0) tva,
                    COALESCE(SUM(total_ttc), 0) ttc,
                    COALESCE(SUM(regle), 0) encaisse
               FROM tz_facture
              WHERE statut <> 'annulee'
                AND date_emission >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL $mois MONTH)
           GROUP BY periode ORDER BY periode"
        );
    }

    public static function annuler(int $id): void
    {
        Db::modifier('tz_facture', ['statut' => 'annulee'], 'id = ?', [$id]);
    }

    /** @return array<int, array<string, mixed>> factures échues non réglées → relances. */
    public static function recouvrement(int $limite = 30): array
    {
        $limite = max(1, min(200, $limite));
        return Db::tous(
            "SELECT f.*, cl.nom, cl.prenom, cl.email, cl.telephone,
                    (f.total_ttc - f.regle) restant,
                    DATEDIFF(CURDATE(), f.date_echeance) retard
               FROM tz_facture f JOIN tz_client cl ON cl.id = f.client_id
              WHERE f.statut IN ('emise','partielle')
                AND f.date_echeance IS NOT NULL AND f.date_echeance < CURDATE()
           ORDER BY retard DESC LIMIT $limite"
        );
    }

    private static function prepare(array $d): array
    {
        $champs = [];
        foreach (['numero', 'type', 'objet', 'mode_reglement', 'mention', 'statut'] as $cle) {
            if (isset($d[$cle]) && trim((string) $d[$cle]) !== '') {
                $champs[$cle] = trim((string) $d[$cle]);
            }
        }
        foreach (['commande_id', 'client_id', 'enseigne_id'] as $cle) {
            if (array_key_exists($cle, $d)) {
                $champs[$cle] = ($d[$cle] === '' || $d[$cle] === null) ? null : (int) $d[$cle];
            }
        }
        foreach (['date_emission', 'date_echeance', 'date_paiement'] as $cle) {
            if (array_key_exists($cle, $d)) {
                $champs[$cle] = ($d[$cle] === '' || $d[$cle] === null) ? null : $d[$cle];
            }
        }
        foreach (['total_ht', 'total_tva', 'total_ttc', 'regle'] as $cle) {
            if (array_key_exists($cle, $d)) {
                $champs[$cle] = (int) $d[$cle];
            }
        }
        if (isset($d['ecrite'])) {
            $champs['ecrite'] = (int) (bool) $d['ecrite'];
        }
        return $champs;
    }

    private static function prepareLigne(array $ligne, int $factureId, int $position): array
    {
        $ht = (int) ($ligne['total_ht'] ?? 0);
        $tva = (int) ($ligne['total_tva'] ?? 0);
        return [
            'facture_id'    => $factureId,
            'position'      => (int) ($ligne['position'] ?? $position),
            'designation'   => mb_substr(trim((string) $ligne['designation']), 0, 220),
            'quantite'      => (int) ($ligne['quantite'] ?? 1),
            'prix_unitaire' => (int) ($ligne['prix_unitaire'] ?? 0),
            'remise'        => (int) ($ligne['remise'] ?? 0),
            'tva_taux'      => (int) ($ligne['tva_taux'] ?? TZ_TVA_STANDARD),
            'total_ht'      => $ht,
            'total_tva'     => $tva,
            'total_ttc'     => (int) ($ligne['total_ttc'] ?? $ht + $tva),
        ];
    }
}
