<?php $titre = 'Produits'; ?>
<section class="admin-section">
  <header class="admin-section-tete">
    <h1>Produits</h1>
    <a class="admin-bouton admin-bouton-petit" href="<?= e(Url::to('/admin/produit/0')) ?>">+ Nouveau produit</a>
  </header>

  <form method="get" class="admin-filtres">
    <input type="text" name="q" value="<?= e($q) ?>" placeholder="Nom du produit…">
    <select name="enseigne_id">
      <option value="0">Toutes les maisons</option>
      <?php foreach ($enseignes as $en): ?>
        <option value="<?= (int) $en['id'] ?>" <?= $enseigneId === (int) $en['id'] ? 'selected' : '' ?>><?= e($en['nom']) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="etat">
      <option value="">Tous les états</option>
      <?php foreach ($etatsProduit as $code => $libelle): ?>
        <option value="<?= e($code) ?>" <?= $etat === $code ? 'selected' : '' ?>><?= e($libelle) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="admin-bouton admin-bouton-petit">Filtrer</button>
  </form>

  <table class="admin-table">
    <thead><tr><th>Produit</th><th>Maison</th><th>Catégorie</th><th>Prix</th><th>Stock</th><th>État</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($produits as $p): ?>
        <tr>
          <td><?= e($p['nom']) ?></td>
          <td><?= e((string) $p['enseigne_nom']) ?></td>
          <td><?= e((string) $p['categorie_nom']) ?></td>
          <td>
            <?php if ((int) $p['prix_min'] === (int) $p['prix_max']): ?>
              <?= number_format(((int) $p['prix_min']) / 1000, 3, ',', ' ') ?> DT
            <?php else: ?>
              <?= number_format(((int) $p['prix_min']) / 1000, 3, ',', ' ') ?> – <?= number_format(((int) $p['prix_max']) / 1000, 3, ',', ' ') ?> DT
            <?php endif; ?>
          </td>
          <td><?= (int) $p['stock_total'] ?></td>
          <td><span class="admin-badge"><?= e($etatsProduit[$p['etat']] ?? $p['etat']) ?></span></td>
          <td><a href="<?= e(Url::to('/admin/produit/' . $p['id'])) ?>">Modifier</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if ($produits === []): ?><tr><td colspan="7">Aucun produit.</td></tr><?php endif; ?>
    </tbody>
  </table>

  <?php if ($nbPages > 1): ?>
    <nav class="admin-pagination">
      <?php for ($p = 1; $p <= $nbPages; $p++): ?>
        <a href="?page=<?= $p ?>&q=<?= urlencode($q) ?>" class="<?= $p === $page ? 'actif' : '' ?>"><?= $p ?></a>
      <?php endfor; ?>
    </nav>
  <?php endif; ?>
</section>