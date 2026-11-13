<?php
/** Vitrine des maisons : sélection, en-tête de marque, agrégats. */
class Enseigne
{
    public static function parId(int $id): ?array
    {
        return Db::un('SELECT * FROM tz_enseigne WHERE id = ?', [$id]);
    }

    public static function parSlug(string $slug): ?array
    {
        return Db::un('SELECT * FROM tz_enseigne WHERE slug = ? AND etat = ?', [$slug, 'active']);
    }

    /** @return array<int, array<string, mixed>> */
    public static function actives(bool $avecChiffres = false): array
    {
        $colonnes = 'e.id, e.nom, e.slug, e.slogan, e.description, e.logo, e.banniere, e.couleur,
                    e.couleur_secondaire, e.ville, e.telephone, e.email, e.position, e.vedette,
                    e.meta_titre, e.meta_description';
        if (!$avecChiffres) {
            return Db::tous("SELECT $colonnes FROM tz_enseigne e WHERE e.etat = 'active'
                             ORDER BY e.position, e.nom");
        }
        return Db::tous("SELECT $colonnes,
                   (SELECT COUNT(*) FROM tz_produit p WHERE p.enseigne_id = e.id AND p.etat = 'publie') nb_produits,
                   (SELECT COALESCE(MIN(p.prix_min), 0) FROM tz_produit p WHERE p.enseigne_id = e.id AND p.etat = 'publie') prix_min,
                   (SELECT COALESCE(AVG(a.note), 0) FROM tz_avis a JOIN tz_produit p ON p.id = a.produit_id
                     WHERE p.enseigne_id = e.id AND a.statut = 'publie') note_moyenne
              FROM tz_enseigne e WHERE e.etat = 'active' ORDER BY e.position, e.nom");
    }

    public static function enregistrer(array $donnees, ?int $id = null): int
    {
        $champ = self::prepares($donnees);
        if ($id === null) {
            return Db::inserer('tz_enseigne', $champ);
        }
        Db::modifier('tz_enseigne', $champ, 'id = ?', [$id]);
        return $id;
    }

    public static function etat(int $id, string $etat): void
    {
        Db::modifier('tz_enseigne', ['etat' => $etat], 'id = ?', [$id]);
    }

    private static function prepares(array $d): array
    {
        $sorties = [];
        $liste = ['nom', 'slug', 'slogan', 'description', 'histoire', 'logo', 'banniere', 'couleur',
                  'couleur_secondaire', 'ville', 'telephone', 'email', 'rib', 'patente',
                  'commission_bpo', 'position', 'meta_titre', 'meta_description', 'etat'];
        foreach ($liste as $cle) {
            if (array_key_exists($cle, $d)) {
                $sorties[$cle] = $d[$cle] === '' ? null : $d[$cle];
            }
        }
        $sorties['vedette'] = isset($d['vedette']) ? 1 : 0;
        $sorties['commission_bpo'] = (int) ($d['commission_bpo'] ?? 0);
        $sorties['position'] = (int) ($d['position'] ?? 0);
        return $sorties;
    }
}
