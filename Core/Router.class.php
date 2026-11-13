<?php
/** Aiguillement par motifs : /produit/{slug} → Front->produitAction. */
class Router
{
    /** @var array<string, array<int, array{0:string,1:string,2:array<int,string>,3:callable|array}>> */
    private array $table = [];

    public function get(string $motif, callable|array $traitant): void
    {
        $this->inscrire('GET', $motif, $traitant);
    }

    public function post(string $motif, callable|array $traitant): void
    {
        $this->inscrire('POST', $motif, $traitant);
    }

    public function any(string $motif, callable|array $traitant): void
    {
        $this->inscrire('GET', $motif, $traitant);
        $this->inscrire('POST', $motif, $traitant);
    }

    private function inscrire(string $methode, string $motif, callable|array $traitant): void
    {
        $cleefs = [];
        $regex = preg_replace_callback(
            '#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#',
            static function (array $m) use (&$cleefs): string {
                $cleefs[] = $m[1];
                return '([^/]+)';
            },
            $motif
        );
        $this->table[$methode][] = ['#^' . $regex . '$#', $motif, $cleefs, $traitant];
    }

    public function dispatcher(string $chemin, string $methode): mixed
    {
        foreach ($this->table[$methode] ?? [] as [$regex, $motif, $cleefs, $traitant]) {
            if (preg_match($regex, $chemin, $m)) {
                array_shift($m);
                $params = array_combine($cleefs, $m) ?: [];
                return $this->invoquer($traitant, $params);
            }
        }
        // Même chemin en GET alors qu'il n'existe qu'en POST (ou l'inverse) : on le dit.
        foreach ($this->table as $methodeConnue => $lignes) {
            if ($methodeConnue === $methode) {
                continue;
            }
            foreach ($lignes as [$regex]) {
                if (preg_match($regex, $chemin)) {
                    Http::code(405);
                    return Erreur::page(405, 'Cette adresse n’accepte que la méthode ' . $methodeConnue . '.');
                }
            }
        }
        Http::code(404);
        return Erreur::page(404, 'Page introuvable.');
    }

    private function invoquer(callable|array $traitant, array $params): mixed
    {
        if (is_array($traitant)) {
            [$nom, $action] = $traitant;
            $controleur = new $nom();
            return $controleur->{$action . 'Action'}($params);
        }
        return $traitant($params);
    }
}
