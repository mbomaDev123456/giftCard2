<?php
/** Remboursements : demande, validation, exécution (le remboursement bancaire lui-même est manuel côté GPG). */
class Remboursement
{
    public static function parId(int $id): ?array
    {
        return Db::un(
            'SELECT r.*, c.reference, c.total_ttc, p.order_id, p.statut paiement_statut
               FROM tz_remboursement r
               JOIN tz_commande c ON c.id = r.commande_id
               LEFT JOIN tz_paiement p ON p.id = r.paiement_id
              WHERE r.id = ?',
            [$id]
        );
    }

    public static function pourCommande(int $commandeId): array
    {
        return Db::tous('SELECT * FROM tz_remboursement WHERE commande_id = ? ORDER BY id', [$commandeId]);
    }

    /** Déjà remboursé sur cette commande, toutes demandes confondues. */
    public static function totalPourCommande(int $commandeId, array $statuts = ['execute']): int
    {
        $placeholders = implode(',', array_fill(0, count($statuts), '?'));
        return (int) (Db::valeur(
            "SELECT COALESCE(SUM(montant), 0) FROM tz_remboursement
              WHERE commande_id = ? AND statut IN ($placeholders)",
            array_merge([$commandeId], $statuts)
        ) ?? 0);
    }

    public static function creer(array $donnees, ?int $utilisateurId = null): int
    {
        $id = Db::inserer('tz_remboursement', [
            'commande_id'    => (int) $donnees['commande_id'],
            'paiement_id'    => isset($donnees['paiement_id']) ? (int) $donnees['paiement_id'] : null,
            'montant'        => (int) $donnees['montant'],
            'motif'          => ($donnees['motif'] ?? '') === '' ? null : $donnees['motif'],
            'statut'         => $donnees['statut'] ?? 'demande',
            'utilisateur_id' => $utilisateurId,
            'facture_id'     => $donnees['facture_id'] ?? null,
        ]);
        ActionJournal::noter('remboursement_cree', 'remboursement', $id, [
            'commande' => $donnees['commande_id'], 'montant' => (int) $donnees['montant'],
        ]);
        return $id;
    }

    public static function statut(int $id, string $statut, ?int $factureId = null): bool
    {
        $autorises = ['demande', 'accepte', 'refuse', 'execute'];
        if (!in_array($statut, $autorises, true)) {
            return false;
        }
        $donnees = ['statut' => $statut];
        if ($factureId !== null) {
            $donnees['facture_id'] = $factureId;
        }
        Db::modifier('tz_remboursement', $donnees, 'id = ?', [$id]);
        ActionJournal::noter('remboursement_' . $statut, 'remboursement', $id);
        return true;
    }

    /** File d'attente de l'admin : demandes en cours, par ancienneté. */
    public static function aTraiter(int $limite = 25): array
    {
        $limite = max(1, min(100, $limite));
        return Db::tous(
            "SELECT r.*, c.reference, c.total_ttc, c.statut commande_statut,
                    cl.nom client_nom, cl.prenom client_prenom, cl.email client_email
               FROM tz_remboursement r
               JOIN tz_commande c ON c.id = r.commande_id
               LEFT JOIN tz_client cl ON cl.id = c.client_id
              WHERE r.statut IN ('demande','accepte')
           ORDER BY r.created_at LIMIT $limite"
        );
    }
}
