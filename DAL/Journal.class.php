<?php
/** Journaux comptables : VE, AC, BN, CA, ET, RS, OD. */
class Journal
{
    public const NATURES = ['ventes', 'achats', 'banque', 'caisse', 'etre', 'od'];

    public static function parId(int $id): ?array
    {
        return Db::un('SELECT * FROM tz_journal WHERE id = ?', [$id]);
    }

    public static function parCode(string $code): ?array
    {
        return Db::un('SELECT * FROM tz_journal WHERE code = ? AND etat = ?', [mb_strtoupper(trim($code)), 'actif']);
    }

    /** Journal par nature, avec repli sur OD : une écriture doit toujours trouver un journal. */
    public static function pourNature(string $nature): array
    {
        $journal = Db::un("SELECT * FROM tz_journal WHERE nature = ? AND etat = 'actif' ORDER BY id LIMIT 1",
            [$nature]);
        if ($journal !== null) {
            return $journal;
        }
        $repli = Db::un("SELECT * FROM tz_journal WHERE nature = 'od' AND etat = 'actif' ORDER BY id LIMIT 1");
        if ($repli === null) {
            throw new RuntimeException("Aucun journal pour la nature « $nature » et aucun journal OD disponible.");
        }
        return $repli;
    }

    public static function tous(): array
    {
        return Db::tous('SELECT * FROM tz_journal ORDER BY nature, code');
    }

    public static function actifs(): array
    {
        return Db::tous("SELECT * FROM tz_journal WHERE etat = 'actif' ORDER BY nature, code");
    }

    public static function idParCode(string $code): ?int
    {
        $journal = self::parCode($code);
        return $journal === null ? null : (int) $journal['id'];
    }

    public static function enregistrer(array $d, ?int $id = null): int
    {
        $champs = [
            'code'    => mb_strtoupper(mb_substr(trim((string) $d['code']), 0, 4)),
            'libelle' => trim((string) $d['libelle']),
            'nature'  => in_array($d['nature'] ?? '', self::NATURES, true) ? $d['nature'] : 'od',
            'devise'  => mb_strtoupper(trim((string) ($d['devise'] ?? 'TND'))),
            'etat'    => ($d['etat'] ?? 'actif') === 'archive' ? 'archive' : 'actif',
        ];
        if ($id === null) {
            if (Db::compter('tz_journal', 'code = ?', [$champs['code']]) > 0) {
                throw new RuntimeException('Ce code de journal existe déjà : ' . $champs['code']);
            }
            return Db::inserer('tz_journal', $champs);
        }
        Db::modifier('tz_journal', $champs, 'id = ?', [$id]);
        return $id;
    }

    /** Volume par journal, pour l'écran de contrôle. */
    public static function statistiques(?string $debut = null, ?string $fin = null): array
    {
        $conditions = ['1'];
        $params = [];
        if ($debut !== null && $debut !== '') {
            $conditions[] = 'e.date_ecriture >= ?';
            $params[] = $debut;
        }
        if ($fin !== null && $fin !== '') {
            $conditions[] = 'e.date_ecriture <= ?';
            $params[] = $fin;
        }
        return Db::tous(
            'SELECT j.id, j.code, j.libelle, j.nature, j.etat,
                    COUNT(e.id) ecritures,
                    COALESCE(SUM(e.debit), 0) debit,
                    COALESCE(SUM(e.credit), 0) credit
               FROM tz_journal j
               LEFT JOIN tz_ecriture e ON e.journal_id = j.id AND ' . implode(' AND ', $conditions) . '
           GROUP BY j.id, j.code, j.libelle, j.nature, j.etat
           ORDER BY j.nature, j.code',
            $params
        );
    }
}
