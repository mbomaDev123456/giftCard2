/*
 * Tozeur Shop — amélioration progressive.
 * Tout le site fonctionne sans ce fichier : les gestes sont des formulaires POST.
 * Ici on remplace le rechargement de page par du JSON pour les gestes de panier,
 * on tient le tiroir, les menus, la galerie et les compteurs de publicité.
 */
(function () {
    'use strict';

    var doc = document;
    var corps = doc.body;
    var page = corps ? corps.getAttribute('data-page') || '' : '';
    var surPanier = page === '/panier';

    function $(sel, racine) { return (racine || doc).querySelector(sel); }
    function $$(sel, racine) { return Array.prototype.slice.call((racine || doc).querySelectorAll(sel)); }

    /* ------------------------------------------------------------ monnaie */

    function dt(millimes) {
        var valeur = (Number(millimes) || 0) / 1000;
        try {
            return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 3, maximumFractionDigits: 3 }).format(valeur) + ' DT';
        } catch (e) {
            return valeur.toFixed(3) + ' DT';
        }
    }

    /* -------------------------------------------------------------- alerte */

    function alerte(message, genre) {
        if (!message) { return; }
        var zone = $('.alertes');
        if (!zone) {
            zone = doc.createElement('div');
            zone.className = 'alertes';
            zone.setAttribute('role', 'status');
            var bandeau = $('.bandeau-haut');
            if (bandeau && bandeau.parentNode) {
                bandeau.parentNode.insertBefore(zone, bandeau.nextSibling);
            } else {
                corps.insertBefore(zone, corps.firstChild);
            }
        }
        var p = doc.createElement('p');
        p.className = 'alerte alerte-' + (genre || 'juste');
        p.textContent = message;
        zone.appendChild(p);
        setTimeout(function () {
            p.style.transition = 'opacity .4s';
            p.style.opacity = '0';
            setTimeout(function () { if (p.parentNode) { p.parentNode.removeChild(p); } }, 450);
        }, 5200);
    }

    /* --------------------------------------------------------------- menus */

    var burger = $('[data-burger]');
    var menu = $('#menu-principal');
    if (burger && menu) {
        burger.addEventListener('click', function () {
            var ouvert = menu.classList.toggle('ouvert');
            burger.setAttribute('aria-expanded', ouvert ? 'true' : 'false');
        });
    }

    // Sous-menus : au clavier le focus suffit, au tactile le premier toucher ouvre.
    $$('.possede-sous').forEach(function (parent) {
        var lien = $(':scope > a', parent);
        if (!lien) { return; }
        lien.addEventListener('click', function (ev) {
            if (window.matchMedia('(min-width: 1081px)').matches) { return; }
            if (parent.classList.contains('ouvert')) { return; }
            ev.preventDefault();
            $$('.possede-sous.ouvert').forEach(function (autre) { if (autre !== parent) { autre.classList.remove('ouvert'); } });
            parent.classList.add('ouvert');
        });
    });

    $$('.filtres-accroche a').forEach(function (bouton) {
        bouton.addEventListener('click', function (ev) {
            ev.preventDefault();
            var bloc = bouton.closest('.filtres');
            if (bloc) {
                bloc.classList.toggle('ouvert');
                if (bloc.classList.contains('ouvert') && window.innerWidth > 1080) { bloc.classList.remove('ouvert'); }
            }
        });
    });

    /* ------------------------------------------------- formulaires diffères */

    // Un <form data-defer> se soumet dès qu'on change un select ou une case :
    // les champs nombre restent sous la main de l'utilisateur.
    $$('form[data-defer]').forEach(function (forme) {
        forme.addEventListener('change', function (ev) {
            var champ = ev.target;
            if (!champ) { return; }
            if (champ.tagName !== 'SELECT' && champ.type !== 'checkbox') { return; }
            forme.submit();
        });
    });

    $$('[data-confirm]').forEach(function (bouton) {
        bouton.addEventListener('click', function (ev) {
            if (!window.confirm(bouton.getAttribute('data-confirm'))) {
                ev.preventDefault();
                ev.stopImmediatePropagation();
            }
        });
    });

    /* ------------------------------------------------------------- galerie */

    var principal = $('#visuel-principal');
    $$('[data-visuel]').forEach(function (bouton) {
        bouton.addEventListener('click', function () {
            if (!principal) { return; }
            principal.src = bouton.getAttribute('data-visuel');
            $$('[data-visuel]').forEach(function (autre) { autre.classList.remove('choix'); });
            bouton.classList.add('choix');
        });
    });

    /* ---------------------------------------------------------- quantités */

    $$('[data-pas]').forEach(function (bouton) {
        bouton.addEventListener('click', function () {
            var boite = bouton.closest('.quantite-boite');
            var champ = boite ? $('input', boite) : null;
            if (!champ) { return; }
            var pas = parseInt(bouton.getAttribute('data-pas'), 10) || 1;
            var mini = parseInt(champ.getAttribute('min'), 10);
            var maxi = parseInt(champ.getAttribute('max'), 10);
            var valeur = (parseInt(champ.value, 10) || 0) + pas;
            if (!isNaN(mini)) { valeur = Math.max(mini, valeur); }
            if (!isNaN(maxi)) { valeur = Math.min(maxi, valeur); }
            champ.value = valeur;
            champ.dispatchEvent(new Event('change', { bubbles: true }));
        });
    });

    /* ------------------------------------------------- choix du format */

    var achat = $('[data-achat]');
    if (achat) {
        var affiche = $('[data-prix-affiche]', achat);
        var barre = $('[data-prix-barre]', achat);
        var restant = $('[data-restant-affiche]', achat);
        var valider = $('button[type="submit"]', achat);

        var rendre = function (format) {
            var prix = parseInt(format.getAttribute('data-prix'), 10) || 0;
            var plein = parseInt(format.getAttribute('data-barre'), 10) || 0;
            var dispo = parseInt(format.getAttribute('data-restant'), 10) || 0;
            if (affiche) { affiche.textContent = dt(prix); }
            if (barre) {
                if (plein > prix && prix >= 0) {
                    barre.textContent = dt(plein);
                    barre.removeAttribute('hidden');
                } else {
                    barre.setAttribute('hidden', 'hidden');
                }
            }
            if (restant) {
                restant.textContent = dispo === 0
                    ? 'Réassort en cours auprès de la maison.'
                    : (dispo <= 3 ? 'Plus que ' + dispo + ' pièces disponibles.' : 'En stock — expédié sous 48 h ouvrées.');
            }
            if (valider) { valider.disabled = dispo === 0 || prix <= 0; }
        };

        $$('input[name="variante_id"]', achat).forEach(function (format) {
            format.addEventListener('change', function () { rendre(format); });
        });

        var choisi = $('input[name="variante_id"]:checked', achat) || $('input[name="variante_id"]', achat);
        if (choisi) { rendre(choisi); }
    }

    /* ---------------------------------------------------------------- tiroir */

    var voile = $('[data-voile]');
    var tiroir = voile ? $('.tiroir', voile) : null;
    var dernierFocus = null;

    function ouvrirTiroir() {
        if (!voile || voile.getAttribute('data-visible') === '1') { return; }
        dernierFocus = doc.activeElement;
        voile.hidden = false;
        requestAnimationFrame(function () { voile.setAttribute('data-visible', '1'); });
        doc.documentElement.classList.add('sans-defilement');
        var fermer = $('[data-fermer]', voile);
        if (fermer) { fermer.focus(); }
    }

    function fermerTiroir() {
        if (!voile || voile.hidden) { return; }
        voile.setAttribute('data-visible', '0');
        doc.documentElement.classList.remove('sans-defilement');
        setTimeout(function () { voile.hidden = true; }, 220);
        if (dernierFocus && dernierFocus.focus) { dernierFocus.focus(); }
    }

    $$('[data-ouvrir-panier]').forEach(function (bouton) {
        bouton.addEventListener('click', function (ev) {
            ev.preventDefault();
            ouvrirTiroir();
        });
    });
    $$('[data-fermer]').forEach(function (bouton) { bouton.addEventListener('click', fermerTiroir); });
    if (voile) {
        voile.addEventListener('click', function (ev) { if (ev.target === voile) { fermerTiroir(); } });
    }
    doc.addEventListener('keydown', function (ev) {
        if (ev.key === 'Escape') { fermerTiroir(); }
    });
    if (tiroir) {
        tiroir.addEventListener('keydown', function (ev) {
            if (ev.key !== 'Tab') { return; }
            var champs = $$('a[href], button, input, select, textarea', tiroir)
                .filter(function (c) { return !c.disabled && c.offsetParent !== null; });
            if (champs.length === 0) { return; }
            var premier = champs[0], dernier = champs[champs.length - 1];
            if (ev.shiftKey && doc.activeElement === premier) { ev.preventDefault(); dernier.focus(); }
            else if (!ev.shiftKey && doc.activeElement === dernier) { ev.preventDefault(); premier.focus(); }
        });
    }

    function peindreTiroir(panier) {
        var zone = $('[data-tiroir-corps]');
        if (!zone) { return; }
        zone.textContent = '';
        if (!panier || !panier.lignes || panier.lignes.length === 0) {
            var vide = doc.createElement('p');
            vide.className = 'tiroir-vide';
            vide.textContent = 'Votre panier est vide.';
            zone.appendChild(vide);
            return;
        }
        panier.lignes.forEach(function (ligne) {
            var article = doc.createElement('div');
            article.className = 'article';

            var visuel;
            if (ligne.image_url) {
                visuel = doc.createElement('img');
                visuel.src = ligne.image_url;
                visuel.alt = '';
                visuel.width = 56;
                visuel.height = 56;
                visuel.loading = 'lazy';
            } else {
                visuel = doc.createElement('span');
                visuel.className = 'sans-image';
                visuel.textContent = (ligne.enseigne_nom || 'T').charAt(0).toUpperCase();
            }
            article.appendChild(visuel);

            var texte = doc.createElement('span');
            var titre = doc.createElement('strong');
            titre.textContent = ligne.designation;
            var detail = doc.createElement('small');
            detail.textContent = ligne.variante_nom + ' · ' + ligne.quantite + ' × ' + dt(ligne.prix_unitaire);
            texte.appendChild(titre);
            texte.appendChild(detail);
            article.appendChild(texte);

            var total = doc.createElement('span');
            total.textContent = dt(ligne.total_brut - ligne.remise_ligne);
            article.appendChild(total);

            zone.appendChild(article);
        });
    }

    function peindre(panier) {
        var chiffre = $('[data-panier-nb]');
        var montant = $('[data-panier-montant]');
        var tiroirMontant = $('[data-tiroir-montant]');
        if (chiffre) { chiffre.textContent = panier.articles; }
        if (montant) { montant.textContent = dt(panier.total_ttc); }
        if (tiroirMontant) { tiroirMontant.textContent = dt(panier.total_ttc); }
        peindreTiroir(panier);
    }

    /* -------------------------------------------------------- gestes JSON */

    function envoyer(forme) {
        var donnees = new FormData(forme);
        return fetch(forme.action, {
            method: 'POST',
            body: donnees,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            credentials: 'same-origin'
        }).then(function (reponse) {
            return reponse.json().catch(function () { return {}; }).then(function (json) {
                if (!reponse.ok || json.ok === false) {
                    throw new Error(json.erreur || 'Le geste a été refusé.');
                }
                return json;
            });
        });
    }

    $$('form[data-panier-form], form[data-achat]').forEach(function (forme) {
        // Sur la page panier, la table et le récapitulatif viennent du serveur :
        // laisser le POST classique reconstruire la page entière.
        if (surPanier && forme.hasAttribute('data-panier-form')) { return; }

        forme.addEventListener('submit', function (ev) {
            var bouton = $('button[type="submit"]', forme);
            if (bouton && bouton.disabled) { return; }
            ev.preventDefault();
            if (bouton) { bouton.disabled = true; }

            envoyer(forme).then(function (json) {
                if (bouton) { bouton.disabled = false; }
                peindre(json);
                alerte(json.message, 'juste');
                ouvrirTiroir();
            }).catch(function (erreur) {
                if (bouton) { bouton.disabled = false; }
                alerte(erreur.message, 'faute');
                if (erreur.message === 'Le geste a été refusé.') { forme.submit(); }
            });
        });
    });

    // La table du panier : un champ quantité modifié et quitté vaut une mise à jour.
    if (surPanier) {
        $$('[data-quanti]').forEach(function (champ) {
            champ.addEventListener('change', function () {
                var forme = champ.closest('form');
                if (forme) { forme.submit(); }
            });
        });
    }

    /* ------------------------------------------------------------- pub * */

    $$('[data-banniere-clic]').forEach(function (lien) {
        lien.addEventListener('click', function () {
            var identifiant = lien.getAttribute('data-banniere-clic');
            var charge = JSON.stringify({ banniere_id: identifiant });
            if (navigator.sendBeacon) {
                navigator.sendBeacon('/ws/banniere/clic', new Blob([charge], { type: 'application/json' }));
            }
        });
    });

    $$('.offre-code').forEach(function (code) {
        code.addEventListener('click', function () {
            var texte = code.textContent.trim();
            if (!navigator.clipboard) { return; }
            navigator.clipboard.writeText(texte).then(function () {
                alerte('Code ' + texte + ' copié : collez-le dans le panier.', 'note');
            }, function () { /* le visiteur peut toujours le recopier */ });
        });
    });
})();
