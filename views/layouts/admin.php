<?php
/** Habillage minimal et responsive du back-office. */
$adminConnecte = Auth::administrateur();
$flash = Session::consommerFlash();
$titrePage = (string) ($titre ?? 'Administration');
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
