<?php
/** Journal des entrées/sorties. Toute modification de stock passe par ici. */
class StockMouvement
{
    public static function enregistrer(array $d): int
    {
        $quantite = (int) $d['quantite'];
        $id = Db::transaction(function () use ($d, $quantite) {
            $ligne = Db::un('SELECT id, stock_quantite FROM tz_variante WHERE id = ? FOR UPDATE', [(int) $d['variante_id']]);
            if ($ligne === null) {
                throw new InvalidArgumentException('Référence ' . $d['variante_id'] . ' inconnue.');
            }
            $apres = (int) $ligne['stock_quantite'] + $quantite;
            if ($apres < 0) {
                throw new RuntimeException('Stock insuffisant sur la référence ' . $ligne['id'] . '.');
            }
            Db::modifier('tz_variante', ['stock_quantite' => $apres], 'id = ?', [(int) $d['variante_id']]);
            return Db::inserer('tz_stock_mouvement', [
                'variante_id'    => (int) $d['variante_id'],
                'type'           => (string) $d['type'],
                'quantite'       => $quantite,
                'stock_apres'    => $apres,
                'motif'          => $d['motif'] ?? null,
                'commande_id'    => isset($d['commande_id']) && $d['commande_id'] !== '' ? (int) $d['commande_id'] : null,
                'utilisateur_id' => isset($d['utilisateur_id']) && $d['utilisateur_id'] !== '' ? (int) $d['utilisateur_id'] : null,
                'enseigne_id'    => isset($d['enseigne_id']) && $d['enseigne_id'] !== '' ? (int) $d['enseigne_id'] : null,
                'cout_unitaire'  => (int) ($d['cout_unitaire'] ?? 0),
            ]);
        });
        return (int) $id;
    }

    public static function dernierPourVariante(int $varianteId, int $limite = 20): array
    {
        return Db::tous(
            'SELECT m.*, u.login utilisateur_login, c.reference commande_reference
               FROM tz_stock_mouvement m
          LEFT JOIN tz_utilisateur u ON u.id = m.utilisateur_id
          LEFT JOIN tz_commande c ON c.id = m.commande_id
              WHERE m.variante_id = ?
           ORDER BY m.created_at DESC, m.id DESC LIMIT ' . (int) $limite,
            [$varianteId]
        );
    }

    public static function historique(array $f = []): array
    {
        $conditions = ['1=1'];
        $valeurs = [];
        if (!empty($f['type'])) {
            $conditions[] = 'm.type = ?';
            $valeurs[] = (string) $f['type'];
        }
        if (!empty($f['enseigne_id'])) {
            $conditions[] = 'p.enseigne_id = ?';
            $valeurs[] = (int) $f['enseigne_id'];
        }
        if (!empty($f['variante_id'])) {
            $conditions[] = 'm.variante_id = ?';
            $valeurs[] = (int) $f['variante_id'];
        }
        if (!empty($f['du']) || !empty($f['au'])) {
            if (!empty($f['du'])) {
                $conditions[] = 'm.created_at >= ?';
                $valeurs[] = $f['du'] . ' 00:00:00';
            }
            if (!empty($f['au'])) {
                $conditions[] = 'm.created_at <= ?';
                $valeurs[] = $f['au'] . ' 23:59:59';
            }
        }
        $where = implode(' AND ', $conditions);
        $page = max(1, (int) ($f['page'] ?? 1));
        $parPage = 40;
        return [
            'total'  => (int) Db::valeur("SELECT COUNT(*) FROM tz_stock_mouvement m
                                           JOIN tz_variante v ON v.id = m.variante_id
                                           JOIN tz_produit p ON p.id = v.produit_id WHERE $where", $valeurs),
            'lignes' => Db::tous(
                "SELECT m.*, v.sku, v.nom variante_nom, v.emplacement, p.id produit_id, p.nom produit_nom,
                        p.slug produit_slug, e.nom enseigne_nom
                   FROM tz_stock_mouvement m
                   JOIN tz_variante v ON v.id = m.variante_id
                   JOIN tz_produit p ON p.id = v.produit_id
                   JOIN tz_enseigne e ON e.id = p.enseigne_id
                  WHERE $where
                ORDER BY m.created_at DESC, m.id DESC
                   LIMIT $parPage OFFSET " . (($page - 1) * $parPage),
                $valeurs
            ),
        ];
    }

    /** Ce qui est sorti depuis l'ouverture, par produit : base des ratios de réassort. */
    public static function ventesParProduit(string $du, string $au): array
    {
        return Db::tous(
            'SELECT p.id, p.nom, p.slug, e.nom enseigne_nom,
                    SUM(ABS(m.quantite)) vendus,
                    SUM(ABS(m.quantite) * m.cout_unitaire) cout_engage
               FROM tz_stock_mouvement m
               JOIN tz_variante v ON v.id = m.variante_id
               JOIN tz_produit p ON p.id = v.produit_id
               JOIN tz_enseigne e ON e.id = p.enseigne_id
              WHERE m.type = ? AND m.created_at BETWEEN ? AND ?
           GROUP BY p.id, p.nom, p.slug, e.nom
           ORDER BY vendus DESC',
            ['vente', $du . ' 00:00:00', $au . ' 23:59:59']
        );
    }
}
