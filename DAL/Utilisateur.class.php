<?php
/** Comptes de l'administration (rôles : super, gestionnaire, comptable, logistique, vendeur). */
class Utilisateur
{
    public static function parLogin(string $login): ?array
    {
        return Db::un('SELECT * FROM tz_utilisateur WHERE login = ?', [trim($login)]);
    }

    public static function parId(int $id): ?array
    {
        return Db::un('SELECT * FROM tz_utilisateur WHERE id = ?', [$id]);
    }

    public static function tous(): array
    {
        return Db::tous('SELECT id, login, nom, email, role, enseigne_id, etat, nbr_tentatives, last_login, created_at
                         FROM tz_utilisateur ORDER BY FIELD(role, "super", "gestionnaire", "comptable", "logistique", "vendeur"), login');
    }

    /**
     * Vérifie un login/mot de passe côté plateforme.
     * Verrouille le compte après cinq échecs, comme dans l'ancienne maison.
     */
    public static function authentifier(string $login, string $motDePasse): array
    {
        $utilisateur = self::parLogin($login);
        if ($utilisateur === null) {
            return ['ok' => false, 'erreur' => 'Identifiant ou mot de passe incorrect.'];
        }
        if ($utilisateur['etat'] !== 'actif') {
            return ['ok' => false, 'erreur' => 'Ce compte est ' . $utilisateur['etat'] . '.'];
        }
        if ($utilisateur['bloque_jusqua'] !== null && strtotime((string) $utilisateur['bloque_jusqua']) > time()) {
            return ['ok' => false, 'erreur' => 'Trop de tentatives. Réessayez après '
                . date('H:i', strtotime((string) $utilisateur['bloque_jusqua'])) . '.'];
        }
        $empreinte = (string) $utilisateur['mot_de_passe'];
        $valide = false;
        if (preg_match('/^\$2y\$/', $empreinte)) {
            $valide = password_verify($motDePasse, $empreinte);
        } elseif (preg_match('/^[0-9a-f]{64}$/', $empreinte)) {
            // Import de l'ancienne plateforme (sha-256 non salé) : on remonte en bcrypt.
            if (hash_equals($empreinte, hash('sha256', $motDePasse))) {
                $valide = true;
                self::changerMotDePasse((int) $utilisateur['id'], $motDePasse);
            }
        } elseif (strlen($empreinte) === 32) {
            if (hash_equals($empreinte, md5($motDePasse))) {
                $valide = true;
                self::changerMotDePasse((int) $utilisateur['id'], $motDePasse);
            }
        }
        if (!$valide) {
            $tentatives = (int) $utilisateur['nbr_tentatives'] + 1;
            $champs = ['nbr_tentatives' => $tentatives];
            if ($tentatives >= 5) {
                $champs['bloque_jusqua'] = date('Y-m-d H:i:s', time() + 1800);
                $champs['nbr_tentatives'] = 0;
            }
            Db::modifier('tz_utilisateur', $champs, 'id = ?', [(int) $utilisateur['id']]);
            tz_log('LoginLogout', 'échec de connexion admin', [
                'login' => $login, 'ip' => Http::ip(), 'tentatives' => $tentatives,
            ]);
            return ['ok' => false, 'erreur' => 'Identifiant ou mot de passe incorrect.'];
        }
        Db::modifier('tz_utilisateur', [
            'nbr_tentatives' => 0, 'bloque_jusqua' => null, 'last_login' => date('Y-m-d H:i:s'),
        ], 'id = ?', [(int) $utilisateur['id']]);
        tz_log('LoginLogout', 'connexion admin réussie', ['login' => $login, 'ip' => Http::ip(), 'role' => $utilisateur['role']]);
        return ['ok' => true, 'utilisateur' => $utilisateur];
    }

    public static function changerMotDePasse(int $id, string $motDePasse): void
    {
        Db::modifier('tz_utilisateur', [
            'mot_de_passe' => password_hash($motDePasse, PASSWORD_BCRYPT, ['cost' => TZ_BCRYPT_COST]),
            'password_changed_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);
    }

    public static function enregistrer(array $d, ?int $id = null): int
    {
        $champs = [
            'login'    => mb_strtolower(trim((string) $d['login'])),
            'nom'      => trim((string) $d['nom']),
            'email'    => ($d['email'] ?? '') === '' ? null : mb_strtolower(trim((string) $d['email'])),
            'role'     => (string) ($d['role'] ?? 'gestionnaire'),
            'etat'     => (string) ($d['etat'] ?? 'actif'),
            'enseigne_id' => ($d['enseigne_id'] ?? '') === '' ? null : (int) $d['enseigne_id'],
        ];
        $motDePasse = (string) ($d['mot_de_passe'] ?? '');
        if ($motDePasse !== '') {
            self::validerRobustesse($motDePasse);
            $champs['mot_de_passe'] = password_hash($motDePasse, PASSWORD_BCRYPT, ['cost' => TZ_BCRYPT_COST]);
            $champs['password_changed_at'] = date('Y-m-d H:i:s');
        }
        if ($id === null) {
            if ($motDePasse === '') {
                throw new InvalidArgumentException('Un mot de passe est nécessaire pour créer un compte.');
            }
            $champs['created_at'] = date('Y-m-d H:i:s');
            return Db::inserer('tz_utilisateur', $champs);
        }
        Db::modifier('tz_utilisateur', $champs, 'id = ?', [$id]);
        return $id;
    }

    public static function supprimer(int $id): void
    {
        if ($id === 1) {
            throw new RuntimeException('Le compte fondateur ne peut pas être supprimé.');
        }
        Db::supprimer('tz_utilisateur', 'id = ?', [$id]);
    }

    private static function validerRobustesse(string $motDePasse): void
    {
        $fautes = [];
        if (strlen($motDePasse) < 10) {
            $fautes[] = 'dix caractères minimum';
        }
        if (!preg_match('/[A-Z]/', $motDePasse) || !preg_match('/[a-z]/', $motDePasse)) {
            $fautes[] = 'majuscule et minuscule';
        }
        if (!preg_match('/\d/', $motDePasse)) {
            $fautes[] = 'un chiffre';
        }
        if (!preg_match('/[^A-Za-z0-9]/', $motDePasse)) {
            $fautes[] = 'un caractère spécial';
        }
        if ($fautes !== []) {
            throw new InvalidArgumentException('Mot de passe trop faible : ' . implode(', ', $fautes) . '.');
        }
    }

    /** Deuxième facteur simple pour les rôles sensibles : le code de session est déjà posé. */
    public static function sessionEstFraiche(int $minutes = 30): bool
    {
        $marque = (int) Session::lire('tz_admin_frais', 0);
        return $marque > time() - $minutes * 60;
    }
}
