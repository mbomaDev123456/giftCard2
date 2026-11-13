<?php
/**
 * Écritures comptables en partie double.
 * `insere()` refuse tout lot déséquilibré : la partie double est une invariant, pas une habitude.
 * Montants en millimes, comptes en plan tunisien (411, 44571, 7111…).
 */
class Ecriture
{
    public static function parId(int $id): ?array
    {
        return Db::un('SELECT * FROM tz_ecriture WHERE id = ?', [$id]);
    }

    /**
     * Saisie d'une pièce complète.
     *
     * @param array<int, array<string, mixed>> $ecritures lignes debit/credit partageant la même `piece`
     */
    public static function insere(array $ecritures): int
    {
        if ($ecritures === []) {
            return 0;
        }
        $debit = 0;
        $credit = 0;
        foreach ($ecritures as $ligne) {
            $debit += (int) ($ligne['debit'] ?? 0);
            $credit += (int) ($ligne['credit'] ?? 0);
        }
        if ($debit !== $credit) {
            throw new RuntimeException(sprintf(
                'Écriture déséquilibrée : %s DT au débit contre %s DT au crédit.',
                Money::formater($debit, false),
                Money::formater($credit, false)
            ));
        }

        return Db::transaction(static function () use ($ecritures): int {
            $premiere = null;
            foreach ($ecritures as $ligne) {
                $id = Db::inserer('tz_ecriture', self::prepare($ligne));
                $premiere ??= $id;
            }
            return (int) $premiere;
        });
    }

    public static function pourPiece(string $piece, ?int $journalId = null): array
    {
        $conditions = ['piece = ?'];
        $params = [$piece];
        if ($journalId !== null) {
            $conditions[] = 'journal_id = ?';
            $params[] = $journalId;
        }
        return Db::tous('SELECT * FROM tz_ecriture WHERE ' . implode(' AND ', $conditions) . ' ORDER BY compte, id',
            $params);
    }

    public static function pieceExiste(string $piece, ?int $journalId = null): bool
    {
        $conditions = ['piece = ?'];
        $params = [$piece];
        if ($journalId !== null) {
            $conditions[] = 'journal_id = ?';
            $params[] = $journalId;
        }
        return Db::compter('tz_ecriture', implode(' AND ', $conditions), $params) > 0;
    }

    public static function pourCommande(int $commandeId): array
    {
        return Db::tous(
            'SELECT e.*, j.code journal_code, j.libelle journal
               FROM tz_ecriture e JOIN tz_journal j ON j.id = e.journal_id
              WHERE e.commande_id = ? ORDER BY e.date_ecriture, e.compte, e.id',
            [$commandeId]
        );
    }

    public static function pourFacture(int $factureId): array
    {
        return Db::tous(
            'SELECT e.*, j.code journal_code, j.libelle journal
               FROM tz_ecriture e JOIN tz_journal j ON j.id = e.journal_id
              WHERE e.facture_id = ? ORDER BY e.date_ecriture, e.compte, e.id',
            [$factureId]
        );
    }

    /** @param array<string, mixed> $f */
    public static function administrer(array $f = [], int $page = 1, int $parPage = 50): array
    {
        $conditions = [];
        $params = [];
        if (!empty($f['journal_id'])) {
            $conditions[] = 'e.journal_id = ?';
            $params[] = (int) $f['journal_id'];
        }
        if (($f['compte'] ?? '') !== '') {
            $conditions[] = 'e.compte LIKE ?';
            $params[] = $f['compte'] . '%';
        }
        if (($f['piece'] ?? '') !== '') {
            $conditions[] = 'e.piece = ?';
            $params[] = $f['piece'];
        }
        if (($f['q'] ?? '') !== '') {
            $conditions[] = '(e.intitule LIKE ? OR e.counterpartie LIKE ? OR e.piece LIKE ?)';
            $motif = '%' . $f['q'] . '%';
            array_push($params, $motif, $motif, $motif);
        }
        if (($f['du'] ?? '') !== '') {
            $conditions[] = 'e.date_ecriture >= ?';
            $params[] = $f['du'];
        }
        if (($f['au'] ?? '') !== '') {
            $conditions[] = 'e.date_ecriture <= ?';
            $params[] = $f['au'];
        }
        if (($f['validee'] ?? '') !== '') {
            $conditions[] = 'e.validee = ?';
            $params[] = (int) $f['validee'];
        }
        $where = $conditions === [] ? '1' : implode(' AND ', $conditions);
        $base = "FROM tz_ecriture e JOIN tz_journal j ON j.id = e.journal_id WHERE $where";

        $total = (int) Db::valeur("SELECT COUNT(*) $base", $params);
        $pages = max(1, (int) ceil($total / $parPage));
        $page = min(max(1, $page), $pages);
        $depart = ($page - 1) * $parPage;

        return [
            'lignes' => Db::tous(
                "SELECT e.*, j.code journal_code, j.libelle journal_libelle, j.nature journal_nature
                   $base
               ORDER BY e.date_ecriture DESC, e.id DESC, e.compte
               LIMIT $parPage OFFSET $depart",
                $params
            ),
            'total' => $total, 'pages' => $pages, 'page' => $page,
            'equilibre' => self::totauxSur($where, $params),
        ];
    }

    /** @return array{debit: int, credit: int, lignes: int} */
    public static function totauxSur(string $where, array $params): array
    {
        return Db::un(
            "SELECT COALESCE(SUM(e.debit), 0) debit, COALESCE(SUM(e.credit), 0) credit, COUNT(*) lignes
               FROM tz_ecriture e WHERE $where",
            $params
        ) ?? ['debit' => 0, 'credit' => 0, 'lignes' => 0];
    }

    /** Balance : soldes débiteur/créditeur par compte sur la période. */
    public static function balance(string $debut, string $fin): array
    {
        return Db::tous(
            'SELECT compte,
                    COUNT(*) ecritures,
                    COALESCE(SUM(debit), 0) debit,
                    COALESCE(SUM(credit), 0) credit,
                    COALESCE(SUM(debit) - SUM(credit), 0) solde
               FROM tz_ecriture
              WHERE date_ecriture BETWEEN ? AND ? AND validee = 1
           GROUP BY compte
           ORDER BY compte',
            [$debut, $fin]
        );
    }

    /** Grand livre d'un compte, avec solde courant filant. */
    public static function grandLivre(string $compte, string $debut, string $fin): array
    {
        $lignes = Db::tous(
            'SELECT e.*, j.code journal_code
               FROM tz_ecriture e JOIN tz_journal j ON j.id = e.journal_id
              WHERE e.compte = ? AND e.date_ecriture BETWEEN ? AND ?
           ORDER BY e.date_ecriture, e.id',
            [$compte, $debut, $fin]
        );
        $anteriorite = Db::un(
            'SELECT COALESCE(SUM(debit), 0) - COALESCE(SUM(credit), 0) reporte
               FROM tz_ecriture WHERE compte = ? AND date_ecriture < ? AND validee = 1',
            [$compte, $debut]
        ) ?? ['reporte' => 0];

        $solde = (int) $anteriorite['reporte'];
        foreach ($lignes as $i => $ligne) {
            $solde += (int) $ligne['debit'] - (int) $ligne['credit'];
            $lignes[$i]['solde'] = $solde;
        }
        return ['lignes' => $lignes, 'reporte' => (int) $anteriorite['reporte'], 'solde' => $solde];
    }

    /**
     * TVA d'une période pour un compte donné (44571 collectée, 44566 déductible).
     *
     * @return array{base_ht: int, tva: int, par_taux: array<int,int>}
     */
    public static function tva(string $debut, string $fin, string $compte = '44571'): array
    {
        $colonnes = $compte === '44571' ? 'credit' : 'debit';
        $lignes = Db::tous(
            "SELECT tva_taux, COALESCE(SUM($colonnes), 0) tva
               FROM tz_ecriture
              WHERE compte = ? AND date_ecriture BETWEEN ? AND ? AND validee = 1
           GROUP BY tva_taux",
            [$compte, $debut, $fin]
        );
        $base = 0;
        $total = 0;
        $parTaux = [];
        foreach ($lignes as $ligne) {
            $taux = (int) ($ligne['tva_taux'] ?? 0);
            $montant = (int) $ligne['tva'];
            $total += $montant;
            if ($taux > 0) {
                $base += (int) round($montant * 100000 / $taux);
                $parTaux[$taux] = ($parTaux[$taux] ?? 0) + $montant;
            }
        }
        return ['base_ht' => $base, 'tva' => $total, 'par_taux' => $parTaux];
    }

    /**
     * Export type FEC : une ligne par écriture, colonnes figées par l'administration.
     *
     * @return array<int, array<string, string|int>>
     */
    public static function fec(string $debut, string $fin): array
    {
        $lignes = Db::tous(
            'SELECT e.id, e.compte, e.date_ecriture, e.piece, e.intitule, e.debit, e.credit,
                    e.lettrage, e.counterpartie, j.code journal_code
               FROM tz_ecriture e JOIN tz_journal j ON j.id = e.journal_id
              WHERE e.date_ecriture BETWEEN ? AND ?
           ORDER BY j.code, e.date_ecriture, e.id',
            [$debut, $fin]
        );
        return array_map(static fn(array $e): array => [
            'JournalCode'   => $e['journal_code'],
            'JournalLib'    => $e['journal_code'],
            'EcritureNum'   => (int) $e['id'],
            'EcritureDate'  => str_replace('-', '', (string) $e['date_ecriture']),
            'CompteNum'     => $e['compte'],
            'CompteLib'     => $e['counterpartie'] ?? '',
            'EcritureLib'   => $e['intitule'],
            'Debit'         => number_format(((int) $e['debit']) / 1000, 3, '.', ''),
            'Credit'        => number_format(((int) $e['credit']) / 1000, 3, '.', ''),
            'LettrageNum'   => $e['lettrage'] ?? '',
            'ValidDate'     => str_replace('-', '', (string) $e['date_ecriture']),
            'PieceRef'      => $e['piece'] ?? '',
            'ecr_init'      => (int) $e['id'],
            'periodes'      => substr((string) $e['date_ecriture'], 0, 7),
        ], $lignes);
    }

    public static function lettrer(int $id, string $lettrage): void
    {
        Db::modifier('tz_ecriture', ['lettrage' => mb_substr(trim($lettrage), 0, 20)], 'id = ?', [$id]);
    }

    /**
     * Lettrage automatique : dans une même pièce, un débit est appairé au crédit
     * de même montant ; la paire reçoit la même référence de lettrage.
     *
     * @return int nombre d'écritures lettrées
     */
    public static function lettrerClient(string $compte = '411'): int
    {
        $lignes = Db::tous(
            "SELECT id, piece, debit, credit FROM tz_ecriture
              WHERE compte = ? AND (lettrage IS NULL OR lettrage = '') AND piece IS NOT NULL
           ORDER BY piece, id",
            [$compte]
        );
        $parPiece = [];
        foreach ($lignes as $ligne) {
            $parPiece[$ligne['piece']][] = $ligne;
        }

        $lettreees = 0;
        $numero = 1;
        foreach ($parPiece as $piece => $lignesDeLaPiece) {
            $debits = array_values(array_filter($lignesDeLaPiece, static fn(array $l): bool => (int) $l['debit'] > 0));
            $credits = array_values(array_filter($lignesDeLaPiece, static fn(array $l): bool => (int) $l['credit'] > 0));
            foreach ($debits as $debit) {
                foreach ($credits as $cle => $credit) {
                    if ((int) $credit['credit'] !== (int) $debit['debit']) {
                        continue;
                    }
                    $reference = sprintf('L%s-%d', substr(md5((string) $piece), 0, 6), $numero++);
                    self::lettrer((int) $debit['id'], $reference);
                    self::lettrer((int) $credit['id'], $reference);
                    unset($credits[$cle]);
                    $lettreees += 2;
                    continue 2;
                }
            }
        }
        return $lettreees;
    }

    public static function nonValidees(int $limite = 50): array
    {
        $limite = max(1, min(200, $limite));
        return Db::tous(
            "SELECT e.*, j.code journal_code FROM tz_ecriture e
               JOIN tz_journal j ON j.id = e.journal_id
              WHERE e.validee = 0 ORDER BY e.date_ecriture DESC LIMIT $limite"
        );
    }

    public static function valider(int $id, bool $validee = true): void
    {
        Db::modifier('tz_ecriture', ['validee' => (int) $validee], 'id = ?', [$id]);
    }

    /** Suppression d'une pièce complète, uniquement si elle n'est pas lettrée. */
    public static function supprimerPiece(string $piece): int
    {
        if (Db::compter('tz_ecriture', 'piece = ? AND lettrage IS NOT NULL AND lettrage <> ?', [$piece, '']) > 0) {
            throw new RuntimeException("La pièce $piece est lettrée : passage en écriture d'annulation requis.");
        }
        return Db::supprimer('tz_ecriture', 'piece = ?', [$piece]);
    }

    /** Comptes rencontrés dans les écritures, pour alimenter les filtres. */
    public static function comptes(): array
    {
        return Db::tous(
            'SELECT compte, COUNT(*) ecritures, COALESCE(SUM(debit), 0) debit, COALESCE(SUM(credit), 0) credit
               FROM tz_ecriture GROUP BY compte ORDER BY compte'
        );
    }

    private static function prepare(array $ligne): array
    {
        $debit = (int) ($ligne['debit'] ?? 0);
        $credit = (int) ($ligne['credit'] ?? 0);
        if ($debit === 0 && $credit === 0) {
            throw new RuntimeException('Une écriture doit porter un montant, au débit ou au crédit.');
        }
        $journalId = isset($ligne['journal_id']) ? (int) $ligne['journal_id'] : null;
        if ($journalId === null || $journalId <= 0) {
            $code = (string) ($ligne['journal'] ?? 'OD');
            $journal = Journal::parCode($code);
            if ($journal === null) {
                throw new RuntimeException("Journal comptable introuvable : « $code ».");
            }
            $journalId = (int) $journal['id'];
        }
        return [
            'journal_id'     => $journalId,
            'date_ecriture'  => ($ligne['date_ecriture'] ?? '') === '' ? date('Y-m-d') : $ligne['date_ecriture'],
            'piece'          => ($ligne['piece'] ?? '') === '' ? null : mb_substr((string) $ligne['piece'], 0, 40),
            'lettrage'       => ($ligne['lettrage'] ?? '') === '' ? null : mb_substr((string) $ligne['lettrage'], 0, 20),
            'compte'         => mb_substr((string) $ligne['compte'], 0, 7),
            'intitule'       => mb_substr((string) ($ligne['intitule'] ?? ''), 0, 200),
            'debit'          => $debit,
            'credit'         => $credit,
            'counterpartie'  => ($ligne['counterpartie'] ?? '') === '' ? null : mb_substr((string) $ligne['counterpartie'], 0, 120),
            'commande_id'    => ($ligne['commande_id'] ?? null) === null ? null : (int) $ligne['commande_id'],
            'facture_id'     => ($ligne['facture_id'] ?? null) === null ? null : (int) $ligne['facture_id'],
            'enseigne_id'    => ($ligne['enseigne_id'] ?? null) === null ? null : (int) $ligne['enseigne_id'],
            'tva_taux'       => ($ligne['tva_taux'] ?? null) === null ? null : (int) $ligne['tva_taux'],
            'validee'        => (int) ($ligne['validee'] ?? 1),
            'utilisateur_id' => ($ligne['utilisateur_id'] ?? null) === null ? Auth::idAdmin() : (int) $ligne['utilisateur_id'],
        ];
    }
}
