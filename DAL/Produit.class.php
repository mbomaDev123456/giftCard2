<?php
/** Produit : recherche à facettes, fiches, agrégats de vitrine. */
class Produit
{
    private const TRIS = [
        'pertinence' => 'p.vedette DESC, p.position ASC, p.id DESC',
        'nouveautes' => 'p.nouveaute DESC, p.created_at DESC',
        'prix_asc'   => 'p.prix_min ASC, p.id ASC',
        'prix_desc'  => 'p.prix_max DESC, p.id DESC',
        'populaire'  => 'p.vues DESC, p.note_moyenne DESC',
        'note'       => 'p.note_moyenne DESC, p.note_compte DESC',
        'nom'        => 'p.nom ASC',
    ];

    public static function parId(int $id): ?array
    {
        return Db::un('SELECT * FROM tz_produit WHERE id = ?', [$id]);
    }

    /** Fiche complète : maison, rayon, formats en stock,avis, suggestions. */
    public static function fiche(string $slug): ?array
    {
        $produit = Db::un(
            'SELECT p.*, e.nom enseigne_nom, e.slug enseigne_slug, e.couleur enseigne_couleur,
                    e.ville enseigne_ville, e.telephone enseigne_telephone,
                    c.nom categorie_nom, c.slug categorie_slug, c.tva_taux categorie_tva
               FROM tz_produit p
               JOIN tz_enseigne e ON e.id = p.enseigne_id
          LEFT JOIN tz_categorie c ON c.id = p.categorie_id
              WHERE p.slug = ?',
            [$slug]
        );
        if ($produit === null) {
            return null;
        }
        $produit['variantes'] = Variante::pourProduit((int) $produit['id']);
        $produit['avis']      = Avis::publies((int) $produit['id'], 6);
        $produit['associes']  = self::associes($produit);
        $produit['fil_ariane'] = self::filAriane($produit);
        return $produit;
    }

    /**
     * Recherche à facettes.
     * @param array $f filtres : maison, rayon, q, prix_min, prix_max, note, stock, label, tri, page, par_page, vedette, nouveaute
     * @return array{lignes:array,total:int,page:int,par_page:int,facettes:array,trie:string}
     */
    public static function rechercher(array $f = []): array
    {
        $conditions = ["p.etat = 'publie'"];
        $valeurs = [];

        if (!empty($f['maison'])) {
            $conditions[] = 'e.slug = ?';
            $valeurs[] = (string) $f['maison'];
        }
        if (!empty($f['enseigne_id'])) {
            $conditions[] = 'p.enseigne_id = ?';
            $valeurs[] = (int) $f['enseigne_id'];
        }
        if (!empty($f['rayon'])) {
            // Le rayon attire aussi ses sous-rayons.
            $conditions[] = '(c.slug = ? OR cp.slug = ?)';
            $valeurs[] = (string) $f['rayon'];
            $valeurs[] = (string) $f['rayon'];
        }
        if (isset($f['prix_min']) && $f['prix_min'] !== '') {
            $conditions[] = 'p.prix_max >= ?';
            $valeurs[] = Money::analyser((string) $f['prix_min']);
        }
        if (isset($f['prix_max']) && $f['prix_max'] !== '') {
            $conditions[] = 'p.prix_min <= ?';
            $valeurs[] = Money::analyser((string) $f['prix_max']);
        }
        if (!empty($f['q'])) {
            $mots = preg_split('/\s+/', trim((string) $f['q'])) ?: [];
            foreach ($mots as $mot) {
                if (mb_strlen($mot) < 2) {
                    continue;
                }
                $conditions[] = '(p.nom LIKE ? OR p.resume LIKE ? OR p.description LIKE ? OR e.nom LIKE ?)';
                $like = '%' . $mot . '%';
                array_push($valeurs, $like, $like, $like, $like);
            }
        }
        if (!empty($f['note'])) {
            $conditions[] = 'p.note_moyenne >= ?';
            $valeurs[] = (float) $f['note'];
        }
        if (!empty($f['en_stock'])) {
            $conditions[] = 'EXISTS (SELECT 1 FROM tz_variante v WHERE v.produit_id = p.id
                                    AND v.visible = 1 AND v.stock_quantite > 0)';
        }
        if (!empty($f['en_promo'])) {
            $conditions[] = 'EXISTS (SELECT 1 FROM tz_variante v WHERE v.produit_id = p.id
                                    AND v.prix_barre > v.prix)';
        }
        if (!empty($f['label'])) {
            $conditions[] = 'p.label = ?';
            $valeurs[] = (string) $f['label'];
        }
        if (!empty($f['vedette'])) {
            $conditions[] = 'p.vedette = 1';
        }
        if (!empty($f['nouveaute'])) {
            $conditions[] = 'p.nouveaute = 1';
        }
        if (!empty($f['savoir_faire'])) {
            $conditions[] = 'p.savoir_faire = ?';
            $valeurs[] = (string) $f['savoir_faire'];
        }

        $where = implode(' AND ', $conditions);
        $tri = self::TRIS[$f['tri'] ?? 'pertinence'] ?? self::TRIS['pertinence'];
        $parPage = max(6, min(48, (int) ($f['par_page'] ?? ParametresApp::reglure('nb_produits_par_page', 12))));
        $page = max(1, (int) ($f['page'] ?? 1));

        $total = (int) Db::valeur(
            "SELECT COUNT(*) FROM tz_produit p
               JOIN tz_enseigne e ON e.id = p.enseigne_id
          LEFT JOIN tz_categorie c ON c.id = p.categorie_id
          LEFT JOIN tz_categorie cp ON cp.id = c.parent_id
             WHERE $where",
            $valeurs
        );

        $lignes = Db::tous(
            "SELECT p.id, p.nom, p.nom_ar, p.slug, p.resume, p.origine, p.matiere, p.label, p.image_principale,
                    p.prix_min, p.prix_max, p.tva_taux, p.vedette, p.nouveaute, p.note_moyenne, p.note_compte,
                    p.savoir_faire, p.poids_grammes,
                    e.nom enseigne_nom, e.slug enseigne_slug, e.couleur enseigne_couleur,
                    c.nom categorie_nom, c.slug categorie_slug,
                    (SELECT COALESCE(SUM(v.stock_quantite), 0) FROM tz_variante v
                      WHERE v.produit_id = p.id AND v.visible = 1) stock_total,
                    (SELECT MIN(v.prix) FROM tz_variante v WHERE v.produit_id = p.id AND v.visible = 1) variante_prix_mini,
                    (SELECT MAX(v.prix_barre) FROM tz_variante v WHERE v.produit_id = p.id AND v.visible = 1) variante_barre_max
               FROM tz_produit p
               JOIN tz_enseigne e ON e.id = p.enseigne_id
          LEFT JOIN tz_categorie c ON c.id = p.categorie_id
          LEFT JOIN tz_categorie cp ON cp.id = c.parent_id
              WHERE $where
           ORDER BY $tri
              LIMIT $parPage OFFSET " . (($page - 1) * $parPage),
            $valeurs
        );

        return [
            'lignes'   => $lignes,
            'total'    => $total,
            'page'     => $page,
            'par_page' => $parPage,
            'trie'     => (string) ($f['tri'] ?? 'pertinence'),
            'facettes' => self::facettes($f),
        ];
    }

    /** Compteurs de facettes, calculés sur le reste des filtres (prix hors mis). */
    private static function facettes(array $f): array
    {
        $conditions = ["p.etat = 'publie'"];
        $valeurs = [];
        if (!empty($f['maison'])) {
            $conditions[] = 'e.slug = ?';
            $valeurs[] = (string) $f['maison'];
        }
        if (!empty($f['enseigne_id'])) {
            $conditions[] = 'p.enseigne_id = ?';
            $valeurs[] = (int) $f['enseigne_id'];
        }
        $where = implode(' AND ', $conditions);

        return [
            'rayons' => Db::tous(
                "SELECT c.slug, c.nom, COUNT(p.id) nb
                   FROM tz_produit p
                   JOIN tz_categorie c ON c.id = p.categorie_id
                   JOIN tz_enseigne e ON e.id = p.enseigne_id
                  WHERE $where
               GROUP BY c.id, c.slug, c.nom
               ORDER BY c.position",
                $valeurs
            ),
            'maisons' => Db::tous(
                "SELECT e.slug, e.nom, e.couleur, COUNT(p.id) nb
                   FROM tz_produit p
                   JOIN tz_enseigne e ON e.id = p.enseigne_id
                  WHERE $where
               GROUP BY e.id, e.slug, e.nom, e.couleur
               ORDER BY e.position",
                $valeurs
            ),
            'prix' => Db::un(
                "SELECT COALESCE(MIN(p.prix_min), 0) mini, COALESCE(MAX(p.prix_max), 0) maxi
                   FROM tz_produit p JOIN tz_enseigne e ON e.id = p.enseigne_id
                  WHERE $where",
                $valeurs
            ) ?? ['mini' => 0, 'maxi' => 0],
            'savoir_faire' => Db::tous(
                "SELECT p.savoir_faire nom, COUNT(*) nb
                   FROM tz_produit p JOIN tz_enseigne e ON e.id = p.enseigne_id
                  WHERE $where AND p.savoir_faire IS NOT NULL
               GROUP BY p.savoir_faire ORDER BY nb DESC",
                $valeurs
            ),
        ];
    }

    public static function vedettes(int $limite = 8, ?int $enseigneId = null): array
    {
        $sql = "SELECT p.id, p.nom, p.slug, p.resume, p.image_principale, p.prix_min, p.prix_max,
                       p.note_moyenne, p.note_compte, p.label, p.tva_taux,
                       e.nom enseigne_nom, e.slug enseigne_slug, e.couleur enseigne_couleur,
                       (SELECT COALESCE(SUM(v.stock_quantite),0) FROM tz_variante v WHERE v.produit_id = p.id) stock_total,
                       (SELECT MIN(v.prix) FROM tz_variante v WHERE v.produit_id = p.id AND v.visible = 1) variante_prix_mini,
                       (SELECT MAX(v.prix_barre) FROM tz_variante v WHERE v.produit_id = p.id) variante_barre_max
                  FROM tz_produit p JOIN tz_enseigne e ON e.id = p.enseigne_id
                 WHERE p.etat = 'publie' AND p.vedette = 1";
        $params = [];
        if ($enseigneId !== null) {
            $sql .= ' AND p.enseigne_id = ?';
            $params[] = $enseigneId;
        }
        $sql .= ' ORDER BY p.position, p.id DESC LIMIT ' . (int) $limite;
        return Db::tous($sql, $params);
    }

    public static function nouveautes(int $limite = 6): array
    {
        return Db::tous(
            "SELECT p.id, p.nom, p.slug, p.resume, p.image_principale, p.prix_min, p.created_at,
                    e.nom enseigne_nom, e.slug enseigne_slug, e.couleur enseigne_couleur,
                    (SELECT MIN(v.prix) FROM tz_variante v WHERE v.produit_id = p.id AND v.visible = 1) variante_prix_mini
               FROM tz_produit p JOIN tz_enseigne e ON e.id = p.enseigne_id
              WHERE p.etat = 'publie'
           ORDER BY p.created_at DESC, p.id DESC LIMIT " . (int) $limite
        );
    }

    /** Produits de la même maison ou du même rayon, pour « on l'achète avec ». */
    private static function associes(array $produit): array
    {
        return Db::tous(
            "SELECT p.id, p.nom, p.slug, p.resume, p.image_principale, p.prix_min,
                    e.nom enseigne_nom, e.slug enseigne_slug,
                    (SELECT MIN(v.prix) FROM tz_variante v WHERE v.produit_id = p.id AND v.visible = 1) variante_prix_mini,
                    (SELECT COALESCE(SUM(v.stock_quantite),0) FROM tz_variante v WHERE v.produit_id = p.id) stock_total
               FROM tz_produit p JOIN tz_enseigne e ON e.id = p.enseigne_id
              WHERE p.etat = 'publie' AND p.id <> ?
                AND (p.categorie_id = ? OR p.enseigne_id = ?)
           ORDER BY (p.categorie_id = ?) DESC, p.vedette DESC, p.position
              LIMIT 4",
            [(int) $produit['id'], $produit['categorie_id'], (int) $produit['enseigne_id'], $produit['categorie_id']]
        );
    }

    private static function filAriane(array $produit): array
    {
        $fil = [['titre' => 'Accueil', 'url' => Url::to('/')]];
        if (!empty($produit['enseigne_slug'])) {
            $fil[] = ['titre' => $produit['enseigne_nom'], 'url' => Url::to('/boutique/' . $produit['enseigne_slug'])];
        }
        if (!empty($produit['categorie_slug'])) {
            $fil[] = ['titre' => $produit['categorie_nom'], 'url' => Url::to('/catalogue', ['rayon' => $produit['categorie_slug']])];
        }
        $fil[] = ['titre' => $produit['nom'], 'url' => null];
        return $fil;
    }

    public static function incrementerVues(int $id): void
    {
        Db::modifier('tz_produit', ['vues' => 'vues + 1'], 'id = ?', [$id], brutes: ['vues']);
    }

    /** Recalcule prix_min / prix_max depuis les variantes visibles. */
    public static function rafraichirPrix(int $produitId): void
    {
        $bornes = Db::un(
            'SELECT COALESCE(MIN(prix), 0) mini, COALESCE(MAX(prix), 0) maxi
               FROM tz_variante WHERE produit_id = ? AND visible = 1',
            [$produitId]
        );
        Db::modifier('tz_produit', [
            'prix_min' => (int) ($bornes['mini'] ?? 0),
            'prix_max' => (int) ($bornes['maxi'] ?? 0),
        ], 'id = ?', [$produitId]);
    }

    public static function enregistrer(array $d, ?int $id = null): int
    {
        $champs = [];
        foreach (['enseigne_id', 'categorie_id', 'nom', 'nom_ar', 'slug', 'resume', 'description', 'conseils',
                  'origine', 'matiere', 'savoir_faire', 'image_principale', 'dimensions', 'certifications',
                  'label', 'position', 'etat'] as $cle) {
            if (array_key_exists($cle, $d)) {
                $champs[$cle] = in_array($cle, ['categorie_id'], true)
                    ? ($d[$cle] === '' || $d[$cle] === null ? null : (int) $d[$cle])
                    : ($d[$cle] === '' ? null : $d[$cle]);
            }
        }
        foreach (['tva_taux' => 19000, 'prix_achat_bpo' => 0, 'poids_grammes' => 0, 'peremption_jours' => 0] as $cle => $defaut) {
            if (array_key_exists($cle, $d)) {
                $champs[$cle] = $d[$cle] === '' ? $defaut : (int) $d[$cle];
            }
        }
        foreach (['vedette', 'nouveaute'] as $cle) {
            if (array_key_exists($cle, $d)) {
                $champs[$cle] = $d[$cle] ? 1 : 0;
            }
        }
        if (!empty($d['galerie'])) {
            $champs['galerie'] = json_encode(array_values(array_filter((array) $d['galerie'])), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        if ($id === null) {
            $champs['created_at'] = date('Y-m-d H:i:s');
            return Db::inserer('tz_produit', $champs);
        }
        Db::modifier('tz_produit', $champs, 'id = ?', [$id]);
        return $id;
    }

    /** @return array<int, array<string, mixed>> */
    public static function administrer(array $f = []): array
    {
        $conditions = ['1=1'];
        $valeurs = [];
        if (!empty($f['q'])) {
            $conditions[] = '(p.nom LIKE ? OR p.slug LIKE ? OR v.sku LIKE ?)';
            $like = '%' . $f['q'] . '%';
            $valeurs[] = $like; $valeurs[] = $like; $valeurs[] = $like;
        }
        if (!empty($f['enseigne_id'])) {
            $conditions[] = 'p.enseigne_id = ?';
            $valeurs[] = (int) $f['enseigne_id'];
        }
        if (!empty($f['etat'])) {
            $conditions[] = 'p.etat = ?';
            $valeurs[] = (string) $f['etat'];
        }
        if (($f['stock_bas'] ?? '') === '1') {
            $conditions[] = '(SELECT COALESCE(SUM(v2.stock_quantite),0) FROM tz_variante v2 WHERE v2.produit_id = p.id)
                             <= (SELECT COALESCE(MIN(v2.stock_seuil),0) FROM tz_variante v2 WHERE v2.produit_id = p.id)';
        }
        $where = implode(' AND ', $conditions);
        $page = max(1, (int) ($f['page'] ?? 1));
        $parPage = 25;
        return [
            'total' => (int) Db::valeur("SELECT COUNT(DISTINCT p.id) FROM tz_produit p
                                          JOIN tz_enseigne e ON e.id = p.enseigne_id
                                     LEFT JOIN tz_variante v ON v.produit_id = p.id WHERE $where", $valeurs),
            'lignes' => Db::tous(
                "SELECT p.*, e.nom enseigne_nom, e.slug enseigne_slug, c.nom categorie_nom,
                        (SELECT COUNT(*) FROM tz_variante v WHERE v.produit_id = p.id) nb_variantes,
                        (SELECT COALESCE(SUM(v.stock_quantite),0) FROM tz_variante v WHERE v.produit_id = p.id) stock_total
                   FROM tz_produit p
                   JOIN tz_enseigne e ON e.id = p.enseigne_id
              LEFT JOIN tz_categorie c ON c.id = p.categorie_id
                  WHERE $where
                GROUP BY p.id
                ORDER BY p.updated_at DESC
                   LIMIT $parPage OFFSET " . (($page - 1) * $parPage),
                $valeurs
            ),
        ];
    }
}
