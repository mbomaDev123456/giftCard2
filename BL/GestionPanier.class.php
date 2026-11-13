<?php
/**
 * Moteur de prix du panier : disponibilité, remises cumulables ou non,
 * paliers, port offert, répartition par enseigne.
 * Tout est en millimes ; les totaux sont rendus exacts au millime près.
 */
class GestionPanier
{
    /** @return array<int, array<string, mixed>> lignes du panier, enrichies du produit et de la maison */
    public static function chargerLignes(array $items): array
    {
        $ids = array_values(array_unique(array_map(static fn(array $i): int => (int) $i['variante_id'], $items)));
        if ($ids === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $parId = [];
        foreach (Db::tous(
            "SELECT v.id variante_id, v.sku, v.nom variante_nom, v.details, v.prix, v.prix_barre, v.prix_achat,
                    v.stock_quantite, v.stock_seuil, v.reserve_vente, v.poids_grammes, v.visible,
                    p.id produit_id, p.nom produit_nom, p.slug produit_slug, p.image_principale, p.tva_taux,
                    p.etat produit_etat, p.categorie_id, p.enseigne_id, p.peremption_jours,
                    e.nom enseigne_nom, e.slug enseigne_slug, e.couleur enseigne_couleur, e.commission_bpo
               FROM tz_variante v
               JOIN tz_produit p ON p.id = v.produit_id
               JOIN tz_enseigne e ON e.id = p.enseigne_id
              WHERE v.id IN ($placeholders)",
            $ids
        ) as $ligne) {
            $parId[(int) $ligne['variante_id']] = $ligne;
        }
        $lignes = [];
        foreach ($items as $item) {
            $varianteId = (int) $item['variante_id'];
            if (!isset($parId[$varianteId])) {
                continue;
            }
            $ligne = $parId[$varianteId];
            $ligne['quantite_demandee'] = max(1, (int) $item['quantite']);
            $lignes[] = $ligne;
        }
        return $lignes;
    }

    /**
     * @param array $lignes  lignes chargées par chargerLignes()
     * @param array $options client_id, code_promo, transporteur_id, ville, point_relais, date_souhaitee, emballage
     */
    public static function tarifer(array $lignes, array $options = []): array
    {
        $clientId = isset($options['client_id']) ? (int) $options['client_id'] : (int) Auth::idClient();
        $code     = mb_strtoupper(trim((string) ($options['code_promo'] ?? '')));
        $promos   = self::candidates($code, $clientId);
        $avertissements = [];

        // 1. Disponibilité : on plafonne au stock réellement proposable.
        foreach ($lignes as $i => $ligne) {
            $restant = Variante::disponible((int) $ligne['stock_quantite'], (int) $ligne['reserve_vente']);
            if ((int) $ligne['visible'] === 0 || $ligne['produit_etat'] !== 'publie') {
                $avertissements[] = sprintf('« %s » n’est plus vendu : retirez-le du panier.', $ligne['produit_nom']);
                $lignes[$i]['quantite'] = 0;
                continue;
            }
            $demandee = (int) $ligne['quantite_demandee'];
            $lignes[$i]['restant'] = $restant;
            $lignes[$i]['quantite'] = min($demandee, $restant);
            if ($restant === 0 && $demandee > 0) {
                $avertissements[] = sprintf('« %s — %s » est épuisé.', $ligne['produit_nom'], $ligne['variante_nom']);
            } elseif ($demandee > $restant) {
                $avertissements[] = sprintf('Il ne reste que %d « %s — %s » : le panier a été ajusté.',
                    $restant, $ligne['produit_nom'], $ligne['variante_nom']);
            }
        }
        $lignes = array_values(array_filter($lignes, static fn(array $l): bool => (int) $l['quantite'] > 0));

        // 2. Sous-total et bases de chaque ligne.
        $sousTotal = 0;
        $poids = 0;
        foreach ($lignes as $i => $ligne) {
            $lignes[$i]['designation'] = $ligne['produit_nom'] . ' — ' . $ligne['variante_nom'];
            $lignes[$i]['prix_unitaire'] = (int) $ligne['prix'];
            $lignes[$i]['total_brut'] = (int) $ligne['prix'] * (int) $ligne['quantite'];
            $lignes[$i]['remise_ligne'] = 0;
            $lignes[$i]['cout_achat'] = (int) $ligne['prix_achat'] * (int) $ligne['quantite'];
            $sousTotal += $lignes[$i]['total_brut'];
            $poids += (int) $ligne['poids_grammes'] * (int) $ligne['quantite'];
        }

        // 3. Promotions : la plus favorable passe en premier, le non-cumulable exclut les autres.
        $portOffert = false;
        $promosAppliquees = [];
        foreach (self::classer(self::evaluer($lignes, $promos, $sousTotal, $clientId)) as $essai) {
            $promo = $essai['promo'];
            if ($promosAppliquees !== [] && !$essai['cumulable']) {
                $avertissements[] = sprintf('« %s » n’est pas cumulable : seule la remise la plus avantageuse a été gardée.',
                    $promo['libelle']);
                break;
            }
            if (!$essai['eligible']) {
                if ($essai['code'] !== '') {
                    $avertissements[] = sprintf('Le code %s %s.', $essai['code'], $essai['motif_refus']);
                }
                continue;
            }
            if ($essai['type'] === 'port_gratuit') {
                $portOffert = true;
                $promosAppliquees[] = self::tracePromo($promo, 0, true);
                continue;
            }
            if ($essai['montant'] <= 0) {
                if ($essai['code'] !== '') {
                    $avertissements[] = sprintf('Le code %s ne réduit pas ce panier.', $essai['code']);
                }
                continue;
            }
            $montant = self::appliquer($lignes, $essai);
            if ($montant > 0) {
                $promosAppliquees[] = self::tracePromo($promo, $montant, false);
            }
        }
        $remise = array_sum(array_column($lignes, 'remise_ligne'));

        // 4. Livraison.
        $frais = self::fraisLivraison($options, $poids, $sousTotal - $remise, $portOffert);

        // 5. Totaux.
        $totalTtc = max(0, $sousTotal - $remise + $frais);
        [$lignes, $totalHt, $totalTva] = self::decomposer($lignes, $frais);

        return [
            'lignes'          => $lignes,
            'articles'        => array_sum(array_column($lignes, 'quantite')),
            'sous_total'      => $sousTotal,
            'remise'          => $remise,
            'livraison_frais' => $frais,
            'port_offert'     => $portOffert,
            'total_ttc'       => $totalTtc,
            'total_ht'        => $totalHt,
            'total_tva'       => $totalTva,
            'cout_achat'      => array_sum(array_column($lignes, 'cout_achat')),
            'poids'           => $poids,
            'promos'          => $promosAppliquees,
            'par_enseigne'    => self::repartirParEnseigne($lignes, $frais),
            'avertissements'  => $avertissements,
            'code'            => $code,
            'minimum_atteint' => $totalTtc >= (int) ParametresApp::reglure('livraison_minimum', 0),
        ];
    }

    /** Panier de session tout prêt (contrôleurs et WS passent par là). */
    public static function duSession(array $options = []): array
    {
        return self::tarifer(self::chargerLignes(Panier::items()), $options + [
            'code_promo'      => Panier::code(),
            'transporteur_id' => Panier::transporterId(),
            'point_relais'    => Panier::pointRelais(),
            'date_souhaitee'  => Panier::dateSouhaitee(),
            'emballage'       => Panier::emballage(),
        ]);
    }

    /**
     * Le panier réduit à ce que l'écran de panier affiche : mêmes champs pour
     * le tiroir JavaScript et pour le web-service.
     *
     * @return array<string, mixed>
     */
    public static function exposer(array $panier): array
    {
        return [
            'articles'        => (int) $panier['articles'],
            'sous_total'      => (int) $panier['sous_total'],
            'remise'          => (int) $panier['remise'],
            'livraison'       => (int) $panier['livraison_frais'],
            'port_offert'     => (bool) $panier['port_offert'],
            'total_ttc'       => (int) $panier['total_ttc'],
            'total_ht'        => (int) $panier['total_ht'],
            'total_tva'       => (int) $panier['total_tva'],
            'vide'            => (int) $panier['articles'] === 0,
            'code'            => $panier['code'],
            'avertissements'  => $panier['avertissements'],
            'promos'          => array_map(static fn(array $p): array => [
                'libelle'     => $p['libelle'],
                'code'        => $p['code'],
                'montant'     => (int) $p['montant'],
                'port_offert' => (bool) $p['port_offert'],
            ], $panier['promos']),
            'lignes'          => array_map(static fn(array $l): array => [
                'variante_id'   => (int) $l['variante_id'],
                'produit_slug'  => $l['produit_slug'],
                'designation'   => $l['designation'],
                'variante_nom'  => $l['variante_nom'],
                'enseigne_nom'  => $l['enseigne_nom'],
                'image'         => $l['image_principale'],
                'image_url'     => Url::upload($l['image_principale']),
                'quantite'      => (int) $l['quantite'],
                'restant'       => (int) ($l['restant'] ?? 0),
                'prix_unitaire' => (int) $l['prix_unitaire'],
                'total_brut'    => (int) $l['total_brut'],
                'remise_ligne'  => (int) $l['remise_ligne'],
            ], $panier['lignes']),
        ];
    }

    /* ---------------------------------------------------------------- promos */

    private static function candidates(string $code, int $clientId): array
    {
        $candidates = Promo::automatiques();
        if ($code !== '') {
            $avecCode = Promo::parCode($code);
            if ($avecCode === null) {
                $GLOBALS['tz_code_inconnu'] = $code;
            } else {
                $candidates[] = $avecCode;
            }
        }
        return $candidates;
    }

    /** @return array<int, array<string, mixed>> candidature évaluée */
    private static function evaluer(array $lignes, array $promos, int $sousTotal, int $clientId): array
    {
        $essais = [];
        foreach ($promos as $promo) {
            $essai = [
                'promo'       => $promo,
                'code'        => (string) ($promo['code'] ?? ''),
                'type'        => (string) $promo['type'],
                'cumulable'   => (int) $promo['cumulable'] === 1,
                'eligible'    => true,
                'motif_refus' => '',
                'montant'     => 0,
                'cibles'      => self::lignesCiblees($lignes, $promo),
            ];
            if (!Promo::estFenetre($promo)) {
                $essai['eligible'] = false;
                $essai['motif_refus'] = "n’est pas valable pour cette période";
            } elseif ((int) $promo['quota_total'] > 0 && (int) $promo['quota_utilise'] >= (int) $promo['quota_total']) {
                $essai['eligible'] = false;
                $essai['motif_refus'] = 'a épuisé son quota';
            } elseif ((int) $promo['une_fois_par_client'] === 1 && Promo::dejaUtiliseePar((int) $promo['id'], $clientId)) {
                $essai['eligible'] = false;
                $essai['motif_refus'] = 'a déjà été utilisé sur votre compte';
            } elseif ((int) $promo['premiere_commande_seule'] === 1 && Promo::clientAPayee($clientId)) {
                $essai['eligible'] = false;
                $essai['motif_refus'] = 'est réservé à la première commande';
            }

            $baseCible = array_sum(array_column($essai['cibles'], 'total_brut'));
            if ($essai['eligible'] && $baseCible === 0 && $promo['type'] !== 'port_gratuit') {
                $essai['eligible'] = false;
                $essai['motif_refus'] = 'ne correspond à aucun article du panier';
            }
            $seuil = (int) $promo['seuil_achat'];
            if ($essai['eligible'] && $seuil > 0 && $promo['type'] !== 'n_plus_gratuit' && $baseCible < $seuil) {
                $essai['eligible'] = false;
                $essai['motif_refus'] = sprintf('demande %s d’achat sur le rayon concerné', Money::formater($seuil));
            }
            $essai['montant'] = $essai['eligible']
                ? self::montant($promo, $essai['cibles'], $baseCible)
                : 0;
            $essais[] = $essai;
        }
        return $essais;
    }

    /** Éligibles d'abord, puis par remise décroissante. */
    private static function classer(array $essais): array
    {
        usort($essais, static function (array $a, array $b): int {
            $ordre = [$b['eligible'], $b['montant'], $b['type'] === 'port_gratuit' ? 1 : 0];
            $contre = [$a['eligible'], $a['montant'], $a['type'] === 'port_gratuit' ? 1 : 0];
            foreach ($ordre as $i => $valeur) {
                if ($valeur !== $contre[$i]) {
                    return $valeur <=> $contre[$i];
                }
            }
            return 0;
        });
        return $essais;
    }

    private static function lignesCiblees(array $lignes, array $promo): array
    {
        $cible = (string) $promo['cible_type'];
        $ids = array_values(array_filter(array_map('intval', explode(',', (string) ($promo['cible_ids'] ?? '')))));
        $exclus = array_values(array_filter(array_map('intval', explode(',', (string) ($promo['exclusion_ids'] ?? '')))));
        $maison = $promo['enseigne_id'] === null ? null : (int) $promo['enseigne_id'];

        $sortie = array_filter($lignes, static function (array $ligne) use ($cible, $ids, $maison): bool {
            if ($maison !== null && (int) $ligne['enseigne_id'] !== $maison) {
                return false;
            }
            return match ($cible) {
                'categorie' => in_array((int) $ligne['categorie_id'], $ids, true),
                'produit'   => in_array((int) $ligne['produit_id'], $ids, true),
                'variante'  => in_array((int) $ligne['variante_id'], $ids, true),
                'enseigne'  => in_array((int) $ligne['enseigne_id'], $ids, true),
                default     => true,
            };
        });
        if ($exclus !== []) {
            $sortie = array_filter($sortie, static fn(array $l): bool => !in_array((int) $l['produit_id'], $exclus, true));
        }
        return array_values($sortie);
    }

    private static function montant(array $promo, array $cibles, int $baseCible): int
    {
        $valeur = (int) $promo['valeur'];
        $montant = match ($promo['type']) {
            'pourcentage', 'palier_pourcentage' => (int) round($baseCible * $valeur / 100000),
            'montant', 'palier_montant'         => min($valeur, $baseCible),
            'n_plus_gratuit'                    => self::gratuites($promo, $cibles),
            'prix_impose'                       => self::prixImpose($valeur, $cibles),
            default                             => 0,
        };
        if ((int) $promo['plafond_remise'] > 0) {
            $montant = min($montant, (int) $promo['plafond_remise']);
        }
        return max(0, $montant);
    }

    /** « 3 achetés, 1 offert » : sur chaque ligne, les unités offertes sont les moins chères. */
    private static function gratuites(array $promo, array $cibles): int
    {
        $declencheur = max(1, (int) $promo['quantite_declencheur']);
        $offerts = max(1, (int) $promo['nombre_offert']);
        $montant = 0;
        foreach ($cibles as $ligne) {
            $quantite = (int) $ligne['quantite'];
            $unites = min(intdiv($quantite, $declencheur) * $offerts, max(0, $quantite - 1));
            $montant += $unites * (int) $ligne['prix_unitaire'];
        }
        return $montant;
    }

    private static function prixImpose(int $valeur, array $cibles): int
    {
        $montant = 0;
        foreach ($cibles as $ligne) {
            $difference = (int) $ligne['total_brut'] - $valeur * (int) $ligne['quantite'];
            if ($difference > 0) {
                $montant += $difference;
            }
        }
        return $montant;
    }

    /**
     * Impute la remise sur les lignes ciblées, au prorata du reste à remettre,
     * puis sur tout le panier s'il reste de la place.
     * @return int remise réellement imputée
     */
    private static function appliquer(array &$lignes, array $essai): int
    {
        $positions = [];
        foreach ($lignes as $i => $ligne) {
            $positions[(int) $ligne['variante_id']] = $i;
        }
        $cibles = array_map(static fn(array $l): int => (int) $l['variante_id'], $essai['cibles']);
        $reste = $essai['montant'];
        if ($cibles !== []) {
            $reste = self::repartir($lignes, $cibles, $reste, $positions);
        } else {
            $reste = self::repartir($lignes, array_keys($positions), $reste, $positions);
        }
        if ($reste > 0) {
            $reste = self::repartir($lignes, array_keys($positions), $reste, $positions);
        }
        return $essai['montant'] - max(0, $reste);
    }

    /** @return int reliquat non imputé */
    private static function repartir(array &$lignes, array $ids, int $montant, array $positions): int
    {
        if ($montant <= 0) {
            return $montant;
        }
        $exploitables = [];
        $base = 0;
        foreach ($ids as $id) {
            $i = $positions[$id] ?? null;
            if ($i === null) {
                continue;
            }
            $disponible = max(0, (int) $lignes[$i]['total_brut'] - (int) $lignes[$i]['remise_ligne']);
            if ($disponible === 0) {
                continue;
            }
            $exploitables[$i] = $disponible;
            $base += $disponible;
        }
        if ($base === 0) {
            return $montant;
        }
        $reparti = 0;
        $derniere = array_key_last($exploitables);
        foreach ($exploitables as $i => $disponible) {
            $quote = $i === $derniere
                ? min($disponible, $montant - $reparti)
                : min($disponible, (int) floor($montant * $disponible / $base));
            $lignes[$i]['remise_ligne'] = (int) $lignes[$i]['remise_ligne'] + $quote;
            $reparti += $quote;
            if ($reparti >= $montant) {
                break;
            }
        }
        return $montant - $reparti;
    }

    private static function tracePromo(array $promo, int $montant, bool $port): array
    {
        return [
            'promo_id'    => (int) $promo['id'],
            'code'        => $promo['code'],
            'libelle'     => (string) $promo['libelle'],
            'type'        => (string) $promo['type'],
            'montant'     => $port ? 0 : $montant,
            'port_offert' => $port,
        ];
    }

    /* -------------------------------------------------------------- livraison */

    /** Frais de port d'un transporteur pour ce panier, franchise et port offert compris. */
    public static function fraisLivraison(array $options, int $poids, int $netAvantPort, bool $portOffert): int
    {
        if ($portOffert) {
            return 0;
        }
        $transporteurId = (int) ($options['transporteur_id'] ?? 0);
        $frais = (int) ParametresApp::reglure('livraison_frais', TZ_LOGISTICS_FRAIS_BASE);
        if ($transporteurId > 0 && ($transporteur = Transporteur::parId($transporteurId)) !== null) {
            $frais = Transporteur::frais($transporteur, $poids, $netAvantPort, (string) ($options['ville'] ?? ''));
        }
        $seuil = (int) ParametresApp::reglure('livraison_gratuite_a', TZ_LOGISTICS_GRATUITE_A_PART);
        if ($seuil > 0 && $netAvantPort >= $seuil) {
            return 0;
        }
        return max(0, $frais);
    }

    /* ------------------------------------------------------------------ totaux */

    /**
     * Décompose le TTC de chaque ligne en HT + TVA, puis y ajoute le port.
     * @return array{0:array,1:int,2:int}
     */
    private static function decomposer(array $lignes, int $frais): array
    {
        $totalHt = 0;
        $totalTva = 0;
        foreach ($lignes as $i => $ligne) {
            $ttc = (int) $ligne['total_brut'] - (int) $ligne['remise_ligne'];
            $nette = Money::decomposerTva($ttc, (int) $ligne['tva_taux']);
            $lignes[$i]['total_ttc'] = $ttc;
            $lignes[$i]['total_ht'] = $nette['ht'];
            $lignes[$i]['total_tva'] = $nette['tva'];
            $lignes[$i]['prix_ht'] = Money::decomposerTva((int) $ligne['prix_unitaire'], (int) $ligne['tva_taux'])['ht'];
            $lignes[$i]['remise_ht'] = Money::decomposerTva((int) $ligne['remise_ligne'], (int) $ligne['tva_taux'])['ht'];
            $totalHt += $nette['ht'];
            $totalTva += $nette['tva'];
        }
        if ($frais > 0) {
            $port = Money::decomposerTva($frais, (int) ParametresApp::reglure('tva_standard', TZ_TVA_STANDARD));
            $totalHt += $port['ht'];
            $totalTva += $port['tva'];
        }
        return [$lignes, $totalHt, $totalTva];
    }

    /** Une commande, plusieurs préparations : ce que chaque maison doit emballer. */
    private static function repartirParEnseigne(array $lignes, int $frais): array
    {
        $groupes = [];
        foreach ($lignes as $ligne) {
            $id = (int) $ligne['enseigne_id'];
            if (!isset($groupes[$id])) {
                $groupes[$id] = [
                    'enseigne_id'   => $id,
                    'enseigne_nom'  => (string) $ligne['enseigne_nom'],
                    'enseigne_slug' => (string) $ligne['enseigne_slug'],
                    'couleur'       => (string) $ligne['enseigne_couleur'],
                    'commission_bpo' => (int) $ligne['commission_bpo'],
                    'articles'      => 0,
                    'sous_total'    => 0,
                    'remise'        => 0,
                    'net'           => 0,
                    'cout_achat'    => 0,
                    'lignes'        => 0,
                    'frais'         => 0,
                ];
            }
            $groupes[$id]['articles'] += (int) $ligne['quantite'];
            $groupes[$id]['sous_total'] += (int) $ligne['total_brut'];
            $groupes[$id]['remise'] += (int) $ligne['remise_ligne'];
            $groupes[$id]['net'] += (int) $ligne['total_ttc'];
            $groupes[$id]['cout_achat'] += (int) $ligne['cout_achat'];
            $groupes[$id]['lignes']++;
        }
        if ($frais > 0 && $groupes !== []) {
            $plusFournie = array_key_first($groupes);
            foreach ($groupes as $id => $groupe) {
                if ($groupe['net'] > $groupes[$plusFournie]['net']) {
                    $plusFournie = $id;
                }
            }
            $groupes[$plusFournie]['frais'] = $frais;
            $groupes[$plusFournie]['net'] += $frais;
        }
        foreach ($groupes as $id => $groupe) {
            // commission_bpo est un pourcentage en millièmes : 5000 = 5,000 %.
            $groupes[$id]['commission'] = (int) round($groupe['net'] * $groupe['commission_bpo'] / 100000);
            $groupes[$id]['revient'] = $groupe['net'] - $groupes[$id]['commission'];
            $groupes[$id]['marge'] = $groupe['net'] - $groupe['remise'] - $groupe['cout_achat'];
        }
        return $groupes;
    }
}
