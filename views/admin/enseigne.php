<?php $titre = $enseigne ? 'Modifier : ' . $enseigne['nom'] : 'Nouvelle maison'; ?>
<section class="admin-section">
  <h1><?= e($titre) ?></h1>
  <form method="post" action="<?= e(Url::to('/admin/enseigne/enregistrer')) ?>" class="admin-carte admin-formulaire-grille">
    <?= Csrf::champ() ?>
    <input type="hidden" name="id" value="<?= (int) ($enseigne['id'] ?? 0) ?>">
    <label>Nom <input type="text" name="nom" value="<?= e($enseigne['nom'] ?? '') ?>" required></label>
    <label>Ville <input type="text" name="ville" value="<?= e($enseigne['ville'] ?? 'Tozeur') ?>"></label>
    <label>Téléphone <input type="text" name="telephone" value="<?= e($enseigne['telephone'] ?? '') ?>"></label>
    <label>E-mail <input type="email" name="email" value="<?= e($enseigne['email'] ?? '') ?>"></label>
    <label>Commission plateforme (millièmes, 5000 = 5%)
      <input type="number" name="commission_bpo" value="<?= (int) ($enseigne['commission_bpo'] ?? 5000) ?>">
    </label>
    <label>État
      <select name="etat">
        <?php foreach (['active' => 'Active', 'suspendue' => 'Suspendue', 'archive' => 'Archivée'] as $code => $libelle): ?>
          <option value="<?= $code ?>" <?= ($enseigne['etat'] ?? 'active') === $code ? 'selected' : '' ?>><?= $libelle ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label class="admin-span-2">Description <textarea name="description" rows="3"><?= e($enseigne['description'] ?? '') ?></textarea></label>
    <div class="admin-span-2"><button type="submit" class="admin-bouton">Enregistrer</button></div>
  </form>
</section>