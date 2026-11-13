-- =====================================================================
-- Contrôle du jeu importé : chaque requête doit retourner 0 ligne.
-- Usage : mysql -uroot -psecure tozeur_shop < 03-controle.sql
-- =====================================================================
USE `tozeur_shop`;

SELECT '1. pièces comptables déséquilibrées' controle, piece valeur, SUM(debit) - SUM(credit) ecart
  FROM tz_ecriture GROUP BY piece HAVING ecart <> 0;

SELECT '2. écritures sans journal connu' controle, e.id valeur, e.compte ecart
  FROM tz_ecriture e LEFT JOIN tz_journal j ON j.id = e.journal_id WHERE j.id IS NULL;

SELECT '3. en-tête de commande <> somme des lignes' controle, c.reference valeur,
       c.total_ttc - (SELECT COALESCE(SUM(l.total_ttc), 0) + c.livraison_frais
                        FROM tz_commande_ligne l WHERE l.commande_id = c.id) ecart
  FROM tz_commande c
 WHERE c.statut <> 'panier_abandonne'
HAVING ecart <> 0;

SELECT '4. HT + TVA <> TTC (commande)' controle, c.reference valeur,
       c.total_ht + c.total_tva - c.total_ttc ecart
  FROM tz_commande c HAVING ecart <> 0;

SELECT '5. ligne de commande incohérente' controle, l.id valeur,
       l.total_ttc - (l.quantite * l.prix_unitaire - l.remise_ligne) ecart
  FROM tz_commande_ligne l HAVING ecart <> 0;

SELECT '6. en-tête de facture <> somme des lignes' controle, f.numero valeur,
       f.total_ttc - (SELECT COALESCE(SUM(fl.total_ttc), 0) FROM tz_facture_ligne fl
                       WHERE fl.facture_id = f.id) ecart
  FROM tz_facture f WHERE f.statut <> 'annulee' HAVING ecart <> 0;

SELECT '7. facture : réglé <> encaissements' controle, f.numero valeur,
       f.regle - COALESCE(SUM(e.montant), 0) ecart
  FROM tz_facture f LEFT JOIN tz_encaissement e ON e.facture_id = f.id
 GROUP BY f.id, f.numero, f.regle HAVING ecart <> 0;

SELECT '8. facture : statut incohérent avec le réglé' controle, f.numero valeur, f.statut ecart
  FROM tz_facture f
 WHERE (f.regle = f.total_ttc AND f.statut <> 'payee' AND f.statut <> 'annulee' AND f.total_ttc > 0)
    OR (f.regle > 0 AND f.regle < f.total_ttc AND f.statut NOT IN ('partielle', 'payee'))
    OR (f.regle = 0 AND f.statut IN ('payee', 'partielle'));

SELECT '9. stock négatif' controle, v.sku valeur, v.stock_quantite ecart
  FROM tz_variante v WHERE v.stock_quantite < 0;

SELECT '10. stock_apres <> mouvement précédent' controle, s.id valeur,
       s.stock_apres - (v.stock_quantite) ecart
  FROM tz_stock_mouvement s
  JOIN (SELECT sm.variante_id, sm.id, sm.stock_apres FROM tz_stock_mouvement sm
         WHERE sm.id = (SELECT MAX(sm2.id) FROM tz_stock_mouvement sm2
                         WHERE sm2.variante_id = sm.variante_id)) dernier ON dernier.id = s.id
  JOIN tz_variante v ON v.id = s.variante_id
 HAVING ecart <> 0;

SELECT '11. paiement <> total de la commande' controle, c.reference valeur,
       p.montant - c.total_ttc ecart
  FROM tz_paiement p JOIN tz_commande c ON c.id = p.commande_id
 WHERE p.statut = 'accorde' HAVING ecart <> 0;

SELECT '12. commande payée sans paiement accordé' controle, c.reference valeur, c.statut ecart
  FROM tz_commande c
 WHERE c.statut IN ('payee','preparation','controle_qualite','expediee','livree')
   AND c.mode_paiement = 'carte_gpg'
   AND NOT EXISTS (SELECT 1 FROM tz_paiement p WHERE p.commande_id = c.id AND p.statut = 'accorde');

SELECT '13. avis sur un produit inexistant ou sans note cohérente' controle, p.id valeur,
       p.note_moyenne - (SELECT ROUND(AVG(a.note), 2) FROM tz_avis a
                          WHERE a.produit_id = p.id AND a.statut = 'publie') ecart
  FROM tz_produit p
 WHERE EXISTS (SELECT 1 FROM tz_avis a WHERE a.produit_id = p.id AND a.statut = 'publie')
HAVING ABS(ecart) > 0.05;

SELECT '14. prix_min/prix_max non alignés sur les variantes' controle, p.id valeur,
       p.prix_min - (SELECT MIN(v.prix) FROM tz_variante v
                      WHERE v.produit_id = p.id AND v.visible = 1) ecart
  FROM tz_produit p HAVING ecart <> 0;

SELECT '15. promo consommée sans ligne d utilisation' controle, c.reference valeur, c.code_promo ecart
  FROM tz_commande c
 WHERE c.code_promo IS NOT NULL
   AND NOT EXISTS (SELECT 1 FROM tz_promo_utilisation u JOIN tz_promo pr ON pr.id = u.promo_id
                    WHERE u.commande_id = c.id AND pr.code = c.code_promo);

SELECT '16. client : nb_commandes / total_engage périmés' controle, cl.id valeur,
       cl.total_engage - (SELECT COALESCE(SUM(c.total_ttc), 0) FROM tz_commande c
                          WHERE c.client_id = cl.id
                            AND c.statut IN ('payee','preparation','controle_qualite','expediee','livree')) ecart
  FROM tz_client cl HAVING ecart <> 0;

SELECT '17. bannière en ligne hors fenêtre' controle, b.id valeur, b.etat ecart
  FROM tz_banniere b
 WHERE (b.etat = 'en_ligne' AND (b.debut_at > NOW() OR (b.fin_at IS NOT NULL AND b.fin_at < NOW())))
    OR (b.etat = 'programmee' AND b.debut_at <= NOW() AND (b.fin_at IS NULL OR b.fin_at >= NOW()));

SELECT '18. clics > impressions' controle, b.id valeur, b.clics - b.impressions ecart
  FROM tz_banniere b HAVING ecart > 0;

SELECT 'FIN — aucune ligne au-dessus = jeu cohérent' controle;
