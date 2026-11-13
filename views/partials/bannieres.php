<?php
/**
 * Bannières de la régie : un slot = une requête, et une exposition comptée.
 * $slot : hero | bandeau | flottante | cote_catalogue | pied | interstitiel | newsletter
 */

$slot = (string) ($slot ?? 'bandeau');
$page = Http::chemin();

$groupe = ParametresApp::voyageur('groupe_audience');
if ($groupe === null) {
    $groupe = Banniere::groupe(Auth::idClient(), Auth::client()['email'] ?? null);
    ParametresApp::definir('groupe_audience', $groupe);
}

$parSlot = Banniere::enService($page, ParametresApp::enseigneCourante(), $groupe);
$lignes = $parSlot[$slot] ?? [];
if ($lignes === []) {
    return;
}
$sessionId = session_id();
$ip = Http::ip();
?>
<?php foreach ($lignes as $une): ?>
  <?php Banniere::exposer((int) $une['id'], $page, $sessionId, $ip); ?>
  <?php
    $lien = (string) ($une['url'] ?? '');
    $externe = preg_match('#^https?://#', $lien) === 1;
    $vers = $lien === '' ? null : ($externe ? $lien : Url::to($lien));
    $image = imageexiste($une['image']) ? Url::upload($une['image']) : '';
    $fond = couleur_sure($une['couleur_fond'], '');
  ?>
  <section class="pubi pubi-<?= e($slot) ?> <?= $une['type_offre'] === 'sponsorisee' ? 'pubi-sponsorisee' : 'pubi-interne' ?>"
       <?= $fond === '' ? '' : 'style="--pubi-fond:' . e($fond) . '"' ?>
       data-banniere="<?= (int) $une['id'] ?>">
    <?php if ($vers !== null): ?><a class="pubi-lien" href="<?= e($vers) ?>" data-banniere-clic="<?= (int) $une['id'] ?>"<?= $externe ? ' rel="noopener"' : '' ?>><?php endif; ?>
      <?php if ($image !== ''): ?>
        <img class="pubi-image" src="<?= e($image) ?>"
             <?php if ($une['image_mobile'] !== null && imageexiste($une['image_mobile'])): ?>
               srcset="<?= e(Url::upload($une['image_mobile'])) ?> 640w, <?= e($image) ?> 1400w"
               sizes="(max-width: 700px) 100vw, 1240px"
             <?php endif; ?>
             alt="<?= e($une['titre']) ?>" loading="lazy">
      <?php endif; ?>
      <div class="pubi-texte">
        <?php if ($slot === 'hero'): ?><p class="sourcils pubi-sourcils">Tozeur · Jérid · Tunisie</p><?php endif; ?>
        <?php if ($une['type_offre'] === 'sponsorisee'): ?><p class="pubi-mention">Publicité</p><?php endif; ?>
        <h2 class="pubi-titre"><?= e($une['titre']) ?></h2>
        <?php if (!empty($une['sous_titre'])): ?><p class="pubi-sous"><?= e($une['sous_titre']) ?></p><?php endif; ?>
        <?php if (!empty($une['texte_bouton'])): ?>
          <span class="btn btn-invers"><?= e($une['texte_bouton']) ?></span>
        <?php endif; ?>
      </div>
    <?php if ($vers !== null): ?></a><?php endif; ?>
    <?php if ($slot === 'hero'): ?><span class="pubi-defile" aria-hidden="true"></span><?php endif; ?>
  </section>
<?php endforeach; ?>
