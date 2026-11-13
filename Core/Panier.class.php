<?php
/** Panier stocké en session, avec jeton de reprise et sauvegarde pour la relance. */
class Panier
{
    private const CLE_TOKEN = 'tz_panier_token';

    public static function items(): array
    {
        $items = Session::lire(TZ_CART_SESSION_KEY, []);
        return is_array($items) ? $items : [];
    }

    public static function code(): string
    {
        return (string) Session::lire('tz_panier_code', '');
    }

    public static function transporterId(): ?int
    {
        $id = Session::lire('tz_panier_transporteur');
        return $id === null ? null : (int) $id;
    }

    public static function pointRelais(): string
    {
        return (string) Session::lire('tz_panier_relais', '');
    }

    public static function dateSouhaitee(): ?string
    {
        return Session::lire('tz_panier_date');
    }

    public static function emballage(): bool
    {
        return (bool) Session::lire('tz_panier_emballage', false);
    }

    public static function message(): string
    {
        return (string) Session::lire('tz_panier_message', '');
    }

    public static function token(): string
    {
        $token = Session::lire(self::CLE_TOKEN);
        if (!is_string($token) || strlen($token) !== 32) {
            $token = bin2hex(random_bytes(16));
            Session::ecrire(self::CLE_TOKEN, $token);
        }
        return $token;
    }

    public static function ajouter(int $varianteId, int $quantite = 1): array
    {
        $quantite = max(1, $quantite);
        $items = self::items();
        $trouve = self::index($items, $varianteId);
        if ($trouve === null) {
            $items[] = ['variante_id' => $varianteId, 'quantite' => $quantite];
        } else {
            $items[$trouve]['quantite'] += $quantite;
        }
        return self::ecrire($items);
    }

    public static function fixerQuantite(int $varianteId, int $quantite): array
    {
        $items = self::items();
        $trouve = self::index($items, $varianteId);
        if ($quantite <= 0) {
            if ($trouve !== null) {
                unset($items[$trouve]);
            }
            return self::ecrire(array_values($items));
        }
        if ($trouve === null) {
            return self::ecrire($items);
        }
        $items[$trouve]['quantite'] = $quantite;
        return self::ecrire($items);
    }

    public static function supprimer(int $varianteId): array
    {
        $items = array_values(array_filter(
            self::items(),
            static fn(array $i): bool => (int) $i['variante_id'] !== $varianteId
        ));
        return self::ecrire($items);
    }

    public static function vider(): void
    {
        Session::ecrire(TZ_CART_SESSION_KEY, []);
        Session::effacer('tz_panier_code');
        Session::effacer('tz_panier_emballage');
        Session::effacer('tz_panier_message');
    }

    public static function appliquerCode(string $code): void
    {
        Session::ecrire('tz_panier_code', mb_strtoupper(trim($code)));
    }

    public static function choisirTransporteur(int $id, string $relais = '', ?string $date = null): void
    {
        Session::ecrire('tz_panier_transporteur', $id);
        Session::ecrire('tz_panier_relais', $relais);
        if ($date === null) {
            Session::effacer('tz_panier_date');
        } else {
            Session::ecrire('tz_panier_date', $date);
        }
    }

    public static function choisirEmballage(bool $cadeau, string $message = ''): void
    {
        Session::ecrire('tz_panier_emballage', $cadeau);
        Session::ecrire('tz_panier_message', $message);
    }

    /** @return int nombre d'unités dans le panier */
    public static function articles(): int
    {
        return array_sum(array_map(static fn(array $i): int => (int) $i['quantite'], self::items()));
    }

    public static function estVide(): bool
    {
        return self::items() === [];
    }

    /**
     * Le panier fusionne avec celui d'un client qui se connecte, et hérite
     * de son adresse par défaut.
     */
    public static function absorberClient(int $clientId): void
    {
        $stocke = Db::un('SELECT contenu FROM tz_session_panier WHERE client_id = ? ORDER BY updated_at DESC LIMIT 1', [$clientId]);
        if ($stocke !== null) {
            $autres = json_decode((string) $stocke['contenu'], true) ?: [];
            foreach ($autres as $autre) {
                if (isset($autre['variante_id'])) {
                    self::ajouter((int) $autre['variante_id'], (int) ($autre['qte'] ?? 1));
                }
            }
        }
        $lignes = self::items();
        $parId = [];
        foreach ($lignes as $ligne) {
            $parId[(int) $ligne['variante_id']] = (int) $ligne['quantite'];
        }
        self::persister($clientId, $parId);
    }

    /** Sauvegarde en base pour la relance de panier abandonné. */
    public static function persister(?int $clientId = null, ?array $parId = null, int $articles = 0, int $montant = 0): void
    {
        $parId ??= array_combine(
            array_map(static fn(array $i): int => (int) $i['variante_id'], self::items()),
            array_map(static fn(array $i): int => (int) $i['quantite'], self::items())
        );
        $contenu = [];
        foreach ($parId as $varianteId => $quantite) {
            $contenu[] = ['variante_id' => (int) $varianteId, 'qte' => (int) $quantite];
        }
        $email = Auth::client()['email'] ?? null;
        $existant = Db::un('SELECT id FROM tz_session_panier WHERE token = ?', [self::token()]);
        $donnees = [
            'token'     => self::token(),
            'client_id' => $clientId ?? Auth::idClient(),
            'contenu'   => json_encode($contenu, JSON_UNESCAPED_SLASHES),
            'email'     => $email,
            'articles'  => $articles ?: self::articles(),
            'montant'   => $montant,
        ];
        if ($contenu === []) {
            if ($existant !== null) {
                Db::supprimer('tz_session_panier', 'token = ?', [self::token()]);
            }
            return;
        }
        if ($existant === null) {
            Db::inserer('tz_session_panier', $donnees);
            return;
        }
        Db::modifier('tz_session_panier', array_diff_key($donnees, ['token' => 1, 'client_id' => 1]), 'token = ?', [self::token()]);
    }

    /** Paniers abandonnés à relancer (articles > 0, pas de commande dans les 48 h). */
    public static function abandonnes(int $apresHeures = 24): array
    {
        return Db::tous(
            'SELECT s.*, c.prenom, c.nom, c.email email_client, c.telephone
               FROM tz_session_panier s
          LEFT JOIN tz_client c ON c.id = s.client_id
              WHERE s.updated_at < DATE_SUB(NOW(), INTERVAL ' . (int) $apresHeures . ' HOUR)
                AND s.relance_envoyee = 0 AND s.articles > 0
           ORDER BY s.updated_at DESC LIMIT 100'
        );
    }

    public static function marquerRelance(int $id): void
    {
        Db::modifier('tz_session_panier', ['relance_envoyee' => 1], 'id = ?', [$id]);
    }

    private static function ecrire(array $items): array
    {
        $items = array_values(array_filter(
            $items,
            static fn(array $i): bool => isset($i['variante_id']) && (int) $i['quantite'] > 0
        ));
        Session::ecrire(TZ_CART_SESSION_KEY, $items);
        self::persister();
        return $items;
    }

    private static function index(array $items, int $varianteId): ?int
    {
        foreach ($items as $position => $item) {
            if ((int) ($item['variante_id'] ?? 0) === $varianteId) {
                return (int) $position;
            }
        }
        return null;
    }
}
