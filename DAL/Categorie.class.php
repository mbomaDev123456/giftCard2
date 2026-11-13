<?php
/** Rayons, y compris les sous-rayons des dattes. */
class Categorie
{
    public static function parSlug(string $slug): ?array
    {
        return Db::un('SELECT * FROM tz_categorie WHERE slug = ?', [$slug]);
    }

    public static function parId(int $id): ?array
    {
        return Db::un('SELECT * FROM tz_categorie WHERE id = ?', [$id]);
    }

    /** Arborescence complète avec compteurs de produits publiés. */
    public static function arbre(): array
    {
        $lignes = Db::tous(
            "SELECT c.id, c.parent_id, c.nom, c.nom_ar, c.slug, c.description, c.image, c.tva_taux, c.position,
                    (SELECT COUNT(*) FROM tz_produit p WHERE p.categorie_id = c.id AND p.etat = 'publie') nb
               FROM tz_categorie c WHERE c.etat = 'publiee'
            ORDER BY c.position, c.nom"
        );
        $parId = [];
        foreach ($lignes as $ligne) {
            $ligne['enfants'] = [];
            $parId[(int) $ligne['id']] = $ligne;
        }
        $racines = [];
        foreach ($parId as $id => $ligne) {
            $parent = $ligne['parent_id'] === null ? null : (int) $ligne['parent_id'];
            if ($parent !== null && isset($parId[$parent])) {
                $parId[$parent]['enfants'][] = &$parId[$id];
            } else {
                $racines[] = &$parId[$id];
            }
        }
        return $racines;
    }

    public static function plats(): array
    {
        return Db::tous(
            "SELECT c.*, e.nom enseigne_nom,
                    (SELECT COUNT(*) FROM tz_produit p WHERE p.categorie_id = c.id AND p.etat = 'publie') nb
               FROM tz_categorie c LEFT JOIN tz_enseigne e ON e.id = c.enseigne_id
              ORDER BY c.parent_id IS NOT NULL, c.position, c.nom"
        );
    }

    public static function enregistrer(array $d, ?int $id = null): int
    {
        $champs = [
            'nom'      => trim((string) $d['nom']),
            'nom_ar'   => ($d['nom_ar'] ?? '') === '' ? null : $d['nom_ar'],
            'slug'     => trim((string) $d['slug']),
            'parent_id'=> $d['parent_id'] === '' || $d['parent_id'] === null ? null : (int) $d['parent_id'],
            'description' => ($d['description'] ?? '') === '' ? null : $d['description'],
            'image'    => ($d['image'] ?? '') === '' ? null : $d['image'],
            'tva_taux' => (int) ($d['tva_taux'] ?? 19000),
            'position' => (int) ($d['position'] ?? 0),
            'etat'     => (string) ($d['etat'] ?? 'publiee'),
        ];
        if ($id === null) {
            return Db::inserer('tz_categorie', $champs);
        }
        Db::modifier('tz_categorie', $champs, 'id = ?', [$id]);
        return $id;
    }

    public static function supprimer(int $id): void
    {
        // ON DELETE SET NULL protège les produits : ils se retrouvent sans rayon.
        Db::supprimer('tz_categorie', 'id = ?', [$id]);
    }

    /** Filtre « rayon » du catalogue : le slug attire aussi ses enfants. */
    public static function idsEnfants(int $id): array
    {
        return array_map(
            static fn(array $l): int => (int) $l['id'],
            Db::tous('SELECT id FROM tz_categorie WHERE id = ? OR parent_id = ?', [$id, $id])
        );
    }
}
