<?php
/** Le panier : lignes, quantités, code, préparation cadeau, répartition par maison. */

$panier = $panier ?? ['lignes' => [], 'articles' => 0, 'sous_total' => 0, 'remise' => 0, 'livraison_frais' => 0,
    'port_offert' => false, 'total_ttc' => 0, 'total_ht' => 0, 'total_tva' => 0, 'promos' => [],
    'par_enseigne' => [], 'avertissements' => [], 'code' => '', 'poids' => 0, 'minimum_atteint' => true];
$franchise = (int) ParametresApp::reglure('livraison_gratuite_a', TZ_LOGISTICS_GRATUITE_A_PART);
$reste = max(0, $franchise - ($panier['sous_total'] - $panier['remise']));
?>
<div class="rang panneau">
  <?= View::corps('partials/fil', ['fil' => [
      ['titre' => 'Accueil', 'url' => Url::to('/')],
      ['titre' => t('panier.titre'), 'url' => null],
  ]]) ?>

  <h1 class="panneau-titre"><?= e(t('panier.titre')) ?></h1>

  <?php foreach ($panier['avertissements'] as $unAvertissement): ?>
    <p class="alerte alerte-note"><?= e($unAvertissement) ?></p>
  <?php endforeach; ?>

  <?php if ($panier['lignes'] === []): ?>
    <div class="panneau-sec">
      <p class="panneau-sec-titre"><?= e(t('panier.vide')) ?></p>
      <p class="panneau-sec-geste">
        <a class="btn btn-juste btn-large" href="<?= e(Url::to('/catalogue')) ?>"><?= e(t('panier.continuer')) ?></a>
        <a class="btn btn-lien" href="<?= e(Url::to('/boutiques')) ?>"><?= e(t('enseigne.nav')) ?></a>
      </p>
      <?php if ($promos !== []): ?>
        <ul class="offres-ecs offres-mini">
          <?php foreach (array_slice($promos, 0, 3) as $une): ?>
            <li class="offre">
              <?php if (!empty($une['code'])): ?><p class="offre-code"><?= e($une['code']) ?></p><?php endif; ?>
              <h3 class="offre-titre"><?= e($une['libelle']) ?></h3>
              <?php if (!empty($une['description'])): ?><p class="offre-texte"><?= e($une['description']) ?></p><?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  <?php else: ?>

  <div class="panneau-grille">
    <div class="panneau-col">
      <form method="post" action="<?= e(Url::to('/panier/modifier')) ?>" data-panier-form>
        <input type="hidden" name="csrf" value="<?= e(Csrf::jeton()) ?>">
        <input type="hidden" name="retour" value="/panier">
        <table class="lignes">
          <caption class="visuel-cache">Produits du panier</caption>
          <tbody>
          <?php foreach ($panier['lignes'] as $une): ?>
            <?php
              $image = imageexiste($une['image_principale'] ?? null) ? Url::upload($une['image_principale']) : '';
              $etat = Variante::etatStock($une);
            ?>
            <tr>
              <td class="ligne-visuel">
                <a href="<?= e(Url::to('/produit/' . $une['produit_slug'])) ?>">
                  <?php if ($image !== ''): ?>
                    <img src="<?= e($image) ?>" alt="" width="96" height="96" loading="lazy">
                  <?php else: ?>
                    <span class="sans-image" style="--puce:<?= e(couleur_sure($une['enseigne_couleur'], '#7a3b1e')) ?>"><?= e(mb_strtoupper(mb_substr((string) $une['enseigne_nom'], 0, 1))) ?></span>
                  <?php endif; ?>
                </a>
              </td>
              <td class="ligne-descriptif">
                <p class="ligne-maison" style="--puce:<?= e(couleur_sure($une['enseigne_couleur'], '#7a3b1e')) ?>"><?= e($une['enseigne_nom']) ?></p>
                <p class="ligne-nom"><a href="<?= e(Url::to('/produit/' . $une['produit_slug'])) ?>"><?= e($une['produit_nom']) ?></a></p>
                <p class="ligne-format"><?= e($une['variante_nom']) ?><?= $une['details'] !== null ? ' · ' . e($une['details']) : '' ?></p>
                <p class="ligne-etat etat-<?= e($etat) ?>">
                  <?= e($etat === 'en_stock' ? t('produit.en_stock') : ($etat === 'bientot' ? t('produit.bientot') : t('produit.epuise'))) ?>
                </p>
                <?php if ((int) ($une['restant'] ?? 0) <= 3 && (int) ($une['restant'] ?? 0) > 0): ?>
                  <p class="ligne-serre">Plus que <?= (int) $une['restant'] ?> disponibles.</p>
                <?php endif; ?>
                <p class="ligne-prix"><?= e(dt((int) $une['prix'])) ?> l’unité
                  <?php if ((int) $une['prix_barre'] > (int) $une['prix']): ?>
                    <del><?= e(dt((int) $une['prix_barre'])) ?></del>
                  <?php endif; ?>
                </p>
              </td>
              <td class="ligne-quantite">
                <label class="visuel-cache" for="q<?= (int) $une['variante_id'] ?>">Quantité</label>
                <input id="q<?= (int) $une['variante_id'] ?>" type="number" min="0" max="99"
                       name="quantites[<?= (int) $une['variante_id'] ?>]"
                       value="<?= (int) $une['quantite_demandee'] ?>" data-quanti>
              </td>
              <td class="ligne-total">
                <strong><?= e(dt((int) $une['total_brut'] - (int) $une['remise_ligne'])) ?></strong>
                <?php if ((int) $une['remise_ligne'] > 0): ?>
                  <del><?= e(dt((int) $une['total_brut'])) ?></del>
                <?php endif; ?>
              </td>
              <td class="ligne-sort">
                <button class="retirer" type="submit" name="variante_id" value="<?= (int) $une['variante_id'] ?>"
                        formaction="<?= e(Url::to('/panier/supprimer')) ?>" aria-label="Retirer <?= e($une['produit_nom']) ?>">×</button>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <p class="lignes-bas">
          <button class="btn btn-courroie btn-etroit" type="submit">Mettre à jour</button>
          <button class="btn btn-lien btn-etroit" type="submit" formaction="<?= e(Url::to('/panier/vider')) ?>"
                  data-confirm="Vider entièrement le panier ?">Vider</button>
          <a class="btn btn-lien btn-etroit" href="<?= e(Url::to('/catalogue')) ?>"><?= e(t('panier.continuer')) ?></a>
        </p>
      </form>

      <form class="cadeau" method="post" action="<?= e(Url::to('/panier/emballage')) ?>">
        <input type="hidden" name="csrf" value="<?= e(Csrf::jeton()) ?>">
        <input type="hidden" name="retour" value="/panier">
        <p><label class="filtre-case"><input type="checkbox" name="cadeau" value="1" <?= Panier::emballage() ? 'checked' : '' ?>>
          Emballage cadeau et carte écrite à la main (offert)</label></p>
        <p><label>Message à poser sur la carte
          <textarea name="message" rows="2" maxlength="240" placeholder="Bonne fête, Slim…"><?= e(Panier::message()) ?></textarea></label></p>
        <p><button class="btn btn-courroie btn-etroit" type="submit">Enregistrer</button></p>
      </form>

      <?php if ($panier['par_enseigne'] !== []): ?>
        <section class="maisons-part">
          <h2>Ce que prépare chaque maison</h2>
          <ul>
            <?php foreach ($panier['par_enseigne'] as $une): ?>
              <li style="--puce:<?= e(couleur_sure($une['couleur'] ?? null, 'var(--primaire)')) ?>">
                <span><a href="<?= e(Url::to('/boutique/' . $une['enseigne_slug'])) ?>"><?= e($une['enseigne_nom']) ?></a></span>
                <em><?= (int) $une['articles'] ?> article<?= $une['articles'] > 1 ? 's' : '' ?></em>
                <strong><?= e(dt((int) $une['net'])) ?></strong>
              </li>
            <?php endforeach; ?>
          </ul>
          <p class="maisons-part-note">Chaque maison prépare et expédie sa part ; vous recevez une facture par maison.</p>
        </section>
      <?php endif; ?>
    </div>

    <aside class="panneau-recap" aria-label="<?= e(t('commande.recapitulatif')) ?>">
      <h2><?= e(t('commande.recapitulatif')) ?></h2>

      <form class="recap-code" method="post" action="<?= e(Url::to('/panier/code')) ?>">
        <input type="hidden" name="csrf" value="<?= e(Csrf::jeton()) ?>">
        <input type="hidden" name="retour" value="/panier">
        <label for="code-promo"><?= e(t('promo.code')) ?>
          <input id="code-promo" type="text" name="code" value="<?= e($panier['code']) ?>"
                 maxlength="40" placeholder="TOZEUR10" autocomplete="off"></label>
        <button class="btn btn-courroie btn-etroit" type="submit"><?= e(t('promo.appliquer')) ?></button>
      </form>

      <dl class="recap">
        <div><dt><?= e(t('panier.sous_total')) ?></dt><dd><?= e(dt($panier['sous_total'])) ?></dd></div>
        <?php if ($panier['remise'] > 0): ?>
          <div class="recap-juste"><dt><?= e(t('panier.remise')) ?></dt><dd>&minus;<?= e(dt($panier['remise'])) ?></dd></div>
          <?php foreach ($panier['promos'] as $une): ?>
            <div class="recap-detail"><dt><?= e($une['libelle']) ?></dt>
              <dd><?= $une['port_offert'] ? 'port offert' : '&minus;' . e(dt((int) $une['montant'])) ?></dd></div>
          <?php endforeach; ?>
        <?php endif; ?>
        <div><dt><?= e(t('panier.livraison')) ?></dt>
          <dd><?= $panier['port_offert'] ? 'Offert' : ($panier['livraison_frais'] > 0 ? e(dt($panier['livraison_frais'])) : 'Calculée à l’étape suivante') ?></dd></div>
        <div class="recap-total"><dt><?= e(t('panier.total')) ?></dt><dd><?= e(dt($panier['total_ttc'])) ?></dd></div>
        <div class="recap-detail"><dt>dont <?= e(Money::tauxPourLabel((int) ParametresApp::reglure('tva_standard'))) ?></dt>
          <dd><?= e(dt($panier['total_tva'])) ?></dd></div>
        <div class="recap-detail"><dt>hors taxes</dt><dd><?= e(dt($panier['total_ht'])) ?></dd></div>
      </dl>

      <?php if ($reste > 0 && !$panier['port_offert']): ?>
        <p class="recap-franchise">
          Encore <strong><?= e(dt($reste)) ?></strong> pour le port offert.
          <span class="barre"><span class="barre-plein" style="width:<?= (int) min(100, round(100 * ($panier['sous_total'] - $panier['remise']) / max(1, $franchise))) ?>%"></span></span>
        </p>
      <?php elseif ($panier['port_offert'] || $panier['sous_total'] - $panier['remise'] >= $franchise): ?>
        <p class="recap-franchise recap-franchise-juste">Le port vous est offert.</p>
      <?php endif; ?>

      <?php if (!$panier['minimum_atteint']): ?>
        <p class="alerte alerte-faute">Le minimum de commande n’est pas atteint.</p>
      <?php endif; ?>

      <p class="recap-geste">
        <a class="btn btn-juste btn-large btn-plein" href="<?= e(Url::to('/commande')) ?>"><?= e(t('panier.commander')) ?></a>
      </p>
      <p class="recap-sure">Paiement carte, livraison ou virement · Retrait possible à Tozeur · Facture émise par la plateforme.</p>

      <?php if ($transporteurs !== []): ?>
        <ul class="recap-transport">
          <?php foreach ($transporteurs as $un): ?>
            <li><strong><?= e($un['nom']) ?></strong>
              <span><?= (int) $un['delai_jours'] ?> j ·
                <?= $un['type'] === 'retait' ? 'gratuit' : e(dt(Transporteur::frais($un, (int) $panier['poids'], $panier['sous_total'] - $panier['remise']))) ?></span></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </aside>
  </div>
  <?php endif; ?>
</div>
