<?php
/** Accueil : la grand-place — maisons, récolte, savoir-faire, offres. */

$hero = View::corps('partials/bannieres', ['slot' => 'hero']);
?>
<?php if (trim($hero) === ''): ?>
  <section class="hero">
    <div class="hero-ecran">
      <p class="sourcils"><?= e(t('enseigne.toutes')) ?></p>
      <h1 class="hero-titre">Tozeur, <em>sous toutes ses mains</em></h1>
      <p class="hero-texte">
        Cinq maisons du Jérid réunies sous une même boutique : la datte récoltée à la botte,
        l’argile de Jebeldiour, la laine nouée du Chott, l’huile de figue de Barbarie et l’épicerie fine.
        Un panier, une commande, une facture.
      </p>
      <p class="hero-geste">
        <a class="btn btn-juste btn-large" href="<?= e(Url::to('/catalogue')) ?>"><?= e(t('nav.catalogue')) ?></a>
        <a class="btn btn-courroie btn-large" href="<?= e(Url::to('/boutiques')) ?>"><?= e(t('enseigne.nav')) ?></a>
      </p>
      <ul class="hero-fil">
        <?php foreach (array_slice($enseignes, 0, 5) as $une): ?>
          <li style="--puce:<?= e(couleur_sure($une['couleur'], '#7a3b1e')) ?>"><?= e($une['nom']) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div class="hero-miroir" aria-hidden="true">
      <span class="motif motif-medja"></span>
      <span class="motif motif-palmier"></span>
    </div>
  </section>
<?php else: ?>
  <?= $hero ?>
<?php endif; ?>

<section class="rail rail-maisons">
  <div class="rang">
    <header class="rail-tete">
      <h2 class="rail-titre"><?= e(t('enseigne.toutes')) ?></h2>
      <a class="rail-plus" href="<?= e(Url::to('/boutiques')) ?>">Toutes les vitrines</a>
    </header>
    <div class="maisons-ecs">
      <?php foreach ($enseignes as $une): ?>
        <a class="maison" style="--puce:<?= e(couleur_sure($une['couleur'], '#7a3b1e')) ?>;--secondaire:<?= e(couleur_sure($une['couleur_secondaire'], '#c98b3f')) ?>"
           href="<?= e(Url::to('/boutique/' . $une['slug'])) ?>">
          <?php if (!empty($une['banniere']) && imageexiste($une['banniere'])): ?>
            <img class="maison-fond" src="<?= e(Url::upload($une['banniere'])) ?>" alt="" loading="lazy">
          <?php endif; ?>
          <span class="maison-corps">
            <span class="maison-ville"><?= e($une['ville']) ?></span>
            <span class="maison-nom"><?= e($une['nom']) ?></span>
            <span class="maison-slogan"><?= e($une['slogan']) ?></span>
            <span class="maison-chiffres">
              <em><?= (int) $une['nb_produits'] ?> produits</em>
              <?php if ((float) $une['note_moyenne'] > 0): ?>
                <em><?= e(number_format((float) $une['note_moyenne'], 1, ',', ' ')) ?> / 5</em>
              <?php endif; ?>
              <?php if ((int) $une['prix_min'] > 0): ?>
                <em>dès <?= e(dt((int) $une['prix_min'], false)) ?> DT</em>
              <?php endif; ?>
            </span>
            <span class="maison-aller"><?= e(t('enseigne.visiter')) ?></span>
          </span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php if ($vedettes !== []): ?>
<section class="rail">
  <div class="rang">
    <header class="rail-tete">
      <p class="sourcils">Récolte &amp; atelier</p>
      <h2 class="rail-titre">Les pièces que l’on nous demande</h2>
    </header>
    <div class="grille-produits">
      <?php foreach ($vedettes as $un): ?>
        <?= View::corps('partials/carte', ['p' => $un]) ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="promesse">
  <div class="rang promesse-grille">
    <article>
      <span class="promesse-glyphe" aria-hidden="true">١</span>
      <h3>Du palmier à la botte</h3>
      <p>Les Deglet Nour sont cueillies à la main sur les palmeraies du Jerid, triées trois fois,
         et expédiées dans les 48 heures. Pas de stock dormant.</p>
    </article>
    <article>
      <span class="promesse-glyphe" aria-hidden="true">٢</span>
      <h3>Chaque maison répond</h3>
      <p>Une commande se découpe par maison : l’atelier de Jebeldiour prépare ses jarres,
         la tisseuse son tapis. Vous suivez les deux au même endroit.</p>
    </article>
    <article>
      <span class="promesse-glyphe" aria-hidden="true">٣</span>
      <h3>Payé en dinars, simplement</h3>
      <p>Carte e-Payment par GPG Checkout, paiement à la livraison ou virement.
         Facture et avoir édités par la plateforme, TVA tunisienne comprise.</p>
    </article>
  </div>
</section>

<?php if ($meilleures !== []): ?>
<section class="rail rail-semaine">
  <div class="rang">
    <header class="rail-tete">
      <p class="sourcils">Les trente derniers jours</p>
      <h2 class="rail-titre">Ce qui est parti cette semaine</h2>
    </header>
    <div class="grille-produits">
      <?php foreach ($meilleures as $un): ?>
        <?= View::corps('partials/carte', ['p' => $un]) ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($rayons !== []): ?>
<section class="rail">
  <div class="rang">
    <header class="rail-tete">
      <h2 class="rail-titre">Entrer par rayon</h2>
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

<?php if ($nouveautes !== []): ?>
<section class="rail">
  <div class="rang">
    <header class="rail-tete">
      <h2 class="rail-titre">Entré récemment</h2>
    </header>
    <div class="grille-produits">
      <?php foreach ($nouveautes as $un): ?>
        <?= View::corps('partials/carte', ['p' => $un]) ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($promos !== []): ?>
<section class="rail rail-offres">
  <div class="rang">
    <header class="rail-tete">
      <p class="sourcils">En ce moment</p>
      <h2 class="rail-titre">Nos offres</h2>
      <a class="rail-plus" href="<?= e(Url::to('/promotions')) ?>">Voir les produits concernés</a>
    </header>
    <ul class="offres-ecs">
      <?php foreach ($promos as $une): ?>
        <li class="offre" style="--puce:<?= e(couleur_sure($une['enseigne_couleur'] ?? null, 'var(--accent)')) ?>">
          <?php if (!empty($une['code'])): ?>
            <p class="offre-code"><?= e($une['code']) ?></p>
          <?php endif; ?>
          <h3 class="offre-titre"><?= e($langue === 'ar' && !empty($une['libelle_ar']) ? $une['libelle_ar'] : $une['libelle']) ?></h3>
          <?php if (!empty($une['description'])): ?><p class="offre-texte"><?= e($une['description']) ?></p><?php endif; ?>
          <p class="offre-pied">
            <?php if ((int) $une['seuil_achat'] > 0): ?>
              <em>dès <?= e(dt((int) $une['seuil_achat'], false)) ?> DT</em>
            <?php endif; ?>
            <?php if (!empty($une['enseigne_nom'])): ?><em><?= e($une['enseigne_nom']) ?></em><?php endif; ?>
            <?php if (!empty($une['fin_at'])): ?>
              <em>jusqu’au <?= e(date('d/m/Y', strtotime((string) $une['fin_at']))) ?></em>
            <?php endif; ?>
          </p>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
<?php endif; ?>

<?= View::corps('partials/bannieres', ['slot' => 'cote_catalogue']) ?>
