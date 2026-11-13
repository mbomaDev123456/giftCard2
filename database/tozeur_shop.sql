-- =====================================================================
-- Tozeur Souk — plateforme e-commerce multi-enseigne
-- Base : tozeur_shop  |  MySQL 8.0  |  InnoDB  |  utf8mb4_0900_ai_ci
-- Règle d'or héritée de la maison : tous les montants sont en MILLIMES
-- (entier). 35 TND = 35000. Les taux de TVA sont en millièmes pour 100000
-- (19 % = 19000, 7 % = 7000).
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `tozeur_shop`
  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `tozeur_shop`;

-- --------------------------------------------------------------- 1. Maisons
DROP TABLE IF EXISTS `tz_enseigne`;
CREATE TABLE `tz_enseigne` (
  `id`               INT NOT NULL AUTO_INCREMENT,
  `nom`              VARCHAR(120) NOT NULL,
  `slug`             VARCHAR(120) NOT NULL,
  `slogan`           VARCHAR(200) DEFAULT NULL,
  `description`      TEXT,
  `histoire`         MEDIUMTEXT,
  `logo`             VARCHAR(255) DEFAULT NULL,
  `banniere`         VARCHAR(255) DEFAULT NULL,
  `couleur`          CHAR(7) NOT NULL DEFAULT '#8c4a2f',
  `couleur_secondaire` CHAR(7) NOT NULL DEFAULT '#c98b3f',
  `ville`            VARCHAR(80)  DEFAULT 'Tozeur',
  `telephone`        VARCHAR(30)  DEFAULT NULL,
  `email`            VARCHAR(120) DEFAULT NULL,
  `rib`              VARCHAR(30)  DEFAULT NULL,
  `patente`          VARCHAR(30)  DEFAULT NULL,
  `commission_bpo`   INT NOT NULL DEFAULT 5000,   -- 5,000 % retenu par la plateforme
  `position`         SMALLINT NOT NULL DEFAULT 0,
  `vedette`          TINYINT(1) NOT NULL DEFAULT 0,
  `meta_titre`       VARCHAR(160) DEFAULT NULL,
  `meta_description` VARCHAR(300) DEFAULT NULL,
  `etat`             ENUM('active','suspendue','archive') NOT NULL DEFAULT 'active',
  `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_enseigne_slug` (`slug`),
  KEY `ix_enseigne_etat` (`etat`, `position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ------------------------------------------------------------ 2. Rayons
DROP TABLE IF EXISTS `tz_categorie`;
CREATE TABLE `tz_categorie` (
  `id`          INT NOT NULL AUTO_INCREMENT,
  `parent_id`   INT DEFAULT NULL,
  `enseigne_id` INT DEFAULT NULL,               -- NULL = rayon commun à toutes les maisons
  `nom`         VARCHAR(120) NOT NULL,
  `nom_ar`      VARCHAR(120) DEFAULT NULL,
  `slug`        VARCHAR(120) NOT NULL,
  `description` TEXT,
  `image`       VARCHAR(255) DEFAULT NULL,
  `tva_taux`    INT NOT NULL DEFAULT 19000,
  `position`    SMALLINT NOT NULL DEFAULT 0,
  `etat`        ENUM('publiee','cachee') NOT NULL DEFAULT 'publiee',
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_categorie_slug` (`slug`),
  KEY `ix_categorie_parent` (`parent_id`),
  CONSTRAINT `fk_categorie_parent` FOREIGN KEY (`parent_id`) REFERENCES `tz_categorie` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ------------------------------------------------------------- 3. Produits
DROP TABLE IF EXISTS `tz_produit`;
CREATE TABLE `tz_produit` (
  `id`               INT NOT NULL AUTO_INCREMENT,
  `enseigne_id`      INT NOT NULL,
  `categorie_id`     INT DEFAULT NULL,
  `nom`              VARCHAR(200) NOT NULL,
  `nom_ar`           VARCHAR(200) DEFAULT NULL,
  `slug`             VARCHAR(220) NOT NULL,
  `resume`           VARCHAR(300) DEFAULT NULL,
  `description`      MEDIUMTEXT,
  `conseils`         TEXT,
  `origine`          VARCHAR(120) DEFAULT NULL,
  `matiere`          VARCHAR(120) DEFAULT NULL,
  `savoir_faire`     VARCHAR(120) DEFAULT NULL,       -- Label artisanal / AOC / bio
  `tva_taux`         INT NOT NULL DEFAULT 19000,
  `prix_achat_bpo`   INT NOT NULL DEFAULT 0,          -- coût d'achat fournisseur, en millimes
  `prix_min`         INT NOT NULL DEFAULT 0,          -- recopié depuis les variantes, pour le tri
  `prix_max`         INT NOT NULL DEFAULT 0,
  `image_principale` VARCHAR(255) DEFAULT NULL,
  `galerie`          JSON DEFAULT NULL,
  `couleurs`         JSON DEFAULT NULL,
  `poids_grammes`    INT NOT NULL DEFAULT 0,
  `dimensions`       VARCHAR(80) DEFAULT NULL,
  `peremption_jours` SMALLINT DEFAULT NULL,
  `certifications`   VARCHAR(255) DEFAULT NULL,
  `label`            VARCHAR(40) DEFAULT NULL,        -- "Récolte 2026", "Pièce unique"
  `vedette`          TINYINT(1) NOT NULL DEFAULT 0,
  `nouveaute`        TINYINT(1) NOT NULL DEFAULT 0,
  `note_moyenne`     DECIMAL(3,2) NOT NULL DEFAULT 0.00,
  `note_compte`      SMALLINT NOT NULL DEFAULT 0,
  `vues`             INT NOT NULL DEFAULT 0,
  `position`         SMALLINT NOT NULL DEFAULT 0,
  `etat`             ENUM('brouillon','publie','archive') NOT NULL DEFAULT 'brouillon',
  `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_produit_slug` (`slug`),
  KEY `ix_produit_enseigne` (`enseigne_id`),
  KEY `ix_produit_categorie` (`categorie_id`),
  KEY `ix_produit_etat` (`etat`, `vedette`, `prix_min`),
  FULLTEXT KEY `fx_produit_texte` (`nom`, `resume`, `description`),
  CONSTRAINT `fk_produit_enseigne` FOREIGN KEY (`enseigne_id`) REFERENCES `tz_enseigne` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_produit_categorie` FOREIGN KEY (`categorie_id`) REFERENCES `tz_categorie` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ------------------------------------------------------------- 4. Formats
DROP TABLE IF EXISTS `tz_variante`;
CREATE TABLE `tz_variante` (
  `id`            INT NOT NULL AUTO_INCREMENT,
  `produit_id`    INT NOT NULL,
  `sku`           VARCHAR(60) NOT NULL,
  `nom`           VARCHAR(120) NOT NULL,             -- "Kilo premier choix", "160 x 230 cm"
  `details`       VARCHAR(200) DEFAULT NULL,
  `prix`          INT NOT NULL,                      -- TTC, millimes
  `prix_barre`    INT NOT NULL DEFAULT 0,            -- prix d'affichage barré (0 = pas de promo)
  `prix_achat`    INT NOT NULL DEFAULT 0,
  `stock_quantite` INT NOT NULL DEFAULT 0,
  `stock_seuil`   INT NOT NULL DEFAULT 3,            -- alerte de réapprovisionnement
  `stock_max`     INT NOT NULL DEFAULT 0,            -- 0 = non plafonné
  `emplacement`   VARCHAR(60) DEFAULT NULL,          -- dépôt / étagère
  `reserve_vente` INT NOT NULL DEFAULT 0,            -- unités déjà engagées, non déduites du stock physique
  `poids_grammes` INT NOT NULL DEFAULT 0,
  `code_barre`    VARCHAR(40) DEFAULT NULL,
  `visible`       TINYINT(1) NOT NULL DEFAULT 1,
  `position`      SMALLINT NOT NULL DEFAULT 0,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_variante_sku` (`sku`),
  KEY `ix_variante_produit` (`produit_id`, `visible`, `position`),
  CONSTRAINT `fk_variante_produit` FOREIGN KEY (`produit_id`) REFERENCES `tz_produit` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------- 5. Mouvements de stock
DROP TABLE IF EXISTS `tz_stock_mouvement`;
CREATE TABLE `tz_stock_mouvement` (
  `id`             BIGINT NOT NULL AUTO_INCREMENT,
  `variante_id`    INT NOT NULL,
  `type`           ENUM('reception','vente','annulation','retour','ajustement','casse','transfert') NOT NULL,
  `quantite`       INT NOT NULL,                    -- signé : + entrée, - sortie
  `stock_apres`    INT NOT NULL DEFAULT 0,
  `motif`          VARCHAR(200) DEFAULT NULL,
  `commande_id`    INT DEFAULT NULL,
  `utilisateur_id` INT DEFAULT NULL,
  `enseigne_id`    INT DEFAULT NULL,
  `cout_unitaire`  INT NOT NULL DEFAULT 0,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_mouvement_variante` (`variante_id`, `created_at`),
  KEY `ix_mouvement_type` (`type`, `created_at`),
  CONSTRAINT `fk_mouvement_variante` FOREIGN KEY (`variante_id`) REFERENCES `tz_variante` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ------------------------------------------------------------ 6. Clients
DROP TABLE IF EXISTS `tz_client`;
CREATE TABLE `tz_client` (
  `id`            INT NOT NULL AUTO_INCREMENT,
  `prenom`        VARCHAR(80) NOT NULL,
  `nom`           VARCHAR(80) NOT NULL,
  `email`         VARCHAR(160) NOT NULL,
  `telephone`     VARCHAR(30) DEFAULT NULL,
  `mot_de_passe`  VARCHAR(255) DEFAULT NULL,        -- password_hash() bcrypt
  `societe`       VARCHAR(120) DEFAULT NULL,
  `patente`       VARCHAR(30) DEFAULT NULL,
  `adresse_l1`    VARCHAR(200) DEFAULT NULL,
  `adresse_l2`    VARCHAR(200) DEFAULT NULL,
  `ville`         VARCHAR(80) DEFAULT NULL,
  `delegation`    VARCHAR(80) DEFAULT NULL,
  `code_postal`   VARCHAR(10) DEFAULT NULL,
  `pays`          CHAR(2) NOT NULL DEFAULT 'TN',
  `depuis_dattes` DATE DEFAULT NULL,
  `points_fidelite` INT NOT NULL DEFAULT 0,
  `newsletter`    TINYINT(1) NOT NULL DEFAULT 0,
  `sms_promo`     TINYINT(1) NOT NULL DEFAULT 0,
  `apport`        VARCHAR(60) DEFAULT NULL,          -- SEO, parrainage, Instagram, salon...
  `parrain_id`    INT DEFAULT NULL,
  `nb_commandes`  SMALLINT NOT NULL DEFAULT 0,
  `total_engage`  INT NOT NULL DEFAULT 0,            -- millimes, CA client cumulé
  `blocage`       TINYINT(1) NOT NULL DEFAULT 0,
  `note_interne`  VARCHAR(300) DEFAULT NULL,
  `last_login`    DATETIME DEFAULT NULL,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_client_email` (`email`),
  KEY `ix_client_ville` (`ville`),
  KEY `ix_client_parrain` (`parrain_id`),
  CONSTRAINT `fk_client_parrain` FOREIGN KEY (`parrain_id`) REFERENCES `tz_client` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

DROP TABLE IF EXISTS `tz_adresse`;
CREATE TABLE `tz_adresse` (
  `id`         INT NOT NULL AUTO_INCREMENT,
  `client_id`  INT NOT NULL,
  `libelle`    VARCHAR(60) NOT NULL DEFAULT 'Domicile',
  `prenom`     VARCHAR(80) DEFAULT NULL,
  `nom`        VARCHAR(80) DEFAULT NULL,
  `telephone`  VARCHAR(30) DEFAULT NULL,
  `ligne1`     VARCHAR(200) NOT NULL,
  `ligne2`     VARCHAR(200) DEFAULT NULL,
  `ville`      VARCHAR(80) NOT NULL,
  `delegation` VARCHAR(80) DEFAULT NULL,
  `code_postal` VARCHAR(10) DEFAULT NULL,
  `pays`       CHAR(2) NOT NULL DEFAULT 'TN',
  `par_defaut` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_adresse_client` (`client_id`),
  CONSTRAINT `fk_adresse_client` FOREIGN KEY (`client_id`) REFERENCES `tz_client` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ---------------------------------------------------------- 7. Transport
DROP TABLE IF EXISTS `tz_transporteur`;
CREATE TABLE `tz_transporteur` (
  `id`          INT NOT NULL AUTO_INCREMENT,
  `nom`         VARCHAR(80) NOT NULL,
  `type`        ENUM('domicile','relais','retait') NOT NULL DEFAULT 'domicile',
  `zones`       JSON DEFAULT NULL,                  -- ["Tozeur","Gabès"] ou ["*"]
  `delai_jours` SMALLINT NOT NULL DEFAULT 3,
  `tarif_base`  INT NOT NULL DEFAULT 5000,          -- millimes
  `tarif_kg`    INT NOT NULL DEFAULT 700,           -- millimes par kg au-delà du premier
  `poids_inclus` INT NOT NULL DEFAULT 5000,         -- grammes
  `suivi_url`   VARCHAR(200) DEFAULT NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------- 8. Commandes
DROP TABLE IF EXISTS `tz_commande`;
CREATE TABLE `tz_commande` (
  `id`               INT NOT NULL AUTO_INCREMENT,
  `reference`        VARCHAR(30) NOT NULL,
  `client_id`        INT DEFAULT NULL,
  `email`            VARCHAR(160) DEFAULT NULL,     -- e-mail du destinataire, y compris pour les invités
  `enseigne_ids`     VARCHAR(120) DEFAULT NULL,     -- liste des maisons engagées, pour les filtres
  `statut`           ENUM('attente_validation','en_attente','panier_abandonne','payee','preparation','controle_qualite','expediee','livree','annulee','rembourse_partiel','remboursee','litige') NOT NULL DEFAULT 'en_attente',
  `type_livraison`   ENUM('domicile','relais','retait') NOT NULL DEFAULT 'domicile',
  `transporteur_id`  INT DEFAULT NULL,
  `suivi_numero`     VARCHAR(60) DEFAULT NULL,
  `adresse_destinataire` TEXT,                     -- snapshot JSON au moment de la commande
  `adresse_facturation` TEXT,
  `point_relais`     VARCHAR(200) DEFAULT NULL,
  `date_souhaitee`   DATE DEFAULT NULL,
  `sous_total`       INT NOT NULL DEFAULT 0,        -- millimes, hors remise et livraison
  `remise_montant`   INT NOT NULL DEFAULT 0,
  `remise_detail`    TEXT,                          -- JSON : promo par ligne + global
  `livraison_frais`  INT NOT NULL DEFAULT 0,
  `total_ttc`        INT NOT NULL DEFAULT 0,
  `total_ht`         INT NOT NULL DEFAULT 0,
  `total_tva`        INT NOT NULL DEFAULT 0,
  `cout_achat`       INT NOT NULL DEFAULT 0,        -- marge = HT - coût d'achat
  `code_promo`       VARCHAR(40) DEFAULT NULL,
  `mode_paiement`    ENUM('carte_gpg','livraison','virement','cheque','especes_point','cadeau') NOT NULL DEFAULT 'carte_gpg',
  `statut_paiement`  ENUM('aucun','initialise','en_attente','accorde','refuse','rembourse','partiellement_rembourse') NOT NULL DEFAULT 'aucun',
  `message_client`   TEXT,
  `note_interne`     TEXT,
  `emballage_cadeau` TINYINT(1) NOT NULL DEFAULT 0,
  `carte_message`    VARCHAR(300) DEFAULT NULL,
  `apport`           VARCHAR(60) DEFAULT NULL,
  `validee_le`       DATETIME DEFAULT NULL,
  `preparee_le`      DATETIME DEFAULT NULL,
  `expediee_le`      DATETIME DEFAULT NULL,
  `livree_le`        DATETIME DEFAULT NULL,
  `annulee_le`       DATETIME DEFAULT NULL,
  `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_commande_reference` (`reference`),
  KEY `ix_commande_statut` (`statut`, `created_at`),
  KEY `ix_commande_client` (`client_id`, `created_at`),
  KEY `ix_commande_paiement` (`statut_paiement`),
  KEY `ix_commande_email` (`email`),
  CONSTRAINT `fk_commande_client` FOREIGN KEY (`client_id`) REFERENCES `tz_client` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_commande_transporteur` FOREIGN KEY (`transporteur_id`) REFERENCES `tz_transporteur` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

DROP TABLE IF EXISTS `tz_commande_ligne`;
CREATE TABLE `tz_commande_ligne` (
  `id`             INT NOT NULL AUTO_INCREMENT,
  `commande_id`    INT NOT NULL,
  `enseigne_id`    INT NOT NULL,
  `produit_id`     INT DEFAULT NULL,
  `variante_id`    INT DEFAULT NULL,
  `designation`    VARCHAR(220) NOT NULL,
  `sku`            VARCHAR(60) DEFAULT NULL,
  `quantite`       SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `prix_unitaire`  INT NOT NULL DEFAULT 0,          -- TTC millimes, figé à la commande
  `remise_ligne`   INT NOT NULL DEFAULT 0,
  `tva_taux`       INT NOT NULL DEFAULT 19000,
  `total_ttc`      INT NOT NULL DEFAULT 0,
  `total_ht`       INT NOT NULL DEFAULT 0,
  `total_tva`      INT NOT NULL DEFAULT 0,
  `cout_achat`     INT NOT NULL DEFAULT 0,
  `image`          VARCHAR(255) DEFAULT NULL,
  `createur_lot`   VARCHAR(40) DEFAULT NULL,        -- n° de lot pour les produits périssables
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_ligne_commande` (`commande_id`),
  KEY `ix_ligne_enseigne` (`enseigne_id`, `created_at`),
  KEY `ix_ligne_produit` (`produit_id`),
  CONSTRAINT `fk_ligne_commande` FOREIGN KEY (`commande_id`) REFERENCES `tz_commande` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------- 9. Paiements
DROP TABLE IF EXISTS `tz_paiement`;
CREATE TABLE `tz_paiement` (
  `id`                 INT NOT NULL AUTO_INCREMENT,
  `commande_id`        INT NOT NULL,
  `fournisseur`        ENUM('gpg_checkout','sandbox','livraison','virement','especes') NOT NULL DEFAULT 'gpg_checkout',
  `order_id`           VARCHAR(40) NOT NULL,        -- reference envoyée à GPG (payid côté marchand)
  `num_site`           VARCHAR(20) DEFAULT NULL,    -- gpg_site.num_client
  `montant`            INT NOT NULL,                -- millimes
  `devise`             CHAR(3) NOT NULL DEFAULT 'TND',
  `statut`             ENUM('initialise','en_attente_3ds','accorde','refuse','annule','rembourse','erreur') NOT NULL DEFAULT 'initialise',
  `code_autorisation`  VARCHAR(20) DEFAULT NULL,
  `ref_checkout`       VARCHAR(40) DEFAULT NULL,    -- gpg_paiement.paiementId
  `id_transaction_gpg` VARCHAR(40) DEFAULT NULL,
  `dernier_trans_status` CHAR(2) DEFAULT NULL,      -- 00 / 05 / 06
  `signature_recue`    CHAR(40) DEFAULT NULL,
  `session_marchande`  VARCHAR(60) DEFAULT NULL,    -- MerchandSession (anti-rejeu)
  `carte_type`         VARCHAR(20) DEFAULT NULL,
  `carte_fin`          CHAR(4) DEFAULT NULL,        -- 4 derniers seulement, jamais la PAN
  `reponse_brute`      MEDIUMTEXT,
  `erreur_message`     VARCHAR(255) DEFAULT NULL,
  `ip_client`          VARCHAR(45) DEFAULT NULL,
  `initie_le`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `solde_le`           DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_paiement_order` (`order_id`, `fournisseur`),
  KEY `ix_paiement_commande` (`commande_id`),
  KEY `ix_paiement_statut` (`statut`, `initie_le`),
  CONSTRAINT `fk_paiement_commande` FOREIGN KEY (`commande_id`) REFERENCES `tz_commande` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

DROP TABLE IF EXISTS `tz_remboursement`;
CREATE TABLE `tz_remboursement` (
  `id`             INT NOT NULL AUTO_INCREMENT,
  `commande_id`    INT NOT NULL,
  `paiement_id`    INT DEFAULT NULL,
  `montant`        INT NOT NULL,
  `motif`          VARCHAR(255) DEFAULT NULL,
  `statut`         ENUM('demande','accepte','refuse','execute') NOT NULL DEFAULT 'demande',
  `utilisateur_id` INT DEFAULT NULL,
  `facture_id`     INT DEFAULT NULL,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_remboursement_commande` (`commande_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ------------------------------------------------------- 10. Promotions
DROP TABLE IF EXISTS `tz_promo`;
CREATE TABLE `tz_promo` (
  `id`               INT NOT NULL AUTO_INCREMENT,
  `code`             VARCHAR(40) DEFAULT NULL,      -- NULL = remise automatique
  `enseigne_id`      INT DEFAULT NULL,              -- NULL = toutes les maisons
  `libelle`          VARCHAR(160) NOT NULL,
  `libelle_ar`       VARCHAR(160) DEFAULT NULL,
  `description`      VARCHAR(300) DEFAULT NULL,
  `type`             ENUM('pourcentage','montant','port_gratuit','palier_pourcentage','palier_montant','n_plus_gratuit','prix_impose','cadeau') NOT NULL DEFAULT 'pourcentage',
  `valeur`           INT NOT NULL DEFAULT 0,        -- % en millièmes (10000 = 10 %), ou montant en millimes
  `seuil_achat`      INT NOT NULL DEFAULT 0,        -- déclenchement à partir de (millimes)
  `quantite_declencheur` SMALLINT NOT NULL DEFAULT 0,
  `nombre_offert`    SMALLINT NOT NULL DEFAULT 0,
  `plafond_remise`   INT NOT NULL DEFAULT 0,        -- 0 = non plafonnée
  `cumulable`        TINYINT(1) NOT NULL DEFAULT 1,
  `une_fois_par_client` TINYINT(1) NOT NULL DEFAULT 0,
  `premiere_commande_seule` TINYINT(1) NOT NULL DEFAULT 0,
  `quota_total`      INT NOT NULL DEFAULT 0,        -- 0 = illimité
  `quota_utilise`    INT NOT NULL DEFAULT 0,
  `cible_type`       ENUM('global','categorie','produit','variante','enseigne') NOT NULL DEFAULT 'global',
  `cible_ids`        VARCHAR(255) DEFAULT NULL,     -- liste d'ids séparés par des virgules
  `exclusion_ids`    VARCHAR(255) DEFAULT NULL,
  `debut_at`         DATETIME NOT NULL,
  `fin_at`           DATETIME DEFAULT NULL,
  `visuel`           VARCHAR(255) DEFAULT NULL,
  `afficher_vitrine` TINYINT(1) NOT NULL DEFAULT 0,
  `etat`             ENUM('brouillon','active','suspendue','terminee') NOT NULL DEFAULT 'brouillon',
  `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_promo_code` (`code`),
  KEY `ix_promo_fenetre` (`etat`, `debut_at`, `fin_at`),
  KEY `ix_promo_enseigne` (`enseigne_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

DROP TABLE IF EXISTS `tz_promo_utilisation`;
CREATE TABLE `tz_promo_utilisation` (
  `id`            INT NOT NULL AUTO_INCREMENT,
  `promo_id`      INT NOT NULL,
  `commande_id`   INT NOT NULL,
  `client_id`     INT DEFAULT NULL,
  `montant_applique` INT NOT NULL DEFAULT 0,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_promo_use` (`promo_id`, `client_id`),
  CONSTRAINT `fk_promouse_promo` FOREIGN KEY (`promo_id`) REFERENCES `tz_promo` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ------------------------------------------------- 11. Régie publicitaire
DROP TABLE IF EXISTS `tz_banniere`;
CREATE TABLE `tz_banniere` (
  `id`            INT NOT NULL AUTO_INCREMENT,
  `enseigne_id`   INT DEFAULT NULL,                  -- NULL = bannière de la plateforme
  `emplacement`   ENUM('hero','bandeau','flottante','cote_catalogue','pied','interstitiel','newsletter') NOT NULL DEFAULT 'hero',
  `titre`         VARCHAR(160) NOT NULL,
  `sous_titre`    VARCHAR(220) DEFAULT NULL,
  `texte_bouton`  VARCHAR(60) DEFAULT NULL,
  `image`         VARCHAR(255) DEFAULT NULL,
  `image_mobile`  VARCHAR(255) DEFAULT NULL,
  `url`           VARCHAR(255) DEFAULT NULL,
  `couleur_fond`  CHAR(7) DEFAULT NULL,
  `ciblage_pages` VARCHAR(255) DEFAULT NULL,         -- "/,/boutique/deglat,*"
  `ciblage_client` ENUM('tous','nouveaux','fideles','abandonnistes') NOT NULL DEFAULT 'tous',
  `debut_at`      DATETIME NOT NULL,
  `fin_at`        DATETIME DEFAULT NULL,
  `priorite`      SMALLINT NOT NULL DEFAULT 50,
  `type_offre`    ENUM('interne','sponsorisee','slot') NOT NULL DEFAULT 'interne',
  `tarif_jour`    INT NOT NULL DEFAULT 0,            -- millimes, pour le suivi du revenu pub
  `budget`        INT NOT NULL DEFAULT 0,
  `impressions`   INT NOT NULL DEFAULT 0,
  `clics`         INT NOT NULL DEFAULT 0,
  `plafond_impressions` INT NOT NULL DEFAULT 0,
  `etat`          ENUM('brouillon','programmee','en_ligne','pause','terminee') NOT NULL DEFAULT 'brouillon',
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_banniere_slot` (`emplacement`, `etat`, `debut_at`, `priorite`),
  KEY `ix_banniere_enseigne` (`enseigne_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

DROP TABLE IF EXISTS `tz_banniere_evenement`;
CREATE TABLE `tz_banniere_evenement` (
  `id`          BIGINT NOT NULL AUTO_INCREMENT,
  `banniere_id` INT NOT NULL,
  `type`        ENUM('vue','clic') NOT NULL,
  `session_id`  CHAR(32) DEFAULT NULL,
  `ip`          VARCHAR(45) DEFAULT NULL,
  `page`        VARCHAR(160) DEFAULT NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_bann_evt` (`banniere_id`, `type`, `created_at`),
  CONSTRAINT `fk_bann_evt_banniere` FOREIGN KEY (`banniere_id`) REFERENCES `tz_banniere` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ------------------------------------------------------------ 12. Avis
DROP TABLE IF EXISTS `tz_avis`;
CREATE TABLE `tz_avis` (
  `id`         INT NOT NULL AUTO_INCREMENT,
  `produit_id` INT NOT NULL,
  `client_id`  INT DEFAULT NULL,
  `commande_id` INT DEFAULT NULL,
  `auteur`     VARCHAR(120) DEFAULT NULL,
  `note`       TINYINT NOT NULL,
  `titre`      VARCHAR(160) DEFAULT NULL,
  `texte`      TEXT,
  `statut`     ENUM('en_attente','publie','rejete') NOT NULL DEFAULT 'en_attente',
  `reponse`    TEXT,
  `achat_verifie` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_avis_produit` (`produit_id`, `statut`),
  CONSTRAINT `fk_avis_produit` FOREIGN KEY (`produit_id`) REFERENCES `tz_produit` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ------------------------------------------------- 13. Pages tranquilles
DROP TABLE IF EXISTS `tz_page`;
CREATE TABLE `tz_page` (
  `id`               INT NOT NULL AUTO_INCREMENT,
  `slug`             VARCHAR(120) NOT NULL,
  `titre`            VARCHAR(160) NOT NULL,
  `titre_ar`         VARCHAR(160) DEFAULT NULL,
  `corps`            MEDIUMTEXT,
  `corps_ar`         MEDIUMTEXT,
  `visuel`           VARCHAR(255) DEFAULT NULL,
  `meta_description` VARCHAR(300) DEFAULT NULL,
  `position`         SMALLINT NOT NULL DEFAULT 0,
  `dans_menu`        TINYINT(1) NOT NULL DEFAULT 0,
  `etat`             ENUM('publiee','cachee') NOT NULL DEFAULT 'cachee',
  `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_page_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------- 14. Comptabilité générale
DROP TABLE IF EXISTS `tz_journal`;
CREATE TABLE `tz_journal` (
  `id`       INT NOT NULL AUTO_INCREMENT,
  `code`     CHAR(4) NOT NULL,
  `libelle`  VARCHAR(120) NOT NULL,
  `nature`   ENUM('ventes','achats','banque','caisse','etre','od') NOT NULL,
  `devise`   CHAR(3) NOT NULL DEFAULT 'TND',
  `etat`     ENUM('actif','archive') NOT NULL DEFAULT 'actif',
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_journal_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

DROP TABLE IF EXISTS `tz_ecriture`;
CREATE TABLE `tz_ecriture` (
  `id`             BIGINT NOT NULL AUTO_INCREMENT,
  `journal_id`     INT NOT NULL,
  `date_ecriture`  DATE NOT NULL,
  `piece`          VARCHAR(40) DEFAULT NULL,        -- n° de facture ou de recette
  `lettrage`       VARCHAR(20) DEFAULT NULL,
  `compte`         CHAR(7) NOT NULL,                -- 411, 401, 44571, 7111, 5121...
  `intitule`       VARCHAR(200) NOT NULL,
  `debit`          INT NOT NULL DEFAULT 0,          -- millimes
  `credit`         INT NOT NULL DEFAULT 0,          -- millimes
  `counterpartie`  VARCHAR(120) DEFAULT NULL,       -- client ou fournisseur
  `commande_id`    INT DEFAULT NULL,
  `facture_id`     INT DEFAULT NULL,
  `enseigne_id`    INT DEFAULT NULL,
  `tva_taux`       INT DEFAULT NULL,
  `validee`        TINYINT(1) NOT NULL DEFAULT 1,
  `utilisateur_id` INT DEFAULT NULL,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_ecriture_date` (`date_ecriture`, `journal_id`),
  KEY `ix_ecriture_compte` (`compte`, `date_ecriture`),
  KEY `ix_ecriture_piece` (`piece`),
  KEY `ix_ecriture_source` (`commande_id`, `facture_id`),
  CONSTRAINT `fk_ecriture_journal` FOREIGN KEY (`journal_id`) REFERENCES `tz_journal` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

DROP TABLE IF EXISTS `tz_facture`;
CREATE TABLE `tz_facture` (
  `id`            INT NOT NULL AUTO_INCREMENT,
  `numero`        VARCHAR(30) NOT NULL,
  `type`          ENUM('facture','avoir','proforma','facture_acompte') NOT NULL DEFAULT 'facture',
  `commande_id`   INT DEFAULT NULL,
  `client_id`     INT DEFAULT NULL,
  `enseigne_id`   INT DEFAULT NULL,
  `date_emission` DATE NOT NULL,
  `date_echeance` DATE DEFAULT NULL,
  `date_paiement` DATE DEFAULT NULL,
  `objet`         VARCHAR(200) DEFAULT NULL,
  `total_ht`      INT NOT NULL DEFAULT 0,
  `total_tva`     INT NOT NULL DEFAULT 0,
  `total_ttc`     INT NOT NULL DEFAULT 0,
  `regle`         INT NOT NULL DEFAULT 0,           -- millimes déjà encaissés
  `statut`        ENUM('brouillon','emise','partielle','payee','annulee','recouvre') NOT NULL DEFAULT 'emise',
  `mode_reglement` ENUM('carte_gpg','livraison','virement','cheque','especes') NOT NULL DEFAULT 'carte_gpg',
  `mention`       VARCHAR(255) DEFAULT NULL,
  `ecrite`        TINYINT(1) NOT NULL DEFAULT 0,     -- écritures comptables déjà générées
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_facture_numero` (`numero`),
  KEY `ix_facture_client` (`client_id`, `date_emission`),
  KEY `ix_facture_statut` (`statut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

DROP TABLE IF EXISTS `tz_facture_ligne`;
CREATE TABLE `tz_facture_ligne` (
  `id`            INT NOT NULL AUTO_INCREMENT,
  `facture_id`    INT NOT NULL,
  `position`      SMALLINT NOT NULL DEFAULT 0,
  `designation`   VARCHAR(220) NOT NULL,
  `quantite`      SMALLINT NOT NULL DEFAULT 1,
  `prix_unitaire` INT NOT NULL DEFAULT 0,           -- TTC, millimes (comme tz_commande_ligne)
  `remise`        INT NOT NULL DEFAULT 0,           -- millimes, sur le TTC de la ligne
  `tva_taux`      INT NOT NULL DEFAULT 19000,
  `total_ht`      INT NOT NULL DEFAULT 0,
  `total_tva`     INT NOT NULL DEFAULT 0,
  `total_ttc`     INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `ix_fligne_facture` (`facture_id`),
  CONSTRAINT `fk_fligne_facture` FOREIGN KEY (`facture_id`) REFERENCES `tz_facture` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

DROP TABLE IF EXISTS `tz_encaissement`;
CREATE TABLE `tz_encaissement` (
  `id`          INT NOT NULL AUTO_INCREMENT,
  `facture_id`  INT NOT NULL,
  `date_recue`  DATE NOT NULL,
  `montant`     INT NOT NULL,
  `mode`        ENUM('carte_gpg','virement','cheque','especes','livraison') NOT NULL,
  `reference`   VARCHAR(60) DEFAULT NULL,
  `banque`      VARCHAR(80) DEFAULT NULL,
  `note`        VARCHAR(255) DEFAULT NULL,
  `utilisateur_id` INT DEFAULT NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_encaissement_facture` (`facture_id`, `date_recue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ---------------------------------------- 15. Accès, réglages, traçabilité
DROP TABLE IF EXISTS `tz_utilisateur`;
CREATE TABLE `tz_utilisateur` (
  `id`             INT NOT NULL AUTO_INCREMENT,
  `login`          VARCHAR(60) NOT NULL,
  `nom`            VARCHAR(120) NOT NULL,
  `email`          VARCHAR(160) DEFAULT NULL,
  `mot_de_passe`   VARCHAR(255) NOT NULL,            -- password_hash() bcrypt cost 11
  `role`           ENUM('super','gestionnaire','comptable','logistique','vendeur') NOT NULL DEFAULT 'gestionnaire',
  `enseigne_id`    INT DEFAULT NULL,                 -- rôle vendeur : rattachement à une maison
  `etat`           ENUM('actif','suspendu','archive') NOT NULL DEFAULT 'actif',
  `nbr_tentatives` SMALLINT NOT NULL DEFAULT 0,
  `bloque_jusqua`  DATETIME DEFAULT NULL,
  `last_login`     DATETIME DEFAULT NULL,
  `password_changed_at` DATETIME DEFAULT NULL,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_utilisateur_login` (`login`),
  KEY `ix_utilisateur_enseigne` (`enseigne_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

DROP TABLE IF EXISTS `tz_parametre`;
CREATE TABLE `tz_parametre` (
  `cle`      VARCHAR(80) NOT NULL,
  `valeur`   TEXT,
  `type`     ENUM('texte','entier','reel','booleen','json','couleur','date') NOT NULL DEFAULT 'texte',
  `libelle`  VARCHAR(160) DEFAULT NULL,
  `groupe`   VARCHAR(40) NOT NULL DEFAULT 'general',
  `modifiable` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`cle`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

DROP TABLE IF EXISTS `tz_action_journal`;
CREATE TABLE `tz_action_journal` (
  `id`             BIGINT NOT NULL AUTO_INCREMENT,
  `utilisateur_id` INT DEFAULT NULL,
  `client_id`      INT DEFAULT NULL,
  `acteur`         ENUM('admin','client','systeme','passerelle') NOT NULL DEFAULT 'admin',
  `action`         VARCHAR(80) NOT NULL,
  `cible_type`     VARCHAR(40) DEFAULT NULL,
  `cible_id`       INT DEFAULT NULL,
  `details`        MEDIUMTEXT,
  `ip`             VARCHAR(45) DEFAULT NULL,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_journal_action_date` (`created_at`),
  KEY `ix_journal_action_cible` (`cible_type`, `cible_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

DROP TABLE IF EXISTS `tz_session_panier`;
CREATE TABLE `tz_session_panier` (
  `id`            INT NOT NULL AUTO_INCREMENT,
  `token`         CHAR(32) NOT NULL,
  `client_id`     INT DEFAULT NULL,
  `contenu`       MEDIUMTEXT,
  `email`         VARCHAR(160) DEFAULT NULL,
  `articles`      SMALLINT NOT NULL DEFAULT 0,
  `montant`       INT NOT NULL DEFAULT 0,
  `relance_envoyee` TINYINT(1) NOT NULL DEFAULT 0,
  `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_panier_token` (`token`),
  KEY `ix_panier_client` (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

SET FOREIGN_KEY_CHECKS = 1;
