<?php
/** Format vendable : c'est lui qui porte le prix, le SKU et le stock. */
class Variante
{
    public static function parId(int $id): ?array
    {
        return Db::un(
            'SELECT v.*, p.enseigne_id, p.nom produit_nom, p.slug produit_slug, p.tva_taux,
                    p.etat produit_etat, p.image_principale
               FROM tz_variante v JOIN tz_produit p ON p.id = v.produit_id
              WHERE v.id = ?',
            [$id]
        );
    }

    public static function parSku(string $sku): ?array
    {
        return Db::un('SELECT v.*, p.enseigne_id, p.tva_taux, p.etat produit_etat
                         FROM tz_variante v JOIN tz_produit p ON p.id = v.produit_id
                        WHERE v.sku = ?', [$sku]);
    }

    public static function pourProduit(int $produitId, bool $visiblesSeules = true): array
    {
        $sql = 'SELECT id, sku, nom, details, prix, prix_barre, stock_quantite, stock_seuil, reserve_vente,
                       poids_grammes, code_barre, emplacement, position, visible
                  FROM tz_variante WHERE produit_id = ?';
        if ($visiblesSeules) {
            $sql .= ' AND visible = 1';
        }
        return Db::tous($sql . ' ORDER BY position, prix', [$produitId]);
    }

    public static function pourProduits(array $produitIds): array
    {
        if ($produitIds === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($produitIds), '?'));
        $groupes = [];
        foreach (Db::tous(
            "SELECT produit_id, id, sku, nom, details, prix, prix_barre, stock_quantite, reserve_vente, visible
               FROM tz_variante WHERE produit_id IN ($placeholders) AND visible = 1
            ORDER BY position, prix",
            array_map('intval', $produitIds)
        ) as $ligne) {
            $groupes[(int) $ligne['produit_id']][] = $ligne;
        }
        return $groupes;
    }

    public static function enregistrer(array $d, ?int $id = null): int
    {
        $champs = [
            'produit_id'    => (int) $d['produit_id'],
            'sku'           => trim((string) $d['sku']),
            'nom'           => trim((string) $d['nom']),
            'details'       => ($d['details'] ?? '') === '' ? null : $d['details'],
            'prix'          => Money::analyser((string) $d['prix']),
            'prix_barre'    => Money::analyser((string) ($d['prix_barre'] ?? '')),
            'prix_achat'    => Money::analyser((string) ($d['prix_achat'] ?? '')),
            'stock_seuil'   => (int) ($d['stock_seuil'] ?? 3),
            'stock_max'     => (int) ($d['stock_max'] ?? 0),
            'poids_grammes' => (int) ($d['poids_grammes'] ?? 0),
            'code_barre'    => ($d['code_barre'] ?? '') === '' ? null : $d['code_barre'],
            'emplacement'   => ($d['emplacement'] ?? '') === '' ? null : $d['emplacement'],
            'position'      => (int) ($d['position'] ?? 0),
            'visible'       => isset($d['visible']) ? 1 : 0,
        ];
        if ($id === null) {
            $champs['stock_quantite'] = (int) ($d['stock_quantite'] ?? 0);
            $id = Db::inserer('tz_variante', $champs);
            if ($champs['stock_quantite'] > 0) {
                StockMouvement::enregistrer([
                    'variante_id' => $id,
                    'type'        => 'reception',
                    'quantite'    => $champs['stock_quantite'],
                    'motif'       => 'Stock initial de la référence',
                    'cout_unitaire' => $champs['prix_achat'],
                ]);
            }
        } else {
            if (array_key_exists('stock_quantite', $d)) {
                $champs['stock_quantite'] = (int) $d['stock_quantite'];
            }
            Db::modifier('tz_variante', $champs, 'id = ?', [$id]);
        }
        Produit::rafraichirPrix((int) $champs['produit_id']);
        return $id;
    }

    public static function cacher(int $id, int $produitId): void
    {
        Db::modifier('tz_variante', ['visible' => 0], 'id = ?', [$id]);
        Produit::rafraichirPrix($produitId);
    }

    /** Unités réellement proposées à la vente. */
    public static function disponible(int $stock, int $reserve): int
    {
        return max(0, $stock - $reserve);
    }

    /**
     * Engage des unités pour une commande non payée, sans toucher au stock
     * physique. Faux si la référence ne peut plus fournir la quantité demandée.
     */
    public static function reserver(int $id, int $quantite): bool
    {
        $quantite = max(0, $quantite);
        if ($quantite === 0) {
            return true;
        }
        return Db::transaction(static function () use ($id, $quantite): bool {
            $ligne = Db::un('SELECT stock_quantite, reserve_vente FROM tz_variante WHERE id = ? FOR UPDATE', [$id]);
            if ($ligne === null
                || self::disponible((int) $ligne['stock_quantite'], (int) $ligne['reserve_vente']) < $quantite) {
                return false;
            }
            Db::modifier('tz_variante', ['reserve_vente' => 'reserve_vente + ' . $quantite],
                'id = ?', [$id], brutes: ['reserve_vente']);
            return true;
        });
    }

    /** Rend les unités engagées : paiement refusé, commande annulée ou vente soldée. */
    public static function liberer(int $id, int $quantite): void
    {
        $quantite = max(0, $quantite);
        if ($quantite === 0) {
            return;
        }
        Db::modifier('tz_variante', ['reserve_vente' => 'GREATEST(reserve_vente - ' . $quantite . ', 0)'],
            'id = ?', [$id], brutes: ['reserve_vente']);
    }

    public static function etatStock(array $variante): string
    {
        $restant = self::disponible((int) $variante['stock_quantite'], (int) ($variante['reserve_vente'] ?? 0));
        if ($restant <= 0) {
            return 'epuise';
        }
        return $restant <= (int) $variante['stock_seuil'] ? 'bientot' : 'en_stock';
    }

    /** @return array<int, array<string, mixed>> référentiels bas ou épuisés, pour le bandeau d'alerte. */
    public static function alertes(): array
    {
        return Db::tous(
            "SELECT v.id, v.sku, v.nom, v.stock_quantite, v.stock_seuil, v.reserve_vente, v.emplacement,
                    p.id produit_id, p.nom produit_nom, p.slug produit_slug, e.id enseigne_id, e.nom enseigne_nom
               FROM tz_variante v
               JOIN tz_produit p ON p.id = v.produit_id
               JOIN tz_enseigne e ON e.id = p.enseigne_id
              WHERE v.visible = 1 AND p.etat <> 'archive'
                AND v.stock_quantite - v.reserve_vente <= v.stock_seuil
           ORDER BY (v.stock_quantite - v.reserve_vente) ASC, e.nom, p.nom"
        );
    }

    public static function reapproSuggestion(): array
    {
        return Db::tous(
            "SELECT v.id, v.sku, v.nom, v.stock_quantite, v.stock_seuil, v.stock_max, v.prix_achat,
                    GREATEST(v.stock_max - v.stock_quantite, v.stock_seuil * 2) a_commander,
                    p.nom produit_nom, e.nom enseigne_nom, p.enseigne_id
               FROM tz_variante v
               JOIN tz_produit p ON p.id = v.produit_id
               JOIN tz_enseigne e ON e.id = p.enseigne_id
              WHERE v.visible = 1 AND v.stock_quantite - v.reserve_vente <= v.stock_seuil
           ORDER BY a_commander DESC"
        );
    }
}
