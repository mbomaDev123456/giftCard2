<?php
/** Mise en page de la vitrine : en-tête de maison, navigation, panier, pied. */

$maisonId = ParametresApp::enseigneCourante();
$maison = $maisonId === null ? null : Enseigne::parId($maisonId);
$enseignes = ParametresApp::enseignesDisponibles();
$rayons = Categorie::arbre();
$pagesMenu = Page::menu();
$articles = Panier::articles();

// Les pages qui ont déjà tarifées (panier, fiche, commande) passent leur panier ;
// les autres paient ce seul calcul.
$panier ??= GestionPanier::duSession();

$titre = trim((string) ($titre_meta ?? ''));
if ($titre === '') {
    $titre = $maison === null
        ? (string) ParametresApp::reglure('meta_titre', TZ_SITE_NAME . ' — Dattes et artisanat de Tozeur')
        : trim((string) $maison['nom']) . ' — ' . TZ_SITE_NAME;
}
$description = trim((string) ($description_meta ?? ''));
if ($description === '') {
    $description = trim((string) ($maison['meta_description']
        ?? ParametresApp::reglure('meta_description',
            'Dattes Deglet Nour, terre cuite, tapis noués et huiles du Jérid : les maisons artisanales de Tozeur.')));
}

$theme = $maison === null
    ? sprintf(
        '--primaire:%s;--accent:%s',
        couleur_sure(ParametresApp::reglure('couleur_primaire'), '#c06030'),
        couleur_sure(ParametresApp::reglure('couleur_accent'), '#d99a2b')
      )
    : sprintf(
        '--primaire:%s;--accent:%s',
        couleur_sure($maison['couleur'], '#c06030'),
        couleur_sure($maison['couleur_secondaire'], '#d99a2b')
      );

$policeTitre = (string) ParametresApp::reglure('police_titre', 'Georgia, "Times New Roman", serif');
$flash = Session::consommerFlash();
$current = Http::chemin();
$siteNom = (string) ParametresApp::reglure('site_nom', TZ_SITE_NAME);
?>
<!DOCTYPE html>
<html lang="<?= e($langue) ?>" dir="<?= e($sens) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($titre) ?></title>
<meta name="description" content="<?= e($description) ?>">
<meta property="og:title" content="<?= e($titre) ?>">
<meta property="og:description" content="<?= e($description) ?>">
<meta property="og:type" content="website">
<?php if (!empty($visuel_og)): ?>
<meta property="og:image" content="<?= e(Url::upload($visuel_og)) ?>">
<?php endif; ?>
<link rel="icon" href="<?= e(Url::asset('img/sceau.svg')) ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= e(Url::asset('css/tozeur.css')) ?>">
<style>:root{--police-titre:<?= e($policeTitre) ?>}</style>
</head>
<body class="<?= $maison === null ? 'plateforme' : 'sous-maison' ?>" style="<?= e($theme) ?>" data-page="<?= e($current) ?>">

<p class="passer"><a href="#contenu">Aller au contenu</a></p>

<header class="bandeau-haut">
  <div class="cordeau">
  <div class="rang rang-utile">
    <p class="souffle"><?= e(t('footer.parure')) ?></p>
    <nav class="utile" aria-label="Compte">
      <a href="<?= e(Url::to('/page/livraison')) ?>"><?= e(t('nav.livraison')) ?></a>
      <a href="<?= e(Url::to('/suivi')) ?>">Suivi de commande</a>
      <?php if (Auth::client() === null): ?>
        <a href="<?= e(Url::to('/compte/connexion')) ?>"><?= e(t('compte.connexion')) ?></a>
        <a href="<?= e(Url::to('/compte/inscription')) ?>"><?= e(t('compte.inscription')) ?></a>
      <?php else: ?>
        <a href="<?= e(Url::to('/compte')) ?>"><?= e(Auth::client()['nom']) ?></a>
        <a href="<?= e(Url::to('/compte/deconnexion')) ?>"><?= e(t('compte.deconnexion')) ?></a>
      <?php endif; ?>
      <span class="langue">
        <a class="<?= $langue === 'fr' ? 'actif' : '' ?>" href="<?= e(filtre_url($_GET, ['langue' => 'fr'], $current)) ?>">FR</a>
        <a class="<?= $langue === 'ar' ? 'actif' : '' ?>" href="<?= e(filtre_url($_GET, ['langue' => 'ar'], $current)) ?>">عربي</a>
      </span>
    </nav>
  </div>
  </div>

  <div class="rang rang-principal">
    <a class="griffe" href="<?= e(Url::to('/')) ?>">
      <?php if (!empty($maison['logo']) && imageexiste($maison['logo'])): ?>
        <img class="griffe-image" src="<?= e(Url::upload($maison['logo'])) ?>" alt="<?= e($maison['nom']) ?>">
      <?php else: ?>
        <span class="griffe-sceau" aria-hidden="true"><?= e($maison === null ? 'TS' : mb_strtoupper(mb_substr((string) $maison['nom'], 0, 1))) ?></span>
      <?php endif; ?>
      <span class="griffe-texte">
        <strong><?= e($maison['nom'] ?? $siteNom) ?></strong>
        <em><?= e($maison['slogan'] ?? 'Tozeur · Tunisie') ?></em>
      </span>
    </a>

    <form class="recherche" method="get" action="<?= e(Url::to('/recherche')) ?>" role="search">
      <label class="visuel-cache" for="champ-recherche"><?= e(t('commun.rechercher')) ?></label>
      <input id="champ-recherche" type="search" name="q" value="<?= e(Http::get('q', '')) ?>"
             placeholder="<?= e(t('commun.rechercher')) ?>" maxlength="80" autocomplete="off">
      <button type="submit" aria-label="<?= e(t('commun.rechercher')) ?>">
        <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5" fill="none" stroke="currentColor" stroke-width="2"/><path d="M15.5 15.5 21 21" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
      </button>
    </form>

    <a class="panier-badge" href="<?= e(Url::to('/panier')) ?>" data-ouvrir-panier>
      <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path d="M4 7h16l-1.6 11.2A2 2 0 0 1 16.4 20H7.6a2 2 0 0 1-2-1.8L4 7Z" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="M9 7a3 3 0 0 1 6 0" fill="none" stroke="currentColor" stroke-width="1.7"/></svg>
      <span class="panier-texte"><?= e(t('panier')) ?></span>
      <span class="panier-chiffre" data-panier-nb><?= (int) $articles ?></span>
      <span class="panier-montant" data-panier-montant><?= e(dt($panier['total_ttc'])) ?></span>
    </a>

    <button class="burger" type="button" aria-expanded="false" aria-controls="menu-principal" data-burger>
      <span></span><span></span><span></span><span class="visuel-cache">Menu</span>
    </button>
  </div>

  <nav class="menu" id="menu-principal" aria-label="<?= e(t('enseigne.nav')) ?>">
    <ul class="menu-liste">
      <li class="<?= $current === '/' ? 'actif' : '' ?>"><a href="<?= e(Url::to('/')) ?>"><?= e(t('nav.accueil')) ?></a></li>

      <li class="possede-sous <?= str_starts_with($current, '/boutique') ? 'actif' : '' ?>">
        <a href="<?= e(Url::to('/boutiques')) ?>"><?= e(t('enseigne.nav')) ?></a>
        <div class="sous">
          <ul class="grille-maisons">
            <?php foreach ($enseignes as $une): ?>
              <li>
                <a href="<?= e(Url::to('/boutique/' . $une['slug'])) ?>" style="--puce:<?= e(couleur_sure($une['couleur'], '#c06030')) ?>">
                  <strong><?= e($une['nom']) ?></strong>
                  <small><?= e($une['slogan']) ?></small>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
          <a class="sous-lien" href="<?= e(Url::to('/boutiques')) ?>"><?= e(t('enseigne.toutes')) ?></a>
        </div>
      </li>

      <li class="possede-sous <?= $current === '/catalogue' ? 'actif' : '' ?>">
        <a href="<?= e(Url::to('/catalogue')) ?>"><?= e(t('nav.boutique')) ?></a>
        <div class="sous">
          <ul class="liste-rayons">
            <?php foreach ($rayons as $unRayon): ?>
              <li><a href="<?= e(filtre_url([], ['rayon' => $unRayon['slug']])) ?>">
                <strong><?= e($langue === 'ar' && !empty($unRayon['nom_ar']) ? $unRayon['nom_ar'] : $unRayon['nom']) ?></strong>
                <small><?= (int) $unRayon['nb'] ?> produits</small>
              </a>
                <?php if ($unRayon['enfants'] !== []): ?>
                  <span class="rayons-enfants">
                    <?php foreach ($unRayon['enfants'] as $enfant): ?>
                      <a href="<?= e(filtre_url([], ['rayon' => $enfant['slug']])) ?>"><?= e($enfant['nom']) ?></a>
                    <?php endforeach; ?>
                  </span>
                <?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
          <a class="sous-lien" href="<?= e(Url::to('/catalogue')) ?>"><?= e(t('nav.catalogue')) ?></a>
        </div>
      </li>

      <li class="<?= $current === '/promotions' ? 'actif' : '' ?>"><a class="etiquette-promo" href="<?= e(Url::to('/promotions')) ?>"><?= e(t('nav.promos')) ?></a></li>

      <?php foreach ($pagesMenu as $unePage): ?>
        <li class="<?= $current === '/page/' . $unePage['slug'] ? 'actif' : '' ?>">
          <a href="<?= e(Url::to('/page/' . $unePage['slug'])) ?>"><?= e($unePage['titre']) ?></a>
        </li>
      <?php endforeach; ?>
    </ul>
  </nav>
</header>

<?php if ($flash !== []): ?>
  <div class="alertes" role="status">
    <?php foreach ($flash as $type => $messages): ?>
      <?php foreach ((array) $messages as $unMessage): ?>
        <p class="alerte alerte-<?= e($type === 'succes' ? 'juste' : ($type === 'erreur' ? 'faute' : 'note')) ?>"><?= e($unMessage) ?></p>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?= View::corps('partials/bannieres', ['slot' => 'bandeau']) ?>

<main id="contenu">
<?= $contenu ?>
</main>

<?= View::corps('partials/bannieres', ['slot' => 'newsletter']) ?>

<footer class="pied-de-page">
  <div class="rang pied-grille">
    <section class="pied-present">
      <h3 class="pied-titre"><?= e($siteNom) ?></h3>
      <p><?= e(t('footer.parure')) ?></p>
      <p class="pied-coordonnees">
        <?= e(ParametresApp::reglure('site_adresse', TZ_SITE_CITY)) ?><br>
        <a href="tel:<?= e(preg_replace('/\s+/', '', (string) ParametresApp::reglure('site_telephone', ''))) ?>"><?= e(ParametresApp::reglure('site_telephone', '')) ?></a>
        · <a href="mailto:<?= e(ParametresApp::reglure('site_email', '')) ?>"><?= e(ParametresApp::reglure('site_email', '')) ?></a>
      </p>
    </section>
    <nav aria-label="<?= e(t('enseigne.nav')) ?>">
      <h4 class="pied-titre"><?= e(t('enseigne.toutes')) ?></h4>
      <ul>
        <?php foreach ($enseignes as $une): ?>
          <li><a href="<?= e(Url::to('/boutique/' . $une['slug'])) ?>"><?= e($une['nom']) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </nav>
    <nav aria-label="<?= e(t('nav.catalogue')) ?>">
      <h4 class="pied-titre"><?= e(t('nav.boutique')) ?></h4>
      <ul>
        <li><a href="<?= e(Url::to('/catalogue')) ?>"><?= e(t('nav.catalogue')) ?></a></li>
        <li><a href="<?= e(Url::to('/promotions')) ?>"><?= e(t('nav.promos')) ?></a></li>
        <?php foreach (array_slice($rayons, 0, 4) as $unRayon): ?>
          <li><a href="<?= e(filtre_url([], ['rayon' => $unRayon['slug']])) ?>"><?= e($unRayon['nom']) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </nav>
    <nav aria-label="<?= e(t('nav.contact')) ?>">
      <h4 class="pied-titre"><?= e(t('nav.contact')) ?></h4>
      <ul>
        <?php foreach ($pagesMenu as $unePage): ?>
          <li><a href="<?= e(Url::to('/page/' . $unePage['slug'])) ?>"><?= e($unePage['titre']) ?></a></li>
        <?php endforeach; ?>
        <li><a href="<?= e(Url::to('/suivi')) ?>">Suivi de commande</a></li>
        <li><a href="<?= e(Url::to('/admin/connexion')) ?>">Régie</a></li>
      </ul>
    </nav>
  </div>
  <div class="rang pied-jury">
    <p>© <?= e(date('Y')) ?> <?= e($siteNom) ?> — <?= e(TZ_SITE_CITY) ?> · Matricule <?= e(ParametresApp::reglure('matricule_fiscal', '')) ?></p>
    <p class="pied-paiement">Paiement par carte via GPG Checkout · Retrait possible à l’atelier de Tozeur</p>
  </div>
</footer>

<?= View::corps('partials/bannieres', ['slot' => 'flottante']) ?>

<div class="voile" data-voile hidden>
  <aside class="tiroir" role="dialog" aria-modal="true" aria-label="<?= e(t('panier.titre')) ?>">
    <header class="tiroir-tete">
      <h2><?= e(t('panier.titre')) ?></h2>
      <button type="button" class="fermer" data-fermer aria-label="Fermer">&times;</button>
    </header>
    <div class="tiroir-corps" data-tiroir-corps>
      <?php if ($panier['lignes'] === []): ?>
        <p class="tiroir-vide"><?= e(t('panier.vide')) ?></p>
      <?php else: ?>
        <?php foreach ($panier['lignes'] as $une): ?>
          <?php $imageTiroir = imageexiste($une['image_principale'] ?? null) ? Url::upload($une['image_principale']) : ''; ?>
          <div class="article">
            <?php if ($imageTiroir !== ''): ?>
              <img src="<?= e($imageTiroir) ?>" alt="" width="56" height="56" loading="lazy">
            <?php else: ?>
              <span class="sans-image" style="--puce:<?= e(couleur_sure($une['enseigne_couleur'] ?? null, '#c06030')) ?>"><?= e(mb_strtoupper(mb_substr((string) $une['enseigne_nom'], 0, 1))) ?></span>
            <?php endif; ?>
            <span>
              <strong><?= e($une['designation']) ?></strong>
              <small><?= e($une['variante_nom']) ?> · <?= (int) $une['quantite'] ?> × <?= e(dt((int) $une['prix_unitaire'])) ?></small>
            </span>
            <span><?= e(dt((int) $une['total_brut'] - (int) $une['remise_ligne'])) ?></span>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
    <footer class="tiroir-pied">
      <p class="tiroir-total"><span><?= e(t('panier.total')) ?></span> <strong data-tiroir-montant><?= e(dt($panier['total_ttc'])) ?></strong></p>
      <a class="btn btn-large" href="<?= e(Url::to('/panier')) ?>"><?= e(t('panier.titre')) ?></a>
      <a class="btn btn-juste" href="<?= e(Url::to('/commande')) ?>"><?= e(t('panier.commander')) ?></a>
    </footer>
  </aside>
</div>

<noscript><p class="sans-js">Le site reste utilisable sans JavaScript : les boutons sont des formulaires.</p></noscript>
<script src="<?= e(Url::asset('js/tozeur.js')) ?>" defer></script>
</body>
</html>
