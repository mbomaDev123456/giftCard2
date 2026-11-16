<?php
$titre = 'Connexion';
?>
<section class="admin-auth" aria-labelledby="admin-titre">
  <p class="admin-surtitre">Espace sécurisé</p>
  <h1 id="admin-titre">Connexion à l’administration</h1>
  <p class="admin-intro">Connectez-vous avec le compte administrateur créé dans la base de données du site.</p>

  <?php if (!empty($erreur)): ?>
    <p class="admin-erreur" role="alert"><?= e($erreur) ?></p>
  <?php endif; ?>

  <form method="post" action="<?= e(Url::to('/admin')) ?>" class="admin-formulaire">
    <?= Csrf::champ() ?>
    <label for="admin-login">Identifiant</label>
    <input id="admin-login" name="login" type="text" autocomplete="username" required maxlength="60" autofocus>

    <label for="admin-password">Mot de passe</label>
    <input id="admin-password" name="mot_de_passe" type="password" autocomplete="current-password" required>

    <button class="admin-bouton" type="submit">Se connecter</button>
  </form>
  <a class="admin-retour" href="<?= e(Url::to('/')) ?>">Retour à la boutique</a>
</section>
