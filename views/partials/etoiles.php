<?php
/** Étoiles pleine note. $note entre 0 et 5. */
$note = (float) ($note ?? 0);
$pleines = (int) round($note);
?>
<span class="etoiles" role="img" aria-label="<?= e(number_format($note, 1, ',', ' ')) ?> sur 5">
  <?php for ($i = 1; $i <= 5; $i++): ?>
    <svg class="<?= $i <= $pleines ? 'pleine' : 'vide' ?>" viewBox="0 0 24 24" width="14" height="14" aria-hidden="true">
      <path d="M12 2.6l2.9 5.9 6.5.9-4.7 4.6 1.1 6.5-5.7-3-5.8 3 1.1-6.5L2.6 9.4l6.5-.9z"/>
    </svg>
  <?php endfor; ?>
</span>
