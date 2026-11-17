<?php $titre = 'Commande ' . $commande['reference']; ?>
<section class="admin-section">
  <header class="admin-section-tete">
    <h1>Commande <?= e($commande['reference']) ?></h1>
    <a href="<?= e(Url::to('/admin/commandes')) ?>">&larr; Retour à la liste</a>
  </header>

  <div class="admin-grille-2">
    <div class="admin-carte">
      <h2>Lignes</h2>
      <table class="admin-table">
        <thead><tr><th>Article</th><th>Qté</th><th>PU TTC</th><th>Total TTC</th></tr></thead>
        <tbody>
          <?php foreach ($lignes as $l): ?>
            <tr>
              <td><?= e($l['designation']) ?><br><small><?= e((string) $l['sku']) ?></small></td>
              <td><?= (int) $l['quantite'] ?></td>
              <td><?= number_format(((int) $l['prix_unitaire']) / 1000, 3, ',', ' ') ?> DT</td>
              <td><?= number_format(((int) $l['total_ttc']) / 1000, 3, ',', ' ') ?> DT</td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <dl class="admin-totaux">
        <dt>Sous-total</dt><dd><?= number_format(((int) $commande['sous_total']) / 1000, 3, ',', ' ') ?> DT</dd>
        <dt>Remise</dt><dd>-<?= number_format(((int) $commande['remise_montant']) / 1000, 3, ',', ' ') ?> DT</dd>
        <dt>Livraison</dt><dd><?= number_format(((int) $commande['livraison_frais']) / 1000, 3, ',', ' ') ?> DT</dd>
        <dt><strong>Total TTC</strong></dt><dd><strong><?= number_format(((int) $commande['total_ttc']) / 1000, 3, ',', ' ') ?> DT</strong></dd>
        <dt>Marge estimée (HT - coût d'achat)</dt><dd><?= number_format($marge / 1000, 3, ',', ' ') ?> DT</dd>
      </dl>
    </div>

    <div class="admin-carte">
      <h2>Statut</h2>
      <form method="post" action="<?= e(Url::to('/admin/commande/' . $commande['id'] . '/statut')) ?>">
        <?= Csrf::champ() ?>
        <select name="statut">
          <?php foreach ($statutsCommande as $code => $libelle): ?>
            <option value="<?= e($code) ?>" <?= $commande['statut'] === $code ? 'selected' : '' ?>><?= e($libelle) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="admin-bouton admin-bouton-petit">Mettre à jour</button>
      </form>

      <h2>Transport</h2>
      <form method="post" action="<?= e(Url::to('/admin/commande/' . $commande['id'] . '/transport')) ?>">
        <?= Csrf::champ() ?>
        <select name="transporteur_id">
          <option value="">— Transporteur —</option>
          <?php foreach ($transporteurs as $t): ?>
            <option value="<?= (int) $t['id'] ?>" <?= (int) $commande['transporteur_id'] === (int) $t['id'] ? 'selected' : '' ?>><?= e($t['nom']) ?></option>
          <?php endforeach; ?>
        </select>
        <input type="text" name="suivi_numero" value="<?= e((string) $commande['suivi_numero']) ?>" placeholder="N° de suivi">
        <button type="submit" class="admin-bouton admin-bouton-petit">Enregistrer</button>
      </form>

      <h2>Facture</h2>
      <?php if ($facture): ?>
        <p><?= e($facture['numero']) ?> — <?= e($facture['statut']) ?></p>
      <?php else: ?>
        <form method="post" action="<?= e(Url::to('/admin/commande/' . $commande['id'] . '/facture')) ?>">
          <?= Csrf::champ() ?>
          <button type="submit" class="admin-bouton admin-bouton-petit">Générer la facture</button>
        </form>
      <?php endif; ?>

      <h2>Remboursement</h2>
      <form method="post" action="<?= e(Url::to('/admin/commande/' . $commande['id'] . '/rembourse')) ?>">
        <?= Csrf::champ() ?>
        <input type="text" name="montant" placeholder="Montant en DT, ex: 25,500">
        <input type="text" name="motif" placeholder="Motif">
        <button type="submit" class="admin-bouton admin-bouton-petit">Demander le remboursement</button>
      </form>
      <?php foreach ($remboursements as $r): ?>
        <p><?= number_format(((int) $r['montant']) / 1000, 3, ',', ' ') ?> DT — <?= e($r['statut']) ?> — <?= e((string) $r['motif']) ?></p>
      <?php endforeach; ?>

      <h2>Paiements GPGCheckout</h2>
      <?php foreach ($paiements as $p): ?>
        <p><?= e($p['order_id']) ?> — <?= e($p['statut']) ?> — <?= number_format(((int) $p['montant']) / 1000, 3, ',', ' ') ?> DT</p>
      <?php endforeach; ?>
    </div>
  </div>
</section>