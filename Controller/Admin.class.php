<?php
/** Accès et tableau de bord de l'administration. */
class Admin
{
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
}
