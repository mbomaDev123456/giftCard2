<?php
/** Page d'erreur commune : code, titre, message et porte de sortie. */
?>
<section class="incident">
  <div class="rang incident-corps">
    <p class="incident-chiffre"><?= e((string) $code) ?></p>
    <h1 class="incident-titre"><?= e($titre) ?></h1>
    <p class="incident-texte"><?= e($message) ?></p>
    <p class="incident-geste">
      <a class="btn btn-juste btn-large" href="<?= e(Url::to('/')) ?>"><?= e(t('nav.accueil')) ?></a>
      <a class="btn btn-courroie btn-large" href="<?= e(Url::to('/catalogue')) ?>"><?= e(t('nav.catalogue')) ?></a>
      <a class="btn btn-lien" href="<?= e(Url::to('/boutiques')) ?>"><?= e(t('enseigne.toutes')) ?></a>
    </p>
    <form class="incident-recherche" method="get" action="<?= e(Url::to('/recherche')) ?>" role="search">
      <label class="visuel-cache" for="raccourci"><?= e(t('commun.rechercher')) ?></label>
      <input id="raccourci" type="search" name="q" maxlength="80"
             placeholder="<?= e(t('commun.rechercher')) ?> : deglet nour, tapis, jarre…">
      <button class="btn btn-courroie btn-etroit" type="submit"><?= e(t('commun.rechercher')) ?></button>
    </form>
  </div>
</section>
