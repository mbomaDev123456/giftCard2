<?php
/**
 * Tous les montants de l'application circulent en millimes (entier),
 * comme dans gpg_* : 35 TND = 35000. Le formatage imite Format::formatFolat().
 */
class Money
{
    public const MILLI_PER_DT = 1000;

    public static function formater(int $millimes, bool $avecSuffixe = true): string
    {
        $signe = $millimes < 0 ? '-' : '';
        $texte = number_format((int) abs($millimes) / self::MILLI_PER_DT, 3, ',', ' ');
        return $signe . $texte . ($avecSuffixe ? ' DT' : '');
    }

    /** Saisie tolérée : "1 250,500", "1250.500", "1250,5", "1250 DT" → millimes. */
    public static function analyser(string $saisie): int
    {
        $brut = preg_replace('/[^\d.,\-]/u', '', $saisie) ?? '';
        $virgule = strrpos($brut, ',');
        $point   = strrpos($brut, '.');
        if ($virgule !== false && ($point === false || $virgule > $point)) {
            $brut = str_replace('.', '', $brut);
            $brut = substr_replace($brut, '.', $virgule, 1);
        } else {
            $brut = str_replace(',', '', $brut);
        }
        if ($brut === '' || $brut === '-' || $brut === '.') {
            return 0;
        }
        return (int) round((float) $brut * self::MILLI_PER_DT);
    }

    /** Millimes -> valeur affichable dans un champ de saisie. */
    public static function pourSaisie(int $millimes): string
    {
        return number_format($millimes / self::MILLI_PER_DT, 3, '.', '');
    }

    /**
     * Décompose un TTC en HT + TVA.
     * @return array{ht:int,tva:int,ttc:int}
     */
    public static function decomposerTva(int $ttc, int $tauxMilliemes): array
    {
        $ht = (int) round($ttc * 100000 / (100000 + $tauxMilliemes));
        return ['ht' => $ht, 'tva' => $ttc - $ht, 'ttc' => $ttc];
    }

    public static function tva(int $ht, int $tauxMilliemes): int
    {
        return (int) round($ht * $tauxMilliemes / 100000);
    }

    public static function quantitePrix(int $prixUnitaire, int $quantite): int
    {
        return $prixUnitaire * $quantite;
    }

    public static function tauxPourLabel(int $tauxMilliemes): string
    {
        return rtrim(rtrim(number_format($tauxMilliemes / 1000, 2, ',', ''), '0'), ',') . ' %';
    }
}
