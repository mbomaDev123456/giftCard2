<?php
/** Transporteurs et calcul du frais de port. */
class Transporteur
{
    public static function tous(): array
    {
        return Db::tous('SELECT * FROM tz_transporteur ORDER BY type, delai_jours, nom');
    }

    public static function parId(int $id): ?array
    {
        return Db::un('SELECT * FROM tz_transporteur WHERE id = ?', [$id]);
    }

    /**
     * Frais de port d'une commande, avant remise « port offert ».
     * @param array $transporteur
     * @param int $poidsGrammes
     * @param int $sousTotal millimes
     * @param string $ville
     */
    public static function frais(array $transporteur, int $poidsGrammes, int $sousTotal, string $ville = ''): int
    {
        if ($transporteur['type'] === 'retait') {
            return 0;
        }
        $base = (int) $transporteur['tarif_base'];
        $kg = max(0, $poidsGrammes - (int) $transporteur['poids_inclus']);
        $supplement = (int) ceil($kg / 1000) * (int) $transporteur['tarif_kg'];
        return max(0, $base + $supplement);
    }

    /** Transporteurs desservant la ville (zones JSON, "*" = tout le pays). */
    public static function pour(string $ville): array
    {
        return array_values(array_filter(self::tous(), static fn(array $t): bool => self::dessert($t, $ville)));
    }

    /** Ce transporteur accepte-t-il cette ville ? Une zone vide vaut tout le territoire. */
    public static function dessert(array $transporteur, string $ville): bool
    {
        $zones = json_decode((string) ($transporteur['zones'] ?? '[]'), true) ?: [];
        if ($zones === [] || in_array('*', $zones, true)) {
            return true;
        }
        $villes = array_map(static fn($z): string => mb_strtolower(trim((string) $z)), $zones);
        return in_array(mb_strtolower(trim($ville)), $villes, true);
    }

    public static function enregistrer(array $d, ?int $id = null): int
    {
        $champs = [
            'nom'          => trim((string) $d['nom']),
            'type'         => (string) ($d['type'] ?? 'domicile'),
            'zones'        => json_encode(array_values(array_filter((array) ($d['zones'] ?? []))), JSON_UNESCAPED_UNICODE),
            'delai_jours'  => (int) ($d['delai_jours'] ?? 3),
            'tarif_base'   => Money::analyser((string) ($d['tarif_base'] ?? '')),
            'tarif_kg'     => Money::analyser((string) ($d['tarif_kg'] ?? '')),
            'poids_inclus' => (int) ($d['poids_inclus'] ?? 5000),
            'suivi_url'    => ($d['suivi_url'] ?? '') === '' ? null : $d['suivi_url'],
        ];
        if ($id === null) {
            return Db::inserer('tz_transporteur', $champs);
        }
        Db::modifier('tz_transporteur', $champs, 'id = ?', [$id]);
        return $id;
    }
}
