<?php
/** Carnet d'adresses du client. */
class Adresse
{
    public static function pourClient(int $clientId): array
    {
        return Db::tous('SELECT * FROM tz_adresse WHERE client_id = ? ORDER BY par_defaut DESC, id', [$clientId]);
    }

    public static function parId(int $id, int $clientId): ?array
    {
        return Db::un('SELECT * FROM tz_adresse WHERE id = ? AND client_id = ?', [$id, $clientId]);
    }

    public static function enregistrer(array $d, ?int $id = null): int
    {
        $champs = [
            'client_id'  => (int) $d['client_id'],
            'libelle'    => trim((string) ($d['libelle'] ?? 'Domicile')),
            'prenom'     => ($d['prenom'] ?? '') === '' ? null : $d['prenom'],
            'nom'        => ($d['nom'] ?? '') === '' ? null : $d['nom'],
            'telephone'  => ($d['telephone'] ?? '') === '' ? null : $d['telephone'],
            'ligne1'     => trim((string) $d['ligne1']),
            'ligne2'     => ($d['ligne2'] ?? '') === '' ? null : $d['ligne2'],
            'ville'      => trim((string) $d['ville']),
            'delegation' => ($d['delegation'] ?? '') === '' ? null : $d['delegation'],
            'code_postal'=> ($d['code_postal'] ?? '') === '' ? null : $d['code_postal'],
            'pays'       => strtoupper(trim((string) ($d['pays'] ?? 'TN'))) ?: 'TN',
        ];
        if ($id === null) {
            $id = Db::inserer('tz_adresse', $champs);
        } else {
            Db::modifier('tz_adresse', $champs, 'id = ? AND client_id = ?', [$id, (int) $d['client_id']]);
        }
        if (!empty($d['par_defaut'])) {
            Db::modifier('tz_adresse', ['par_defaut' => 0], 'client_id = ? AND id <> ?', [(int) $d['client_id'], $id]);
            Db::modifier('tz_adresse', ['par_defaut' => 1], 'id = ?', [$id]);
        }
        return $id;
    }

    public static function supprimer(int $id, int $clientId): void
    {
        Db::supprimer('tz_adresse', 'id = ? AND client_id = ?', [$id, $clientId]);
    }
}
