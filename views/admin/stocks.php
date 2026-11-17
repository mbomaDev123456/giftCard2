<?php $titre = 'Stocks'; ?>
<section class="admin-section">
  <h1>Stocks</h1>
  <form method="get" class="admin-filtres">
    <input type="text" name="q" value="<?= e($q) ?>" placeholder="Produit ou SKU…">
    <label><input type="checkbox" name="alerte" value="1" <?= $alerte ? 'checked' : '' ?>> Alertes seulement</label>
    <button type="submit" class="admin-bouton admin-bouton-petit">Filtrer</button>
  </form>

  <table class="admin-table">
    <thead><tr><th>Produit</th><th>SKU</th><th>Maison</th><th>Stock</th><th>Seuil</th><th>Réappro</th><th>Ajuster</th></tr></thead>
    <tbody>
      <?php foreach ($variantes as $v): ?>
        <tr class="<?= (int) $v['stock_quantite'] <= (int) $v['stock_seuil'] ? 'admin-ligne-alerte' : '' ?>">
          <td><?= e($v['produit_nom']) ?> — <?= e($v['variante_nom']) ?></td>
          <td><?= e($v['sku']) ?></td>
          <td><?= e((string) $v['enseigne_nom']) ?></td>
          <td><?= (int) $v['stock_quantite'] ?></td>
          <td><?= (int) $v['stock_seuil'] ?></td>
          <td>
            <form method="post" action="<?= e(Url::to('/admin/stock/reappro')) ?>">
              <?= Csrf::champ() ?>
              <input type="hidden" name="variante_id" value="<?= (int) $v['id'] ?>">
              <input type="number" name="quantite" min="1" placeholder="+qté" style="width:70px">
              <input type="text" name="cout_unitaire" placeholder="coût DT" style="width:80px">
              <button type="submit">Réceptionner</button>
            </form>
          </td>
          <td>
            <form method="post" action="<?= e(Url::to('/admin/stock/ajuster')) ?>">
              <?= Csrf::champ() ?>
              <input type="hidden" name="variante_id" value="<?= (int) $v['id'] ?>">
              <input type="number" name="nouvelle_quantite" value="<?= (int) $v['stock_quantite'] ?>" style="width:70px">
              <button type="submit">Corriger</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>