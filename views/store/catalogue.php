<?php
/** Le catalogue : toutes les maisons, filtré par rayon, maison, prix, savoir-faire. */

$resultat = $resultat ?? ['lignes' => [], 'total' => 0, 'page' => 1, 'par_page' => 12, 'trie' => 'pertinence',
    'facettes' => ['rayons' => [], 'maisons' => [], 'prix' => ['mini' => 0, 'maxi' => 0], 'savoir_faire' => []]];
$filtres = $filtres ?? [];
$facettes = $resultat['facettes'];
$active = array_filter($filtres, static fn($v, $k): bool => !in_array($k, ['tri', 'page'], true) && $v !== '', ARRAY_FILTER_USE_BOTH);

$titrePage = isset($recherche) && $recherche !== ''
    ? '« ' . $recherche . ' »'
    : ($rayon['nom'] ?? ($maison['nom'] ?? t('catalogue.titre')));
$cheminPage = $maison === null ? '/catalogue' : '/boutique/' . $maison['slug'];
?>
<section class="etale-voile">
  <div class="rang">
    <?= View::corps('partials/fil', ['fil' => array_merge(
        [['titre' => 'Accueil', 'url' => Url::to('/')], ['titre' => t('nav.catalogue'), 'url' => Url::to('/catalogue')]],
        $rayon === null ? [] : [['titre' => $rayon['nom'], 'url' => null]]
    )]) ?>
    <p class="sourcils"><?= $maison === null ? e(t('enseigne.toutes')) : e($maison['nom']) ?></p>
    <h1 class="etale-titre"><?= e($titrePage) ?></h1>
    <?php if ($rayon !== null && !empty($rayon['description'])): ?>
      <p class="etale-chapeau"><?= e($rayon['description']) ?></p>
    <?php elseif ($maison !== null): ?>
      <p class="etale-chapeau"><?= e($maison['slogan']) ?></p>
    <?php endif; ?>
  </div>
</section>

<?= View::corps('partials/bannieres', ['slot' => 'cote_catalogue']) ?>

<div class="rang etale-corps">
  <aside class="filtres" id="filtres">
    <p class="filtres-accroche"><a href="#filtres">Filtrer</a></p>
    <form data-defer method="get" action="<?= e(Url::to('/catalogue')) ?>">
      <?php if ($maison !== null): ?>
        <input type="hidden" name="maison" value="<?= e($maison['slug']) ?>">
      <?php endif; ?>

      <fieldset class="filtre">
        <legend><?= e(t('catalogue.filtres')) ?></legend>

        <p class="filtre-sous">Maison</p>
        <ul class="filtre-liste">
          <li><a class="<?= ($filtres['maison'] ?? '') === '' ? 'choix' : '' ?>"
                 href="<?= e(filtre_url($filtres, ['maison' => ''])) ?>">Toutes <span><?= (int) $resultat['total'] ?></span></a></li>
          <?php foreach ($facettes['maisons'] as $une): ?>
            <li>
              <a class="<?= ($filtres['maison'] ?? '') === $une['slug'] ? 'choix' : '' ?>"
                 style="--puce:<?= e(couleur_sure($une['couleur'], '#7a3b1e')) ?>"
                 href="<?= e(filtre_url($filtres, ['maison' => $une['slug']])) ?>">
                 <?= e($une['nom']) ?> <span><?= (int) $une['nb'] ?></span></a>
            </li>
          <?php endforeach; ?>
        </ul>

        <p class="filtre-sous">Rayon</p>
        <ul class="filtre-liste">
          <li><a class="<?= ($filtres['rayon'] ?? '') === '' ? 'choix' : '' ?>"
                 href="<?= e(filtre_url($filtres, ['rayon' => ''])) ?>">Tout</a></li>
          <?php foreach ($facettes['rayons'] as $un): ?>
            <li><a class="<?= ($filtres['rayon'] ?? '') === $un['slug'] ? 'choix' : '' ?>"
                   href="<?= e(filtre_url($filtres, ['rayon' => $un['slug']])) ?>">
                   <?= e($un['nom']) ?> <span><?= (int) $un['nb'] ?></span></a></li>
          <?php endforeach; ?>
        </ul>
      </fieldset>

      <fieldset class="filtre">
        <legend><?= e(t('commun.prix')) ?> (DT)</legend>
        <p class="filtre-prix">
          <label><span class="visuel-cache">Prix minimum</span>
            <input type="number" name="prix_min" min="0" step="0.5"
                   value="<?= e($filtres['prix_min'] ?? '') ?>"
                   placeholder="<?= e(number_format((int) $facettes['prix']['mini'] / 1000, 0)) ?>"></label>
          <span aria-hidden="true">—</span>
          <label><span class="visuel-cache">Prix maximum</span>
            <input type="number" name="prix_max" min="0" step="0.5"
                   value="<?= e($filtres['prix_max'] ?? '') ?>"
                   placeholder="<?= e(number_format((int) $facettes['prix']['maxi'] / 1000, 0)) ?>"></label>
        </p>
        <p><label class="filtre-case"><input type="checkbox" name="en_promo" value="1" <?= isset($filtres['en_promo']) ? 'checked' : '' ?>> Prix barrés seulement</label></p>
        <p><label class="filtre-case"><input type="checkbox" name="en_stock" value="1" <?= isset($filtres['en_stock']) ? 'checked' : '' ?>> En stock</label></p>
        <p><label class="filtre-case"><input type="checkbox" name="nouveaute" value="1" <?= isset($filtres['nouveaute']) ? 'checked' : '' ?>> Nouveautés</label></p>
      </fieldset>

      <?php if ($facettes['savoir_faire'] !== []): ?>
        <fieldset class="filtre">
          <legend>Savoir-faire</legend>
          <ul class="filtre-liste">
            <?php foreach ($facettes['savoir_faire'] as $un): ?>
              <li><a class="<?= ($filtres['savoir_faire'] ?? '') === $un['nom'] ? 'choix' : '' ?>"
                     href="<?= e(filtre_url($filtres, ['savoir_faire' => $un['nom']])) ?>">
                     <?= e($un['nom']) ?> <span><?= (int) $un['nb'] ?></span></a></li>
            <?php endforeach; ?>
          </ul>
        </fieldset>
      <?php endif; ?>

      <p class="filtre-geste">
        <button class="btn btn-courroie btn-etroit" type="submit"><?= e(t('catalogue.filtres')) ?></button>
        <?php if ($active !== []): ?><a class="btn btn-lien btn-etroit" href="<?= e(Url::to('/catalogue')) ?>"><?= e(t('catalogue.effacer')) ?></a><?php endif; ?>
      </p>
    </form>
  </aside>

  <div class="etale-etale">
    <header class="etale-tete">
      <p class="etale-compte">
        <?= (int) $resultat['total'] ?> <?= e(t('catalogue.resultats')) ?>
        <?php if ($active !== []): ?>
          <span class="etale-motifs"><?php foreach ($active as $clef => $valeur): ?>
            <em><?= e((string) $clef) ?> : <?= e((string) $valeur) ?></em>
          <?php endforeach; ?></span>
        <?php endif; ?>
      </p>
      <form class="etale-tri" data-defer method="get" action="<?= e(Url::to($cheminPage)) ?>">
        <?php foreach ($filtres as $clef => $valeur): ?>
          <?php if (!in_array($clef, ['tri', 'page'], true) && $valeur !== '' && $valeur !== null): ?>
            <input type="hidden" name="<?= e((string) $clef) ?>" value="<?= e((string) $valeur) ?>">
          <?php endif; ?>
        <?php endforeach; ?>
        <label for="tri-catalogue"><?= e(t('catalogue.tri')) ?>
          <select id="tri-catalogue" name="tri">
            <?php foreach (['pertinence' => 'Le mieux mis en avant', 'nouveautes' => t('catalogue.tri.nouveautes'),
                            'prix_asc' => t('catalogue.tri.prix_asc'), 'prix_desc' => t('catalogue.tri.prix_desc'),
                            'populaire' => t('catalogue.tri.populaire'), 'note' => 'La meilleure note',
                            'nom' => 'Ordre alphabétique'] as $clef => $libelle): ?>
              <option value="<?= e($clef) ?>" <?= $resultat['trie'] === $clef ? 'selected' : '' ?>><?= e($libelle) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <button class="visuel-cache" type="submit">Trier</button>
      </form>
    </header>

    <?php if ($resultat['lignes'] === []): ?>
      <div class="etale-sec">
        <p><?= e(t('catalogue.vide')) ?></p>
        <?php if ($active !== []): ?><p><a class="btn btn-courroie" href="<?= e(Url::to('/catalogue')) ?>"><?= e(t('catalogue.effacer')) ?></a></p><?php endif; ?>
      </div>
    <?php else: ?>
      <div class="grille-produits">
        <?php foreach ($resultat['lignes'] as $un): ?>
          <?= View::corps('partials/carte', ['p' => $un]) ?>
        <?php endforeach; ?>
      </div>
      <?= View::corps('partials/pager', [
          'page' => $resultat['page'], 'parPage' => $resultat['par_page'],
          'total' => $resultat['total'], 'filtres' => $filtres, 'chemin' => $cheminPage,
      ]) ?>
    <?php endif; ?>
  </div>
</div>
