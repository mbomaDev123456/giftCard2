<?php $titre = $produit ? 'Modifier : ' . $produit['nom'] : 'Nouveau produit'; ?>
<section class="admin-section">
  <h1><?= e($titre) ?></h1>

  <form method="post" action="<?= e(Url::to('/admin/produit/enregistrer')) ?>" class="admin-carte admin-formulaire-grille">
    <?= Csrf::champ() ?>
    <input type="hidden" name="id" value="<?= (int) ($produit['id'] ?? 0) ?>">

    <label>Nom
      <input type="text" name="nom" value="<?= e($produit['nom'] ?? '') ?>" required>
    </label>
    <label>Maison
      <select name="enseigne_id">
        <option value="">—</option>
        <?php foreach ($enseignes as $en): ?>
          <option value="<?= (int) $en['id'] ?>" <?= (int) ($produit['enseigne_id'] ?? 0) === (int) $en['id'] ? 'selected' : '' ?>><?= e($en['nom']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Catégorie
      <select name="categorie_id">
        <option value="">—</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= (int) $c['id'] ?>" <?= (int) ($produit['categorie_id'] ?? 0) === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['nom']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>État
      <select name="etat">
        <?php foreach ($etatsProduit as $code => $libelle): ?>
          <option value="<?= e($code) ?>" <?= ($produit['etat'] ?? 'brouillon') === $code ? 'selected' : '' ?>><?= e($libelle) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Résumé
      <input type="text" name="resume" value="<?= e($produit['resume'] ?? '') ?>">
    </label>
    <label>Coût d'achat (DT)
      <input type="text" name="prix_achat_bpo" value="<?= isset($produit['prix_achat_bpo']) ? number_format($produit['prix_achat_bpo'] / 1000, 3, ',', '') : '' ?>">
    </label>
    <label class="admin-span-2">Description
      <textarea name="description" rows="4"><?= e($produit['description'] ?? '') ?></textarea>
    </label>
    <label><input type="checkbox" name="vedette" <?= !empty($produit['vedette']) ? 'checked' : '' ?>> Mise en avant</label>
    <label><input type="checkbox" name="nouveaute" <?= !empty($produit['nouveaute']) ? 'checked' : '' ?>> Nouveauté</label>

    <div class="admin-span-2"><button type="submit" class="admin-bouton">Enregistrer</button></div>
  </form>

  <?php if ($produit): ?>
    <div class="admin-carte">
      <h2>Variantes / formats</h2>
      <table class="admin-table">
        <thead><tr><th>SKU</th><th>Nom</th><th>Prix</th><th>Stock</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($variantes as $v): ?>
            <tr>
              <td><?= e($v['sku']) ?></td>
              <td><?= e($v['nom']) ?></td>
              <td><?= number_format(((int) $v['prix']) / 1000, 3, ',', ' ') ?> DT</td>
              <td><?= (int) $v['stock_quantite'] ?></td>
              <td>
                <form method="post" action="<?= e(Url::to('/admin/variante/' . $v['id'] . '/supprimer')) ?>" onsubmit="return confirm('Supprimer cette variante ?');">
                  <?= Csrf::champ() ?><button type="submit">Supprimer</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <h3>Ajouter une variante</h3>
      <form method="post" action="<?= e(Url::to('/admin/variante/enregistrer')) ?>" class="admin-formulaire-grille">
        <?= Csrf::champ() ?>
        <input type="hidden" name="produit_id" value="<?= (int) $produit['id'] ?>">
        <label>SKU <input type="text" name="sku" required></label>
        <label>Nom <input type="text" name="nom" required></label>
        <label>Prix TTC (DT) <input type="text" name="prix" placeholder="25,000" required></label>
        <label>Stock initial <input type="number" name="stock_quantite" value="0"></label>
        <label>Seuil d'alerte <input type="number" name="stock_seuil" value="3"></label>
        <div><button type="submit" class="admin-bouton admin-bouton-petit">Ajouter</button></div>
      </form>
    </div>
  <?php endif; ?>
</section>