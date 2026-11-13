<?php
/**
 * Vitrine : les maisons, les rayons, les fiches produits et le panier.
 * Le tunnel de commande et le paiement sont dans les actions commande / paiement.
 */
class Front
{
    /* ------------------------------------------------------------ Accueil */

    public function accueilAction(array $params = []): void
    {
        $maisonId = ParametresApp::enseigneCourante();
        $demandes = Produit::rechercher(['tri' => 'populaire', 'par_page' => 6, 'page' => 1]);
        View::page('store/accueil', [
            'enseignes'  => Enseigne::actives(true),
            'vedettes'   => $this->attacherFormats(Produit::vedettes(8, $maisonId)),
            'nouveautes' => $this->attacherFormats(Produit::nouveautes(4)),
            'meilleures' => $this->attacherFormats($demandes['lignes']),
            'rayons'     => Categorie::arbre(),
            'promos'     => Promo::vitrine(),
        ]);
    }

    /* ------------------------------------------------------------ Maisons */

    public function boutiquesAction(array $params = []): void
    {
        View::page('store/boutiques', [
            'enseignes' => Enseigne::actives(true),
            'rayons'    => Categorie::arbre(),
        ]);
    }

    public function boutiqueAction(array $params): void
    {
        $maison = Enseigne::parSlug((string) $params['slug']);
        if ($maison === null) {
            Http::code(404);
            Erreur::page(404, 'Cette maison n’existe plus, ou elle a fermé sa vitrine.');
            return;
        }
        ParametresApp::definir('enseigne_id', (int) $maison['id']);
        $filtres = $this->filtres() + ['enseigne_id' => (int) $maison['id']];
        $filtres['maison'] = '';
        View::page('store/boutique', [
            'maison'     => $maison,
            'resultat'   => $this->vitrineListe($filtres),
            'filtres'    => $filtres,
            'promos'     => Promo::vitrine(),
        ]);
    }

    /* --------------------------------------------------------- Catalogue */

    public function catalogueAction(array $params = []): void
    {
        $filtres = $this->filtres();
        $maisonId = ParametresApp::enseigneCourante();
        if ($maisonId !== null && $filtres['maison'] === '' && $filtres['rayon'] === '') {
            $filtres['enseigne_id'] = $maisonId;
        }
        $rayon = $filtres['rayon'] === '' ? null : Categorie::parSlug($filtres['rayon']);
        View::page('store/catalogue', [
            'resultat' => $this->vitrineListe($filtres),
            'filtres'  => $filtres,
            'rayon'    => $rayon,
            'maison'   => $filtres['maison'] === '' ? null : Enseigne::parSlug($filtres['maison']),
        ]);
    }

    public function rechercheAction(array $params = []): void
    {
        $question = (string) Http::get('q', '');
        if ($question === '') {
            Http::redirection(Url::to('/catalogue'));
        }
        $filtres = $this->filtres();
        View::page('store/catalogue', [
            'resultat' => $this->vitrineListe($filtres),
            'filtres'  => $filtres,
            'rayon'    => null,
            'maison'   => null,
            'recherche' => $question,
        ]);
    }

    public function promotionsAction(array $params = []): void
    {
        $filtres = $this->filtres();
        $filtres['en_promo'] = 1;
        if (($filtres['tri'] ?? '') === 'pertinence') {
            $filtres['tri'] = 'nouveautes';
        }
        View::page('store/promotions', [
            'resultat' => $this->vitrineListe($filtres),
            'filtres'  => $filtres,
            'promos'   => Promo::vitrine(),
        ]);
    }

    /* -------------------------------------------------------------- Fiche */

    public function produitAction(array $params): void
    {
        $slug = (string) $params['slug'];
        $produit = Produit::fiche($slug);
        if ($produit === null || $produit['etat'] !== 'publie') {
            Http::code(404);
            Erreur::page(404, 'Cette pièce n’est plus proposée à la vente.');
            return;
        }
        Produit::incrementerVues((int) $produit['id']);
        $panier = GestionPanier::duSession();
        $variantes = $produit['variantes'];
        $selectionnee = $this->choisirVariante($variantes, Http::entier('variante'));
        View::page('store/produit', [
            'produit'    => $produit,
            'variantes'  => $variantes,
            'variante'   => $selectionnee,
            'echelle'    => Avis::echelle((int) $produit['id']),
            'vendus'     => CommandeLigne::vendusParProduit((int) $produit['id']),
            'transporteurs' => Transporteur::tous(),
            'panier'     => $panier,
        ]);
    }

    public function pageAction(array $params): void
    {
        $page = Page::parSlug((string) $params['slug']);
        if ($page === null) {
            Http::code(404);
            Erreur::page(404, 'Cette page a été retirée du site.');
            return;
        }
        View::page('store/page', ['page' => $page]);
    }

    public function avisEnregistrerAction(array $params): void
    {
        Csrf::exiger();
        $produit = Produit::fiche((string) $params['slug']);
        if ($produit === null) {
            Http::code(404);
            Erreur::page(404, 'Produit introuvable.');
            return;
        }
        $note = Http::entier('note', 0);
        $texte = (string) Http::post('texte', '');
        if ($note < 1 || $note > 5) {
            Session::flash('erreur', 'Choisissez une note de 1 à 5 étoiles.');
            $this->revenir('/produit/' . $produit['slug']);
        }
        if (mb_strlen($texte) < 10) {
            Session::flash('erreur', 'Quelques mots de plus : dix caractères au minimum.');
            $this->revenir('/produit/' . $produit['slug']);
        }
        $client = Auth::client();
        if ($client === null && (string) Http::post('auteur', '') === '') {
            Session::flash('erreur', 'Indiquez votre prénom, ou connectez-vous.');
            $this->revenir('/produit/' . $produit['slug']);
        }
        Avis::enregistrer([
            'produit_id'  => (int) $produit['id'],
            'client_id'   => $client === null ? null : (int) $client['id'],
            'auteur'      => $client === null ? (string) Http::post('auteur', '') : null,
            'note'        => $note,
            'titre'       => (string) Http::post('titre', ''),
            'texte'       => $texte,
        ]);
        Session::flash('succes', 'Merci ! Votre avis sera publié après lecture.');
        $this->revenir('/produit/' . $produit['slug']);
    }

    /* -------------------------------------------------------------- Panier */

    public function panierAction(array $params = []): void
    {
        View::page('store/panier', [
            'panier'        => GestionPanier::duSession(),
            'transporteurs' => Transporteur::pour((string) (Auth::client()['ville'] ?? '')),
            'promos'        => Promo::vitrine(),
        ]);
    }

    public function panierAjouterAction(array $params = []): void
    {
        Csrf::exiger();
        $varianteId = Http::entier('variante_id');
        $quantite = max(1, min(99, Http::entier('quantite', 1)));
        $variante = $varianteId > 0 ? Variante::parId($varianteId) : null;
        if ($variante === null || $variante['produit_etat'] !== 'publie' || (int) $variante['visible'] !== 1) {
            $this->refusPanier('Cette référence n’est plus vendue.');
        }
        Panier::ajouter($varianteId, $quantite);
        $this->reponsePanier('Ajouté à votre panier.');
    }

    public function panierModifierAction(array $params = []): void
    {
        Csrf::exiger();
        $saisies = Http::postTableau('quantites');
        foreach ($saisies as $varianteId => $quantite) {
            Panier::fixerQuantite((int) $varianteId, max(0, min(99, (int) $quantite)));
        }
        $this->reponsePanier('Panier mis à jour.');
    }

    public function panierSupprimerAction(array $params = []): void
    {
        Csrf::exiger();
        Panier::supprimer(Http::entier('variante_id'));
        $this->reponsePanier('Article retiré du panier.');
    }

    public function panierViderAction(array $params = []): void
    {
        Csrf::exiger();
        Panier::vider();
        $this->reponsePanier('Votre panier est vide.');
    }

    public function panierCodeAction(array $params = []): void
    {
        Csrf::exiger();
        $code = mb_strtoupper((string) Http::post('code', ''));
        if ($code === '' || Promo::parCode($code) === null) {
            $this->refusPanier('Ce code ne correspond à aucune offre en cours.');
        }
        Panier::appliquerCode($code);
        $this->reponsePanier('Code ' . $code . ' enregistré.');
    }

    public function panierEmballageAction(array $params = []): void
    {
        Csrf::exiger();
        $cadeau = Http::post('cadeau') !== null && Http::post('cadeau') !== '';
        Panier::choisirEmballage($cadeau, (string) Http::post('message', ''));
        $this->reponsePanier('Préparation cadeau mise à jour.');
    }

    /* ------------------------------------------------------------- Aides */

    /** Filtres de liste lus dans l'URL, prêts pour Produit::rechercher(). */
    private function filtres(): array
    {
        $sortie = [
            'q' => trim((string) Http::get('q', '')),
            'rayon' => trim((string) Http::get('rayon', '')),
            'maison' => trim((string) Http::get('maison', '')),
            'tri' => (string) Http::get('tri', 'pertinence'),
            'prix_min' => trim((string) Http::get('prix_min', '')),
            'prix_max' => trim((string) Http::get('prix_max', '')),
            'savoir_faire' => trim((string) Http::get('savoir_faire', '')),
            'note' => trim((string) Http::get('note', '')),
            'label' => trim((string) Http::get('label', '')),
            'page' => max(1, Http::entier('page', 1)),
        ];
        foreach (['en_stock', 'en_promo', 'nouveaute', 'vedette'] as $case) {
            if (Http::get($case) !== null) {
                $sortie[$case] = 1;
            }
        }
        return $sortie;
    }

    /** Liste de vitrine : facettes + format prêt à ajouter. */
    private function vitrineListe(array $filtres): array
    {
        $resultat = Produit::rechercher($filtres);
        $resultat['lignes'] = $this->attacherFormats($resultat['lignes']);
        return $resultat;
    }

    /**
     * Ajoute « achat_id » aux cartes : l'identifiant du seul format
     * proposable quand il n'y a aucun choix à faire, zéro sinon.
     *
     * @param array<int, array<string, mixed>> $lignes
     * @return array<int, array<string, mixed>>
     */
    private function attacherFormats(array $lignes): array
    {
        $ids = array_values(array_unique(array_map(static fn(array $l): int => (int) $l['id'], $lignes)));
        if ($ids === []) {
            return $lignes;
        }
        $formats = Variante::pourProduits($ids);
        foreach ($lignes as $position => $ligne) {
            $utilisables = array_filter(
                $formats[(int) $ligne['id']] ?? [],
                static fn(array $v): bool => Variante::disponible((int) $v['stock_quantite'], (int) $v['reserve_vente']) > 0
            );
            $lignes[$position]['achat_id'] = count($utilisables) === 1 ? (int) reset($utilisables)['id'] : 0;
        }
        return $lignes;
    }

    /** Format proposé d'office sur la fiche : le premier en stock, sinon le premier. */
    private function choisirVariante(array $variantes, int $idDemande): ?array
    {
        foreach ($variantes as $variante) {
            if ((int) $variante['id'] === $idDemande) {
                return $variante;
            }
        }
        foreach ($variantes as $variante) {
            if (Variante::disponible((int) $variante['stock_quantite'], (int) $variante['reserve_vente']) > 0) {
                return $variante;
            }
        }
        return $variantes[0] ?? null;
    }

    private function estAjax(): bool
    {
        return str_contains(Http::enTete('X-Requested-With'), 'XMLHttpRequest')
            || str_contains(Http::enTete('Accept'), 'application/json');
    }

    /** Réponse d'un geste de panier : JSON pour le JavaScript, redirection sinon. */
    private function reponsePanier(string $message): never
    {
        $sortie = GestionPanier::exposer(GestionPanier::duSession()) + ['ok' => true, 'message' => $message];
        if ($this->estAjax()) {
            Http::json($sortie);
        }
        $this->revenir(Http::post('retour', '/panier'));
    }

    private function refusPanier(string $message): never
    {
        if ($this->estAjax()) {
            Http::json(['ok' => false, 'erreur' => $message], 422);
        }
        Session::flash('erreur', $message);
        $this->revenir(Http::post('retour', '/panier'));
    }

    private function revenir(mixed $chemin): never
    {
        $chemin = (string) $chemin;
        if ($chemin === '' || !str_starts_with($chemin, '/') || str_starts_with($chemin, '//')) {
            $chemin = '/panier';
        }
        Http::redirection(Url::to($chemin));
    }
}
