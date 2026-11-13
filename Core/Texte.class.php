<?php
/**
 * Contenu éditorial (pages, histoires de maison) : du HTML saisi en régie,
 * rendu public. On ne fait confiance qu'à une liste courte de balises et
 * d'attributs ; tout le reste tombe, attribut par attribut.
 */
class Texte
{
    /** @var array<string, array<int, string>|null> balise => attributs tolérés, null = aucun */
    private const DICTEE = [
        'p' => null, 'br' => null, 'hr' => null, 'span' => null, 'small' => null,
        'strong' => null, 'b' => null, 'em' => null, 'i' => null, 'u' => null,
        's' => null, 'del' => null, 'ins' => null, 'sub' => null, 'sup' => null,
        'ul' => null, 'ol' => null, 'li' => null,
        'dl' => null, 'dt' => null, 'dd' => null,
        'blockquote' => null, 'code' => null, 'pre' => null,
        'h2' => null, 'h3' => null, 'h4' => null, 'h5' => null,
        'table' => null, 'thead' => null, 'tbody' => null, 'tfoot' => null,
        'tr' => null, 'th' => ['colspan', 'rowspan', 'scope'], 'td' => ['colspan', 'rowspan'],
        'figure' => null, 'figcaption' => null,
        'a' => ['href', 'title'],
        'img' => ['src', 'alt'],
    ];

    private const VIDES = ['br', 'hr', 'img'];

    /** URLs autorisées dans href/src : le site, le téléphone, le mail, l'http(s). */
    private const SCHEMES = '#^\s*(?:https?:|mailto:|tel:|/|\#)#i';

    public static function propre(?string $brut): string
    {
        $html = trim((string) $brut);
        if ($html === '') {
            return '';
        }
        $tolerees = '#<' . implode('><', array_keys(self::DICTEE)) . '>';
        $html = strip_tags($html, $tolerees);

        // Les balises refusées par strip_tags laissent leur texte ; un reste de
        // commentaire ou de CDATA ne doit jamais repasser en balise.
        $html = (string) preg_replace('/<!--.*?-->|<!\[CDATA\[.*?\]\]>/s', '', $html);

        return (string) preg_replace_callback(
            '#<\s*(/?)\s*([a-z0-9]+)((?:\s+[^<>]*?)?)(/?)\s*>#i',
            static function (array $m): string {
                $fermante = $m[1] === '/';
                $balise = strtolower($m[2]);
                if (!array_key_exists($balise, self::DICTEE)) {
                    return '';
                }
                if ($fermante) {
                    return in_array($balise, self::VIDES, true) ? '' : '</' . $balise . '>';
                }
                $attributs = self::attributs($balise, $m[3]);
                return '<' . $balise . $attributs . '>';
            },
            $html
        );
    }

    private static function attributs(string $balise, string $brut): string
    {
        $toleres = self::DICTEE[$balise] ?? [];
        if ($toleres === []) {
            return '';
        }
        $sorties = [];
        if (preg_match_all('/([a-zA-Z_:][-a-zA-Z0-9_:.]*)\s*=\s*("([^"]*)"|\'([^\']*)\'|([^\s"\'>]+))/', $brut, $paires, PREG_SET_ORDER) === false) {
            return '';
        }
        foreach ($paires as $paire) {
            $nom = strtolower($paire[1]);
            $valeur = $paire[3] ?? $paire[4] ?? $paire[5] ?? '';
            if (!in_array($nom, $toleres, true)) {
                continue;
            }
            if (in_array($nom, ['href', 'src'], true)) {
                $valeur = trim($valeur);
                if (preg_match(self::SCHEMES, $valeur) !== 1) {
                    continue;
                }
            }
            $sorties[] = $nom . '="' . htmlspecialchars($valeur, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
        }
        return $sorties === [] ? '' : ' ' . implode(' ', $sorties);
    }
}
