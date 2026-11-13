<?php
/** Les offres du moment : codes actifs et produits réellement barrés. */

$resultat = $resultat ?? ['lignes' => [], 'total' => 0, 'page' => 1, 'par_page' => 12, 'facettes' => []];
$filtres = $filtres ?? [];
?>
<div class="rang promos">
  <?= View::corps('partials/fil', ['fil' => [
      ['titre' => 'Accueil', 'url' => Url::to('/')],
      ['titre' => t('nav.promos'), 'url' => null],
  ]]) ?>

  <header class="promos-tete">
    <p class="sourcils">Rabais du souk</p>
    <h1 class="promos-titre"><?= e(t('nav.promos')) ?></h1>
    <p class="promos-chapeau">
      Les codes se saisissent dans le panier, les remises automatiques seules s’y posent d’elles-mêmes.
      Cumul, quota et date de fin sont vérifiés au moment de payer — rien n’est annoncé si ce n’est pas applicable.
    </p>
  </header>

  <?php if ($promos !== []): ?>
    <ul class="offres-ecs">
      <?php foreach ($promos as $une): ?>
        <li class="offre" style="--puce:<?= e(couleur_sure($une['enseigne_couleur'] ?? null, 'var(--accent)')) ?>">
          <?php if (!empty($une['code'])): ?>
            <p class="offre-code" title="Copiez ce code dans le panier"><?= e($une['code']) ?></p>
          <?php else: ?>
            <p class="offre-auto">Remise automatique</p>
          <?php endif; ?>
          <h2 class="offre-titre"><?= e($langue === 'ar' && !empty($une['libelle_ar']) ? $une['libelle_ar'] : $une['libelle']) ?></h2>
          <?php if (!empty($une['description'])): ?><p class="offre-texte"><?= e($une['description']) ?></p><?php endif; ?>
          <p class="offre-pied">
            <em><?= e(Promo::libelleType($une['type'])) ?></em>
            <?php if ((int) $une['seuil_achat'] > 0): ?><em>dès <?= e(dt((int) $une['seuil_achat'], false)) ?> DT</em><?php endif; ?>
            <?php if ((int) $une['plafond_remise'] > 0): ?><em>dans la limite de <?= e(dt((int) $une['plafond_remise'], false)) ?> DT</em><?php endif; ?>
            <?php if (!empty($une['enseigne_nom'])): ?><em><?= e($une['enseigne_nom']) ?></em><?php endif; ?>
            <?php if ((int) $une['cumulable'] === 0): ?><em>non cumulable</em><?php endif; ?>
            <?php if (!empty($une['fin_at'])): ?><em>jusqu’au <?= e(date('d/m/Y', strtotime((string) $une['fin_at']))) ?></em><?php endif; ?>
          </p>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <section class="promos-etale">
    <header class="etale-tete">
      <p class="etale-compte"><?= (int) $resultat['total'] ?> produits affichés barrés</p>
      <form class="etale-tri" data-defer method="get" action="<?= e(Url::to('/promotions')) ?>">
        <label for="tri-promo"><?= e(t('catalogue.tri')) ?>
          <select id="tri-promo" name="tri">
            <?php foreach (['nouveautes' => t('catalogue.tri.nouveautes'), 'prix_asc' => t('catalogue.tri.prix_asc'),
                            'prix_desc' => t('catalogue.tri.prix_desc'), 'populaire' => t('catalogue.tri.populaire')] as $clef => $libelle): ?>
              <option value="<?= e($clef) ?>" <?= ($resultat['trie'] ?? '') === $clef ? 'selected' : '' ?>><?= e($libelle) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <button class="visuel-cache" type="submit">Trier</button>
      </form>
    </header>

    <?php if ($resultat['lignes'] === []): ?>
      <div class="etale-sec">
        <p>Aucun prix barré en ce moment. Les récoltes repartent au plein tarif.</p>
        <p><a class="btn btn-juste" href="<?= e(Url::to('/catalogue')) ?>"><?= e(t('nav.catalogue')) ?></a></p>
      </div>
    <?php else: ?>
      <div class="grille-produits">
        <?php foreach ($resultat['lignes'] as $un): ?>
          <?= View::corps('partials/carte', ['p' => $un]) ?>
        <?php endforeach; ?>
      </div>
      <?= View::corps('partials/pager', [
          'page' => $resultat['page'], 'parPage' => $resultat['par_page'],
          'total' => $resultat['total'], 'filtres' => $filtres, 'chemin' => '/promotions',
      ]) ?>
    <?php endif; ?>
  </section>
</div>
