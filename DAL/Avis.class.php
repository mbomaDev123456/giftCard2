<?php
/** Avis clients, avec modération et achat vérifié. */
class Avis
{
    public static function publies(int $produitId, int $limite = 10): array
    {
        return Db::tous(
            'SELECT id, auteur, note, titre, texte, reponse, achat_verifie, created_at
               FROM tz_avis WHERE produit_id = ? AND statut = ?
           ORDER BY created_at DESC LIMIT ' . (int) $limite,
            [$produitId, 'publie']
        );
    }

    public static function aModerer(int $limite = 50): array
    {
        return Db::tous(
            "SELECT a.*, p.nom produit_nom, p.slug produit_slug, c.reference commande_reference
               FROM tz_avis a
               JOIN tz_produit p ON p.id = a.produit_id
          LEFT JOIN tz_commande c ON c.id = a.commande_id
              WHERE a.statut = 'en_attente'
           ORDER BY a.created_at DESC LIMIT " . (int) $limite
        );
    }

    public static function enregistrer(array $d): int
    {
        $produitId = (int) $d['produit_id'];
        $clientId = isset($d['client_id']) && $d['client_id'] ? (int) $d['client_id'] : null;
        $aAchete = $clientId === null ? 0 : (int) (Db::valeur(
            'SELECT COUNT(*) FROM tz_commande_ligne l JOIN tz_commande c ON c.id = l.commande_id
              WHERE c.client_id = ? AND l.produit_id = ? AND c.statut IN (?, ?, ?)',
            [$clientId, $produitId, 'payee', 'expediee', 'livree']
        ) > 0);
        $moderer = (bool) ParametresApp::reglure('avis_moderation', true);
        $id = Db::inserer('tz_avis', [
            'produit_id'    => $produitId,
            'client_id'     => $clientId,
            'commande_id'   => isset($d['commande_id']) && $d['commande_id'] ? (int) $d['commande_id'] : null,
            'auteur'        => trim((string) ($d['auteur'] ?? '')) ?: null,
            'note'          => max(1, min(5, (int) $d['note'])),
            'titre'         => trim((string) ($d['titre'] ?? '')) ?: null,
            'texte'         => trim((string) ($d['texte'] ?? '')) ?: null,
            'statut'        => $moderer ? 'en_attente' : 'publie',
            'achat_verifie' => $aAchete,
        ]);
        if (!$moderer) {
            self::recalculerNote($produitId);
        }
        return $id;
    }

    public static function moderer(int $id, string $statut, ?string $reponse = null): void
    {
        $avis = Db::un('SELECT produit_id FROM tz_avis WHERE id = ?', [$id]);
        if ($avis === null) {
            return;
        }
        $champs = ['statut' => $statut];
        if ($reponse !== null) {
            $champs['reponse'] = $reponse;
        }
        Db::modifier('tz_avis', $champs, 'id = ?', [$id]);
        self::recalculerNote((int) $avis['produit_id']);
    }

    public static function recalculerNote(int $produitId): void
    {
        $note = Db::un(
            "SELECT COALESCE(AVG(note), 0) moyenne, COUNT(*) compte
               FROM tz_avis WHERE produit_id = ? AND statut = 'publie'",
            [$produitId]
        );
        Db::modifier('tz_produit', [
            'note_moyenne' => round((float) ($note['moyenne'] ?? 0), 2),
            'note_compte'  => (int) ($note['compte'] ?? 0),
        ], 'id = ?', [$produitId]);
    }

    /** Répartition 5→1 étoiles pour l'affichage de la fiche. */
    public static function echelle(int $produitId): array
    {
        $lignes = Db::tous(
            "SELECT note, COUNT(*) nb FROM tz_avis WHERE produit_id = ? AND statut = 'publie'
            GROUP BY note",
            [$produitId]
        );
        $echelle = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
        foreach ($lignes as $ligne) {
            $echelle[(int) $ligne['note']] = (int) $ligne['nb'];
        }
        return $echelle;
    }
}
