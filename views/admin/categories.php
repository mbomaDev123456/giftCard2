<?php $titre = 'Catégories'; ?>
<section class="admin-section">
  <h1>Catégories</h1>
  <table class="admin-table">
    <thead><tr><th>Nom</th><th>Parent</th><th>TVA</th><th>État</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($categories as $c): ?>
        <tr>
          <td><?= e($c['nom']) ?></td>
          <td><?= e((string) $c['parent_nom']) ?></td>
          <td><?= (int) $c['tva_taux'] / 1000 ?>%</td>
          <td><?= e($c['etat']) ?></td>
          <td>
            <form method="post" action="<?= e(Url::to('/admin/categorie/' . $c['id'] . '/supprimer')) ?>" onsubmit="return confirm('Supprimer ?');">
              <?= Csrf::champ() ?><button type="submit">Supprimer</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="admin-carte">
    <h2>Ajouter une catégorie</h2>
    <form method="post" action="<?= e(Url::to('/admin/categorie/enregistrer')) ?>">
      <?= Csrf::champ() ?>
      <input type="text" name="nom" placeholder="Nom" required>
      <select name="parent_id">
        <option value="">Rayon principal</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= (int) $c['id'] ?>"><?= e($c['nom']) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="admin-bouton admin-bouton-petit">Ajouter</button>
    </form>
  </div>
</section>