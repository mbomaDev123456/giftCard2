<?php
/**
 * Carte produit. $p vient de Produit::rechercher(), vedettes() ou nouveautes() :
 * les clefs secondaires sont tolérées car les rails n'ont pas tous la même richesse.
 */
$p = $p ?? [];
$id = (int) ($p['achat_id'] ?? 0);
$lien = Url::to('/produit/' . $p['slug']);
$nom = nom_produit($p);
$image = imageexiste($p['image_principale'] ?? null) ? Url::upload($p['image_principale']) : '';
$prix = (int) ($p['variante_prix_mini'] ?? $p['prix_min'] ?? 0);
$barre = (int) ($p['variante_barre_max'] ?? 0);
$stock = (int) ($p['stock_total'] ?? 0);
$note = (float) ($p['note_moyenne'] ?? 0);
$compte = (int) ($p['note_compte'] ?? 0);
$remise = $barre > $prix && $barre > 0 ? (int) round(100 - 100 * $prix / $barre) : 0;
$couleur = couleur_sure($p['enseigne_couleur'] ?? null, 'var(--primaire)');
?>
<article class="carte<?= $stock === 0 ? ' carte-hors' : '' ?>">
  <a class="carte-image" href="<?= e($lien) ?>" tabindex="-1" aria-hidden="true">
    <?php if ($image !== ''): ?>
      <img src="<?= e($image) ?>" alt="" loading="lazy" width="600" height="600">
    <?php else: ?>
      <span class="sans-image" style="--puce:<?= e($couleur) ?>"><?= e(mb_strtoupper(mb_substr((string) ($p['enseigne_nom'] ?? 'T'), 0, 1))) ?></span>
    <?php endif; ?>
    <?php if ($remise > 0): ?><span class="pastille pastille-promo">&minus;<?= (int) $remise ?> %</span><?php endif; ?>
    <?php if (!empty($p['nouveaute'])): ?><span class="pastille pastille-neuf">Nouveauté</span><?php endif; ?>
    <?php if (!empty($p['label'])): ?><span class="pastille pastille-label"><?= e($p['label']) ?></span><?php endif; ?>
    <?php if ($stock === 0): ?><span class="pastille pastille-hors">Épuisé</span><?php endif; ?>
  </a>

  <div class="carte-corps">
    <p class="carte-maison" style="--puce:<?= e($couleur) ?>">
      <a href="<?= e(Url::to('/boutique/' . $p['enseigne_slug'])) ?>"><?= e($p['enseigne_nom']) ?></a>
    </p>
    <h3 class="carte-titre"><a href="<?= e($lien) ?>"><?= e($nom) ?></a></h3>
    <?php if (!empty($p['resume'])): ?><p class="carte-resume"><?= e($p['resume']) ?></p><?php endif; ?>

    <?php if ($compte > 0): ?>
      <p class="carte-note"><?= View::corps('partials/etoiles', ['note' => $note]) ?>
        <span><?= e(number_format($note, 1, ',', ' ')) ?> · <?= (int) $compte ?> avis</span></p>
    <?php endif; ?>

    <p class="carte-prix">
      <strong><?= e(dt($prix)) ?></strong>
      <?php if ($remise > 0): ?><del><?= e(dt($barre)) ?></del><?php endif; ?>
      <small><?= e(t('commun.prix')) ?></small>
    </p>

    <div class="carte-geste">
      <?php if ($stock === 0): ?>
        <a class="btn btn-carte" href="<?= e($lien) ?>">Prévenir</a>
      <?php elseif ($id > 0): ?>
        <form method="post" action="<?= e(Url::to('/panier/ajouter')) ?>" data-panier-form>
          <?= Csrf::champ() ?>
          <input type="hidden" name="variante_id" value="<?= $id ?>">
          <input type="hidden" name="quantite" value="1">
          <input type="hidden" name="retour" value="<?= e(Http::chemin()) ?>">
          <button class="btn btn-carte" type="submit">Ajouter</button>
        </form>
      <?php else: ?>
        <a class="btn btn-carte" href="<?= e($lien) ?>"><?= e(t('commun.voir')) ?></a>
      <?php endif; ?>
    </div>
  </div>
</article>
