<?php
/** Fil d'Ariane. $fil = tableau de ['titre' => ..., 'url' => ...|null]. */
$fil = $fil ?? [];
if ($fil === []) {
    return;
}
?>
<nav class="fil" aria-label="Fil d’Ariane">
  <ol>
    <?php foreach ($fil as $unMaillon): ?>
      <li>
        <?php if ($unMaillon['url'] === null): ?>
          <span aria-current="page"><?= e($unMaillon['titre']) ?></span>
        <?php else: ?>
          <a href="<?= e($unMaillon['url']) ?>"><?= e($unMaillon['titre']) ?></a>
        <?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ol>
</nav>
