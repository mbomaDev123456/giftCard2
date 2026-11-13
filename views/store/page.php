<?php
/** Page éditoriale : histoire, guide de la datte, livraison, CGV, contact. */

$corps = $langue === 'ar' && !empty($page['corps_ar']) ? $page['corps_ar'] : $page['corps'];
$visuel = imageexiste($page['visuel'] ?? null) ? Url::upload($page['visuel']) : '';
$titrePage = $langue === 'ar' && !empty($page['titre_ar']) ? $page['titre_ar'] : $page['titre'];
?>
<div class="rang editorial">
  <?= View::corps('partials/fil', ['fil' => [
      ['titre' => 'Accueil', 'url' => Url::to('/')],
      ['titre' => $titrePage, 'url' => null],
  ]]) ?>

  <article class="editorial-corps">
    <header class="editorial-tete">
      <h1 class="editorial-titre"><?= e($titrePage) ?></h1>
      <?php if (!empty($page['meta_description'])): ?>
        <p class="editorial-chapeau"><?= e($page['meta_description']) ?></p>
      <?php endif; ?>
    </header>

    <?php if ($visuel !== ''): ?>
      <figure class="editorial-visuel">
        <img src="<?= e($visuel) ?>" alt="" width="1600" height="900" loading="lazy">
      </figure>
    <?php endif; ?>

    <div class="editorial-texte"><?= Texte::propre($corps) ?></div>

    <?php if ($page['slug'] === 'contact' || $page['slug'] === 'livraison'): ?>
      <section class="editorial-maisons">
        <h2>Les maisons à joindre</h2>
        <ul class="ecs-maisons-contact">
          <?php foreach (Enseigne::actives(true) as $une): ?>
            <li style="--puce:<?= e(couleur_sure($une['couleur'], '#7a3b1e')) ?>">
              <a href="<?= e(Url::to('/boutique/' . $une['slug'])) ?>"><?= e($une['nom']) ?></a>
              <span><?= e($une['ville']) ?><?php if (!empty($une['telephone'])): ?> · <?= e($une['telephone']) ?><?php endif; ?></span>
              <?php if (!empty($une['email'])): ?><a class="lien-mail" href="mailto:<?= e($une['email']) ?>"><?= e($une['email']) ?></a><?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      </section>
    <?php endif; ?>

    <p class="editorial-bas">
      <a class="btn btn-lien" href="<?= e(Url::to('/catalogue')) ?>"><?= e(t('panier.continuer')) ?></a>
      <span>Mise à jour le <?= e(date('d/m/Y', strtotime((string) $page['updated_at']))) ?></span>
    </p>
  </article>
</div>
