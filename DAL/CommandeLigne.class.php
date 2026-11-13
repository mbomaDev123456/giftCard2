<?php
/**
 * Lignes de commande : uniquement les agrégats.
 * La lecture d'une commande passe par Commande::lignes(), l'écriture par Commande::creer().
 */
class CommandeLigne
{
    /** Statuts où la marchandise est considérée comme vendue. */
    public const VENDUES = "('payee','preparation','controle_qualite','expediee','livree','rembourse_partiel')";

    /** @return array<int, array<string, mixed>> */
    public static function meilleuresVentes(int $jours = 90, int $limite = 12, ?int $enseigneId = null): array
    {
        $jours = max(1, min(730, $jours));
        $limite = max(1, min(100, $limite));
        $conditions = ["c.statut IN " . self::VENDUES, 'c.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)'];
        $params = [$jours];
        if ($enseigneId !== null) {
            $conditions[] = 'l.enseigne_id = ?';
            $params[] = $enseigneId;
        }
        return Db::tous(
            'SELECT l.produit_id, l.designation, l.image, p.slug produit_slug, e.nom enseigne_nom,
                    SUM(l.quantite) quantite,
                    SUM(l.total_ttc - l.remise_ligne) net,
                    SUM(l.total_ht - l.cout_achat) marge
               FROM tz_commande_ligne l
               JOIN tz_commande c ON c.id = l.commande_id
               JOIN tz_produit p ON p.id = l.produit_id
               JOIN tz_enseigne e ON e.id = l.enseigne_id
              WHERE ' . implode(' AND ', $conditions) . '
           GROUP BY l.produit_id, l.designation, l.image, p.slug, e.nom
           ORDER BY quantite DESC, net DESC
           LIMIT ' . $limite,
            $params
        );
    }

    /** « X vendus » de la fiche produit + garde-fou avant l'affichage d'un avis. */
    public static function vendusParProduit(int $produitId): int
    {
        return (int) Db::valeur(
            'SELECT COALESCE(SUM(l.quantite), 0) FROM tz_commande_ligne l
              JOIN tz_commande c ON c.id = l.commande_id
             WHERE l.produit_id = ? AND c.statut IN ' . self::VENDUES,
            [$produitId]
        );
    }

    /** Achat le plus récent d'un client sur ce produit : sert à vérifier un avis. */
    public static function achatClient(int $clientId, int $produitId): ?array
    {
        return Db::un(
            'SELECT c.id commande_id, c.reference, c.created_at, l.quantite
               FROM tz_commande_ligne l JOIN tz_commande c ON c.id = l.commande_id
              WHERE c.client_id = ? AND l.produit_id = ? AND c.statut IN ' . self::VENDUES . '
           ORDER BY c.created_at DESC LIMIT 1',
            [$clientId, $produitId]
        );
    }

    /** Ce qu'une maison a réellement facturé sur une période (base du reversement). */
    public static function parEnseigne(int $enseigneId, string $depuis, string $jusqu): array
    {
        return Db::un(
            "SELECT COUNT(DISTINCT l.commande_id) commandes,
                    COALESCE(SUM(l.quantite), 0) articles,
                    COALESCE(SUM(l.total_ttc - l.remise_ligne), 0) net,
                    COALESCE(SUM(l.total_ht), 0) ht,
                    COALESCE(SUM(l.total_tva), 0) tva,
                    COALESCE(SUM(l.cout_achat), 0) revient
               FROM tz_commande_ligne l JOIN tz_commande c ON c.id = l.commande_id
              WHERE l.enseigne_id = ? AND c.statut IN " . self::VENDUES . '
                AND c.created_at BETWEEN ? AND ?',
            [$enseigneId, $depuis . ' 00:00:00', $jusqu . ' 23:59:59']
        ) ?? ['commandes' => 0, 'articles' => 0, 'net' => 0, 'ht' => 0, 'tva' => 0, 'revient' => 0];
    }
}
