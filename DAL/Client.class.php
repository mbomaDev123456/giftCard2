<?php
/** Client de la plateforme : compte, adresses, historique, fidélité. */
class Client
{
    public static function parId(int $id): ?array
    {
        return Db::un('SELECT * FROM tz_client WHERE id = ?', [$id]);
    }

    public static function parEmail(string $email): ?array
    {
        return Db::un('SELECT * FROM tz_client WHERE email = ?', [mb_strtolower(trim($email))]);
    }

    public static function enregistrer(array $d, ?int $id = null): int
    {
        $champs = [];
        foreach (['prenom', 'nom', 'telephone', 'societe', 'patente', 'adresse_l1', 'adresse_l2',
                  'ville', 'delegation', 'code_postal', 'apport', 'note_interne'] as $cle) {
            if (array_key_exists($cle, $d)) {
                $champs[$cle] = $d[$cle] === '' ? null : trim((string) $d[$cle]);
            }
        }
        if (array_key_exists('email', $d)) {
            $champs['email'] = mb_strtolower(trim((string) $d['email']));
        }
        foreach (['pays'] as $cle) {
            if (array_key_exists($cle, $d)) {
                $champs[$cle] = strtoupper(substr(trim((string) $d[$cle]), 0, 2)) ?: 'TN';
            }
        }
        foreach (['newsletter', 'sms_promo'] as $cle) {
            if (array_key_exists($cle, $d)) {
                $champs[$cle] = $d[$cle] ? 1 : 0;
            }
        }
        if (!empty($d['mot_de_passe'])) {
            $champs['mot_de_passe'] = password_hash((string) $d['mot_de_passe'], PASSWORD_BCRYPT, ['cost' => TZ_BCRYPT_COST]);
        }
        if ($id === null) {
            $champs['created_at'] = date('Y-m-d H:i:s');
            return Db::inserer('tz_client', $champs);
        }
        Db::modifier('tz_client', $champs, 'id = ?', [$id]);
        return $id;
    }

    public static function verifierMotDePasse(array $client, string $saisie): bool
    {
        $empreinte = (string) ($client['mot_de_passe'] ?? '');
        if ($empreinte === '') {
            return false;
        }
        // Les comptes importés de l'ancienne plateforme portent un sha-256 non salé.
        if (preg_match('/^[0-9a-f]{64}$/', $empreinte)) {
            if (!hash_equals($empreinte, hash('sha256', $saisie))) {
                return false;
            }
            Db::modifier('tz_client', [
                'mot_de_passe' => password_hash($saisie, PASSWORD_BCRYPT, ['cost' => TZ_BCRYPT_COST]),
            ], 'id = ?', [(int) $client['id']]);
            return true;
        }
        return password_verify($saisie, $empreinte);
    }

    public static function marquerConnexion(int $id): void
    {
        Db::modifier('tz_client', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
    }

    public static function administrer(array $f = []): array
    {
        $conditions = ['1=1'];
        $valeurs = [];
        if (!empty($f['q'])) {
            $conditions[] = '(concat(prenom, " ", nom) LIKE ? OR email LIKE ? OR telephone LIKE ? OR nom LIKE ?)';
            $like = '%' . $f['q'] . '%';
            $valeurs[] = $like; $valeurs[] = $like; $valeurs[] = $like; $valeurs[] = $like;
        }
        if (isset($f['blocage']) && $f['blocage'] !== '') {
            $conditions[] = 'blocage = ?';
            $valeurs[] = (int) $f['blocage'];
        }
        if (!empty($f['ville'])) {
            $conditions[] = 'ville = ?';
            $valeurs[] = $f['ville'];
        }
        if (!empty($f['apport'])) {
            $conditions[] = 'apport = ?';
            $valeurs[] = $f['apport'];
        }
        $where = implode(' AND ', $conditions);
        $page = max(1, (int) ($f['page'] ?? 1));
        $parPage = 30;
        return [
            'total' => (int) Db::valeur("SELECT COUNT(*) FROM tz_client WHERE $where", $valeurs),
            'lignes' => Db::tous("SELECT * FROM tz_client WHERE $where
                                  ORDER BY created_at DESC LIMIT $parPage OFFSET " . (($page - 1) * $parPage), $valeurs),
        ];
    }

    public static function villes(): array
    {
        return Db::tous("SELECT ville, COUNT(*) nb FROM tz_client WHERE ville IS NOT NULL
                         GROUP BY ville ORDER BY nb DESC");
    }

    /** Répartition du chiffre d'affaires par apport d'acquisition. */
    public static function apports(): array
    {
        return Db::tous(
            "SELECT COALESCE(apport, 'non renseigné') apport, COUNT(*) clients,
                    COALESCE(SUM(total_engage), 0) montant
               FROM tz_client GROUP BY apport ORDER BY montant DESC"
        );
    }

    public static function blocage(int $id, bool $bloque): void
    {
        Db::modifier('tz_client', ['blocage' => $bloque ? 1 : 0], 'id = ?', [$id]);
    }

    /** Recalcul des totaux du client après une commande payée ou annulée. */
    public static function recalculerTotaux(int $clientId): void
    {
        if ($clientId <= 0) {
            return;
        }
        $totaux = Db::un(
            "SELECT COUNT(*) nb, COALESCE(SUM(total_ttc), 0) engage
               FROM tz_commande
              WHERE client_id = ? AND statut_paiement = 'accorde'",
            [$clientId]
        );
        Db::modifier('tz_client', [
            'nb_commandes' => (int) ($totaux['nb'] ?? 0),
            'total_engage' => (int) ($totaux['engage'] ?? 0),
            'points_fidelite' => (int) floor((int) ($totaux['engage'] ?? 0) / 10000),
        ], 'id = ?', [$clientId]);
    }
}
