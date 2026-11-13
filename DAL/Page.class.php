<?php
/** Pages éditoriales : histoire, guide de la datte, livraison, CGV, contact. */
class Page
{
    public static function parSlug(string $slug): ?array
    {
        return Db::un('SELECT * FROM tz_page WHERE slug = ? AND etat = ?', [$slug, 'publiee']);
    }

    public static function menu(): array
    {
        return Db::tous("SELECT slug, titre FROM tz_page WHERE dans_menu = 1 AND etat = 'publiee'
                         ORDER BY position");
    }

    public static function toutes(): array
    {
        return Db::tous('SELECT * FROM tz_page ORDER BY position, id');
    }

    public static function enregistrer(array $d, ?int $id = null): int
    {
        $champs = [];
        foreach (['slug', 'titre', 'titre_ar', 'corps', 'corps_ar', 'visuel', 'meta_description', 'etat'] as $cle) {
            if (array_key_exists($cle, $d)) {
                $champs[$cle] = $d[$cle] === '' ? null : $d[$cle];
            }
        }
        $champs['position'] = (int) ($d['position'] ?? 0);
        $champs['dans_menu'] = isset($d['dans_menu']) ? 1 : 0;
        if ($id === null) {
            return Db::inserer('tz_page', $champs);
        }
        Db::modifier('tz_page', $champs, 'id = ?', [$id]);
        return $id;
    }
}
