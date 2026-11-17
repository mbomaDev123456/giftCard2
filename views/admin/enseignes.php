<?php $titre = 'Maisons / enseignes'; ?>
<section class="admin-section">
  <header class="admin-section-tete">
    <h1>Maisons</h1>
    <a class="admin-bouton admin-bouton-petit" href="<?= e(Url::to('/admin/enseigne/0')) ?>">+ Nouvelle maison</a>
  </header>
  <table class="admin-table">
    <thead><tr><th>Nom</th><th>Ville</th><th>Commission</th><th>État</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($enseignes as $e): ?>
        <tr>
          <td><?= e($e['nom']) ?></td>
          <td><?= e((string) $e['ville']) ?></td>
          <td><?= number_format($e['commission_bpo'] / 1000, 1) ?>%</td>
          <td><?= e($e['etat']) ?></td>
          <td><a href="<?= e(Url::to('/admin/enseigne/' . $e['id'])) ?>">Modifier</a></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>