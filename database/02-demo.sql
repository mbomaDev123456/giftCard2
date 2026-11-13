-- =====================================================================
-- Jeu de démonstration : maisons, rayons, produits tozérois, promos,
-- régie, réglages, comptabilité, comptes de test.
-- À importer APRÈS tozeur_shop.sql.
-- Montants en millimes : 12,500 DT = 12500.
-- =====================================================================

USE `tozeur_shop`;
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE `tz_banniere_evenement`;
TRUNCATE TABLE `tz_banniere`;
TRUNCATE TABLE `tz_promo_utilisation`;
TRUNCATE TABLE `tz_promo`;
TRUNCATE TABLE `tz_avis`;
TRUNCATE TABLE `tz_ecriture`;
TRUNCATE TABLE `tz_encaissement`;
TRUNCATE TABLE `tz_facture_ligne`;
TRUNCATE TABLE `tz_facture`;
TRUNCATE TABLE `tz_journal`;
TRUNCATE TABLE `tz_remboursement`;
TRUNCATE TABLE `tz_paiement`;
TRUNCATE TABLE `tz_commande_ligne`;
TRUNCATE TABLE `tz_commande`;
TRUNCATE TABLE `tz_stock_mouvement`;
TRUNCATE TABLE `tz_variante`;
TRUNCATE TABLE `tz_produit`;
TRUNCATE TABLE `tz_categorie`;
TRUNCATE TABLE `tz_enseigne`;
TRUNCATE TABLE `tz_adresse`;
TRUNCATE TABLE `tz_client`;
TRUNCATE TABLE `tz_transporteur`;
TRUNCATE TABLE `tz_page`;
TRUNCATE TABLE `tz_action_journal`;
TRUNCATE TABLE `tz_session_panier`;
DELETE FROM `tz_parametre`;
DELETE FROM `tz_utilisateur`;

-- ------------------------------------------------------- Les cinq maisons
INSERT INTO `tz_enseigne`
  (`id`,`nom`,`slug`,`slogan`,`description`,`histoire`,`logo`,`banniere`,`couleur`,`couleur_secondaire`,`ville`,`telephone`,`email`,`commission_bpo`,`position`,`vedette`,`meta_titre`,`meta_description`,`etat`) VALUES
(1,'El Jerid Dattes','el-jerid','La datte de Tozeur, du palmier à votre table',
   'Sélection de Deglet Nour et de Baroud récoltées dans les palmeraies de Tozeur et de Nefzua, triées à la main calibre par calibre.',
   'Trois générations de récoltants dans la palmeraie de Nefzua. La cueillette se fait en bottes, au mois d''octobre, quand la grappe devient "semia" : mi-cuite, fondante, encore humide de sève. Nous n''achetons que chez vingt-deux producteurs que nous connaissons par leur puits.',
   NULL,'enseignes/el-jerid.jpg','#7a3b1e','#d9a441','Tozeur','+216 22 118 400','contact@el-jerid.tn',5000,1,1,
   'El Jerid Dattes — Deglet Nour de Tozeur','Dattes Deglet Nour et Baroud de la palmeraie de Tozeur, récoltées en bottes et triées à la main.','active'),
(2,'Sabra Terre Cuite','sabra-terre-cuite','Le argile de Tozeur, cuite au feu de bois',
   'Poterie utilitaire et décorative façonnée à Jebeldiour : jarres à eau, plats vernissés, théières et verres à thé gravés.',
   'L''argile rouge de Jebeldiour est extraite à douze kilomètres de Tozeur, puis laissée un mois à l''air libre avant tournassage. Les émaux sont posés au pinceau, une cuisson à 980 °C lui donne ce vernis brillant que l''on retrouve dans les maisons de la ville old.',
   NULL,'enseignes/sabra.jpg','#9c4a35','#c9714b','Jebeldiour, Tozeur','+216 22 340 118','atelier@sabra-terre.tn',6000,2,1,
   'Sabra Terre Cuite — Poterie de Tozeur','Poterie de Tozeur façonnée à la main en argile de Jebeldiour : jarres, plats, théières et verres à thé.','active'),
(3,'Tisseuse du Chott','tisseuse-du-chott','Laines et cotons noués à la main',
   'Tapis noués, coussins brodés, burnous et pièces de laine cardée à El Hamdania, teintures végétales.',
   'Chaque tapis demande quatre à six mois de nœuds. Les motifs — losanges, peignes, serpents — sont le langage des femmes de la montagnon. Les laines sont cardées à la main et teintes à la garance, au romarin et à l''indigo.',
   NULL,'enseignes/tisseuse.jpg','#4b2f4a','#a4688b','Tozeur','+216 21 662 075','contact@tisseuse-chott.tn',7000,3,0,
   'Tisseuse du Chott — Tapis et tissage de Tozeur','Tapis berbères noués main, coussins brodés et burnous en laine, teintures végétales de la région de Tozeur.','active'),
(4,'Fleur de Barbarie','fleur-de-barbarie','Huiles précises et savon noir du Jerid',
   'Huile de pépins de figue de barbarie pressée à froid, savon noir à l''huile d''olive, eau de fleur d''oranger distillée.',
   'Il faut une tonne de fruits pour un litre d''huile. Les pépins sont lavés, séchés au soleil de septembre puis pressés à froid dans un atelier de Neuro. Rien n''est chauffé au-delà de 38 °C : c''est ce qui laisse la vitamine E intacte.',
   NULL,'enseignes/barbarie.jpg','#3e5c46','#8fae7b','Neuro, Tozeur','+216 24 507 330','labo@fleur-barbarie.tn',5000,4,0,
   'Fleur de Barbarie — Huile de pépins de figue de Barbarie','Huile de pépins de figue de barbarie pressée à froid, savon noir et eau de fleur d''oranger de la région de Tozeur.','active'),
(5,'Comptoir du Palmaris','comptoir-du-palmaris','L''épicerie fine de la région de Tozeur',
   'Huile d''olive vierge du Jerid, harissa artisanale, miel de jujubier, sirop de dattes, vannerie en palmier.',
   'Le comptoir rassemble ce que la région produit de mieux et le revend au prix juste pour l''artisan. Nous publions sur chaque fiche le nom du producteur, le village et la campagne.',
   NULL,'enseignes/palmaris.jpg','#8a6a1f','#c9a84c','Tozeur','+216 20 445 902','boutique@comptoir-palmaris.tn',4000,5,1,
   'Comptoir du Palmaris — Épicerie fine de Tozeur','Huile d''olive, harissa artisanale, miel de jujubier et vannerie en palmier de la région de Tozeur.','active');

-- ------------------------------------------------------------- Les rayons
INSERT INTO `tz_categorie` (`id`,`parent_id`,`enseigne_id`,`nom`,`nom_ar`,`slug`,`description`,`image`,`tva_taux`,`position`,`etat`) VALUES
(1,NULL,NULL,'Dattes','تمور','dattes','Deglet Nour, Baroud, Boufeggous, dénoyautées et fourrées.','categories/dattes.jpg',7000,1,'publiee'),
(2,1,NULL,'Deglet Nour','تمر دقلة النور','deglet-nour','La reine des dattes de Tozeur, mi-cuite en bottes.',NULL,7000,1,'publiee'),
(3,1,NULL,'Dattes farcies et transformées','تمور محشوة','dattes-transformees','Fourrées amande ou beurre, pâtes, sirop, confiseries.',NULL,7000,2,'publiee'),
(4,NULL,NULL,'Poterie et terre cuite','فخار','poterie','Jarres, plats, théières et verres tournés à Jebeldiour.','categories/poterie.jpg',19000,2,'publiee'),
(5,NULL,NULL,'Tissage et tapis','نسيج','tissage','Tapis noués main, coussins brodés, burnous.','categories/tissage.jpg',19000,3,'publiee'),
(6,NULL,NULL,'Huiles et cosmétiques','زيوت ودهون','huiles-cosmetiques','Huile de pépins de figue de barbarie, savon noir, eaux florales.','categories/huiles.jpg',19000,4,'publiee'),
(7,NULL,NULL,'Épicerie du Jerid','بقالة','epicerie','Huile d''olive, harissa, miel, vinaigre de dattes.','categories/epicerie.jpg',7000,5,'publiee'),
(8,NULL,NULL,'Coffrets et cadeaux','علب هدايا','coffrets','Assortiments à offrir, livrés dans tout le pays.','categories/coffrets.jpg',19000,6,'publiee');

-- ------------------------------------------------------------- Les produits
INSERT INTO `tz_produit`
 (`id`,`enseigne_id`,`categorie_id`,`nom`,`nom_ar`,`slug`,`resume`,`description`,`conseils`,`origine`,`matiere`,`savoir_faire`,`tva_taux`,`prix_achat_bpo`,`prix_min`,`prix_max`,`image_principale`,`galerie`,`poids_grammes`,`peremption_jours`,`certifications`,`label`,`vedette`,`nouveaute`,`note_moyenne`,`note_compte`,`position`,`etat`) VALUES
(1,1,2,'Deglet Nour mi-cuite en bottes','دقلة النور بالسعفات','deglet-nour-mi-cuite-bottes',
  'La datte de Tozeur cueillie en bottes au stade semia, fondante et translucide.',
  'Récolte d''octobre, stade semia : la chair est encore humide, la peau fine se détache sous le doigt. Triée trois fois, calibrée 23/25, conditionnée dans son plateau de palmier avec la botte qui la protège. Aucune fermentation, aucun conservateur, aucun glucose ajouté.',
  'Conservez la botte au frais et sortez-la une heure avant de servir. En Tunisie on la trempe dans le café noir ou on l''ouvre autour d''une amande grillée.',
  'Palmeraie de Nefzua, Tozeur','Datte Deglet Nour (Phoenix dactylifera)','Récolte manuelle en bottes',7000,5200,12500,58000,'produits/deglet-nour-bottes.jpg','["produits/deglet-nour-bottes.jpg","produits/deglet-nour-detail.jpg"]',1000,365,'Aire protégée Nefzua','Récolte 2026',1,0,4.80,42,1,'publie'),
(2,1,2,'Deglet Nour premier choix calibrée','دقلة النور اختيار أول','deglet-nour-premier-choix',
  'Le calibre 25/27, la datte des tables de fête, dénoyautée sur demande.',
  'Même palmeraie, calibre supérieur. Les dattes sont calibrées au tamis et présentées en couche unique. Dénoyautage à la machine à vide sur option, sans casser la chair.',
  'À servir avec un thé à la menthe très sucré, ou farcies d''une amande trempée deux heures.',
  'Palmeraie de Tozeur','Datte Deglet Nour','Calibrage au tamis',7000,6400,16900,72000,'produits/deglet-nour-choix.jpg',NULL,1000,365,NULL,'Calibre 25/27',1,0,4.60,28,2,'publie'),
(3,1,2,'Deglet Nour agriculture biologique','دقلة النور بيولوجي','deglet-nour-bio',
  'Certifiée bio, issue de deux palmiers conduits sans intrant depuis 2019.',
  'Parcelle de 14 palmiers à Behamet, conduite en lutte biologique contre le Boufattata (papillon du palmier) par piégeage. Analyse de résidus jointe à chaque lot.',
  'Se conserve dix-huit mois sealed, puis six mois à l''ombre fraîche.',
  'Behamet, Tozeur','Datte Deglet Nour','Conduite biologique',7000,6800,8200,31000,'produits/deglet-nour-bio.jpg',NULL,500,540,'Contrôle TN-BIO-014','Certifiée bio',0,1,4.70,11,3,'publie'),
(4,1,2,'Baroud séchée aux quatre vents','برقوق','baroud-sechee-vent',
  'La datte sèche du désert, ridée, à croquer comme un fruit sec.',
  'Séchée sur les terrasses en terre de Remada, sans étuve. Chair ferme, goût de caramel brulé et de réglisse, très longue conservation. C''est la datte des caravaniers.',
  'Réhydratez-la vingt minutes dans l''eau de fleur d''oranger pour les pâtisseries.',
  'Remada','Datte Baroud','Séchage solaire traditionnel',7000,4300,9800,26500,'produits/baroud.jpg',NULL,1000,730,NULL,'Séchage solaire',0,0,4.40,17,4,'publie'),
(5,1,3,'Dattes fourrées amande et fleur d''oranger','تمور محشوة باللوز','dattes-fourrees-amande',
  'Dénoyautées à la main, farcies d''une amande de Tébessa grillée.',
  'Chaque datte est incisée, dénoyautée, puis garnie d''une amande torréfiée au four à bois et imbibée d''eau de fleur d''oranger de la maison Fleur de Barbarie. Deux maisons, une confiserie.',
  'À offrir en coffret, ou à servir avec un café turc serré.',
  'Tozeur','Dattes et amandes','Fourrage manuel',7000,7100,11200,21800,'produits/dattes-fourrees.jpg',NULL,500,240,NULL,'Fabriqué à la main',1,1,4.90,33,5,'publie'),
(6,1,3,'Sirop de dattes (rub) pressé à froid','دبس التمر','sirop-dattes-rub',
  'Le rub des anciens : la sève cuite des dattes trop mûres, en flacon de verre.',
  'Pression des dattes de triage, cuisson lente six heures au bain-marie, filtrage sur toile. Ni sucre ajouté ni glucose. Consistance d''un miel liquide, goût de mélasse et de cacao.',
  'Une cuillère dans le lait tiède du matin, ou sur des crêpes à l''huile d''olive.',
  'Tozeur','Datte','Cuisson lente',7000,4600,9500,17200,'produits/sirop-dattes.jpg',NULL,500,540,NULL,'Sans sucre ajouté',0,0,4.50,19,6,'publie'),
(7,1,3,'Pâte de dattes pour la pâtisserie','عجينة التمر','pate-dattes-patisserie',
  'Dénoyautée, mixée, en pain de 1 kg — la base du kaak et du makrout.',
  'Pâte lisse obtenue par broyage fin de Deglet Nour de triage, sans additif. Elle se ramollit dix secondes au micro-ondes et se roule entre deux feuilles plastiques.',
  '1 volume de pâte pour 2 volumes de semoule pour un makrout équilibré.',
  'Tozeur','Datte','Broyage fin',7000,5400,7400,26800,'produits/pate-dattes.jpg',NULL,1000,300,NULL,'Ingrédient',0,0,4.30,8,7,'publie'),
(8,2,4,'Jarre à eau fraiche "Zlabia"','جرة الماء','jarre-eau-zlabia',
  'La cruche qui refroidit l''eau par évaporation, comme dans les maisons de Tozeur.',
  'Tournée en argile de Jebeldiour, vernis intérieur au sel, non émaillée sur les faces externes : l''eau suinte à travers la paroi et se refroidit en traversant la brique crue. Deux tailles, deux débits.',
  'Laissez la jarre à l''ombre et remplie : la paroi doit rester humide pour que le froid se fasse.',
  'Jebeldiour, Tozeur','Argile rouge cuite à 980 °C','Tournassage à la main',19000,32000,68000,98000,'produits/jarre-zlabia.jpg','["produits/jarre-zlabia.jpg"]',4200,NULL,'Pièce unique','Forte demande',1,1,4.80,12,1,'publie'),
(9,2,4,'Plat de service vernissé à décor gravé','صحن التقديم','plat-service-vernisse',
  'Le plat ovale des jours de fête, décor de palme gravé à la pointe.',
  'Cuit puis émaillé, le décor de palmes est gravé dans l''engobe avant la seconde cuisson. Passe au four doux et au lave-vaisselle, mais le vernis brillant préfère l''eau tiède.',
  'Il tient quatre méchouis de taille familiale.',
  'Jebeldiour','Argile vernissée','Gravure à la pointe',19000,19000,42000,42000,'produits/plat-vernisse.jpg',NULL,1800,NULL,NULL,'Usage alimentaire',1,0,4.60,9,2,'publie'),
(10,2,4,'Théière traditionnelle de Tozeur','ابرييق الشاي','theiere-traditionnelle',
  'La théière à panse basse qui tient le thé très chaud sans le brûler.',
  'Terre réfractaire émaillée, anse en osier tressé à la main pour ne pas se brûler. Contenance 1,2 litre — six verres.',
  'Ne jamais la poser sur le feu vif : les Thés de Tozeur se font à la braise.',
  'Jebeldiour','Terre réfractaire et osier','Anse tressée main',19000,24000,55000,55000,'produits/theiere.jpg',NULL,900,NULL,NULL,'Contenance 1,2 L',0,1,4.70,15,3,'publie'),
(11,2,4,'Service à thé : théière et six verres gravés','طقم الشاي','service-the-six-verres',
  'Le rituel complet, gravé au diamant, dans son plateau de terre.',
  'Six verres classiques tournés et gravés, théière assortie, plateau à rebord. Chaque pièce est marquée du poinçon de l''atelier.',
  'Le thé se verse de haut : c''est la mousse qui fait la valeur du service.',
  'Jebeldiour','Terre émaillée et verre','Taille du verre à la main',19000,41000,128000,128000,'produits/service-the.jpg',NULL,2600,NULL,'Poinçon d''atelier','Coffret offert',1,0,4.90,7,4,'publie'),
(12,3,5,'Tapis berbère noué main 160 x 230','زربية بربرية','tapis-berbere-noue-main',
  'Quatre mois de nœuds, laine cardée main, garance et indigo.',
  'Noué par trois tisseuses d''El Hamdania sur un métier vertical. 42 000 nœuds, densité 17 000/dm². Motif du peigne (mishkah) qui protège la maison. Dos apparent, franges nouées à la main.',
  'Aspirez à plat, jamais la brosse tournante. Un nettoyage à sec tous les deux ans.',
  'El Hamdania, Tozeur','Laine de mouton montagne, coton au fond','Noué main, teinture végétale',19000,198000,390000,390000,'produits/tapis-noue.jpg',NULL,3400,NULL,'Pièce signée','Pièce unique',1,0,5.00,4,1,'publie'),
(13,3,5,'Coussin brodé main au point de tige','وسادة مطرزة','coussin-brode-main',
  'Le couvre-coussin de mariage, brodé au fil de soie sur coton d''El Hamdania.',
  'Broderie au point de tige sur un fond de coton écrue, motifs de palmes et de fibules. Doublure coton, fermeture invisible, garnissage en plumes sur option.',
  'Housse lavable à la main à 30 °C, séchage à plat.',
  'El Hamdania','Coton et soie','Broderie main',19000,15000,38000,62000,'produits/coussin-brode.jpg',NULL,600,NULL,NULL,'Format 45 x 45',0,1,4.60,21,2,'publie'),
(14,3,5,'Burnous en laine d''agneau','برنس','burnous-laine-agneau',
  'Le manteau du désert, laine feutrée, capuche profonde contre le vent de sirocco.',
  'Tissu en laine d''agneau foulée au pied, coutures rabattues main, deux poches. Chaud sans être lourd, il prend sa forme à celui qui le porte.',
  'Brossez dans le sens du poil, rangez à plat l''été avec un sachet de cèdre.',
  'El Hamdania','Laine d''agneau','Foulage au pied',19000,118000,220000,245000,'produits/burnous.jpg',NULL,1900,NULL,NULL,'Sur mesures possibles',0,0,4.80,6,3,'publie'),
(15,3,5,'Corbeille de palmier tressée pour dates','قفة الخوص','corbeille-palmier-tressee',
  'La qaffa des récoltants, tressée en feuilles de palmier, pour servir les dattes.',
  'Tressée en palmes séchées et teintes, deux anses, fond renforcé. Elle sert aussi de rangement pour le pain ou le linge.',
  'Humidifiez légèrement les palmes pour les resserrer après quelques années.',
  'Tozeur','Feuille de palmier','Tressage main',19000,17000,45000,45000,'produits/corbeille-palmier.jpg',NULL,1200,NULL,NULL,'Diamètre 32 cm',0,0,4.40,13,4,'publie'),
(16,4,6,'Huile de pépins de figue de barbarie 30 ml','زيت بذور الهندية','huile-pepins-figue-barbarie-30ml',
  'Pressée à froid, une tonne de fruits pour un litre : l''or vert de la région.',
  'Pépins lavés et séchés au soleil de septembre, pressés à froid sans dépasser 38 °C, décantée six semaines en cuve inox à l''obscurité. Acide linoléique 62 %, vitamine E naturelle.',
  'Quatre gouttes sur le contour des yeux le soir, sur peau encore humide d''hydrolat.',
  'Neurès, Tozeur','Pépins de figue de barbarie (Opuntia ficus-indica)','Pression à froid',19000,34000,78000,142000,'produits/huile-figue-barbarie.jpg','["produits/huile-figue-barbarie.jpg"]',30,365,'Analyse de profil d''acides gras jointe','Flacon verre ambré',1,1,4.90,26,1,'publie'),
(17,4,6,'Savon noir à l''huile d''olive du Jerid','الصابون البلدي','savon-noir-huile-olive',
  'Le savon mou des hammams, en pâte d''olive et de potasse.',
  'Cuisson en cuve ouverte huit heures, huile d''olive de deuxième pression et pâte d''olives. Aucun parfum ajouté : c''est l''olive qui sent.',
  'Au gant de kessa, laissez poser trois minutes avant de rincer.',
  'Tozeur','Huile d''olive, potasse','Cuisson en cuve',19000,3100,8500,14900,'produits/savon-noir.jpg',NULL,250,730,NULL,'Sans parfum ajouté',1,0,4.70,38,2,'publie'),
(18,4,6,'Eau de fleur d''oranger distillée 500 ml','ماء الزهر','eau-fleur-oranger-distillee',
  'Distillée à l''alambic de cuivre, la vraie eau de zhar des gâteaux de Tozeur.',
  'Fleurs cueillies en avril à la main, distillées dans les six heures. Rien d''autre dans le flacon que l''hydrolat.',
  'Deux cuillerées dans la pâte à makrout, ou dans un verre d''eau après le repas.',
  'Tozeur','Fleurs d''oranger bigaradier','Distillation alambic cuivre',7000,5200,14000,23500,'produits/eau-fleur-oranger.jpg',NULL,500,730,NULL,'Produit alimentaire',0,0,4.80,14,3,'publie'),
(19,5,7,'Huile d''olive vierge extra du Jerid 1 L','زيت الزيتون','huile-olive-jerid-1l',
  'Pressée le jour de la récolte, une acidité de 0,2 %.',
  'Olives de la variété Chelemeni, cueillies à la main en décembre, pressées dans les douze heures en moulin à vis moderne. Filtre grossier uniquement, jamais désodorisée.',
  'Sur le pain, sur une salade d''orange et de cannelle, jamais pour frire.',
  'Neurès, Tozeur','Olives Chelemeni','Pression à froid 27 °C',7000,14200,26000,47000,'produits/huile-olive.jpg',NULL,1000,540,'AOP Jerba-Cap Bon ? en cours','Récolte décembre 2025',1,0,4.60,22,1,'publie'),
(20,5,7,'Harissa artisanale aux pistils de safran','هريسة','harissa-artisanale',
  'Piments séchés au soleil, ail du pays, carvi et huile d''olive : rien de plus.',
  'Piments de la sekhra séchés sur toit-terrasse, pilés au mortier avec l''ail, le sel et le carvi de Tozeur, puis montés à l''huile d''olive. Deux forces disponibles.',
  'Une cuillère dans un bol d''eau tiède fait une sauce pour les poissons de la Sebkha.',
  'Tozeur','Piments, ail, carvi, huile d''olive','Pilage au mortier',7000,3900,7200,12800,'produits/harissa.jpg',NULL,200,540,NULL,'Douce ou forte',0,0,4.50,31,2,'publie'),
(21,5,7,'Miel de jujubier (sedr) des palmeraies','عسل النبق','miel-jujubier-sedr',
  'Le miel sombre et amer des jujubiers, récolté une fois par an.',
  'Récolté en octobre dans la palmeraie privée, une seule hausse, non chauffé, non filtré finement : il garde son pollen. Goût de réglisse noire et de caramel brulé.',
  'En cuillère le matin, ou avec le fromage de brebis frais.',
  'Tozeur','Nectar de Ziziphus lotus','Récolte unique',7000,18500,39000,68000,'produits/miel-sedr.jpg',NULL,500,1095,NULL,'Récolte 2025',1,1,4.90,10,3,'publie'),
(22,1,8,'Coffret "Les trois bottes"','علبة الهدية','coffret-trois-bottes',
  'Trois calibres de Deglet Nour, une cuiller de bois et le carnet de la palmeraie.',
  'Une botte de chaque calibre (23/25, 25/27 et Bio Behamet), un flacon de sirop de dattes 250 g, une cuiller tournée à Jebeldiour et un carnet de 24 pages qui raconte les vingt-deux producteurs. Écrin en carton de palmier tressé, sans plastique.',
  'Se garde trois mois fermé, à l''ombre.',
  'Tozeur','Dattes et terre cuite','Assemblage manuel',19000,38000,89000,89000,'produits/coffret-trois-bottes.jpg',NULL,2400,90,NULL,'Coffret cadeau',1,1,5.00,5,1,'publie');

-- ---------------------------------------------------------------- Formats
INSERT INTO `tz_variante`
 (`id`,`produit_id`,`sku`,`nom`,`details`,`prix`,`prix_barre`,`prix_achat`,`stock_quantite`,`stock_seuil`,`poids_grammes`,`emplacement`,`position`,`visible`) VALUES
-- Dattes
(1,1,'EJD-BOT-1K','1 kg — botte de 23/25','Plateau de palmier',12500,0,5200,184,20,1000,'Nefzua / A1',1,1),
(2,1,'EJD-BOT-2K','2 kg — deux bottes','Plateau double',24000,26500,10400,96,15,2000,'Nefzua / A1',2,1),
(3,1,'EJD-BOT-5K','5 kg — carton de palme','Idéal famille ou bureau',58000,62000,26000,41,10,5000,'Nefzua / A2',3,1),
(4,2,'EJD-CHI-1K','1 kg — calibre 25/27','Dénoyautage sur option',16900,0,6400,120,15,1000,'Tozeur / B1',1,1),
(5,2,'EJD-CHI-2K','2 kg — calibre 25/27',NULL,32000,34000,12800,58,10,2000,'Tozeur / B1',2,1),
(6,2,'EJD-CHI-5K','5 kg — dénoyautée',"Sans noyau, prête à servir",72000,78000,32000,17,8,5000,'Tozeur / B2',3,1),
(7,3,'EJD-BIO-500','500 g — bio certifié',NULL,8200,0,3400,210,25,500,'Behamet / C1',1,1),
(8,3,'EJD-BIO-2K','2 kg — bio certifié',NULL,31000,0,13600,34,10,2000,'Behamet / C1',2,1),
(9,4,'EJD-BAR-1K','1 kg — Baroud séchée',NULL,9800,0,4300,260,20,1000,'Remada / D1',1,1),
(10,4,'EJD-BAR-2K','2 kg — Baroud séchée',NULL,18600,19800,8600,72,12,2000,'Remada / D1',2,1),
(11,4,'EJD-BAR-5K','5 kg — sachage vrac',NULL,26500,0,21500,22,8,5000,'Remada / D2',3,1),
(12,5,'EJD-FOU-500','500 g — amande fleur d''oranger',NULL,11200,0,5600,142,20,500,'Atelier / E1',1,1),
(13,5,'EJD-FOU-1K','1 kg — boîte de confiserie',NULL,21800,23500,11200,46,10,1000,'Atelier / E1',2,1),
(14,6,'EJD-RUB-500','500 g — flacon verre',NULL,9500,0,4600,98,15,500,'Atelier / E2',1,1),
(15,6,'EJD-RUB-1K','1 kg — flacon famille',NULL,17200,0,9200,41,10,1000,'Atelier / E2',2,1),
(16,7,'EJD-PAT-1K','1 kg — pain sous vide',"Pour le kaak et le makrout",7400,0,5400,203,25,1000,'Atelier / E3',1,1),
(17,7,'EJD-PAT-5K','5 kg — professionnel',NULL,26800,0,27000,19,5,5000,'Atelier / E3',2,1),
-- Poterie
(18,8,'SBT-JAR-2L','Jarre 2 litres','Hauteur 28 cm',68000,0,32000,14,3,4200,'Atelier / F1',1,1),
(19,8,'SBT-JAR-4L','Jarre 4 litres','Hauteur 36 cm, base 20 cm',98000,0,44000,6,3,6100,'Atelier / F1',2,1),
(20,9,'SBT-PLA-40','Plat ovale 40 cm','Gravé palmes',42000,0,19000,11,3,1800,'Atelier / F2',1,1),
(21,10,'SBT-THE-12','Théière 1,2 L','Anse osier',55000,0,24000,9,3,900,'Atelier / F2',1,1),
(22,11,'SBT-SER-06','Service 6 verres + plateau','13 pièces',128000,142000,41000,4,2,2600,'Atelier / F3',1,1),
-- Tissage
(23,12,'TTC-TAP-1623','160 x 230 cm','42 000 nœuds, 3,6 kg',390000,0,198000,2,1,3400,'Galerie / G1',1,1),
(24,12,'TTC-TAP-2030','200 x 300 cm','Sur commande, sept mois',620000,0,318000,1,1,5100,'Galerie / G1',2,1),
(25,13,'TTC-COU-45','Coussin 45 x 45 sans garniture',NULL,38000,0,15000,34,8,600,'Galerie / G2',1,1),
(26,13,'TTC-COU-45G','Coussin 45 x 45 garni plumes',NULL,52000,0,21000,18,6,900,'Galerie / G2',2,1),
(27,13,'TTC-COU-60','Coussin 60 x 60 brodé',NULL,62000,0,25000,7,4,1100,'Galerie / G2',3,1),
(28,14,'TTC-BUR-M','Burnous taille M',"Laine foulée, 1,9 kg",220000,0,118000,4,2,1900,'Galerie / G3',1,1),
(29,14,'TTC-BUR-L','Burnous taille L',NULL,220000,0,118000,3,2,2000,'Galerie / G3',2,1),
(30,14,'TTC-BUR-XL','Burnous taille XL',NULL,245000,0,128000,2,1,2150,'Galerie / G3',3,1),
(31,15,'TTC-QAF-32','Corbeille ø 32 cm','Deux anses',45000,0,17000,26,6,1200,'Galerie / G4',1,1),
-- Huiles
(32,16,'FDB-HUI-30','30 ml — flacon ambré','Compte-gouttes',78000,0,34000,52,10,30,'Labo / H1',1,1),
(33,16,'FDB-HUI-60','60 ml — flacon ambré',NULL,142000,152000,62000,19,6,60,'Labo / H1',2,1),
(34,17,'FDB-SAV-250','Savon noir 250 g','Pâte en pot',8500,0,3100,164,20,250,'Labo / H2',1,1),
(35,17,'FDB-SAV-500','Savon noir 500 g',NULL,14900,0,5900,61,12,500,'Labo / H2',2,1),
(36,18,'FDB-ZHA-500','Eau de zhar 500 ml',NULL,14000,0,5200,88,15,500,'Labo / H3',1,1),
(37,18,'FDB-ZHA-1L','Eau de zhar 1 litre',NULL,23500,0,9800,33,8,1000,'Labo / H3',2,1),
-- Épicerie
(38,19,'CDP-OIL-1L','Huile d''olive 1 litre',NULL,26000,0,14200,126,20,1000,'Comptoir / I1',1,1),
(39,19,'CDP-OIL-5L','Huile d''olive 5 litres',"Bidon métallique",47000,52000,71000,24,8,5000,'Comptoir / I1',2,1),
(40,20,'CDP-HAR-200','Harissa 200 g douce',NULL,7200,0,3900,192,25,200,'Comptoir / I2',1,1),
(41,20,'CDP-HAR-400','Harissa 400 g forte',NULL,12800,0,7600,74,15,400,'Comptoir / I2',2,1),
(42,21,'CDP-MIE-500','Miel sedr 500 g',NULL,39000,0,18500,31,8,500,'Comptoir / I3',1,1),
(43,21,'CDP-MIE-1K','Miel sedr 1 kg',NULL,68000,0,36000,9,4,1000,'Comptoir / I3',2,1),
-- Coffret
(44,22,'EJD-COF-3B','Coffret "Les trois bottes"','Écrin carton de palmier',89000,0,38000,28,10,2400,'Atelier / J1',1,1);

-- ------------------------------- Réceptions de stock d'ouverture (traçable)
INSERT INTO `tz_stock_mouvement` (`variante_id`,`type`,`quantite`,`stock_apres`,`motif`,`enseigne_id`,`cout_unitaire`,`created_at`)
SELECT v.id, 'reception', v.stock_quantite, v.stock_quantite, 'Inventaire d''ouverture de la plateforme', p.enseigne_id, v.prix_achat, DATE_SUB(NOW(), INTERVAL 30 DAY)
  FROM `tz_variante` v JOIN `tz_produit` p ON p.id = v.produit_id;

-- ------------------------------------------------------- Transporteurs
INSERT INTO `tz_transporteur` (`id`,`nom`,`type`,`zones`,`delai_jours`,`tarif_base`,`tarif_kg`,`poids_inclus`,`suivi_url`) VALUES
(1,'Livraison Tozeur et sud (maison)','domicile','["Tozeur","Adham","Remada","Neurès"]',1,5000,0,10000,NULL),
(2,'Olivier Express — territoire','domicile','["*"]',3,8000,900,5000,'https://suivi.exemple.tn/?n={numero}'),
(3,'Point relais Tunis/Sousse','relais','["Tunis","Ariana","Ben Arous","Manouba","Sousse","Monastir","Nabeul"]',3,6000,700,5000,NULL),
(4,'Retrait à l''atelier de Tozeur','retait','["Tozeur"]',0,0,0,50000,NULL);

-- ------------------------------------------------------------ Promotions
INSERT INTO `tz_promo`
 (`id`,`code`,`enseigne_id`,`libelle`,`libelle_ar`,`description`,`type`,`valeur`,`seuil_achat`,`quantite_declencheur`,`nombre_offert`,`plafond_remise`,`cumulable`,`une_fois_par_client`,`premiere_commande_seule`,`quota_total`,`cible_type`,`cible_ids`,`debut_at`,`fin_at`,`visuel`,`afficher_vitrine`,`etat`) VALUES
(1,'TOZEUR10',NULL,'10 % sur les dattes','خصم 10 بالمائة على التمور','Valable sur tout le rayon dattes, cumulable avec les paliers.','pourcentage',10000,0,0,0,30000,1,0,0,500,'categorie','1,2,3','2026-11-01 00:00:00','2026-12-24 23:59:00','promos/tozeur10.jpg',1,'active'),
(2,'BIENVENUE',NULL,'5 DT offerts dès la première commande','خمس دنانير','Remise de bienvenue, une seule fois par client.','montant',5000,50000,0,0,0,0,1,1,0,'global',NULL,'2026-01-01 00:00:00',NULL,NULL,1,'active'),
(3,NULL,NULL,'Trois kilos achetés, un offert','اشترِ ثلاثة واحصل على واحد','Automatique : 3 d''une même référence de 1 kg, la 4e offerte.','n_plus_gratuit',0,0,3,1,0,1,0,0,200,'produit','1,2','2026-11-01 00:00:00','2026-12-31 23:59:00','promos/palier-3-1.jpg',1,'active'),
(4,NULL,NULL,'Port offert dès 150 DT','توصيل مجاني','Livraison offerte sur tout le territoire à partir de 150,000 DT.','port_gratuit',0,150000,0,0,0,1,0,0,0,'global',NULL,'2026-11-01 00:00:00',NULL,NULL,1,'active'),
(5,'COFFRET20',8,'20 % sur les coffrets','خصم على العلب','Sur les coffrets et cadeaux d''affaires.','pourcentage',20000,0,0,0,50000,0,1,0,120,'categorie','8','2026-12-01 00:00:00','2027-01-06 23:59:00',NULL,1,'brouillon'),
(6,'SABRA15',2,'15 % sur la terre cuite','خصم الفخار','Code maison Sabra Terre Cuite.','pourcentage',15000,0,0,0,0,1,0,0,300,'enseigne','2','2026-11-10 00:00:00','2026-11-30 23:59:00','promos/sabra15.jpg',1,'active'),
(7,'PALIER1000',NULL,'5 % dès 1 000 DT d''achat','خصم بالجملة','Pour les achats de groupe et les revendeurs.','palier_pourcentage',5000,1000000,0,0,200000,1,0,0,0,'global',NULL,'2026-11-01 00:00:00',NULL,NULL,0,'active');

-- ------------------------------------------------------ Régie publicitaire
INSERT INTO `tz_banniere`
 (`id`,`enseigne_id`,`emplacement`,`titre`,`sous_titre`,`texte_bouton`,`image`,`url`,`couleur_fond`,`ciblage_pages`,`ciblage_client`,`debut_at`,`fin_at`,`priorite`,`type_offre`,`tarif_jour`,`etat`) VALUES
(1,NULL,'hero','La botte de Deglet Nour arrive','Récolte 2026, stade semia, triée à la main à Nefzua. Trois calibres, cinq kilos en carton de palme.','Voir les dattes','bannieres/hero-recolte.jpg','/catalogue?rayon=dattes',NULL,'/','tous','2026-11-01 00:00:00','2027-01-31 23:59:00',90,'interne',0,'en_ligne'),
(2,4,'hero','Une tonne de fruits pour un litre d''huile','Pressée à froid à Neurès, décantée six semaines. Flacon ambré 30 et 60 ml.','Découvrir l''huile','bannieres/hero-huile.jpg','/boutique/fleur-de-barbarie','#3e5c46','/boutique/fleur-de-barbarie','nouveaux','2026-11-05 00:00:00','2026-12-20 23:59:00',80,'sponsorisee',12000,'en_ligne'),
(3,2,'bandeau','La jarre qui refroidit l''eau sans électricité','Terre de Jebeldiour, 2 et 4 litres.','La poterie de Tozeur','bannieres/bandeau-poterie.jpg','/catalogue?rayon=poterie',NULL,'/catalogue','tous','2026-11-01 00:00:00','2026-12-31 23:59:00',60,'interne',0,'en_ligne'),
(4,3,'bandeau','Un tapis, quatre mois de nœuds','42 000 nœuds, laine cardée main, garance et indigo.','Voir les tapis','bannieres/bandeau-tapis.jpg','/catalogue?rayon=tissage',NULL,'/catalogue','fideles','2026-11-15 00:00:00','2026-12-15 23:59:00',55,'sponsorisee',15000,'programmee'),
(5,NULL,'pied','Coffret "Les trois bottes"','Le cadeau qui raconte la région : trois calibres, du sirop, une cuiller de terre, un carnet.','Réserver un coffret','bannieres/pied-coffret.jpg','/produit/coffret-trois-bottes',NULL,'*','tous','2026-11-20 00:00:00','2027-01-06 23:59:00',40,'interne',0,'brouillon'),
(6,NULL,'cote_catalogue','Livraison offerte dès 150 DT','Et retrait gratuit à l''atelier de Tozeur.',NULL,NULL,'/page/livraison',NULL,'/catalogue,/produit/*','tous','2026-11-01 00:00:00',NULL,30,'interne',0,'en_ligne');

-- --------------------------------------------------------- Pages tranquilles
INSERT INTO `tz_page` (`id`,`slug`,`titre`,`titre_ar`,`corps`,`visuel`,`meta_description`,`position`,`dans_menu`,`etat`) VALUES
(1,'notre-histoire','Cinq maisons, une région','قصتنا','<p>Tozeur vit entre le Chott el Jerid et deux palmeraies. La plateforme réunit cinq maisons qui travaillent dans cette région : dattes de Nefzua, argile de Jebeldiour, laine d''El Hamdania, pépins de figue de barbarie de Neurès et épicerie du comptoir.</p><p>Chaque fiche produit porte le village, le producteur et la campagne. Nous ne vendons rien que nous n''allions chercher sur place.</p>','pages/histoire.jpg','Cinq maisons artisanales de la région de Tozeur : dattes, poterie, tissage, huiles et épicerie.',1,1,'publiee'),
(2,'livraison','Livraison et retrait','التوصيل','<p>Tozeur et le sud : 5,000 DT dès le lendemain. Reste du territoire par Olivier Express : 8,000 DT sous trois jours, puis 0,900 DT par kilo au-delà de 5 kg.</p><p>Point relais Tunis, Sousse, Monastir, Nabeul : 6,000 DT. Retrait à l''atelier de Tozeur : gratuit, du samedi au jeudi de 8 h à 17 h.</p><p>Le port est offert dès 150,000 DT d''achat.</p>',NULL,'Livraison en Tunisie depuis Tozeur : tarifs, délais, points relais et retrait à l''atelier.',2,1,'publiee'),
(3,'dattes-guide','Bien choisir sa datte','دليل اختيار التمر','<p><strong>Deglet Nour</strong> : chair translucide, collante au doigt, peau fine qui se sépare. Elle est mi-cuite (semia) en novembre, sèche en mai.</p><p><strong>Baroud</strong> : ridée, ferme, goût de caramel brulé. Elle se garde deux ans.</p><p><strong>Boufeggous</strong> : petite, très molle, presque confite, rare.</p><p>Signes d''une mauvaise datte : peau détachée sur plus du tiers du fruit, taches de sucre en surface (trop de chaleur), odeur de vinaigre (fermentée).</p>','pages/guide.jpg','Guide : Deglet Nour, Baroud, Boufeggous — comment reconnaître une bonne datte de Tozeur.',3,1,'publiee'),
(4,'cgv','Conditions de vente','شروط البيع','<p>Prix en dinars tunisiens toutes taxes comprises. Rétractation de sept jours sur les pièces non alimentaires, retour à la charge de l''acheteur. Les pièces uniques de poterie et de tissage ne sont ni reprises ni échangées une fois signées et expédiées.</p><p>Les dattes sont garanties conformes au calibre annoncé ; tout triage insuffisant est remboursé sur simple photo.</p>',NULL,'Conditions générales de vente de la plateforme Tozeur Souk.',6,1,'publiee'),
(5,'contact','Nous joindre','اتصل بنا','<p>Atelier et dépôt : route de Nehou, Tozeur 2200.<br>Téléphone : +216 22 118 400 (8 h – 17 h, du samedi au jeudi).<br>Écrire : boutique@tozeursouk.tn</p>',NULL,'Contact de la plateforme Tozeur Souk, atelier à Tozeur.',7,1,'publiee');

-- ----------------------------------------------------- Réglages de plateforme
INSERT INTO `tz_parametre` (`cle`,`valeur`,`type`,`libelle`,`groupe`) VALUES
('site_nom','Tozeur Souk','texte','Nom affiché','general'),
('site_baseline','Artisanat et dattes de la région de Tozeur','texte','Signature','general'),
('site_telephone','+216 22 118 400','texte','Téléphone','general'),
('site_email','boutique@tozeursouk.tn','texte','Email','general'),
('site_adresse','Route de Nehou, Tozeur 2200','texte','Adresse','general'),
('site_devise','TND','texte','Devise','general'),
('monnaie_decimales','3','entier','Décimales affichées','general'),
('couleur_primaire','#7a3b1e','couleur','Couleur principale','design'),
('couleur_accent','#c98b3f','couleur','Couleur accent','design'),
('police_titre','Georgia, "Times New Roman", serif','texte','Police des titres','design'),
('livraison_frais','5000','entier','Frais de livraison par défaut (millimes)','logistique'),
('livraison_gratuite_a','150000','entier','Port offert à partir de (millimes)','logistique'),
('livraison_minimum','25000','entier','Commande minimum (millimes)','logistique'),
('stock_alerte_email','regie@tozeursouk.tn','texte','Alerte stock bas','logistique'),
('stock_reserve_heures','6','entier','Réservation panier en heures','logistique'),
('payment_gpg_enabled','1','booleen','Activer GPG Checkout','paiement'),
('payment_gpg_url','https://www.gpgcheckoutlocal.com/Paiement/Validation_paiement.php','texte','URL de paiement GPG','paiement'),
('payment_gpg_num_site','TZ0001','texte','Numéro de site marchand (gpg_site.num_client)','paiement'),
('payment_gpg_password','','texte','Mot de passe site (servira au md5)','paiement'),
('payment_gpg_language','FR','texte','Langue de la page GPG','paiement'),
('payment_gpg_simule','1','booleen','Mode simulation (aucun débit réel)','paiement'),
('payment_gpg_terminal','','texte','Terminal forcé (vide = routage serveur)','paiement'),
('payment_gpg_vad','','texte','VAD forcée (vide = routage serveur)','paiement'),
('payment_retour_ok','/retour/paiement/ok','texte','Chemin de retour accepté','paiement'),
('payment_retour_ko','/retour/paiement/ko','texte','Chemin de retour refusé','paiement'),
('payment_notification','/retour/paiement/notification','texte','Chemin de notification serveur','paiement'),
('payment_modes_disponibles','["carte_gpg","livraison","virement","especes_point"]','json','Modes de paiement proposés','paiement'),
('tva_standard','19000','entier','Taux normal (millièmes/100000)','comptabilite'),
('tva_reduit','7000','entier','Taux réduit (millièmes/100000)','comptabilite'),
('compte_client','411','texte','Compte clients','comptabilite'),
('compte_fournisseur','401','texte','Compte fournisseurs','comptabilite'),
('compte_tva_collectee','44571','texte','TVA collectée','comptabilite'),
('compte_tva_facture','44566','texte','TVA déductible sur factures','comptabilite'),
('compte_ventes','7111','texte','Ventes de marchandises','comptabilite'),
('compte_transport','6161','texte','Frais de transport','comptabilite'),
('compte_banque','5121','texte','Banque','comptabilite'),
('compte_caisse','5311','texte','Caisse','comptabilite'),
('compte_remboursements','4718','texte','Remboursements à effectuer','comptabilite'),
('facture_prefixe','FS','texte','Préfixe de facture','comptabilite'),
('facture_numerotation','1','entier','Prochain numéro de facture','comptabilite'),
('avoir_prefixe','AV','texte','Préfixe d''avoir','comptabilite'),
('matricule_fiscal','1234567/A/M/000','texte','Matricule fiscal','comptabilite'),
('patente_plateforme','1234567','texte','Patente','comptabilite'),
('nb_produits_par_page','12','entier','Produits par page (vitrine)','design'),
('avis_moderation','1','booleen','Modérer les avis avant publication','general'),
('relance_panier_jours','2','entier','Relance panier abandonné après (jours)','marketing'),
('meta_titre','Tozeur Souk — Dattes et artisanat de Tozeur','texte','Titre par défaut','seo'),
('meta_description','Cinq maisons artisanales de la région de Tozeur : dattes Deglet Nour, poterie de Jebeldiour, tapis noués main, huile de figue de barbarie. Livraison dans toute la Tunisie.','texte','Description par défaut','seo'),
('afficher_detail_erreur','1','booleen','Afficher le détail des erreurs (à couper en production)','general');

-- --------------------------------------------------------------- Comptabilité
INSERT INTO `tz_journal` (`id`,`code`,`libelle`,`nature`) VALUES
(1,'VE','Ventes de marchandises','ventes'),
(2,'AC','Achats','achats'),
(3,'BN','Banque','banque'),
(4,'CA','Caisse','caisse'),
(5,'OD','Opérations diverses','od'),
(6,'ET','Encaissements sur les débits','etre'),
(7,'RS','Remboursements','od');

-- ------------------------------------------------------------ Équipe
INSERT INTO `tz_utilisateur` (`id`,`login`,`nom`,`email`,`mot_de_passe`,`role`,`etat`) VALUES
(1,'admin','Youssef Ben Salha','admin@tozeursouk.tn','$2y$11$DWy2LHA.S8gvuQiYSvXO/ublPeP8G6DOicwQosD5jUtrF7gikyNVa','super','actif'),
(2,'comptable','Nadia Khalfallah','compta@tozeursouk.tn','$2y$11$ojUMOyvq//TSKIe4/NJ2uez84i.2xY7X.Id6USF43M7ndcjbHcbXa','comptable','actif'),
(3,'logistique','Ahmed Trabelsi','souk@tozeursouk.tn','$2y$11$Ow9u1vlXaFegajmJw8LzGeTSKKnIB42HTg78IPZ8mSVsJPc622lzu','logistique','actif'),
(4,'sabra','Sabra Guizani','atelier@sabra-terre.tn','$2y$11$7GkIQgNgtHIl1Iivc8ZobOLqRyZkNeWEGIZAzbW9xrLdgk5PxpoJq','vendeur','actif');
UPDATE `tz_utilisateur` SET `enseigne_id` = 2 WHERE `id` = 4;
UPDATE `tz_utilisateur` SET `password_changed_at` = NOW() WHERE `id` IN (1,2,3,4);

-- ------------------------------------------------------------- Clients
INSERT INTO `tz_client`
 (`id`,`prenom`,`nom`,`email`,`telephone`,`mot_de_passe`,`adresse_l1`,`ville`,`delegation`,`code_postal`,`points_fidelite`,`newsletter`,`apport`,`nb_commandes`,`total_engage`,`last_login`) VALUES
(1,'Amira','Bouazizi','amira.bouazizi@exemple.tn','+216 24 718 220','$2y$11$OZCyknBGOsJcU.g0p9UlBu19PFFTG.VPP4uOXGyqC/9aXqITUhNaa','Cité Ennour, immeuble 4','Tunis','Tunis','1002',120,1,'Instagram',0,0,NULL),
(2,'Karim','Chaabane','karim.chaabane@exemple.tn','+216 98 340 117',NULL,'Avenue de la Liberté 21','Sousse','Sousse','4000',0,0,'Bouche à oreille',0,0,NULL),
(3,'Société Dar El Jeld','Dar El Jeld','achat@darjel.example.tn','+216 71 845 600',NULL,'Rue du Pacha, Medina','Tunis','Tunis','1006',0,1,'Salon des métiers',0,0,NULL);

INSERT INTO `tz_adresse` (`client_id`,`libelle`,`prenom`,`nom`,`telephone`,`ligne1`,`ville`,`delegation`,`code_postal`,`par_defaut`) VALUES
(1,'Domicile','Amira','Bouazizi','+216 24 718 220','Cité Ennour, immeuble 4, apt 9','Tunis','Tunis','1002',1),
(1,'Bureau','Amira','Bouazizi','+216 71 240 555','Les Berges du Lac 2, tour C','Tunis','La Marsa','2036',0),
(2,'Domicile','Karim','Chaabane','+216 98 340 117','Avenue de la Liberté 21','Sousse','Sousse','4000',1);

-- ------------------------------------------------- Trois commandes de démonstration
INSERT INTO `tz_commande`
 (`id`,`reference`,`client_id`,`email`,`enseigne_ids`,`statut`,`type_livraison`,`transporteur_id`,`suivi_numero`,`adresse_destinataire`,`sous_total`,`remise_montant`,`livraison_frais`,`total_ttc`,`total_ht`,`total_tva`,`cout_achat`,`code_promo`,`mode_paiement`,`statut_paiement`,`validee_le`,`expediee_le`,`created_at`) VALUES
(1,'TZ-2026-000101',1,'amira.bouazizi@exemple.tn','1,2','livree','domicile',2,'OLV-771203',
 '{"prenom":"Amira","nom":"Bouazizi","telephone":"+216 24 718 220","ligne1":"Cité Ennour, immeuble 4, apt 9","ville":"Tunis","code_postal":"1002"}',
 102400,2500,5000,104900,92383,12517,45600,'TOZEUR10','carte_gpg','accorde',
 DATE_SUB(NOW(), INTERVAL 12 DAY), DATE_SUB(NOW(), INTERVAL 10 DAY), DATE_SUB(NOW(), INTERVAL 12 DAY)),
(2,'TZ-2026-000102',3,'achat@darjel.example.tn','1,3,4','preparation','relais',3,NULL,
 '{"prenom":"Société Dar El Jeld","telephone":"+216 71 845 600","ligne1":"Rue du Pacha, Medina","ville":"Tunis","code_postal":"1006"}',
 631000,17800,8000,621200,522017,99183,266000,'PALIER1000','virement','en_attente',
 DATE_SUB(NOW(), INTERVAL 2 DAY), NULL, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(3,'TZ-2026-000103',2,'karim.chaabane@exemple.tn','1','en_attente','retait',4,NULL,
 '{"prenom":"Karim","nom":"Chaabane","telephone":"+216 98 340 117","ligne1":"Avenue de la Liberté 21","ville":"Sousse","code_postal":"4000"}',
 24000,0,0,24000,22430,1570,10400,NULL,'livraison','aucun',
 NULL, NULL, DATE_SUB(NOW(), INTERVAL 20 HOUR));

INSERT INTO `tz_commande_ligne`
 (`commande_id`,`enseigne_id`,`produit_id`,`variante_id`,`designation`,`sku`,`quantite`,`prix_unitaire`,`remise_ligne`,`tva_taux`,`total_ttc`,`total_ht`,`total_tva`,`cout_achat`) VALUES
(1,1,1,1,'Deglet Nour mi-cuite en bottes — 1 kg','EJD-BOT-1K',2,12500,2500,7000,22500,21028,1472,10400),
(1,1,5,12,'Dattes fourrées amande et fleur d''oranger — 500 g','EJD-FOU-500',2,11200,0,7000,22400,20935,1465,11200),
(1,2,10,21,'Théière traditionnelle de Tozeur','SBT-THE-12',1,55000,0,19000,55000,46218,8782,24000),
(2,1,22,44,'Coffret "Les trois bottes"','EJD-COF-3B',3,89000,17800,19000,249200,209412,39788,114000),
(2,3,13,26,'Coussin brodé main 45 x 45 garni','TTC-COU-45G',4,52000,0,19000,208000,174790,33210,84000),
(2,4,16,32,'Huile de pépins de figue de barbarie 30 ml','FDB-HUI-30',2,78000,0,19000,156000,131092,24908,68000),
(3,1,1,2,'Deglet Nour mi-cuite en bottes — 2 kg','EJD-BOT-2K',1,24000,0,7000,24000,22430,1570,10400);

-- Les trois commandes ci-dessus ont consommé le stock : on le retrace.
INSERT INTO `tz_stock_mouvement` (`variante_id`,`type`,`quantite`,`stock_apres`,`motif`,`commande_id`,`enseigne_id`,`created_at`) VALUES
(1,'vente',-2,182,'Commande TZ-2026-000101',1,1,DATE_SUB(NOW(), INTERVAL 12 DAY)),
(12,'vente',-2,140,'Commande TZ-2026-000101',1,1,DATE_SUB(NOW(), INTERVAL 12 DAY)),
(21,'vente',-1,8,'Commande TZ-2026-000101',1,2,DATE_SUB(NOW(), INTERVAL 12 DAY)),
(44,'vente',-3,25,'Commande TZ-2026-000102',2,1,DATE_SUB(NOW(), INTERVAL 2 DAY)),
(26,'vente',-4,14,'Commande TZ-2026-000102',2,3,DATE_SUB(NOW(), INTERVAL 2 DAY)),
(32,'vente',-2,50,'Commande TZ-2026-000102',2,4,DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2,'vente',-1,95,'Commande TZ-2026-000103',3,1,DATE_SUB(NOW(), INTERVAL 20 HOUR));

-- Application réelle du mouvement au stock : sans ça, stock_apres et stock_quantite divergent.
UPDATE `tz_variante` v
   SET v.`stock_quantite` = v.`stock_quantite`
     + (SELECT COALESCE(SUM(s.`quantite`), 0) FROM `tz_stock_mouvement` s
         WHERE s.`variante_id` = v.`id` AND s.`type` = 'vente');

-- Un panier abandonné, pour la relance marketing.
INSERT INTO `tz_session_panier` (`token`,`client_id`,`contenu`,`email`,`articles`,`montant`,`relance_envoyee`) VALUES
(MD5(RAND()),1,'[{"variante_id":9,"qte":2}]','amira.bouazizi@exemple.tn',2,19600,0);

INSERT INTO `tz_paiement`
 (`commande_id`,`fournisseur`,`order_id`,`num_site`,`montant`,`devise`,`statut`,`code_autorisation`,`ref_checkout`,`id_transaction_gpg`,`dernier_trans_status`,`session_marchande`,`carte_type`,`carte_fin`,`initie_le`,`solde_le`) VALUES
(1,'sandbox','TZ-2026-000101','TZ0001',104900,'TND','accorde','418207','PAY-0000000418','TRX-0000009921','00','S-7712','Visa','4921',DATE_SUB(NOW(), INTERVAL 12 DAY),DATE_SUB(NOW(), INTERVAL 12 DAY)),
(2,'sandbox','TZ-2026-000102','TZ0001',621200,'TND','initialise',NULL,NULL,NULL,NULL,NULL,NULL,NULL,DATE_SUB(NOW(), INTERVAL 2 DAY),NULL),
(3,'sandbox','TZ-2026-000103','TZ0001',24000,'TND','initialise',NULL,NULL,NULL,NULL,NULL,NULL,NULL,DATE_SUB(NOW(), INTERVAL 20 HOUR),NULL);

-- -------------------------------------------------- Écritures comptables
-- Chaque pièce est en partie double : total débit = total crédit (104 900 et 621 200).
INSERT INTO `tz_ecriture`
 (`journal_id`,`date_ecriture`,`piece`,`compte`,`intitule`,`debit`,`credit`,`counterpartie`,`commande_id`,`enseigne_id`,`tva_taux`,`created_at`) VALUES
-- Commande 1 : ventes 88,181 HT + port 4,202 HT, TVA 11,719 + 0,798, 104,900 TTC au débit du client
(1,DATE_SUB(CURDATE(), INTERVAL 12 DAY),'FS-2026-000101','411','Amira Bouazizi — TZ-2026-000101',104900,0,'Amira Bouazizi',1,1,NULL,DATE_SUB(NOW(), INTERVAL 12 DAY)),
(1,DATE_SUB(CURDATE(), INTERVAL 12 DAY),'FS-2026-000101','7111','Ventes de marchandises',0,88181,'Amira Bouazizi',1,1,NULL,DATE_SUB(NOW(), INTERVAL 12 DAY)),
(1,DATE_SUB(CURDATE(), INTERVAL 12 DAY),'FS-2026-000101','44571','TVA collectée',0,11719,'Amira Bouazizi',1,1,NULL,DATE_SUB(NOW(), INTERVAL 12 DAY)),
(1,DATE_SUB(CURDATE(), INTERVAL 12 DAY),'FS-2026-000101','7111','Ventes — port client',0,4202,'Amira Bouazizi',1,NULL,19000,DATE_SUB(NOW(), INTERVAL 12 DAY)),
(1,DATE_SUB(CURDATE(), INTERVAL 12 DAY),'FS-2026-000101','44571','TVA collectée sur port',0,798,'Amira Bouazizi',1,NULL,19000,DATE_SUB(NOW(), INTERVAL 12 DAY)),
-- Encaissement carte GPG : banque débitée, client soldé
(3,DATE_SUB(CURDATE(), INTERVAL 12 DAY),'CB-418207','5121','Encaissement carte GPG — TZ-2026-000101',104900,0,'Banque',1,NULL,NULL,DATE_SUB(NOW(), INTERVAL 12 DAY)),
(3,DATE_SUB(CURDATE(), INTERVAL 12 DAY),'CB-418207','411','Amira Bouazizi — TZ-2026-000101',0,104900,'Banque',1,NULL,NULL,DATE_SUB(NOW(), INTERVAL 12 DAY)),
-- Commande 2 (virement attendu)
(1,DATE_SUB(CURDATE(), INTERVAL 2 DAY),'FS-2026-000102','411','Dar El Jeld — TZ-2026-000102',621200,0,'Société Dar El Jeld',2,1,NULL,DATE_SUB(NOW(), INTERVAL 2 DAY)),
(1,DATE_SUB(CURDATE(), INTERVAL 2 DAY),'FS-2026-000102','7111','Ventes de marchandises',0,515294,'Société Dar El Jeld',2,1,NULL,DATE_SUB(NOW(), INTERVAL 2 DAY)),
(1,DATE_SUB(CURDATE(), INTERVAL 2 DAY),'FS-2026-000102','44571','TVA collectée',0,97906,'Société Dar El Jeld',2,1,NULL,DATE_SUB(NOW(), INTERVAL 2 DAY)),
(1,DATE_SUB(CURDATE(), INTERVAL 2 DAY),'FS-2026-000102','7111','Ventes — port client',0,6723,'Société Dar El Jeld',2,NULL,19000,DATE_SUB(NOW(), INTERVAL 2 DAY)),
(1,DATE_SUB(CURDATE(), INTERVAL 2 DAY),'FS-2026-000102','44571','TVA collectée sur port',0,1277,'Société Dar El Jeld',2,NULL,19000,DATE_SUB(NOW(), INTERVAL 2 DAY));

INSERT INTO `tz_facture`
 (`id`,`numero`,`type`,`commande_id`,`client_id`,`date_emission`,`date_echeance`,`date_paiement`,`total_ht`,`total_tva`,`total_ttc`,`regle`,`statut`,`mode_reglement`,`ecrite`) VALUES
(1,'FS-2026-000101','facture',1,1,DATE_SUB(CURDATE(), INTERVAL 12 DAY),NULL,DATE_SUB(CURDATE(), INTERVAL 12 DAY),92383,12517,104900,104900,'payee','carte_gpg',1),
(2,'FS-2026-000102','facture',2,3,DATE_SUB(CURDATE(), INTERVAL 2 DAY),DATE_ADD(CURDATE(), INTERVAL 13 DAY),NULL,522017,99183,621200,0,'emise','virement',1);

-- prix_unitaire et remise sont saisis TTC, comme dans tz_commande_ligne ; total_ht se déduit du taux.
INSERT INTO `tz_facture_ligne` (`facture_id`,`position`,`designation`,`quantite`,`prix_unitaire`,`remise`,`tva_taux`,`total_ht`,`total_tva`,`total_ttc`) VALUES
(1,1,'Deglet Nour mi-cuite en bottes — 1 kg',2,12500,2500,7000,21028,1472,22500),
(1,2,'Dattes fourrées amande — 500 g',2,11200,0,7000,20935,1465,22400),
(1,3,'Théière traditionnelle de Tozeur',1,55000,0,19000,46218,8782,55000),
(1,4,'Frais de livraison',1,5000,0,19000,4202,798,5000),
(2,1,'Coffret "Les trois bottes"',3,89000,17800,19000,209412,39788,249200),
(2,2,'Coussin brodé main 45 x 45 garni',4,52000,0,19000,174790,33210,208000),
(2,3,'Huile de pépins de figue de barbarie 30 ml',2,78000,0,19000,131092,24908,156000),
(2,4,'Frais de livraison',1,8000,0,19000,6723,1277,8000);

INSERT INTO `tz_encaissement` (`facture_id`,`date_recue`,`montant`,`mode`,`reference`,`banque`) VALUES
(1,DATE_SUB(CURDATE(), INTERVAL 12 DAY),104900,'carte_gpg','CB-418207','Banque de Tunisie — 30 0041 8207 0001');

-- Promos consommées : l'écran « promotions » doit montrer un usage réel.
INSERT INTO `tz_promo_utilisation` (`promo_id`,`commande_id`,`client_id`,`montant_applique`) VALUES
(1,1,1,2500),
(7,2,3,17800);
UPDATE `tz_promo` SET `quota_utilise` = 1 WHERE `id` IN (1,7);

-- Totaux clients recalculés comme le ferait Client::recalculerTotaux().
UPDATE `tz_client` cl SET
  cl.`nb_commandes` = (SELECT COUNT(*) FROM tz_commande c
                        WHERE c.client_id = cl.id
                          AND c.statut IN ('payee','preparation','controle_qualite','expediee','livree')),
  cl.`total_engage` = (SELECT COALESCE(SUM(c.total_ttc), 0) FROM tz_commande c
                        WHERE c.client_id = cl.id
                          AND c.statut IN ('payee','preparation','controle_qualite','expediee','livree'));

-- ------------------------------------------------------------------- Avis
INSERT INTO `tz_avis` (`produit_id`,`client_id`,`commande_id`,`auteur`,`note`,`titre`,`texte`,`statut`,`reponse`,`achat_verifie`,`created_at`) VALUES
(1,1,1,'Amira B.',5,'La vraie botte de novembre','Exactement ce que j''avais à Sfax chez ma grand-mère. Peau fine, chair qui colle.','publie','Merci Amira — la prochaine botte part le 12 novembre.',1,DATE_SUB(NOW(), INTERVAL 8 DAY)),
(10,1,1,'Amira B.',4,'Théière superbe, un peu petite','La contenance annoncée de 1,2 L est plutôt 1 L utile. Le travail de l''anse est magnifique.','publie',NULL,1,DATE_SUB(NOW(), INTERVAL 8 DAY)),
(16,3,2,'Dar El Jeld',5,'Huile très stable en crème','Nous l''utilisons en soin après hammam, plus de tiraillement.','en_attente',NULL,1,DATE_SUB(NOW(), INTERVAL 1 DAY)),
(12,NULL,NULL,'Mohamed K.',5,'Tapis reçu pour notre riad','Les couleurs de la garance sont profondes, le dos est propre.','publie',NULL,0,DATE_SUB(NOW(), INTERVAL 22 DAY));

-- Note moyenne recalculée depuis les seuls avis publiés, comme le fait Avis::recalculerNote().
UPDATE `tz_produit` p
   SET p.`note_moyenne` = COALESCE((SELECT ROUND(AVG(a.`note`), 2) FROM `tz_avis` a
                                     WHERE a.`produit_id` = p.`id` AND a.`statut` = 'publie'), 0),
       p.`note_compte`  = (SELECT COUNT(*) FROM `tz_avis` a
                            WHERE a.`produit_id` = p.`id` AND a.`statut` = 'publie');

-- Un coup d'œil de la régie, pour que les statistiques ne soient pas vides.
INSERT INTO `tz_banniere_evenement` (`banniere_id`,`type`,`page`,`created_at`) VALUES
(1,'vue','/',DATE_SUB(NOW(), INTERVAL 1 DAY)),
(1,'vue','/',DATE_SUB(NOW(), INTERVAL 2 DAY)),
(1,'clic','/',DATE_SUB(NOW(), INTERVAL 2 DAY)),
(3,'vue','/catalogue',DATE_SUB(NOW(), INTERVAL 1 DAY)),
(3,'clic','/catalogue',DATE_SUB(NOW(), INTERVAL 3 DAY));
UPDATE `tz_banniere` SET `impressions` = 4180, `clics` = 214 WHERE `id` = 1;
UPDATE `tz_banniere` SET `impressions` = 1662, `clics` = 88  WHERE `id` = 3;
UPDATE `tz_banniere` SET `impressions` = 340,  `clics` = 19  WHERE `id` = 6;

INSERT INTO `tz_action_journal` (`utilisateur_id`,`acteur`,`action`,`cible_type`,`cible_id`,`details`,`ip`) VALUES
(1,'admin','ouverture_plateforme','parametre',NULL,'{"maisons":5,"produits":22}','127.0.0.1'),
(NULL,'systeme','stock_bas','variante',24,'{"sku":"TTC-TAP-2030","restant":1}','127.0.0.1');

SET FOREIGN_KEY_CHECKS = 1;

-- Contrôle rapide après import :
-- SELECT (SELECT COUNT(*) FROM tz_produit) produits,
--        (SELECT COUNT(*) FROM tz_variante) variantes,
--        (SELECT COUNT(*) FROM tz_commande) commandes,
--        (SELECT COUNT(*) FROM tz_ecriture) ecritures;
