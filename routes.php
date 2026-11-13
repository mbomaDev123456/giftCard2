<?php
/** Table des routes. Retourne un Routeur prêt à dispatcher. */
declare(strict_types=1);

$routeur = new Router();

/* ---------------------------------------------------------- Vitrine */
$routeur->get('/',                    [Front::class, 'accueil']);
$routeur->get('/boutiques',           [Front::class, 'boutiques']);
$routeur->get('/boutique/{slug}',     [Front::class, 'boutique']);
$routeur->get('/catalogue',           [Front::class, 'catalogue']);
$routeur->get('/produit/{slug}',      [Front::class, 'produit']);
$routeur->get('/promotions',          [Front::class, 'promotions']);
$routeur->get('/page/{slug}',         [Front::class, 'page']);
$routeur->get('/recherche',           [Front::class, 'recherche']);

/* Panier */
$routeur->get('/panier',                          [Front::class, 'panier']);
$routeur->post('/panier/ajouter',                 [Front::class, 'panierAjouter']);
$routeur->post('/panier/modifier',                [Front::class, 'panierModifier']);
$routeur->post('/panier/supprimer',               [Front::class, 'panierSupprimer']);
$routeur->post('/panier/vider',                   [Front::class, 'panierVider']);
$routeur->post('/panier/code',                    [Front::class, 'panierCode']);
$routeur->post('/panier/emballage',               [Front::class, 'panierEmballage']);

/* Commande + paiement */
$routeur->get('/commande',                     [Front::class, 'commande']);
$routeur->post('/commande/livraison',          [Front::class, 'commandeLivraison']);
$routeur->post('/commande/paiement',           [Front::class, 'commandePaiement']);
$routeur->get('/commande/confirmation/{ref}',  [Front::class, 'confirmation']);
$routeur->get('/paiement/depart/{ref}',        [Front::class, 'paiementDepart']);
$routeur->post('/retour/paiement/ok',          [Front::class, 'retourOk']);
$routeur->post('/retour/paiement/ko',          [Front::class, 'retourKo']);
$routeur->post('/retour/paiement/notification', [Front::class, 'retourNotification']);
$routeur->get('/suivi',                        [Front::class, 'suivi']);
$routeur->post('/suivi',                       [Front::class, 'suiviLancer']);

/* Compte client */
$routeur->any('/compte/connexion',     [Compte::class, 'connexion']);
$routeur->any('/compte/inscription',   [Compte::class, 'inscription']);
$routeur->get('/compte/deconnexion',   [Compte::class, 'deconnexion']);
$routeur->get('/compte',               [Compte::class, 'index']);
$routeur->get('/compte/commandes',     [Compte::class, 'commandes']);
$routeur->get('/compte/commande/{ref}', [Compte::class, 'commande']);
$routeur->post('/compte/adresse',      [Compte::class, 'adresseEnregistrer']);
$routeur->post('/compte/profil',       [Compte::class, 'profilEnregistrer']);

/* Signalement d'un avis, liste d'envie */
$routeur->post('/produit/{slug}/avis', [Front::class, 'avisEnregistrer']);

/* ---------------------------------------------------------- WS (JSON) */
$routeur->get('/ws/panier',        [Api::class, 'panier']);
$routeur->post('/ws/panier',       [Api::class, 'panierEcrire']);
$routeur->get('/ws/bannieres',     [Api::class, 'bannieres']);
$routeur->post('/ws/banniere/vue', [Api::class, 'banniereVue']);
$routeur->post('/ws/banniere/clic', [Api::class, 'banniereClic']);
$routeur->get('/ws/stock/{slug}',  [Api::class, 'stock']);
$routeur->get('/ws/promo',         [Api::class, 'promo']);

/* ---------------------------------------------------------- Administration */
$routeur->get('/admin',                 [Admin::class, 'index']);
$routeur->post('/admin',                [Admin::class, 'connexion']);
$routeur->get('/admin/connexion',       [Admin::class, 'index']);
$routeur->get('/admin/deconnexion',     [Admin::class, 'deconnexion']);
$routeur->get('/admin/tableau',         [Admin::class, 'tableau']);

$routeur->get('/admin/commandes',                 [Admin::class, 'commandes']);
$routeur->get('/admin/commande/{id}',             [Admin::class, 'commande']);
$routeur->post('/admin/commande/{id}/statut',     [Admin::class, 'commandeStatut']);
$routeur->post('/admin/commande/{id}/transport',  [Admin::class, 'commandeTransport']);
$routeur->post('/admin/commande/{id}/facture',    [Admin::class, 'commandeFacture']);
$routeur->post('/admin/commande/{id}/rembourse',  [Admin::class, 'commandeRembourser']);
$routeur->post('/admin/commande/{id}/ligne',      [Admin::class, 'commandeLigneAjouter']);

$routeur->get('/admin/produits',                    [Admin::class, 'produits']);
$routeur->get('/admin/produit/{id}',                [Admin::class, 'produitFormulaire']);
$routeur->post('/admin/produit/enregistrer',        [Admin::class, 'produitEnregistrer']);
$routeur->post('/admin/produit/{id}/supprimer',     [Admin::class, 'produitSupprimer']);
$routeur->post('/admin/variante/enregistrer',       [Admin::class, 'varianteEnregistrer']);
$routeur->post('/admin/variante/{id}/supprimer',    [Admin::class, 'varianteSupprimer']);
$routeur->get('/admin/categories',                  [Admin::class, 'categories']);
$routeur->post('/admin/categorie/enregistrer',      [Admin::class, 'categorieEnregistrer']);
$routeur->post('/admin/categorie/{id}/supprimer',   [Admin::class, 'categorieSupprimer']);
$routeur->get('/admin/enseignes',                   [Admin::class, 'enseignes']);
$routeur->get('/admin/enseigne/{id}',               [Admin::class, 'enseigneFormulaire']);
$routeur->post('/admin/enseigne/enregistrer',       [Admin::class, 'enseigneEnregistrer']);
$routeur->post('/admin/media/enregistrer',          [Admin::class, 'mediaEnregistrer']);

$routeur->get('/admin/stocks',                     [Admin::class, 'stocks']);
$routeur->post('/admin/stock/ajuster',             [Admin::class, 'stockAjuster']);
$routeur->post('/admin/stock/reappro',             [Admin::class, 'stockReappro']);
$routeur->get('/admin/mouvements',                 [Admin::class, 'mouvements']);
$routeur->post('/admin/mouvement/enregistrer',     [Admin::class, 'mouvementEnregistrer']);

$routeur->get('/admin/clients',                    [Admin::class, 'clients']);
$routeur->get('/admin/client/{id}',                [Admin::class, 'client']);
$routeur->post('/admin/client/{id}/blocage',       [Admin::class, 'clientBloquer']);

$routeur->get('/admin/promos',                     [Admin::class, 'promos']);
$routeur->get('/admin/promo/{id}',                 [Admin::class, 'promoFormulaire']);
$routeur->post('/admin/promo/enregistrer',         [Admin::class, 'promoEnregistrer']);
$routeur->post('/admin/promo/{id}/supprimer',      [Admin::class, 'promoSupprimer']);

$routeur->get('/admin/regie',                      [Admin::class, 'regie']);
$routeur->get('/admin/banniere/{id}',              [Admin::class, 'banniereFormulaire']);
$routeur->post('/admin/banniere/enregistrer',      [Admin::class, 'banniereEnregistrer']);
$routeur->post('/admin/banniere/{id}/supprimer',   [Admin::class, 'banniereSupprimer']);
$routeur->get('/admin/regie/statistiques',         [Admin::class, 'regieStatistiques']);

$routeur->get('/admin/compta',                     [Admin::class, 'compta']);
$routeur->get('/admin/compta/ecritures',           [Admin::class, 'comptaEcritures']);
$routeur->get('/admin/compta/tva',                 [Admin::class, 'comptaTva']);
$routeur->get('/admin/compta/factures',            [Admin::class, 'comptaFactures']);
$routeur->post('/admin/compta/ecriture',           [Admin::class, 'comptaEcritureAjouter']);
$routeur->post('/admin/compta/encaissement',       [Admin::class, 'comptaEncaissement']);
$routeur->get('/admin/compta/export/{type}',       [Admin::class, 'comptaExporter']);
$routeur->get('/admin/compta/fichier/{nom}',       [Admin::class, 'comptaTelecharger']);

$routeur->get('/admin/transporteurs',              [Admin::class, 'transporteurs']);
$routeur->post('/admin/transporteur/enregistrer',  [Admin::class, 'transporteurEnregistrer']);
$routeur->get('/admin/paiement',                   [Admin::class, 'paiement']);
$routeur->post('/admin/paiement/enregistrer',      [Admin::class, 'paiementEnregistrer']);
$routeur->post('/admin/paiement/test',             [Admin::class, 'paiementTest']);
$routeur->get('/admin/parametres',                 [Admin::class, 'parametres']);
$routeur->post('/admin/parametres/enregistrer',    [Admin::class, 'parametresEnregistrer']);
$routeur->get('/admin/utilisateurs',               [Admin::class, 'utilisateurs']);
$routeur->post('/admin/utilisateur/enregistrer',   [Admin::class, 'utilisateurEnregistrer']);
$routeur->post('/admin/utilisateur/{id}/supprimer', [Admin::class, 'utilisateurSupprimer']);
$routeur->get('/admin/journal',                    [Admin::class, 'journal']);

return $routeur;
