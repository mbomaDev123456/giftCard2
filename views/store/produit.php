<?php
/** Fiche produit : galerie, choix du format, achat, avis, suggestions. */

$p = $produit;
$maison = ['nom' => $p['enseigne_nom'], 'slug' => $p['enseigne_slug'], 'couleur' => $p['enseigne_couleur']];
$galerie = json_decode((string) ($p['galerie'] ?? '[]'), true) ?: [];
$images = array_values(array_filter(array_merge([$p['image_principale']], $galerie),
    static fn(?string $u): bool => imageexiste($u)));
$certifications = array_filter(array_map('trim', preg_split('/[,\/]/', (string) ($p['certifications'] ?? '')) ?: []));
$couleur = couleur_sure($p['enseigne_couleur'], 'var(--primaire)');
$selectionnee = $variante;
$prixSelectionne = $selectionnee === null ? (int) $p['prix_min'] : (int) $selectionnee['prix'];
$barre = $selectionnee === null ? 0 : (int) $selectionnee['prix_barre'];
$tvaLabel = Money::tauxPourLabel((int) $p['categorie_tva'] ?? $p['tva_taux'] ?? 19000);
$note = (float) $p['note_moyenne'];
$avisTotal = array_sum($echelle);
?>
<div class="rang fiche">
  <?= View::corps('partials/fil', ['fil' => $p['fil_ariane']]) ?>

  <div class="fiche-corps">
    <section class="fiche-visuel">
      <?php if ($images === []): ?>
        <figure class="visuel-kassee" style="--puce:<?= e($couleur) ?>">
          <span class="sans-image sans-image-grand"><?= e(mb_strtoupper(mb_substr((string) $p['nom'], 0, 1))) ?></span>
          <figcaption>Aucune photo posée pour cette pièce</figcaption>
        </figure>
      <?php else: ?>
        <figure class="visuel-cadre">
          <img id="visuel-principal" src="<?= e(Url::upload($images[0])) ?>"
               alt="<?= e($p['nom']) ?>" width="900" height="900" fetchpriority="high">
          <?php if (!empty($p['label'])): ?><span class="pastille pastille-label"><?= e($p['label']) ?></span><?php endif; ?>
        </figure>
        <?php if (count($images) > 1): ?>
          <ul class="visuel-pouces">
            <?php foreach ($images as $position => $uneImage): ?>
              <li><button type="button" data-visuel="<?= e(Url::upload($uneImage)) ?>"
                          class="<?= $position === 0 ? 'choix' : '' ?>"
                          aria-label="Photo <?= (int) $position + 1 ?>">
                <img src="<?= e(Url::upload($uneImage)) ?>" alt="" loading="lazy" width="120" height="120">
              </button></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      <?php endif; ?>

      <?php if (!empty($p['savoir_faire']) || $certifications !== []): ?>
        <ul class="visuel-garanties">
          <?php if (!empty($p['savoir_faire'])): ?><li><?= e($p['savoir_faire']) ?></li><?php endif; ?>
          <?php foreach ($certifications as $une): ?><li><?= e($une) ?></li><?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>

    <section class="fiche-achat">
      <p class="fiche-maison" style="--puce:<?= e($couleur) ?>">
        <a href="<?= e(Url::to('/boutique/' . $maison['slug'])) ?>"><?= e($maison['nom']) ?></a>
        <span>· <?= e($p['enseigne_ville']) ?></span>
      </p>
      <h1 class="fiche-titre"><?= e(nom_produit($p)) ?></h1>
      <?php if (I18n::estArabe() && !empty($p['nom'])): ?>
        <p class="fiche-titre-alt"><?= e($p['nom']) ?></p>
      <?php elseif (!empty($p['nom_ar'])): ?>
        <p class="fiche-titre-alt" dir="rtl"><?= e($p['nom_ar']) ?></p>
      <?php endif; ?>

      <?php if (!empty($p['resume'])): ?><p class="fiche-chapeau"><?= e($p['resume']) ?></p><?php endif; ?>

      <?php if ($avisTotal > 0): ?>
        <p class="fiche-reputation">
          <?= View::corps('partials/etoiles', ['note' => $note]) ?>
          <a href="#avis"><?= e(number_format($note, 1, ',', ' ')) ?> / 5</a>
          <span>(<?= (int) $avisTotal ?> avis<?= $vendus > 0 ? ' · ' . (int) $vendus . ' vendus' : '' ?>)</span>
        </p>
      <?php endif; ?>

      <?php if ($variantes === []): ?>
        <p class="note-note">Cette pièce n’est plus commandable en ligne. Appelez la maison au <?= e($p['enseigne_telephone']) ?>.</p>
      <?php else: ?>
        <form method="post" action="<?= e(Url::to('/panier/ajouter')) ?>" class="achat" data-achat>
          <input type="hidden" name="retour" value="<?= e('/produit/' . $p['slug']) ?>">
          <input type="hidden" name="csrf" value="<?= e(Csrf::jeton()) ?>">

          <?php if (count($variantes) > 1): ?>
            <p class="achat-legende"><?= e(t('produit.variantes')) ?></p>
            <ul class="formats">
              <?php foreach ($variantes as $une): ?>
                <?php
                  $restant = Variante::disponible((int) $une['stock_quantite'], (int) $une['reserve_vente']);
                  $etat = Variante::etatStock($une);
                ?>
                <li>
                  <input type="radio" class="format-choix" name="variante_id" id="format-<?= (int) $une['id'] ?>"
                         value="<?= (int) $une['id'] ?>" data-prix="<?= (int) $une['prix'] ?>"
                         data-barre="<?= (int) $une['prix_barre'] ?>" data-restant="<?= $restant ?>"
                         <?= $selectionnee !== null && (int) $selectionnee['id'] === (int) $une['id'] ? 'checked' : '' ?>
                         <?= $restant === 0 ? 'disabled' : '' ?>>
                  <label for="format-<?= (int) $une['id'] ?>">
                    <span class="format-nom"><?= e($une['nom']) ?></span>
                    <?php if (!empty($une['details'])): ?><span class="format-detail"><?= e($une['details']) ?></span><?php endif; ?>
                    <span class="format-prix"><?= e(dt((int) $une['prix'])) ?></span>
                    <?php if ((int) $une['prix_barre'] > (int) $une['prix']): ?>
                      <span class="format-barre"><?= e(dt((int) $une['prix_barre'])) ?></span>
                    <?php endif; ?>
                    <span class="format-etat etat-<?= e($etat) ?>">
                      <?= e($etat === 'en_stock' ? t('produit.en_stock') : ($etat === 'bientot' ? t('produit.bientot') : t('produit.epuise'))) ?>
                    </span>
                  </label>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php else: ?>
            <?php $une = $variantes[0]; ?>
            <input type="hidden" name="variante_id" class="format-choix" value="<?= (int) $une['id'] ?>"
                   data-prix="<?= (int) $une['prix'] ?>" data-barre="<?= (int) $une['prix_barre'] ?>"
                   data-restant="<?= Variante::disponible((int) $une['stock_quantite'], (int) $une['reserve_vente']) ?>">
            <p class="format-unique"><span><?= e($une['nom']) ?></span>
              <?php if (!empty($une['details'])): ?><em><?= e($une['details']) ?></em><?php endif; ?></p>
          <?php endif; ?>

          <p class="achat-total">
            <strong data-prix-affiche><?= e(dt($prixSelectionne)) ?></strong>
            <del data-prix-barre<?= $barre > $prixSelectionne ? '' : ' hidden' ?>><?= e(dt($barre)) ?></del>
            <small><?= e($tvaLabel) ?> inclus</small>
          </p>

          <p class="achat-restant" data-restant-affiche>
            <?php if ($selectionnee !== null): ?>
              <?php $restant = Variante::disponible((int) $selectionnee['stock_quantite'], (int) $selectionnee['reserve_vente']); ?>
              <?= $restant > 0
                ? ($restant <= 3 ? 'Plus que ' . (int) $restant . ' pièces disponibles.' : 'En stock — expédié sous 48 h ouvrées.')
                : 'Réassort en cours auprès de la maison.' ?>
            <?php endif; ?>
          </p>

          <div class="achat-geste">
            <label class="quantite" for="quantite-achat"><?= e(t('produit.quantite')) ?>
              <span class="quantite-boite">
                <button type="button" data-pas="-1" aria-label="Retirer une pièce">−</button>
                <input id="quantite-achat" name="quantite" type="number" min="1" max="99" value="1" inputmode="numeric">
                <button type="button" data-pas="1" aria-label="Ajouter une pièce">+</button>
              </span>
            </label>
            <button class="btn btn-juste btn-large" type="submit" <?= ($selectionnee === null || $prixSelectionne <= 0) ? 'disabled' : '' ?>>
              <?= e(t('panier.ajouter')) ?>
            </button>
          </div>
        </form>
      <?php endif; ?>

      <ul class="livraison-bref">
        <?php foreach (array_slice($transporteurs, 0, 3) as $un): ?>
          <li>
            <strong><?= e($un['nom']) ?></strong>
            <span><?= (int) $un['delai_jours'] ?> j ·
              <?= $un['type'] === 'retait' ? 'gratuit' : e(dt(Transporteur::frais($un, (int) $p['poids_grammes'], $prixSelectionne))) ?></span>
          </li>
        <?php endforeach; ?>
        <li class="livraison-plus"><a href="<?= e(Url::to('/page/livraison')) ?>">Toutes les modalités de livraison</a></li>
      </ul>

      <dl class="fiche-carte">
        <div><dt><?= e(t('produit.origine')) ?></dt><dd><?= e($p['origine'] ?? $p['enseigne_ville']) ?></dd></div>
        <?php if (!empty($p['matiere'])): ?><div><dt>Matière</dt><dd><?= e($p['matiere']) ?></dd></div><?php endif; ?>
        <?php if (!empty($p['dimensions'])): ?><div><dt>Dimensions</dt><dd><?= e($p['dimensions']) ?></dd></div><?php endif; ?>
        <?php if ((int) $p['poids_grammes'] > 0): ?><div><dt>Poids</dt><dd><?= e(number_format((int) $p['poids_grammes'] / 1000, 3, ',', ' ')) ?> kg</dd></div><?php endif; ?>
        <?php if ((int) $p['peremption_jours'] > 0): ?><div><dt>À consommer</dt><dd>dans <?= (int) $p['peremption_jours'] ?> jours</dd></div><?php endif; ?>
        <?php if (!empty($p['certifications'])): ?><div><dt>Certifications</dt><dd><?= e($p['certifications']) ?></dd></div><?php endif; ?>
        <div><dt>Reference</dt><dd><?= e(strtoupper((string) $p['slug'])) ?></dd></div>
      </dl>
    </section>
  </div>

  <section class="fiche-lecture">
    <div class="lecture-col">
      <h2><?= e(t('produit.description')) ?></h2>
      <div class="lecture-texte"><?= nl2br(e($p['description'] ?? $p['resume'] ?? '')) ?></div>
      <?php if (!empty($p['conseils'])): ?>
        <h2><?= e(t('produit.conseils')) ?></h2>
        <div class="lecture-texte"><?= nl2br(e($p['conseils'])) ?></div>
      <?php endif; ?>
    </div>

    <aside class="lecture-maison">
      <p class="sourcils">La maison</p>
      <h3><?= e($maison['nom']) ?></h3>
      <p><?= e($p['enseigne_ville']) ?></p>
      <?php if (!empty($p['enseigne_telephone'])): ?>
        <p><a href="tel:<?= e(preg_replace('/\s+/', '', (string) $p['enseigne_telephone'])) ?>"><?= e($p['enseigne_telephone']) ?></a></p>
      <?php endif; ?>
      <p><a class="btn btn-courroie btn-etroit" href="<?= e(Url::to('/boutique/' . $maison['slug'])) ?>"><?= e(t('enseigne.visiter')) ?></a></p>
    </aside>
  </section>

  <section class="fiche-avis" id="avis">
    <header class="avis-tete">
      <h2>Vos avis</h2>
      <div class="avis-resume">
        <p class="avis-moyenne"><?= e(number_format($note, 1, ',', ' ')) ?><small> / 5</small></p>
        <ul class="avis-barres">
          <?php for ($etoi = 5; $etoi >= 1; $etoi--): ?>
            <?php $nb = (int) ($echelle[$etoi] ?? 0); ?>
            <li>
              <span><?= $etoi ?> ★</span>
              <span class="barre"><span class="barre-plein" style="width:<?= $avisTotal > 0 ? (int) round(100 * $nb / $avisTotal) : 0 ?>%"></span></span>
              <em><?= $nb ?></em>
            </li>
          <?php endfor; ?>
        </ul>
      </div>
    </header>

    <?php if ($p['avis'] === []): ?>
      <p class="avis-vide">Personne n’a encore écrit sur cette pièce. Soyez la première ou le premier.</p>
    <?php else: ?>
      <ul class="avis-liste">
        <?php foreach ($p['avis'] as $un): ?>
          <li class="avis">
            <p class="avis-entete">
              <?= View::corps('partials/etoiles', ['note' => (float) $un['note']]) ?>
              <strong><?= e($un['titre']) ?></strong>
            </p>
            <p class="avis-who">
              <?= e($un['auteur']) ?>
              <?php if ((int) $un['achat_verifie'] === 1): ?><span class="verifie">Achat vérifié</span><?php endif; ?>
              <time datetime="<?= e(substr((string) $un['created_at'], 0, 10)) ?>"><?= e(date('d/m/Y', strtotime((string) $un['created_at']))) ?></time>
            </p>
            <p class="avis-texte"><?= e($un['texte']) ?></p>
            <?php if (!empty($un['reponse'])): ?>
              <p class="avis-reponse"><span>La maison répond :</span> <?= e($un['reponse']) ?></p>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <details class="avis-forme" <?= Auth::client() === null && Http::get('ecrire') === '1' ? 'open' : '' ?>>
      <summary>Donner mon avis</summary>
      <form method="post" action="<?= e(Url::to('/produit/' . $p['slug'] . '/avis')) ?>">
        <input type="hidden" name="csrf" value="<?= e(Csrf::jeton()) ?>">
        <p class="note-choix">
          <span>Votre note</span>
          <?php for ($etoi = 5; $etoi >= 1; $etoi--): ?>
            <input type="radio" id="note-<?= $etoi ?>" name="note" value="<?= $etoi ?>" required>
            <label for="note-<?= $etoi ?>"><?= $etoi ?> ★</label>
          <?php endfor; ?>
        </p>
        <?php if (Auth::client() === null): ?>
          <p><label>Votre prénom ou maison
            <input type="text" name="auteur" maxlength="60" required></label></p>
        <?php endif; ?>
        <p><label>Titre <input type="text" name="titre" maxlength="80"></label></p>
        <p><label>Votre expérience
          <textarea name="texte" rows="4" minlength="10" maxlength="1200" required
                    placeholder="La texture, le goût, l’emballage, le délai…"></textarea></label></p>
        <p><button class="btn btn-juste" type="submit">Envoyer l’avis</button>
          <small>Publié après lecture de la régie.</small></p>
      </form>
    </details>
  </section>

  <?php if ($p['associes'] !== []): ?>
    <section class="rail">
      <header class="rail-tete"><h2 class="rail-titre"><?= e(t('produit.on_achete')) ?></h2></header>
      <div class="grille-produits">
        <?php foreach ($p['associes'] as $un): ?>
          <?= View::corps('partials/carte', ['p' => $un]) ?>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>
</div>

<?php if ($variantes !== []): ?>
<script type="application/ld+json">
<?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'name' => $p['nom'],
    'image' => $images === [] ? null : array_map(static fn(string $u): string => Url::upload($u), $images),
    'description' => $p['resume'] ?? null,
    'sku' => $selectionnee['sku'] ?? null,
    'brand' => ['@type' => 'Brand', 'name' => $maison['nom']],
    'offers' => [
        '@type' => 'Offer',
        'priceCurrency' => TZ_CURRENCY,
        'price' => number_format($prixSelectionne / 1000, 3, '.', ''),
        'availability' => ($selectionnee !== null && Variante::disponible((int) $selectionnee['stock_quantite'], (int) $selectionnee['reserve_vente']) > 0)
            ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
        'url' => Http::urlOrigine() . Url::to('/produit/' . $p['slug']),
    ],
    'aggregateRating' => $avisTotal > 0 ? [
        '@type' => 'AggregateRating',
        'ratingValue' => number_format($note, 2, '.', ''),
        'reviewCount' => $avisTotal,
    ] : null,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
</script>
<?php endif; ?>
