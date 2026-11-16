<?php
$titre = 'Tableau de bord';
$admin = $admin ?? [];
$stats = $stats ?? [];
$commandes = $commandes ?? [];
?>
<section class="admin-page">
  <div class="admin-page-tete">
    <div>
      <p class="admin-surtitre">Vue d’ensemble</p>
      <h1>Bonjour <?= e($admin['nom'] ?? $admin['login'] ?? '') ?></h1>
      <p class="admin-intro">Voici l’activité récente de votre boutique.</p>
    </div>
    <span class="admin-role">Rôle : <?= e($admin['role'] ?? '') ?></span>
  </div>

  <div class="admin-stats">
    <article class="admin-stat"><span>Commandes à traiter</span><strong><?= (int) ($stats['commandes_a_traiter'] ?? 0) ?></strong></article>
    <article class="admin-stat"><span>Produits publiés</span><strong><?= (int) ($stats['produits_publies'] ?? 0) ?></strong></article>
    <article class="admin-stat"><span>Clients inscrits</span><strong><?= (int) ($stats['clients'] ?? 0) ?></strong></article>
    <article class="admin-stat"><span>Ventes encaissées ce mois</span><strong><?= e(dt((int) ($stats['ventes_mois'] ?? 0))) ?></strong></article>
  </div>

  <section class="admin-bloc" aria-labelledby="commandes-recentes">
    <div class="admin-bloc-tete">
      <div><p class="admin-surtitre">Suivi</p><h2 id="commandes-recentes">Commandes récentes</h2></div>
    </div>
    <?php if ($commandes === []): ?>
      <p class="admin-vide">Aucune commande pour le moment.</p>
    <?php else: ?>
      <div class="admin-table-defilement">
        <table class="admin-table">
          <thead><tr><th>Référence</th><th>Client</th><th>État</th><th>Paiement</th><th>Total</th><th>Date</th></tr></thead>
          <tbody>
          <?php foreach ($commandes as $commande): ?>
            <tr>
              <td><strong><?= e($commande['reference']) ?></strong></td>
              <td><?= e($commande['email'] ?? '—') ?></td>
              <td><?= e(str_replace('_', ' ', (string) $commande['statut'])) ?></td>
              <td><?= e(str_replace('_', ' ', (string) $commande['statut_paiement'])) ?></td>
              <td><?= e(dt((int) $commande['total_ttc'])) ?></td>
              <td><time datetime="<?= e(date('c', strtotime((string) $commande['created_at']))) ?>"><?= e(date('d/m/Y H:i', strtotime((string) $commande['created_at']))) ?></time></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>

  <p class="admin-note">Les écrans de gestion des commandes, produits, stocks, clients et comptabilité restent à implémenter. Ils ne sont pas encore disponibles dans cette première étape.</p>
</section>
