<?php
/** Une maison : sa vitrine de marque, ses produits. */

$resultat = $resultat ?? ['lignes' => [], 'total' => 0, 'page' => 1, 'par_page' => 12, 'facettes' => ['rayons' => [], 'maisons' => [], 'prix' => ['mini' => 0, 'maxi' => 0], 'savoir_faire' => []]];
$filtres = $filtres ?? [];
$fond = imageexiste($maison['banniere'] ?? null) ? Url::upload($maison['banniere']) : '';
$liens = static fn(string $clef, string $valeur): string => filtre_url($filtres, [$clef => $valeur], '/boutique/' . $maison['slug']);
?>
<section class="enseigne-voile">
  <div class="rang">
    <?= View::corps('partials/fil', ['fil' => [
        ['titre' => 'Accueil', 'url' => Url::to('/')],
        ['titre' => t('enseigne.nav'), 'url' => Url::to('/boutiques')],
        ['titre' => $maison['nom'], 'url' => null],
    ]]) ?>
    <div class="enseigne-tete">
      <div class="enseigne-marque">
        <?php if (!empty($maison['logo']) && imageexiste($maison['logo'])): ?>
          <img class="enseigne-logo" src="<?= e(Url::upload($maison['logo'])) ?>" alt="">
        <?php else: ?>
          <span class="enseigne-sceau" aria-hidden="true"><?= e(mb_strtoupper(mb_substr((string) $maison['nom'], 0, 1))) ?></span>
        <?php endif; ?>
        <div>
          <p class="sourcils"><?= e($maison['ville']) ?></p>
          <h1 class="enseigne-nom"><?= e($maison['nom']) ?></h1>
          <p class="enseigne-slogan"><?= e($maison['slogan']) ?></p>
          <p class="enseigne-geste">
            <?php if (!empty($maison['telephone'])): ?>
              <a class="btn btn-courroie" href="tel:<?= e(preg_replace('/\s+/', '', (string) $maison['telephone'])) ?>">
                Appeler l’atelier · <?= e($maison['telephone']) ?></a>
            <?php endif; ?>
            <a class="btn btn-lien" href="<?= e(Url::to('/boutiques')) ?>">Autres maisons</a>
          </p>
        </div>
      </div>
      <?php if ($fond !== ''): ?>
        <img class="enseigne-fond" src="<?= e($fond) ?>" alt="" width="1200" height="700">
      <?php endif; ?>
    </div>
    <?php if (!empty($maison['description'])): ?>
      <div class="enseigne-texte"><p><?= e($maison['description']) ?></p></div>
    <?php endif; ?>
  </div>
</section>

<section class="boutique-corps">
  <div class="rang boutique-grille">
    <aside class="filtres">
      <form data-defer method="get" action="<?= e(Url::to('/boutique/' . $maison['slug'])) ?>">
        <fieldset class="filtre">
          <legend>Rayon</legend>
          <ul class="filtre-liste">
            <li><a class="<?= ($filtres['rayon'] ?? '') === '' ? 'choix' : '' ?>"
                   href="<?= e($liens('rayon', '')) ?>">Tout</a></li>
            <?php foreach ($resultat['facettes']['rayons'] as $unRayon): ?>
              <li><a class="<?= ($filtres['rayon'] ?? '') === $unRayon['slug'] ? 'choix' : '' ?>"
                     href="<?= e($liens('rayon', $unRayon['slug'])) ?>">
                     <?= e($unRayon['nom']) ?> <span><?= (int) $unRayon['nb'] ?></span></a></li>
            <?php endforeach; ?>
          </ul>
        </fieldset>

        <fieldset class="filtre">
          <legend>Savoir-faire</legend>
          <ul class="filtre-liste">
            <?php foreach ($resultat['facettes']['savoir_faire'] as $unMetier): ?>
              <li><a class="<?= ($filtres['savoir_faire'] ?? '') === $unMetier['nom'] ? 'choix' : '' ?>"
                     href="<?= e($liens('savoir_faire', $unMetier['nom'])) ?>">
                     <?= e($unMetier['nom']) ?> <span><?= (int) $unMetier['nb'] ?></span></a></li>
            <?php endforeach; ?>
          </ul>
        </fieldset>

        <fieldset class="filtre">
          <legend><?= e(t('commun.prix')) ?> (DT)</legend>
          <p class="filtre-prix">
            <label><span class="visuel-cache">Prix minimum</span>
              <input type="number" name="prix_min" min="0" step="0.5" value="<?= e($filtres['prix_min'] ?? '') ?>" placeholder="<?= e(number_format((int) $resultat['facettes']['prix']['mini'] / 1000, 0)) ?>"></label>
            <span aria-hidden="true">—</span>
            <label><span class="visuel-cache">Prix maximum</span>
              <input type="number" name="prix_max" min="0" step="0.5" value="<?= e($filtres['prix_max'] ?? '') ?>" placeholder="<?= e(number_format((int) $resultat['facettes']['prix']['maxi'] / 1000, 0)) ?>"></label>
          </p>
          <p><label class="filtre-case"><input type="checkbox" name="en_promo" value="1" <?= isset($filtres['en_promo']) ? 'checked' : '' ?>> Seulement les prix barrés</label></p>
          <p><label class="filtre-case"><input type="checkbox" name="en_stock" value="1" <?= isset($filtres['en_stock']) ? 'checked' : '' ?>> En stock</label></p>
          <p class="filtre-geste">
            <button class="btn btn-courroie btn-etroit" type="submit">Filtrer</button>
            <a class="btn btn-lien btn-etroit" href="<?= e(Url::to('/boutique/' . $maison['slug'])) ?>">Effacer</a>
          </p>
        </fieldset>
      </form>
    </aside>

    <div class="boutique-etale">
      <header class="etale-tete">
        <p class="etale-compte"><?= (int) $resultat['total'] ?> produits de cette maison</p>
        <form class="etale-tri" data-defer method="get" action="<?= e(Url::to('/boutique/' . $maison['slug'])) ?>">
          <?php foreach ($filtres as $clef => $valeur): ?>
            <?php if (!is_array($valeur) && $valeur !== '' && !in_array($clef, ['tri', 'page'], true)): ?>
              <input type="hidden" name="<?= e($clef) ?>" value="<?= e($valeur) ?>">
            <?php endif; ?>
          <?php endforeach; ?>
          <label for="tri-maison"><?= e(t('catalogue.tri')) ?>
            <select id="tri-maison" name="tri">
              <?php foreach (['nouveautes' => t('catalogue.tri.nouveautes'), 'prix_asc' => t('catalogue.tri.prix_asc'),
                              'prix_desc' => t('catalogue.tri.prix_desc'), 'populaire' => t('catalogue.tri.populaire'),
                              'note' => 'La note', 'nom' => 'A → Z'] as $clef => $libelle): ?>
                <option value="<?= e($clef) ?>" <?= ($resultat['trie'] ?? '') === $clef ? 'selected' : '' ?>><?= e($libelle) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <button class="visuel-cache" type="submit">Trier</button>
        </form>
      </header>

      <?php if ($resultat['lignes'] === []): ?>
        <p class="etale-vide"><?= e(t('catalogue.vide')) ?></p>
      <?php else: ?>
        <div class="grille-produits">
          <?php foreach ($resultat['lignes'] as $un): ?>
            <?= View::corps('partials/carte', ['p' => $un]) ?>
          <?php endforeach; ?>
        </div>
        <?= View::corps('partials/pager', [
            'page' => $resultat['page'], 'parPage' => $resultat['par_page'],
            'total' => $resultat['total'], 'filtres' => $filtres,
            'chemin' => '/boutique/' . $maison['slug'],
        ]) ?>
      <?php endif; ?>
    </div>
  </div>
</section>
