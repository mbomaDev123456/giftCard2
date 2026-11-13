<?php
/** Session d'administration et session client. */
class Auth
{
    public static function administrateur(): ?array
    {
        $admin = Session::lire(TZ_ADMIN_SESSION_KEY);
        return is_array($admin) && isset($admin['id']) ? $admin : null;
    }

    public static function idAdmin(): ?int
    {
        $admin = self::administrateur();
        return $admin === null ? null : (int) $admin['id'];
    }

    public static function roleAdmin(): string
    {
        return (string) (self::administrateur()['role'] ?? '');
    }

    public static function peut(string ...$roles): bool
    {
        $role = self::roleAdmin();
        return $role === 'super' || in_array($role, $roles, true);
    }

    /** Ouvre la session après vérification des identifiants par le BL. */
    public static function connecterAdmin(array $admin): void
    {
        Session::renouveler();
        Csrf::renouveler();
        Session::ecrire(TZ_ADMIN_SESSION_KEY, [
            'id'    => (int) $admin['id'],
            'login' => (string) $admin['login'],
            'nom'   => (string) ($admin['nom'] ?? $admin['login']),
            'role'  => (string) ($admin['role'] ?? 'gestionnaire'),
        ]);
    }

    public static function deconnecterAdmin(): void
    {
        Session::effacer(TZ_ADMIN_SESSION_KEY);
    }

    public static function exigerAdmin(string ...$roles): void
    {
        if (self::administrateur() === null) {
            Http::redirection(Url::to('/admin/connexion'));
        }
        if ($roles !== [] && !self::peut(...$roles)) {
            Http::code(403);
            Erreur::page(403, 'Ce module est réservé aux rôles ' . implode(', ', $roles) . '.');
            exit;
        }
    }

    /* ---------- Clients ---------- */

    public static function client(): ?array
    {
        $client = Session::lire(TZ_CLIENT_SESSION_KEY);
        return is_array($client) && isset($client['id']) ? $client : null;
    }

    public static function idClient(): ?int
    {
        $client = self::client();
        return $client === null ? null : (int) $client['id'];
    }

    public static function connecterClient(array $client): void
    {
        Session::renouveler();
        Csrf::renouveler();
        Session::ecrire(TZ_CLIENT_SESSION_KEY, [
            'id'    => (int) $client['id'],
            'nom'   => (string) $client['nom'],
            'email' => (string) $client['email'],
        ]);
    }

    public static function deconnecterClient(): void
    {
        Session::effacer(TZ_CLIENT_SESSION_KEY);
    }

    public static function exigerClient(): void
    {
        if (self::client() === null) {
            $_SESSION['tz_apres_connexion'] = Http::chemin();
            Http::redirection(Url::to('/compte/connexion'));
        }
    }
}
