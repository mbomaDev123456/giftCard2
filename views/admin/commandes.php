<?php $titre = 'Commandes'; ?>
<section class="admin-section">
  <header class="admin-section-tete">
    <h1>Commandes</h1>
    <p class="admin-compteur"><?= (int) $total ?> commande(s)</p>
  </header>

  <form method="get" action="<?= e(Url::to('/admin/commandes')) ?>" class="admin-filtres">
    <input type="text" name="q" value="<?= e($q) ?>" placeholder="Référence ou e-mail…">
    <select name="statut">
      <option value="">Tous les statuts</option>
      <?php foreach ($statutsCommande as $code => $libelle): ?>
        <option value="<?= e($code) ?>" <?= $statut === $code ? 'selected' : '' ?>><?= e($libelle) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="statut_paiement">
      <option value="">Tous les paiements</option>
      <?php foreach ($statutsPaiement as $code => $libelle): ?>
        <option value="<?= e($code) ?>" <?= $statutPaiement === $code ? 'selected' : '' ?>><?= e($libelle) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="admin-bouton admin-bouton-petit">Filtrer</button>
  </form>

  <table class="admin-table">
    <thead>
      <tr>
        <th>Référence</th><th>Client</th><th>Statut</th><th>Paiement</th><th>Total</th><th>Date</th><th></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($commandes as $c): ?>
        <tr>
          <td><?= e($c['reference']) ?></td>
          <td><?= e($c['email']) ?></td>
          <td><span class="admin-badge admin-badge--<?= e($c['statut']) ?>"><?= e($statutsCommande[$c['statut']] ?? $c['statut']) ?></span></td>
          <td><span class="admin-badge admin-badge--<?= e($c['statut_paiement']) ?>"><?= e($statutsPaiement[$c['statut_paiement']] ?? $c['statut_paiement']) ?></span></td>
          <td><?= number_format(((int) $c['total_ttc']) / 1000, 3, ',', ' ') ?> DT</td>
          <td><?= e(date('d/m/Y H:i', strtotime((string) $c['created_at']))) ?></td>
          <td><a href="<?= e(Url::to('/admin/commande/' . $c['id'])) ?>">Voir</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if ($commandes === []): ?>
        <tr><td colspan="7">Aucune commande ne correspond à ces filtres.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>

  <?php if ($nbPages > 1): ?>
    <nav class="admin-pagination">
      <?php for ($p = 1; $p <= $nbPages; $p++): ?>
        <a href="<?= e(Url::to('/admin/commandes') . '?page=' . $p . '&statut=' . urlencode($statut) . '&statut_paiement=' . urlencode($statutPaiement) . '&q=' . urlencode($q)) ?>"
           class="<?= $p === $page ? 'actif' : '' ?>"><?= $p ?></a>
      <?php endfor; ?>
    </nav>
  <?php endif; ?>
</section>