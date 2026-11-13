<?php
/** Toutes les maisons réunies sous la même plateforme. */

$parMaison = static function (array $une): string {
    return filtre_url([], ['maison' => $une['slug']]);
};
$vedettes = array_values(array_filter($enseignes, static fn(array $une): bool => (int) $une['vedette'] === 1));
$autres   = array_values(array_filter($enseignes, static fn(array $une): bool => (int) $une['vedette'] !== 1));
?>
<section class="etale-etale">
  <div class="rang">
    <?= View::corps('partials/fil', ['fil' => [
        ['titre' => 'Accueil', 'url' => Url::to('/')],
        ['titre' => t('enseigne.nav'), 'url' => null],
    ]]) ?>

    <header class="etale-tete">
      <div>
        <p class="sourcils"><?= e(TZ_SITE_CITY) ?> · <?= count($enseignes) ?> ateliers</p>
        <h1 class="etale-titre">Les maisons du Jérid</h1>
        <p class="etale-chapeau">
          Chacune tient son propre savoir-faire, sa propre signature, ses propres délais.
          Elles partagent un panier, une commande et une facture. Entrez par la porte qui vous intéresse.
        </p>
      </div>
    </header>

    <ul class="ecs-maisons">
      <?php foreach ($vedettes as $une): ?>
        <li class="maison maison-large"
            style="--puce:<?= e(couleur_sure($une['couleur'], '#7a3b1e')) ?>;--secondaire:<?= e(couleur_sure($une['couleur_secondaire'], '#c98b3f')) ?>">
          <?php if (!empty($une['banniere']) && imageexiste($une['banniere'])): ?>
            <img class="maison-fond" src="<?= e(Url::upload($une['banniere'])) ?>" alt="" loading="lazy">
          <?php endif; ?>
          <span class="maison-corps">
            <span class="maison-ville"><?= e($une['ville']) ?> · <?= e(t('enseigne.nav')) ?></span>
            <span class="maison-nom"><?= e($une['nom']) ?></span>
            <span class="maison-slogan"><?= e($une['slogan']) ?></span>
            <?php if (!empty($une['description'])): ?>
              <span class="maison-texte"><?= e($une['description']) ?></span>
            <?php endif; ?>
            <span class="maison-chiffres">
              <em><?= (int) $une['nb_produits'] ?> produits</em>
              <?php if ((float) $une['note_moyenne'] > 0): ?>
                <em><?= e(number_format((float) $une['note_moyenne'], 1, ',', ' ')) ?> / 5</em>
              <?php endif; ?>
              <?php if ((int) $une['prix_min'] > 0): ?>
                <em>dès <?= e(dt((int) $une['prix_min'], false)) ?> DT</em>
              <?php endif; ?>
            </span>
            <span class="maison-geste">
              <a class="btn btn-juste btn-etroit" href="<?= e(Url::to('/boutique/' . $une['slug'])) ?>"><?= e(t('enseigne.visiter')) ?></a>
              <a class="btn btn-lien btn-etroit" href="<?= e($parMaison($une)) ?>">Tous leurs rayons</a>
            </span>
          </span>
        </li>
      <?php endforeach; ?>
    </ul>

    <?php if ($autres !== []): ?>
      <h2 class="rail-titre">Les autres ateliers</h2>
      <ul class="ecs-maisons ecs-maisons-minces">
        <?php foreach ($autres as $une): ?>
          <li class="maison"
              style="--puce:<?= e(couleur_sure($une['couleur'], '#7a3b1e')) ?>;--secondaire:<?= e(couleur_sure($une['couleur_secondaire'], '#c98b3f')) ?>">
            <span class="maison-corps">
              <span class="maison-ville"><?= e($une['ville']) ?></span>
              <span class="maison-nom"><?= e($une['nom']) ?></span>
              <span class="maison-slogan"><?= e($une['slogan']) ?></span>
              <span class="maison-chiffres">
                <em><?= (int) $une['nb_produits'] ?> produits</em>
                <?php if ((int) $une['prix_min'] > 0): ?>
                  <em>dès <?= e(dt((int) $une['prix_min'], false)) ?> DT</em>
                <?php endif; ?>
              </span>
              <span class="maison-aller"><a href="<?= e(Url::to('/boutique/' . $une['slug'])) ?>"><?= e(t('enseigne.visiter')) ?></a></span>
            </span>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</section>

<?php if ($rayons !== []): ?>
<section class="rail">
  <div class="rang">
    <header class="rail-tete">
      <h2 class="rail-titre">Ou entrer par rayon</h2>
      <a class="rail-plus" href="<?= e(Url::to('/catalogue')) ?>"><?= e(t('nav.catalogue')) ?></a>
    </header>
    <ul class="rayons-ecs">
      <?php foreach ($rayons as $unRayon): ?>
        <li class="rayon">
          <a href="<?= e(filtre_url([], ['rayon' => $unRayon['slug']])) ?>">
            <strong><?= e(nom_produit($unRayon, 'nom')) ?></strong>
            <small><?= (int) $unRayon['nb'] ?> pièces</small>
          </a>
          <?php if ($unRayon['enfants'] !== []): ?>
            <ul class="rayon-sous">
              <?php foreach ($unRayon['enfants'] as $enfant): ?>
                <li><a href="<?= e(filtre_url([], ['rayon' => $enfant['slug']])) ?>"><?= e(nom_produit($enfant, 'nom')) ?></a></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
<?php endif; ?>
