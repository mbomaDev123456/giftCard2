<?php
/**
 * Encaissements : chaque règlement reçu sur une facture.
 * `creer()` met à jour la facture dans la même transaction, sinon `regle` dérive.
 */
class Encaissement
{
    public const MODES = ['carte_gpg', 'virement', 'cheque', 'especes', 'livraison'];

    public static function parId(int $id): ?array
    {
        return Db::un(
            'SELECT en.*, f.numero, f.total_ttc, f.client_id, cl.nom client_nom, cl.prenom client_prenom
               FROM tz_encaissement en
               JOIN tz_facture f ON f.id = en.facture_id
               LEFT JOIN tz_client cl ON cl.id = f.client_id
              WHERE en.id = ?',
            [$id]
        );
    }

    public static function pourFacture(int $factureId): array
    {
        return Db::tous('SELECT * FROM tz_encaissement WHERE facture_id = ? ORDER BY date_recue, id',
            [$factureId]);
    }

    public static function totalFacture(int $factureId): int
    {
        return (int) (Db::valeur('SELECT COALESCE(SUM(montant), 0) FROM tz_encaissement WHERE facture_id = ?',
            [$factureId]) ?? 0);
    }

    public static function creer(array $donnees, ?int $utilisateurId = null): array
    {
        $mode = in_array($donnees['mode'] ?? '', self::MODES, true) ? $donnees['mode'] : 'virement';
        $montant = (int) ($donnees['montant'] ?? 0);
        if ($montant <= 0) {
            throw new RuntimeException('Un encaissement doit être positif.');
        }

        $factureId = (int) $donnees['facture_id'];
        return Db::transaction(static function () use ($donnees, $montant, $mode, $factureId, $utilisateurId): array {
            $id = Db::inserer('tz_encaissement', [
                'facture_id'     => $factureId,
                'date_recue'     => ($donnees['date_recue'] ?? '') === '' ? date('Y-m-d') : $donnees['date_recue'],
                'montant'        => $montant,
                'mode'           => $mode,
                'reference'      => ($donnees['reference'] ?? '') === '' ? null : mb_substr((string) $donnees['reference'], 0, 60),
                'banque'         => ($donnees['banque'] ?? '') === '' ? null : mb_substr((string) $donnees['banque'], 0, 80),
                'note'           => ($donnees['note'] ?? '') === '' ? null : mb_substr((string) $donnees['note'], 0, 255),
                'utilisateur_id' => $utilisateurId ?? Auth::idAdmin(),
            ]);
            return [
                'id'      => $id,
                'statut'  => Facture::encaisser($factureId, $montant),
                'facture' => $factureId,
                'montant' => $montant,
            ];
        });
    }

    /** @param array<string, mixed> $f */
    public static function administrer(array $f = [], int $page = 1, int $parPage = 40): array
    {
        $conditions = [];
        $params = [];
        if (($f['mode'] ?? '') !== '') {
            $conditions[] = 'en.mode = ?';
            $params[] = $f['mode'];
        }
        if (($f['du'] ?? '') !== '') {
            $conditions[] = 'en.date_recue >= ?';
            $params[] = $f['du'];
        }
        if (($f['au'] ?? '') !== '') {
            $conditions[] = 'en.date_recue <= ?';
            $params[] = $f['au'];
        }
        if (($f['q'] ?? '') !== '') {
            $conditions[] = '(f.numero LIKE ? OR en.reference LIKE ? OR en.banque LIKE ? OR cl.nom LIKE ?)';
            $motif = '%' . $f['q'] . '%';
            array_push($params, $motif, $motif, $motif, $motif);
        }
        $where = $conditions === [] ? '1' : implode(' AND ', $conditions);
        $base = "FROM tz_encaissement en
                 JOIN tz_facture f ON f.id = en.facture_id
                 LEFT JOIN tz_client cl ON cl.id = f.client_id
                 LEFT JOIN tz_utilisateur u ON u.id = en.utilisateur_id
                 WHERE $where";

        $total = (int) Db::valeur("SELECT COUNT(*) $base", $params);
        $pages = max(1, (int) ceil($total / $parPage));
        $page = min(max(1, $page), $pages);
        $depart = ($page - 1) * $parPage;

        return [
            'lignes' => Db::tous(
                "SELECT en.*, f.numero, f.total_ttc, f.statut facture_statut, f.mode_reglement,
                        cl.nom client_nom, cl.prenom client_prenom, u.login utilisateur_login
                   $base
               ORDER BY en.date_recue DESC, en.id DESC
               LIMIT $parPage OFFSET $depart",
                $params
            ),
            'total' => $total, 'pages' => $pages, 'page' => $page,
            'encaisse' => (int) (Db::valeur("SELECT COALESCE(SUM(en.montant), 0) $base", $params) ?? 0),
        ];
    }

    /** Journal des recettes par jour et par mode, pour le rapprochement bancaire. */
    public static function recette(string $debut, string $fin): array
    {
        return Db::tous(
            'SELECT en.date_recue jour, en.mode, COUNT(*) nb, COALESCE(SUM(en.montant), 0) montant
               FROM tz_encaissement en
              WHERE en.date_recue BETWEEN ? AND ?
           GROUP BY en.date_recue, en.mode
           ORDER BY en.date_recue, en.mode',
            [$debut, $fin]
        );
    }

    /** Sommes encaissées par mode sur la période : la caisse et la banque se lettrent là-dessus. */
    public static function parMode(string $debut, string $fin): array
    {
        return Db::tous(
            'SELECT en.mode, COUNT(*) nb, COALESCE(SUM(en.montant), 0) montant
               FROM tz_encaissement en
              WHERE en.date_recue BETWEEN ? AND ?
           GROUP BY en.mode ORDER BY montant DESC',
            [$debut, $fin]
        );
    }
}
