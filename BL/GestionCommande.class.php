<?php
/**
 * Tunnel de commande : coordonnées, engagement du stock, création de la
 * commande multi-maisons, puis bascule « payée » quand le paiement est confirmé.
 * Les montants viennent du moteur de prix : rien n'est recalculé ici.
 */
class GestionCommande
{
    public const MODES = [
        'carte_gpg'     => 'Carte bancaire — GPG Checkout',
        'livraison'     => 'Paiement à la livraison',
        'virement'      => 'Virement bancaire',
        'cheque'        => 'Chèque',
        'especes_point' => 'Espèces au retrait',
        'cadeau'        => 'Carte cadeau',
    ];

    /** Modes proposés : réglages de la régie, filtrés par le type de livraison. */
    public static function modesPaiement(string $typeLivraison = 'domicile'): array
    {
        $lus = ParametresApp::reglure('payment_modes_disponibles', ['carte_gpg']);
        $lus = is_array($lus) ? $lus : (json_decode((string) $lus, true) ?: []);
        $sortie = [];
        foreach ($lus as $code) {
            $code = (string) $code;
            if (!isset(self::MODES[$code])) {
                continue;
            }
            if ($code === 'carte_gpg' && !GestionPaiement::active()) {
                continue;
            }
            if ($code === 'especes_point' && $typeLivraison !== 'retait') {
                continue;
            }
            if ($code === 'livraison' && $typeLivraison === 'retait') {
                continue;
            }
            $sortie[] = ['code' => $code, 'libelle' => self::MODES[$code], 'note' => self::noteMode($code)];
        }
        return $sortie;
    }

    private static function noteMode(string $code): string
    {
        return match ($code) {
            'carte_gpg' => GestionPaiement::simule()
                ? 'Mode simulation : la page de paiement est locale, aucun débit réel.'
                : 'Vous quittez le site pour la page sécurisée GPG Checkout, puis revenez ici.',
            'livraison' => 'Vous réglez le transporteur à la réception, en dinars.',
            'virement' => (string) ParametresApp::reglure('paiement_virement_consignes',
                'Les coordonnées bancaires vous sont transmises par e-mail après validation.'),
            'especes_point' => 'À l’atelier de Tozeur, route de Nehou, du samedi au jeudi de 8 h à 17 h.',
            default => '',
        };
    }

    /**
     * Coordonnées du tunnel, normalisées et vérifiées.
     *
     * @param array<string, mixed> $saisies champs postés
     * @return array{ ok: bool, erreurs: array<string, string>, donnees: array<string, mixed> }
     */
    public static function coordonnees(array $saisies, array $panier): array
    {
        $client = Auth::client();
        $d = [
            'email'          => mb_strtolower(trim((string) ($saisies['email'] ?? ($client['email'] ?? '')))),
            'prenom'         => trim((string) ($saisies['prenom'] ?? '')),
            'nom'            => trim((string) ($saisies['nom'] ?? '')),
            'telephone'      => trim((string) ($saisies['telephone'] ?? '')),
            'societe'        => trim((string) ($saisies['societe'] ?? '')),
            'ligne1'         => trim((string) ($saisies['ligne1'] ?? '')),
            'ligne2'         => trim((string) ($saisies['ligne2'] ?? '')),
            'ville'          => trim((string) ($saisies['ville'] ?? '')),
            'delegation'     => trim((string) ($saisies['delegation'] ?? '')),
            'code_postal'    => trim((string) ($saisies['code_postal'] ?? '')),
            'pays'           => strtoupper(trim((string) ($saisies['pays'] ?? 'TN'))) ?: 'TN',
            'point_relais'   => trim((string) ($saisies['point_relais'] ?? '')),
            'date_souhaitee' => trim((string) ($saisies['date_souhaitee'] ?? '')),
            'message'        => trim((string) ($saisies['message'] ?? '')),
            'transporteur_id' => (int) ($saisies['transporteur_id'] ?? 0),
        ];
        $erreurs = [];

        if (filter_var($d['email'], FILTER_VALIDATE_EMAIL) === false || mb_strlen($d['email']) > 160) {
            $erreurs['email'] = 'Une adresse e-mail valide est nécessaire pour le suivi et la facture.';
        }
        foreach (['prenom' => 'Prénom', 'nom' => 'Nom'] as $cle => $libelle) {
            if (mb_strlen($d[$cle]) < 2 || mb_strlen($d[$cle]) > 60) {
                $erreurs[$cle] = $libelle . ' : de 2 à 60 caractères.';
            }
        }
        $chiffres = preg_replace('/\D/', '', $d['telephone']);
        if (strlen((string) $chiffres) < 8 || strlen((string) $chiffres) > 15
            || preg_match('/^[0-9+\s().-]{8,20}$/', $d['telephone']) !== 1) {
            $erreurs['telephone'] = 'Téléphone : huit chiffres au moins, pour joindre le destinataire.';
        }

        $transporteur = $d['transporteur_id'] > 0 ? Transporteur::parId($d['transporteur_id']) : null;
        if ($transporteur === null) {
            $erreurs['transporteur_id'] = 'Choisissez un mode de livraison.';
            $d['type_livraison'] = 'domicile';
        } else {
            $d['type_livraison'] = (string) $transporteur['type'];
            $d['transporteur_nom'] = (string) $transporteur['nom'];
            $d['delai_jours'] = (int) $transporteur['delai_jours'];
            if ($d['type_livraison'] !== 'retait') {
                if (mb_strlen($d['ligne1']) < 5) {
                    $erreurs['ligne1'] = 'Adresse : rue, numéro, immeuble — cinq caractères au moins.';
                }
                if (mb_strlen($d['ville']) < 2) {
                    $erreurs['ville'] = 'Indiquez la ville de livraison.';
                }
                if ($d['code_postal'] !== '' && preg_match('/^\d{4}$/', $d['code_postal']) !== 1) {
                    $erreurs['code_postal'] = 'Code postal : quatre chiffres.';
                }
                if (!Transporteur::dessert($transporteur, $d['ville'])) {
                    $erreurs['transporteur_id'] = sprintf('%s ne dessert pas %s.', $transporteur['nom'], $d['ville']);
                }
            }
            if ($d['type_livraison'] === 'relais' && mb_strlen($d['point_relais']) < 2) {
                $erreurs['point_relais'] = 'Précisez la ville du point relais.';
            }
        }

        if ($d['date_souhaitee'] !== '') {
            $quand = strtotime($d['date_souhaitee']);
            if ($quand === false || $quand < strtotime(date('Y-m-d')) || $quand > strtotime('+60 days')) {
                $erreurs['date_souhaitee'] = 'Date souhaitée : entre aujourd’hui et soixante jours.';
            } else {
                $d['date_souhaitee'] = date('Y-m-d', $quand);
            }
        }
        if (mb_strlen($d['message']) > 1000) {
            $erreurs['message'] = 'Message : mille caractères au maximum.';
        }
        if (($saisies['cgv'] ?? '') !== '1') {
            $erreurs['cgv'] = 'Merci d’accepter les conditions de vente.';
        }

        // Facturation : identique à la livraison, sauf demande explicite.
        $d['facture_autre'] = ($saisies['facture_autre'] ?? '') === '1';
        if ($d['facture_autre']) {
            $d['facturation'] = [
                'nom'         => trim((string) ($saisies['facture_nom'] ?? '')),
                'ligne1'      => trim((string) ($saisies['facture_ligne1'] ?? '')),
                'ville'       => trim((string) ($saisies['facture_ville'] ?? '')),
                'code_postal' => trim((string) ($saisies['facture_code_postal'] ?? '')),
                'pays'        => strtoupper(trim((string) ($saisies['facture_pays'] ?? 'TN'))) ?: 'TN',
            ];
            if (mb_strlen($d['facturation']['nom']) < 2 || mb_strlen($d['facturation']['ligne1']) < 5
                || mb_strlen($d['facturation']['ville']) < 2) {
                $erreurs['facture_nom'] = 'Adresse de facturation : nom, adresse et ville sont nécessaires.';
            }
        } else {
            $d['facturation'] = [
                'nom' => trim($d['prenom'] . ' ' . $d['nom']), 'ligne1' => $d['ligne1'],
                'ligne2' => $d['ligne2'], 'ville' => $d['ville'], 'code_postal' => $d['code_postal'],
                'pays' => $d['pays'],
            ];
        }

        if ((int) $panier['articles'] === 0) {
            $erreurs['panier'] = 'Votre panier est vide.';
        } elseif (!(bool) $panier['minimum_atteint']) {
            $erreurs['panier'] = sprintf('Le minimum de commande est de %s.',
                Money::formater((int) ParametresApp::reglure('livraison_minimum', 0)));
        }
        if ($panier['avertissements'] !== []) {
            $erreurs['panier'] = implode(' ', $panier['avertissements']);
        }

        return ['ok' => $erreurs === [], 'erreurs' => $erreurs, 'donnees' => $d];
    }

    /**
     * Fige la commande : lignes aux prix du jour, stock engagé, maisons engagées.
     *
     * @param array<string, mixed> $panier sortie de GestionPanier::tarifer()
     * @param array<string, mixed> $coord  sortie de coordonnees()['donnees']
     * @return array{ ok: bool, erreurs: array<string, string>, commande_id: int|null, reference: string|null }
     */
    public static function creer(array $panier, array $coord, string $mode): array
    {
        if (!isset(self::MODES[$mode])) {
            return self::refus(['mode' => 'Ce mode de paiement n’est pas proposé.']);
        }
        if ($panier['lignes'] === [] || (int) $panier['total_ttc'] <= 0) {
            return self::refus(['panier' => 'Il n’y a rien à commander.']);
        }

        $clientId = Auth::idClient();
        $reference = Commande::nouvelleReference();
        $maisons = array_values(array_unique(array_map(
            static fn(array $l): int => (int) $l['enseigne_id'],
            $panier['lignes']
        )));
        $type = (string) ($coord['type_livraison'] ?? 'domicile');

        $commande = [
            'reference'        => $reference,
            'client_id'        => $clientId,
            'email'            => (string) $coord['email'],
            'enseigne_ids'     => implode(',', $maisons),
            'statut'           => $mode === 'carte_gpg' ? 'en_attente' : 'attente_validation',
            'type_livraison'   => $type,
            'transporteur_id'  => (int) $coord['transporteur_id'],
            'adresse_destinataire' => json_encode(self::adresseSnapshot($coord), JSON_UNESCAPED_UNICODE),
            'adresse_facturation'  => json_encode($coord['facturation'], JSON_UNESCAPED_UNICODE),
            'point_relais'     => $coord['point_relais'] === '' ? null : $coord['point_relais'],
            'date_souhaitee'   => ($coord['date_souhaitee'] ?? '') === '' ? null : $coord['date_souhaitee'],
            'sous_total'       => (int) $panier['sous_total'],
            'remise_montant'   => (int) $panier['remise'],
            'remise_detail'    => json_encode($panier['promos'], JSON_UNESCAPED_UNICODE),
            'livraison_frais'  => (int) $panier['livraison_frais'],
            'total_ttc'        => (int) $panier['total_ttc'],
            'total_ht'         => (int) $panier['total_ht'],
            'total_tva'        => (int) $panier['total_tva'],
            'cout_achat'       => (int) $panier['cout_achat'],
            'code_promo'       => $panier['code'] === '' ? null : $panier['code'],
            'mode_paiement'    => $mode,
            'statut_paiement'  => match ($mode) {
                'carte_gpg' => 'initialise',
                'virement', 'cheque' => 'en_attente',
                default => 'aucun',
            },
            'message_client'   => ($coord['message'] ?? '') === '' ? null : $coord['message'],
            'emballage_cadeau' => Panier::emballage() ? 1 : 0,
            'carte_message'    => Panier::message() === '' ? null : mb_substr(Panier::message(), 0, 300),
            'apport'           => ParametresApp::voyageur('apport'),
        ];

        $lignes = [];
        foreach ($panier['lignes'] as $ligne) {
            $lignes[] = [
                'enseigne_id'   => (int) $ligne['enseigne_id'],
                'produit_id'    => (int) $ligne['produit_id'],
                'variante_id'   => (int) $ligne['variante_id'],
                'designation'   => mb_substr((string) $ligne['designation'], 0, 220),
                'sku'           => $ligne['sku'],
                'quantite'      => (int) $ligne['quantite'],
                'prix_unitaire' => (int) $ligne['prix_unitaire'],
                'remise_ligne'  => (int) $ligne['remise_ligne'],
                'tva_taux'      => (int) $ligne['tva_taux'],
                'total_ttc'     => (int) $ligne['total_ttc'],
                'total_ht'      => (int) $ligne['total_ht'],
                'total_tva'     => (int) $ligne['total_tva'],
                'cout_achat'    => (int) $ligne['cout_achat'],
                'image'         => $ligne['image_principale'],
            ];
        }

        try {
            $commandeId = Db::transaction(static function () use ($commande, $lignes, $panier): int {
                foreach ($panier['lignes'] as $ligne) {
                    if (!Variante::reserver((int) $ligne['variante_id'], (int) $ligne['quantite'])) {
                        throw new RuntimeException(sprintf('« %s » vient de trouver preneur : il ne reste plus assez de stock.',
                            $ligne['designation']));
                    }
                }
                return Commande::creer($commande, $lignes);
            });
        } catch (RuntimeException $e) {
            return self::refus(['panier' => $e->getMessage()]);
        }

        ActionJournal::noter('commande_creee', 'commande', $commandeId,
            ['reference' => $reference, 'montant' => (int) $panier['total_ttc'], 'mode' => $mode], 'client');
        Panier::persister($clientId, [], 0, 0);

        return ['ok' => true, 'erreurs' => [], 'commande_id' => $commandeId, 'reference' => $reference];
    }

    /**
     * Bascule « payée » : stock réellement sorti, promotions consommées.
     * Idempotent — le garde-fou est une UPDATE conditionnelle dans la transaction,
     * si bien qu'une notification rejouée ne sort pas le stock deux fois.
     */
    public static function marquerPayee(int $commandeId, array $trace = []): bool
    {
        return Db::transaction(static function () use ($commandeId, $trace): bool {
            $garde = Db::modifier('tz_commande', ['statut_paiement' => 'accorde'],
                "id = ? AND statut_paiement <> 'accorde' AND statut IN ('en_attente','attente_validation','litige')",
                [$commandeId]);
            if ($garde === 0) {
                return false;
            }
            $commande = Commande::parId($commandeId);
            foreach (Commande::lignes($commandeId) as $ligne) {
                $varianteId = (int) $ligne['variante_id'];
                $quantite = (int) $ligne['quantite'];
                Variante::liberer($varianteId, $quantite);
                StockMouvement::enregistrer([
                    'variante_id' => $varianteId,
                    'type'        => 'vente',
                    'quantite'    => -$quantite,
                    'motif'       => 'Commande ' . $commande['reference'],
                    'commande_id' => $commandeId,
                    'enseigne_id' => (int) $ligne['enseigne_id'],
                    'cout_unitaire' => $quantite > 0 ? intdiv((int) $ligne['cout_achat'], $quantite) : 0,
                ]);
            }
            foreach (json_decode((string) $commande['remise_detail'], true) ?: [] as $promo) {
                if (!empty($promo['promo_id'])) {
                    Promo::consommer((int) $promo['promo_id'], $commandeId,
                        $commande['client_id'] === null ? null : (int) $commande['client_id'],
                        (int) ($promo['montant'] ?? 0));
                }
            }
            Commande::changerStatut($commandeId, 'payee');
            if ($commande['client_id'] !== null) {
                Client::recalculerTotaux((int) $commande['client_id']);
            }
            ActionJournal::noter('commande_payee', 'commande', $commandeId,
                ['reference' => $commande['reference'], 'montant' => (int) $commande['total_ttc']] + $trace, 'client');
            return true;
        });
    }

    /** Rend le stock engagé et clôture la commande sans vente. */
    public static function abandonner(int $commandeId, string $motif, string $statutPaiement = 'refuse'): bool
    {
        return Db::transaction(static function () use ($commandeId, $motif, $statutPaiement): bool {
            $garde = Db::modifier('tz_commande',
                ['statut' => 'annulee', 'statut_paiement' => $statutPaiement, 'annulee_le' => date('Y-m-d H:i:s')],
                "id = ? AND statut_paiement <> 'accorde' AND statut IN ('en_attente','attente_validation')",
                [$commandeId]);
            if ($garde === 0) {
                return false;
            }
            foreach (Commande::lignes($commandeId) as $ligne) {
                Variante::liberer((int) $ligne['variante_id'], (int) $ligne['quantite']);
            }
            $commande = Commande::parId($commandeId);
            ActionJournal::noter('commande_abandonnee', 'commande', $commandeId,
                ['reference' => $commande['reference'], 'motif' => $motif], 'client');
            return true;
        });
    }

    /** Ce que le client peut voir d'une commande : session, compte, ou suivi vérifié. */
    public static function accessible(array $commande): bool
    {
        if ($commande['client_id'] !== null && (int) $commande['client_id'] === Auth::idClient()) {
            return true;
        }
        $autorisees = Session::lire('tz_commandes_vues', []);
        return is_array($autorisees) && in_array((string) $commande['reference'], $autorisees, true);
    }

    public static function autoriser(string $reference): void
    {
        $vues = Session::lire('tz_commandes_vues', []);
        $vues = is_array($vues) ? $vues : [];
        if (!in_array($reference, $vues, true)) {
            $vues[] = $reference;
        }
        Session::ecrire('tz_commandes_vues', array_slice($vues, -20));
        Session::ecrire('tz_derniere_commande', $reference);
    }

    /** @return array<string, mixed> */
    private static function adresseSnapshot(array $coord): array
    {
        $sortie = [];
        foreach (['prenom', 'nom', 'societe', 'telephone', 'email', 'ligne1', 'ligne2',
                  'ville', 'delegation', 'code_postal', 'pays'] as $cle) {
            $sortie[$cle] = (string) ($coord[$cle] ?? '');
        }
        return $sortie;
    }

    /** @return array{ ok: bool, erreurs: array<string, string>, commande_id: int|null, reference: string|null } */
    private static function refus(array $erreurs): array
    {
        return ['ok' => false, 'erreurs' => $erreurs, 'commande_id' => null, 'reference' => null];
    }
}