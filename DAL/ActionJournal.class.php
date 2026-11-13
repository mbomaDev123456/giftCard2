<?php
/** Trace de tout ce que fait l'administration, et les retours de la passerelle. */
class ActionJournal
{
    public static function noter(string $action, ?string $cibleType = null, ?int $cibleId = null,
                                 array $details = [], string $acteur = 'admin', ?int $utilisateurId = null): void
    {
        try {
            Db::inserer('tz_action_journal', [
                'utilisateur_id' => $utilisateurId ?? Auth::idAdmin(),
                'client_id'      => Auth::idClient(),
                'acteur'         => $acteur,
                'action'         => mb_substr($action, 0, 80),
                'cible_type'     => $cibleType === null ? null : mb_substr($cibleType, 0, 40),
                'cible_id'       => $cibleId,
                'details'        => $details === [] ? null : json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'ip'             => Http::ip(),
            ]);
        } catch (Throwable $e) {
            tz_log('Journal', 'écriture impossible: ' . $e->getMessage());
        }
    }

    public static function Derniers(array $f = []): array
    {
        $conditions = ['1=1'];
        $valeurs = [];
        if (!empty($f['acteur'])) {
            $conditions[] = 'j.acteur = ?';
            $valeurs[] = $f['acteur'];
        }
        if (!empty($f['action'])) {
            $conditions[] = 'j.action LIKE ?';
            $valeurs[] = '%' . $f['action'] . '%';
        }
        if (!empty($f['cible_id']) && !empty($f['cible_type'])) {
            $conditions[] = 'j.cible_type = ? AND j.cible_id = ?';
            $valeurs[] = $f['cible_type'];
            $valeurs[] = (int) $f['cible_id'];
        }
        if (!empty($f['du'])) {
            $conditions[] = 'j.created_at >= ?';
            $valeurs[] = $f['du'] . ' 00:00:00';
        }
        $where = implode(' AND ', $conditions);
        $parPage = 60;
        $page = max(1, (int) ($f['page'] ?? 1));
        return [
            'total'  => (int) Db::valeur("SELECT COUNT(*) FROM tz_action_journal j WHERE $where", $valeurs),
            'lignes' => Db::tous(
                "SELECT j.*, u.login, p.nom personne
                   FROM tz_action_journal j
              LEFT JOIN tz_utilisateur u ON u.id = j.utilisateur_id
                  WHERE $where
               ORDER BY j.created_at DESC, j.id DESC
                  LIMIT $parPage OFFSET " . (($page - 1) * $parPage),
                $valeurs
            ),
        ];
    }

    public static function compterParAction(string $du, string $au): array
    {
        return Db::tous(
            'SELECT action, acteur, COUNT(*) nb FROM tz_action_journal
              WHERE created_at BETWEEN ? AND ?
           GROUP BY action, acteur ORDER BY nb DESC LIMIT 40',
            [$du . ' 00:00:00', $au . ' 23:59:59']
        );
    }
}
