<?php
/** Pagination d'une liste : $page, $parPage, $total + $filtres pour reconstruire les liens. */
$page = max(1, (int) ($page ?? 1));
$parPage = max(1, (int) ($parPage ?? 12));
$total = (int) ($total ?? 0);
$derniere = max(1, (int) ceil($total / $parPage));
if ($derniere < 2) {
    return;
}
$debute = max(1, $page - 2);
$jusqu = min($derniere, $debute + 4);
$debute = max(1, $jusqu - 4);
?>
<nav class="pager" aria-label="Pagination">
  <p class="pager-compte"><?= (int) $total ?> articles · page <?= $page ?> sur <?= $derniere ?></p>
  <ul>
    <?php if ($page > 1): ?><li><a rel="prev" href="<?= e(filtre_url($filtres ?? [], ['page' => $page - 1], $chemin ?? '/catalogue')) ?>">←</a></li><?php endif; ?>
    <?php for ($i = $debute; $i <= $jusqu; $i++): ?>
      <li><?= $i === $page
        ? '<span aria-current="page">' . $i . '</span>'
        : '<a href="' . e(filtre_url($filtres ?? [], ['page' => $i], $chemin ?? '/catalogue')) . '">' . $i . '</a>'; ?></li>
    <?php endfor; ?>
    <?php if ($page < $derniere): ?><li><a rel="next" href="<?= e(filtre_url($filtres ?? [], ['page' => $page + 1], $chemin ?? '/catalogue')) ?>">→</a></li><?php endif; ?>
  </ul>
</nav>
