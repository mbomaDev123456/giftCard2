<?php
/** Accès et tableau de bord de l'administration. */
class Admin
{

    private const STATUTS_COMMANDE = [
        'attente_validation' => 'En attente de validation',
        'en_attente'         => 'En attente',
        'panier_abandonne'   => 'Panier abandonné',
        'payee'              => 'Payée',
        'preparation'        => 'En préparation',
        'controle_qualite'   => 'Contrôle qualité',
        'expediee'           => 'Expédiée',
        'livree'             => 'Livrée',
        'annulee'            => 'Annulée',
        'rembourse_partiel'  => 'Remboursée (partiel)',
        'remboursee'         => 'Remboursée',
        'litige'             => 'Litige',
    ];
    private const ETATS_PRODUIT = ['brouillon' => 'Brouillon', 'publie' => 'Publié', 'archive' => 'Archivé'];
    private const TYPES_MOUVEMENT = [
        'reception' => 'Réception', 'vente' => 'Vente', 'annulation' => 'Annulation',
        'retour' => 'Retour', 'ajustement' => 'Ajustement', 'casse' => 'Casse', 'transfert' => 'Transfert',
    ];

    /** Convertit une saisie en dinars ("12,500") vers des millimes entiers. */
    private static function millimes($valeurTexte): int
    {
        return (int) round(((float) str_replace(',', '.', trim((string) $valeurTexte))) * 1000);
    }

    private static function slugifier(string $texte): string
    {
        $t = iconv('UTF-8', 'ASCII//TRANSLIT', $texte) ?: $texte;
        $t = strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $t));
        return trim($t, '-') . '-' . substr(md5(uniqid('', true)), 0, 5);
    }

    private static function recalculerPrixProduit(int $produitId): void
    {
        $bornes = Db::un(
            'SELECT MIN(prix) AS mini, MAX(prix) AS maxi FROM tz_variante WHERE produit_id = ? AND visible = 1',
            [$produitId]
        );
        Db::modifier('tz_produit', [
            'prix_min' => (int) ($bornes['mini'] ?? 0),
            'prix_max' => (int) ($bornes['maxi'] ?? 0),
        ], 'id = ?', [$produitId]);
    }

    /** Applique un mouvement de stock signé et journalise. */
    private static function mouvementStock(int $varianteId, string $type, int $quantiteSignee, string $motif = '', ?int $commandeId = null, int $coutUnitaire = 0): void
    {
        $variante = Db::un('SELECT stock_quantite FROM tz_variante WHERE id = ?', [$varianteId]);
        if ($variante === null) {
            return;
        }
        $nouveauStock = max(0, (int) $variante['stock_quantite'] + $quantiteSignee);
        Db::modifier('tz_variante', ['stock_quantite' => $nouveauStock], 'id = ?', [$varianteId]);

        $admin = Auth::administrateur();
        Db::inserer('tz_stock_mouvement', [
            'variante_id' => $varianteId, 'type' => $type, 'quantite' => $quantiteSignee,
            'stock_apres' => $nouveauStock, 'motif' => $motif ?: null, 'commande_id' => $commandeId,
            'utilisateur_id' => $admin['id'] ?? null, 'cout_unitaire' => $coutUnitaire,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
    private const STATUTS_PAIEMENT = [
        'aucun'                    => 'Aucun',
        'initialise'               => 'Initialisé',
        'en_attente'               => 'En attente',
        'accorde'                  => 'Accordé',
        'refuse'                   => 'Refusé',
        'rembourse'                => 'Remboursé',
        'partiellement_rembourse'  => 'Partiellement remboursé',
    ];
>>>>>>> 9f8e50a (Mise à jour du projet)
    public function indexAction(array $params = []): void
    {
        if (Auth::administrateur() !== null) {
            Http::redirection(Url::to('/admin/tableau'));
        }

        View::page('admin/connexion', [], 'admin');
    }

    public function connexionAction(array $params = []): void
    {
        if (Auth::administrateur() !== null) {
            Http::redirection(Url::to('/admin/tableau'));
        }

        Csrf::exiger();
        $login = (string) Http::post('login', '');
        $motDePasse = (string) Http::post('mot_de_passe', '');
        if ($login === '' || $motDePasse === '') {
            View::page('admin/connexion', ['erreur' => 'Saisissez votre identifiant et votre mot de passe.'], 'admin');
            return;
        }

        $resultat = Utilisateur::authentifier($login, $motDePasse);
        if (empty($resultat['ok'])) {
            View::page('admin/connexion', ['erreur' => (string) ($resultat['erreur'] ?? 'Connexion impossible.')], 'admin');
            return;
        }

        Auth::connecterAdmin($resultat['utilisateur']);
        Session::flash('succes', 'Connexion réussie. Bienvenue dans l’administration.');
        Http::redirection(Url::to('/admin/tableau'));
    }

    public function deconnexionAction(array $params = []): void
    {
        Auth::deconnecterAdmin();
        Session::flash('succes', 'Vous êtes déconnecté.');
        Http::redirection(Url::to('/admin/connexion'));
    }

    public function tableauAction(array $params = []): void
    {
        Auth::exigerAdmin();

        $debutMois = date('Y-m-01 00:00:00');
        $stats = [
            'commandes_a_traiter' => (int) Db::valeur(
                "SELECT COUNT(*) FROM tz_commande WHERE statut IN ('attente_validation','en_attente')"
            ),
            'produits_publies' => (int) Db::valeur(
                "SELECT COUNT(*) FROM tz_produit WHERE etat = 'publie'"
            ),
            'clients' => (int) Db::valeur('SELECT COUNT(*) FROM tz_client'),
            'ventes_mois' => (int) Db::valeur(
                "SELECT COALESCE(SUM(total_ttc), 0) FROM tz_commande
                  WHERE statut_paiement = 'accorde' AND created_at >= ?",
                [$debutMois]
            ),
        ];
        $commandes = Db::tous(
            'SELECT reference, email, total_ttc, statut, statut_paiement, created_at
               FROM tz_commande ORDER BY created_at DESC, id DESC LIMIT 8'
        );

        View::page('admin/tableau', [
            'admin' => Auth::administrateur(),
            'stats' => $stats,
            'commandes' => $commandes,
        ], 'admin');
    }
<<<<<<< HEAD
=======
    
        /** Liste des commandes, avec filtres et pagination. */
    public function commandesAction(array $params = []): void
    {
        Auth::exigerAdmin();

        $statut = (string) Http::get('statut', '');
        $statutPaiement = (string) Http::get('statut_paiement', '');
        $q = trim((string) Http::get('q', ''));
        $page = max(1, (int) Http::get('page', 1));
        $parPage = 20;

        $conditions = [];
        $args = [];
        if ($statut !== '' && array_key_exists($statut, self::STATUTS_COMMANDE)) {
            $conditions[] = 'statut = ?';
            $args[] = $statut;
        }
        if ($statutPaiement !== '' && array_key_exists($statutPaiement, self::STATUTS_PAIEMENT)) {
            $conditions[] = 'statut_paiement = ?';
            $args[] = $statutPaiement;
        }
        if ($q !== '') {
            $conditions[] = '(reference LIKE ? OR email LIKE ?)';
            $args[] = '%' . $q . '%';
            $args[] = '%' . $q . '%';
        }
        $where = $conditions !== [] ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $total = (int) Db::valeur("SELECT COUNT(*) FROM tz_commande $where", $args);
        $commandes = Db::tous(
            "SELECT id, reference, email, statut, statut_paiement, total_ttc, mode_paiement, created_at
               FROM tz_commande $where
               ORDER BY created_at DESC, id DESC
               LIMIT $parPage OFFSET " . (($page - 1) * $parPage),
            $args
        );

        View::page('admin/commandes', [
            'commandes' => $commandes,
            'total' => $total,
            'page' => $page,
            'nbPages' => (int) max(1, ceil($total / $parPage)),
            'statut' => $statut,
            'statutPaiement' => $statutPaiement,
            'q' => $q,
            'statutsCommande' => self::STATUTS_COMMANDE,
            'statutsPaiement' => self::STATUTS_PAIEMENT,
        ], 'admin');
    }

    /** Détail d'une commande : lignes, paiements, remboursements, marge. */
    public function commandeAction(array $params = []): void
    {
        Auth::exigerAdmin();
        $id = (int) ($params['id'] ?? 0);
        $commande = Db::un('SELECT * FROM tz_commande WHERE id = ?', [$id]);
        if ($commande === null) {
            Session::flash('erreur', 'Commande introuvable.');
            Http::redirection(Url::to('/admin/commandes'));
        }

        $lignes = Db::tous('SELECT * FROM tz_commande_ligne WHERE commande_id = ? ORDER BY id', [$id]);
        $paiements = Db::tous('SELECT * FROM tz_paiement WHERE commande_id = ? ORDER BY initie_le DESC', [$id]);
        $remboursements = Db::tous('SELECT * FROM tz_remboursement WHERE commande_id = ? ORDER BY created_at DESC', [$id]);
        $transporteurs = Db::tous('SELECT id, nom FROM tz_transporteur ORDER BY nom');
        $facture = Db::un('SELECT * FROM tz_facture WHERE commande_id = ? AND type = "facture" LIMIT 1', [$id]);

        View::page('admin/commande', [
            'commande' => $commande,
            'lignes' => $lignes,
            'paiements' => $paiements,
            'remboursements' => $remboursements,
            'transporteurs' => $transporteurs,
            'facture' => $facture,
            'marge' => (int) $commande['total_ht'] - (int) $commande['cout_achat'],
            'statutsCommande' => self::STATUTS_COMMANDE,
        ], 'admin');
    }

    public function commandeStatutAction(array $params = []): void
    {
        Auth::exigerAdmin();
        Csrf::exiger();
        $id = (int) ($params['id'] ?? 0);
        $statut = (string) Http::post('statut', '');

        if (!array_key_exists($statut, self::STATUTS_COMMANDE)) {
            Session::flash('erreur', 'Statut invalide.');
            Http::redirection(Url::to('/admin/commande/' . $id));
        }

        $champs = ['statut' => $statut];
        $horodatage = [
            'payee' => 'validee_le', 'preparation' => 'preparee_le',
            'expediee' => 'expediee_le', 'livree' => 'livree_le', 'annulee' => 'annulee_le',
        ][$statut] ?? null;
        if ($horodatage !== null) {
            $champs[$horodatage] = date('Y-m-d H:i:s');
        }
        Db::modifier('tz_commande', $champs, 'id = ?', [$id]);

        tz_log('Commande', 'changement de statut', [
            'commande_id' => $id, 'nouveau_statut' => $statut,
            'admin' => Auth::administrateur()['login'] ?? null,
        ]);
        Session::flash('succes', 'Statut mis à jour.');
        Http::redirection(Url::to('/admin/commande/' . $id));
    }

    public function commandeTransportAction(array $params = []): void
    {
        Auth::exigerAdmin();
        Csrf::exiger();
        $id = (int) ($params['id'] ?? 0);

        Db::modifier('tz_commande', [
            'transporteur_id' => ((int) Http::post('transporteur_id', 0)) ?: null,
            'suivi_numero' => (string) Http::post('suivi_numero', ''),
        ], 'id = ?', [$id]);

        Session::flash('succes', 'Informations de transport enregistrées.');
        Http::redirection(Url::to('/admin/commande/' . $id));
    }

    public function commandeFactureAction(array $params = []): void
    {
        Auth::exigerAdmin();
        Csrf::exiger();
        $id = (int) ($params['id'] ?? 0);
        $commande = Db::un('SELECT * FROM tz_commande WHERE id = ?', [$id]);
        if ($commande === null) {
            Session::flash('erreur', 'Commande introuvable.');
            Http::redirection(Url::to('/admin/commandes'));
        }

        $existe = (int) Db::valeur(
            'SELECT COUNT(*) FROM tz_facture WHERE commande_id = ? AND type = "facture"',
            [$id]
        );
        if ($existe > 0) {
            Session::flash('erreur', 'Une facture existe déjà pour cette commande.');
            Http::redirection(Url::to('/admin/commande/' . $id));
        }

        $numero = 'F' . date('Y') . '-' . str_pad((string) $id, 6, '0', STR_PAD_LEFT);
        $factureId = Db::inserer('tz_facture', [
            'numero' => $numero,
            'type' => 'facture',
            'commande_id' => $id,
            'client_id' => $commande['client_id'],
            'date_emission' => date('Y-m-d'),
            'total_ht' => $commande['total_ht'],
            'total_tva' => $commande['total_tva'],
            'total_ttc' => $commande['total_ttc'],
            'statut' => $commande['statut_paiement'] === 'accorde' ? 'payee' : 'emise',
            'mode_reglement' => 'carte_gpg',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        foreach (Db::tous('SELECT * FROM tz_commande_ligne WHERE commande_id = ?', [$id]) as $i => $ligne) {
            Db::inserer('tz_facture_ligne', [
                'facture_id' => $factureId,
                'position' => $i,
                'designation' => $ligne['designation'],
                'quantite' => $ligne['quantite'],
                'prix_unitaire' => $ligne['prix_unitaire'],
                'tva_taux' => $ligne['tva_taux'],
                'total_ht' => $ligne['total_ht'],
                'total_tva' => $ligne['total_tva'],
                'total_ttc' => $ligne['total_ttc'],
            ]);
        }

        Session::flash('succes', 'Facture ' . $numero . ' créée.');
        Http::redirection(Url::to('/admin/commande/' . $id));
    }

    public function commandeRembourserAction(array $params = []): void
    {
        Auth::exigerAdmin();
        Csrf::exiger();
        $id = (int) ($params['id'] ?? 0);
        $montant = (int) round(((float) str_replace(',', '.', (string) Http::post('montant', '0'))) * 1000);
        $motif = (string) Http::post('motif', '');

        $commande = Db::un('SELECT total_ttc FROM tz_commande WHERE id = ?', [$id]);
        if ($commande === null || $montant <= 0 || $montant > (int) $commande['total_ttc']) {
            Session::flash('erreur', 'Montant de remboursement invalide.');
            Http::redirection(Url::to('/admin/commande/' . $id));
        }

        $admin = Auth::administrateur();
        Db::inserer('tz_remboursement', [
            'commande_id' => $id,
            'montant' => $montant,
            'motif' => $motif,
            'statut' => 'demande',
            'utilisateur_id' => $admin['id'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $nouveauStatut = $montant === (int) $commande['total_ttc'] ? 'rembourse' : 'partiellement_rembourse';
        Db::modifier('tz_commande', ['statut_paiement' => $nouveauStatut], 'id = ?', [$id]);

        tz_log('Commande', 'demande de remboursement', ['commande_id' => $id, 'montant' => $montant]);
        Session::flash('succes', 'Remboursement enregistré comme "demandé". L’exécution réelle côté GPGCheckout reste à faire (voir note ci-dessous).');
        Http::redirection(Url::to('/admin/commande/' . $id));
    }

}
