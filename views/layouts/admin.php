<?php

/** Habillage minimal et responsive du back-office. */
$adminConnecte = Auth::administrateur();
$flash = Session::consommerFlash();
$titrePage = (string) ($titre ?? 'Administration');

/** Habillage du back-office, avec menu latéral une fois connecté. */
$adminConnecte = Auth::administrateur();
$flash = Session::consommerFlash();
$titrePage = (string) ($titre ?? 'Administration');

$rubriques = [
    'Activité' => [
        '/admin/tableau'   => 'Tableau de bord',
        '/admin/commandes' => 'Commandes',
        '/admin/clients'   => 'Clients',
    ],
    'Catalogue' => [
        '/admin/produits'   => 'Produits',
        '/admin/categories' => 'Catégories',
        '/admin/enseignes'  => 'Maisons / enseignes',
        '/admin/stocks'     => 'Stocks',
        '/admin/mouvements' => 'Mouvements de stock',
    ],
    'Commercial' => [
        '/admin/promos' => 'Promotions',
        '/admin/regie'  => 'Régie / bannières',
    ],
    'Comptabilité' => [
        '/admin/compta'           => "Vue d'ensemble",
        '/admin/compta/ecritures' => 'Écritures',
        '/admin/compta/tva'       => 'TVA',
        '/admin/compta/factures'  => 'Factures',
    ],
    'Configuration' => [
        '/admin/transporteurs' => 'Transporteurs',
        '/admin/paiement'      => 'Paiement (GPG)',
        '/admin/parametres'    => 'Paramètres du site',
        '/admin/utilisateurs'  => 'Comptes admin',
        '/admin/journal'       => "Journal d'activité",
    ],
];

$cheminActuel = (string) ($_SERVER['REQUEST_URI'] ?? '');

?>
<!doctype html>
<html lang="fr" dir="ltr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($titrePage) ?> · Administration · <?= e(TZ_SITE_NAME) ?></title>
  <link rel="stylesheet" href="<?= e(Url::asset('css/admin.css')) ?>">
</head>
<body class="admin">
<<<<<<< HEAD
  <header class="admin-entete">
    <a class="admin-marque" href="<?= e(Url::to($adminConnecte === null ? '/admin' : '/admin/tableau')) ?>">
      <span class="admin-sceau" aria-hidden="true">TS</span>
      <span><strong><?= e(TZ_SITE_NAME) ?></strong><small>Administration</small></span>
    </a>
    <?php if ($adminConnecte !== null): ?>
      <div class="admin-identite">
        <span><?= e($adminConnecte['nom'] ?? $adminConnecte['login'] ?? 'Administrateur') ?></span>
        <a href="<?= e(Url::to('/admin/deconnexion')) ?>">Déconnexion</a>
      </div>
    <?php endif; ?>


<?php if ($adminConnecte !== null): ?>

  <div class="admin-shell">
    <aside class="admin-nav">
      <a class="admin-marque" href="<?= e(Url::to('/admin/tableau')) ?>">
        <span class="admin-sceau" aria-hidden="true">TS</span>
        <span><strong><?= e(TZ_SITE_NAME) ?></strong><small>Administration</small></span>
      </a>
      <nav>
        <?php foreach ($rubriques as $groupe => $liens): ?>
          <p class="admin-nav-groupe"><?= e($groupe) ?></p>
          <?php foreach ($liens as $chemin => $libelle): ?>
            <a href="<?= e(Url::to($chemin)) ?>"
               class="<?= str_starts_with($cheminActuel, $chemin) ? 'actif' : '' ?>">
              <?= e($libelle) ?>
            </a>
          <?php endforeach; ?>
        <?php endforeach; ?>
      </nav>
    </aside>

    <div class="admin-corps">
      <header class="admin-entete">
        <h1><?= e($titrePage) ?></h1>
        <div class="admin-identite">
          <span><?= e($adminConnecte['nom'] ?? $adminConnecte['login'] ?? 'Administrateur') ?>
            · <?= e($adminConnecte['role'] ?? '') ?></span>
          <a href="<?= e(Url::to('/admin/deconnexion')) ?>">Déconnexion</a>
        </div>
      </header>

      <?php if ($flash !== []): ?>
        <div class="admin-flashes" role="status">
          <?php foreach ($flash as $type => $messages): ?>
            <?php foreach ((array) $messages as $message): ?>
              <p class="admin-flash admin-flash-<?= e($type) ?>"><?= e($message) ?></p>
            <?php endforeach; ?>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <main class="admin-contenu">
        <?= $contenu ?>
      </main>
      <footer class="admin-pied">Back-office · <?= e(TZ_SITE_CITY) ?></footer>
    </div>
  </div>

<?php else: ?>

  <header class="admin-entete admin-entete-seule">
    <a class="admin-marque" href="<?= e(Url::to('/admin')) ?>">
      <span class="admin-sceau" aria-hidden="true">TS</span>
      <span><strong><?= e(TZ_SITE_NAME) ?></strong><small>Administration</small></span>
    </a>

  </header>

  <?php if ($flash !== []): ?>
    <div class="admin-flashes" role="status">
      <?php foreach ($flash as $type => $messages): ?>
        <?php foreach ((array) $messages as $message): ?>
          <p class="admin-flash admin-flash-<?= e($type) ?>"><?= e($message) ?></p>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>


  <main class="admin-contenu">
    <?= $contenu ?>
  </main>
  <footer class="admin-pied">Back-office · <?= e(TZ_SITE_CITY) ?></footer>
</body>
</html>

  <main class="admin-contenu admin-contenu-seule">
    <?= $contenu ?>
  </main>
  <footer class="admin-pied">Back-office · <?= e(TZ_SITE_CITY) ?></footer>

<?php endif; ?>

</body>
</html>

