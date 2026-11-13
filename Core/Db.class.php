<?php
/** Connexion mysqli + requêtes préparées uniquement. */
class Db
{
    private static ?mysqli $lien = null;
    private static int $profondeur = 0;

    public static function lien(): mysqli
    {
        if (self::$lien instanceof mysqli) {
            return self::$lien;
        }
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $lien = @new mysqli(TZ_DB_HOST, TZ_DB_USER, TZ_DB_PASS, TZ_DB_NAME, TZ_DB_PORT);
        $lien->set_charset('utf8mb4');
        $lien->query("SET time_zone = '+01:00', SESSION sql_mode = 'STRICT_ALL_TABLES,NO_ENGINE_SUBSTITUTION'");
        self::$lien = $lien;
        return self::$lien;
    }

    /** @return array<int, array<string, mixed>> */
    public static function tous(string $sql, array $params = []): array
    {
        return self::lire($sql, $params)->fetch_all(MYSQLI_ASSOC);
    }

    public static function un(string $sql, array $params = []): ?array
    {
        $ligne = self::lire($sql, $params)->fetch_assoc();
        return $ligne ?? null;
    }

    public static function valeur(string $sql, array $params = []): mixed
    {
        $ligne = self::lire($sql, $params)->fetch_row();
        return $ligne[0] ?? null;
    }

    public static function inserer(string $table, array $donnees): int
    {
        $colonnes = array_keys($donnees);
        $sql = sprintf(
            'INSERT INTO `%s` (`%s`) VALUES (%s)',
            $table,
            implode('`, `', $colonnes),
            implode(', ', array_fill(0, count($colonnes), '?'))
        );
        $declaration = self::executer($sql, array_values($donnees));
        return (int) $declaration->insert_id;
    }

    /**
     * @param array<string, string> $donnees colonne => valeur
     * @param array<int, string> $brutes colonnes dont la valeur est du SQL brut (vues + 1, ...)
     */
    public static function modifier(string $table, array $donnees, string $where, array $whereParams = [], array $brutes = []): int
    {
        $affectations = [];
        $params = [];
        foreach ($donnees as $colonne => $valeur) {
            if (in_array($colonne, $brutes, true)) {
                $affectations[] = sprintf('`%s` = %s', $colonne, $valeur);
                continue;
            }
            $affectations[] = sprintf('`%s` = ?', $colonne);
            $params[] = $valeur;
        }
        $sql = sprintf('UPDATE `%s` SET %s WHERE %s', $table, implode(', ', $affectations), $where);
        return self::executer($sql, array_merge($params, $whereParams))->affected_rows;
    }

    public static function supprimer(string $table, string $where, array $params = []): int
    {
        return self::executer(sprintf('DELETE FROM `%s` WHERE %s', $table, $where), $params)->affected_rows;
    }

    public static function compter(string $table, string $where = '1', array $params = []): int
    {
        return (int) self::valeur(sprintf('SELECT COUNT(*) FROM `%s` WHERE %s', $table, $where), $params);
    }

    /**
     * Transaction imbricable : MySQL commite implicitement sur un second
     * BEGIN, donc seul le niveau exterior ouvre et solde la transaction.
     * Un échec interne remonte et fait rollbacker l'ensemble.
     */
    public static function transaction(callable $travail): mixed
    {
        $lien = self::lien();
        if (self::$profondeur === 0) {
            $lien->begin_transaction();
        }
        self::$profondeur++;
        try {
            $resultat = $travail($lien);
        } catch (Throwable $e) {
            self::$profondeur--;
            if (self::$profondeur === 0) {
                $lien->rollback();
            }
            throw $e;
        }
        self::$profondeur--;
        if (self::$profondeur === 0) {
            $lien->commit();
        }
        return $resultat;
    }

    /** Vrai pendant une transaction ouverte : les DAL évitent alors un BEGIN inutile. */
    public static function enTransaction(): bool
    {
        return self::$profondeur > 0;
    }

    /** SELECT : renvoie le jeu de résultats. */
    private static function lire(string $sql, array $params): mysqli_result
    {
        $declaration = self::executer($sql, $params);
        $resultat = $declaration->get_result();
        if ($resultat === false) {
            throw new RuntimeException('Cette requête ne produit pas de jeu de résultats : ' . $sql);
        }
        return $resultat;
    }

    /**
     * Prépare et exécute. INSERT/UPDATE/DELETE n'ont pas de jeu de résultats :
     * c'est `executer()` qui les sert, `lire()` ajoute l'appel à get_result().
     */
    private static function executer(string $sql, array $params): mysqli_stmt
    {
        $declaration = self::lien()->prepare($sql);
        if ($declaration === false) {
            throw new RuntimeException('Requête invalide : ' . self::lien()->error . ' — ' . $sql);
        }
        if ($params !== []) {
            $valeurs = [];
            $pointeurs = [];
            foreach (array_values($params) as $i => $valeur) {
                $valeurs[$i] = self::normaliser($valeur);
                $pointeurs[$i] = &$valeurs[$i];
            }
            $declaration->bind_param(self::types($params), ...$pointeurs);
        }
        if (!$declaration->execute()) {
            throw new RuntimeException('Exécution refusée : ' . $declaration->error . ' — ' . $sql);
        }
        return $declaration;
    }

    private static function normaliser(mixed $valeur): mixed
    {
        if (is_bool($valeur)) {
            return (int) $valeur;
        }
        if ($valeur === null || is_int($valeur) || is_string($valeur) || is_float($valeur)) {
            return $valeur;
        }
        return (string) $valeur;
    }

    private static function types(array $params): string
    {
        $types = '';
        foreach ($params as $valeur) {
            $types .= match (true) {
                is_int($valeur) => 'i',
                is_float($valeur) => 'd',
                default => 's',
            };
        }
        return $types;
    }
}
