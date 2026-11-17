<?php $titre = 'Mouvements de stock'; ?>
<section class="admin-section">
  <h1>Journal des mouvements</h1>
  <table class="admin-table">
    <thead><tr><th>Date</th><th>Produit</th><th>SKU</th><th>Type</th><th>Quantité</th><th>Stock après</th><th>Motif</th></tr></thead>
    <tbody>
      <?php foreach ($mouvements as $m): ?>
        <tr>
          <td><?= e(date('d/m/Y H:i', strtotime((string) $m['created_at']))) ?></td>
          <td><?= e($m['produit_nom']) ?></td>
          <td><?= e($m['sku']) ?></td>
          <td><?= e($typesMouvement[$m['type']] ?? $m['type']) ?></td>
          <td><?= ((int) $m['quantite']) > 0 ? '+' : '' ?><?= (int) $m['quantite'] ?></td>
          <td><?= (int) $m['stock_apres'] ?></td>
          <td><?= e((string) $m['motif']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php if ($nbPages > 1): ?>
    <nav class="admin-pagination">
      <?php for ($p = 1; $p <= $nbPages; $p++): ?>
        <a href="?page=<?= $p ?>" class="<?= $p === $page ? 'actif' : '' ?>"><?= $p ?></a>
      <?php endfor; ?>
    </nav>
  <?php endif; ?>
</section>