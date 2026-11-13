<?php
/**
 * Régie publicitaire : emplacements, ciblage, comptage des vues et des clics.
 * Les compteurs sont incrémentés en SQL brut pour éviter toute course lecture-écriture.
 */
class Banniere
{
    public const SLOTS = ['hero', 'bandeau', 'flottante', 'cote_catalogue', 'pied', 'interstitiel', 'newsletter'];

    public static function parId(int $id): ?array
    {
        return Db::un('SELECT * FROM tz_banniere WHERE id = ?', [$id]);
    }

    /**
     * Bannières prêtes à l'emploi, groupées par emplacement.
     * Le ciblage page/clients se fait en PHP : c'est du matching de motifs, pas du SQL.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function enService(string $page, ?int $enseigneId, string $groupe = 'tous'): array
    {
        $lignes = Db::tous(
            'SELECT id, enseigne_id, emplacement, titre, sous_titre, texte_bouton, image, image_mobile,
                    url, couleur_fond, ciblage_pages, ciblage_client, priorite, type_offre, tarif_jour,
                    budget, impressions, plafond_impressions
               FROM tz_banniere
              WHERE etat = ?
                AND debut_at <= NOW()
                AND (fin_at IS NULL OR fin_at >= NOW())
                AND (enseigne_id IS NULL OR enseigne_id = ?)
           ORDER BY FIELD(emplacement, "hero", "bandeau", "interstitiel", "flottante", "cote_catalogue", "pied", "newsletter"),
                    priorite DESC, type_offre = "interne" DESC, id DESC',
            ['en_ligne', $enseigneId]
        );

        $parSlot = [];
        foreach ($lignes as $ligne) {
            if (!self::pageConcorde($ligne['ciblage_pages'], $page)
                || !self::clientConcorde($ligne['ciblage_client'], $groupe)
                || ($ligne['plafond_impressions'] > 0 && $ligne['impressions'] >= $ligne['plafond_impressions'])) {
                continue;
            }
            $parSlot[$ligne['emplacement']][] = $ligne;
        }
        return $parSlot;
    }

    /** "/,/boutique/deglat,*" : vide ou "*" = partout, sinon égalité ou préfixe. */
    public static function pageConcorde(?string $ciblage, string $page): bool
    {
        $ciblage = trim((string) $ciblage);
        if ($ciblage === '' || $ciblage === '*') {
            return true;
        }
        $page = '/' . trim($page, '/');
        foreach (explode(',', $ciblage) as $motif) {
            $motif = '/' . trim(trim($motif), '/');
            if ($motif === '/' && $page === '/') {
                return true;
            }
            if ($motif !== '/' && ($page === $motif || str_starts_with($page, $motif . '/'))) {
                return true;
            }
        }
        return false;
    }

    public static function clientConcorde(string $groupeBanniere, string $groupeClient): bool
    {
        return $groupeBanniere === 'tous' || $groupeBanniere === $groupeClient;
    }

    /**
     * Groupe d'audience du visiteur courant, pour le ciblage.
     * nouveaux = jamais payé, fideles = 2 commandes payées ou plus, abandonnistes = panier en attente.
     */
    public static function groupe(?int $clientId, ?string $email): string
    {
        $email = strtolower(trim((string) $email));
        if ($clientId === null && $email === '') {
            return 'nouveaux';
        }
        $payees = (int) Db::valeur(
            "SELECT COUNT(*) FROM tz_commande
              WHERE (client_id = ? OR LOWER(email) = ?)
                AND statut IN ('payee','preparation','controle_qualite','expediee','livree')",
            [$clientId, $email]
        );
        if ($payees > 0) {
            return $payees >= 2 ? 'fideles' : 'nouveaux';
        }
        $abandonnes = (int) Db::valeur(
            'SELECT COUNT(*) FROM tz_session_panier
              WHERE (client_id = ? OR LOWER(email) = ?) AND articles > 0',
            [$clientId, $email]
        );
        return $abandonnes > 0 ? 'abandonnistes' : 'nouveaux';
    }

    /** Exposition : un seul compteur incrémenté, l'événement détaillé part dans tz_banniere_evenement. */
    public static function exposer(int $id, string $page, ?string $sessionId, string $ip): void
    {
        Db::modifier('tz_banniere', ['impressions' => 'impressions + 1'], 'id = ?', [$id], brutes: ['impressions']);
        self::evenement($id, 'vue', $sessionId, $ip, $page);
    }

    public static function clic(int $id, string $page, ?string $sessionId, string $ip): void
    {
        Db::modifier('tz_banniere',
            ['impressions' => 'impressions + 1', 'clics' => 'clics + 1'], 'id = ?', [$id],
            brutes: ['impressions', 'clics']);
        self::evenement($id, 'clic', $sessionId, $ip, $page);
    }

    private static function evenement(int $banniereId, string $type, ?string $sessionId, string $ip, string $page): void
    {
        Db::inserer('tz_banniere_evenement', [
            'banniere_id' => $banniereId,
            'type'        => $type,
            'session_id'  => $sessionId === null ? null : substr($sessionId, 0, 32),
            'ip'          => $ip,
            'page'        => mb_substr('/' . trim($page, '/'), 0, 160),
        ]);
    }

    /** @param array<string, mixed> $f */
    public static function administrer(array $f = []): array
    {
        $conditions = [];
        $params = [];
        if (($f['etat'] ?? '') !== '') {
            $conditions[] = 'b.etat = ?';
            $params[] = $f['etat'];
        }
        if (($f['emplacement'] ?? '') !== '') {
            $conditions[] = 'b.emplacement = ?';
            $params[] = $f['emplacement'];
        }
        if (!empty($f['enseigne_id'])) {
            $conditions[] = 'b.enseigne_id = ?';
            $params[] = (int) $f['enseigne_id'];
        }
        $where = $conditions === [] ? '1' : implode(' AND ', $conditions);
        return Db::tous(
            "SELECT b.*, e.nom enseigne_nom,
                    ROUND(b.clics / GREATEST(b.impressions, 1) * 100, 2) taux_clic
               FROM tz_banniere b LEFT JOIN tz_enseigne e ON e.id = b.enseigne_id
              WHERE $where
           ORDER BY FIELD(b.etat, 'en_ligne','programmee','pause','brouillon','terminee'), b.priorite DESC, b.id DESC",
            $params
        );
    }

    public static function enregistrer(array $d, ?int $id = null): int
    {
        $champs = [];
        foreach (['enseigne_id', 'emplacement', 'titre', 'sous_titre', 'texte_bouton', 'image', 'image_mobile',
                  'url', 'couleur_fond', 'ciblage_pages', 'ciblage_client', 'debut_at', 'fin_at',
                  'type_offre', 'etat'] as $cle) {
            if (array_key_exists($cle, $d)) {
                $valeur = is_string($d[$cle]) ? trim($d[$cle]) : $d[$cle];
                $champs[$cle] = ($valeur === '' || $valeur === null) ? null : $valeur;
            }
        }
        $champs['priorite'] = (int) ($d['priorite'] ?? 50);
        $champs['tarif_jour'] = (int) ($d['tarif_jour'] ?? 0);
        $champs['budget'] = (int) ($d['budget'] ?? 0);
        $champs['plafond_impressions'] = (int) ($d['plafond_impressions'] ?? 0);
        if (($d['debut_at'] ?? '') === '') {
            $champs['debut_at'] = date('Y-m-d H:i:s');
        }
        if (isset($champs['enseigne_id'])) {
            $champs['enseigne_id'] = (int) $champs['enseigne_id'];
        }
        if ($id === null) {
            return Db::inserer('tz_banniere', $champs);
        }
        Db::modifier('tz_banniere', $champs, 'id = ?', [$id]);
        return $id;
    }

    public static function etat(int $id, string $etat): bool
    {
        if (!in_array($etat, ['brouillon', 'programmee', 'en_ligne', 'pause', 'terminee'], true)) {
            return false;
        }
        Db::modifier('tz_banniere', ['etat' => $etat], 'id = ?', [$id]);
        return true;
    }

    /**
     * Facturation d'une régie sur une période : tarif journalier × jours de mise en ligne.
     * Les slots sponsorisés se facturent au temps, les internes rapportent 0.
     *
     * @return array<string, mixed>
     */
    public static function facturation(string $debut, string $fin): array
    {
        $lignes = Db::tous(
            "SELECT b.id, b.titre, b.emplacement, b.type_offre, b.tarif_jour, b.clics, b.impressions,
                    e.nom enseigne_nom, b.debut_at, b.fin_at
               FROM tz_banniere b LEFT JOIN tz_enseigne e ON e.id = b.enseigne_id
              WHERE b.type_offre <> 'interne'
                AND b.debut_at <= ?
                AND (b.fin_at IS NULL OR b.fin_at >= ?)",
            [$fin . ' 23:59:59', $debut . ' 00:00:00']
        );
        $detail = [];
        $total = 0;
        $seuil = strtotime($debut . ' 00:00:00');
        $mur = strtotime($fin . ' 23:59:59');
        foreach ($lignes as $ligne) {
            $du = max($seuil, strtotime($ligne['debut_at']));
            $au = $ligne['fin_at'] === null ? $mur : min($mur, strtotime($ligne['fin_at']));
            $jours = $au > $du ? (int) ceil(($au - $du) / 86400) : 0;
            $montant = (int) round($ligne['tarif_jour'] * $jours);
            $total += $montant;
            $detail[] = [
                'banniere'  => $ligne['titre'],
                'emplacement' => $ligne['emplacement'],
                'enseigne'  => $ligne['enseigne_nom'] ?? 'Plateforme',
                'type'      => $ligne['type_offre'],
                'jours'     => $jours,
                'tarif_jour' => (int) $ligne['tarif_jour'],
                'montant'   => $montant,
                'impressions' => (int) $ligne['impressions'],
                'clics'     => (int) $ligne['clics'],
            ];
        }
        return ['detail' => $detail, 'total' => $total];
    }

    /** Suivi temps réel des slots, utilisé par l'écran « statistiques ». */
    public static function performances(int $jours = 30): array
    {
        $jours = max(1, min(365, $jours));
        return Db::tous(
            "SELECT b.id, b.titre, b.emplacement, b.type_offre, b.tarif_jour,
                    SUM(ev.type = 'vue') vues, SUM(ev.type = 'clic') clics
               FROM tz_banniere b
               JOIN tz_banniere_evenement ev ON ev.banniere_id = b.id
              WHERE ev.created_at >= DATE_SUB(CURDATE(), INTERVAL $jours DAY)
           GROUP BY b.id, b.titre, b.emplacement, b.type_offre, b.tarif_jour
           ORDER BY vues DESC"
        );
    }
}
