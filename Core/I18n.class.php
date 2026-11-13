<?php
/** Traductions FR de plein droit, AR pour la vitrine (l'admin reste en FR). */
class I18n
{
    private static string $langue = 'fr';

    private const COMMUNE = [
        'fr' => [],
        'ar' => [],
    ];

    private const VITRINE = [
        'enseigne.nav'          => ['fr' => 'Nos maisons',          'ar' => 'دورنا'],
        'enseigne.toutes'       => ['fr' => 'Toutes les maisons',   'ar' => 'كل الدور'],
        'enseigne.visiter'      => ['fr' => 'Découvrir',            'ar' => 'اكتشف'],
        'nav.accueil'           => ['fr' => 'Accueil',              'ar' => 'الرئيسية'],
        'nav.boutique'          => ['fr' => 'Boutique',             'ar' => 'المتجر'],
        'nav.catalogue'         => ['fr' => 'Tous les produits',    'ar' => 'كل المنتجات'],
        'nav.promos'            => ['fr' => 'Promotions',           'ar' => 'العروض'],
        'nav.livraison'         => ['fr' => 'Livraison',            'ar' => 'التوصيل'],
        'nav.contact'           => ['fr' => 'Contact',              'ar' => 'اتصل بنا'],
        'nav.histoire'          => ['fr' => 'Notre histoire',       'ar' => 'قصتنا'],
        'panier'                => ['fr' => 'Panier',               'ar' => 'السلة'],
        'panier.vide'           => ['fr' => 'Votre panier est vide.', 'ar' => 'سلتك فارغة.'],
        'panier.continuer'      => ['fr' => 'Poursuivre mes achats', 'ar' => 'مواصلة التسوق'],
        'panier.titre'          => ['fr' => 'Panier',               'ar' => 'سلة المشتريات'],
        'panier.sous_total'     => ['fr' => 'Sous-total',           'ar' => 'المجموع الفرعي'],
        'panier.livraison'      => ['fr' => 'Livraison',            'ar' => 'التوصيل'],
        'panier.remise'         => ['fr' => 'Remise',               'ar' => 'الخصم'],
        'panier.total'          => ['fr' => 'Total',                'ar' => 'المجموع'],
        'panier.commander'      => ['fr' => 'Passer commande',      'ar' => 'إتمام الشراء'],
        'panier.ajouter'        => ['fr' => 'Ajouter au panier',    'ar' => 'أضف إلى السلة'],
        'panier.modifie'        => ['fr' => 'Panier mis à jour.',   'ar' => 'تم تحديث السلة.'],
        'panier.supprime'       => ['fr' => 'Article retiré du panier.', 'ar' => 'تم حذف المنتج من السلة.'],
        'produit.quantite'      => ['fr' => 'Quantité',             'ar' => 'الكمية'],
        'produit.variantes'     => ['fr' => 'Format',               'ar' => 'العبوة'],
        'produit.stock'         => ['fr' => 'Disponibilité',        'ar' => 'التوفر'],
        'produit.en_stock'      => ['fr' => 'En stock',             'ar' => 'متوفر'],
        'produit.bientot'       => ['fr' => 'Bientôt disponible',   'ar' => 'قريبًا'],
        'produit.epuise'        => ['fr' => 'Épuisé',               'ar' => 'نفدت الكمية'],
        'produit.description'   => ['fr' => 'Description',          'ar' => 'الوصف'],
        'produit.conseils'      => ['fr' => 'Conseils de dégustation', 'ar' => 'نصائح'],
        'produit.origine'       => ['fr' => 'Origine',              'ar' => 'الأصل'],
        'produit.on_achete'     => ['fr' => 'On l’achète avec',     'ar' => 'اشترِها مع'],
        'catalogue.titre'       => ['fr' => 'La boutique',          'ar' => 'المتجر'],
        'catalogue.filtres'     => ['fr' => 'Filtrer',              'ar' => 'تصفية'],
        'catalogue.tri'         => ['fr' => 'Trier par',            'ar' => 'الترتيب'],
        'catalogue.tri.nouveautes' => ['fr' => 'Nouveautés',        'ar' => 'الأحدث'],
        'catalogue.tri.prix_asc'   => ['fr' => 'Prix croissant',    'ar' => 'السعر تصاعدي'],
        'catalogue.tri.prix_desc'  => ['fr' => 'Prix décroissant',  'ar' => 'السعر تنازلي'],
        'catalogue.tri.populaire'  => ['fr' => 'Les plus demandés', 'ar' => 'الأكثر طلبًا'],
        'catalogue.vide'        => ['fr' => 'Aucun produit ne correspond à ces filtres.', 'ar' => 'لا توجد منتجات مطابقة.'],
        'catalogue.resultats'   => ['fr' => 'produits',             'ar' => 'منتج'],
        'catalogue.effacer'     => ['fr' => 'Effacer les filtres',  'ar' => 'إلغاء التصفية'],
        'commande.titre'        => ['fr' => 'Commande',             'ar' => 'الطلبية'],
        'commande.livraison'    => ['fr' => 'Adresse de livraison',  'ar' => 'عنوان التوصيل'],
        'commande.paiement'     => ['fr' => 'Paiement',             'ar' => 'الدفع'],
        'commande.confirmation' => ['fr' => 'Confirmation',         'ar' => 'التأكيد'],
        'commande.recapitulatif'=> ['fr' => 'Récapitulatif',        'ar' => 'ملخص'],
        'commande.valider'      => ['fr' => 'Valider et payer',     'ar' => 'التأكيد والدفع'],
        'commande.returnner'    => ['fr' => 'Retour',               'ar' => 'رجوع'],
        'commande.merci'        => ['fr' => 'Merci pour votre commande.', 'ar' => 'شكرًا لطلبكم.'],
        'commande.reference'    => ['fr' => 'Référence',            'ar' => 'المرجع'],
        'compte.connexion'      => ['fr' => 'Mon compte',           'ar' => 'حسابي'],
        'compte.inscription'    => ['fr' => 'Créer un compte',      'ar' => 'إنشاء حساب'],
        'compte.deconnexion'    => ['fr' => 'Se déconnecter',       'ar' => 'تسجيل الخروج'],
        'promo.code'            => ['fr' => 'Code promo',           'ar' => 'رمز الخصم'],
        'promo.appliquer'       => ['fr' => 'Appliquer',            'ar' => 'تطبيق'],
        'promo.valide'          => ['fr' => 'Code appliqué.',       'ar' => 'تم تفعيل الرمز.'],
        'promo.refuse'          => ['fr' => 'Ce code est invalide ou expiré.', 'ar' => 'الرمز غير صالح أو منتهي.'],
        'footer.parure'         => ['fr' => 'Artisanat et dattes de la région de Tozeur', 'ar' => 'حرف وتمور من منطقة توزر'],
        'commun.rechercher'     => ['fr' => 'Rechercher',           'ar' => 'بحث'],
        'commun.voir'           => ['fr' => 'Voir',                 'ar' => 'عرض'],
        'commun.prix'           => ['fr' => 'Prix',                 'ar' => 'السعر'],
        'commun.erreur'         => ['fr' => 'Une erreur est survenue.', 'ar' => 'حدث خطأ.'],
        'commun.obligatoire'    => ['fr' => 'Champ obligatoire',   'ar' => 'حقل إلزامي'],
    ];

    public static function definir(string $langue): void
    {
        self::$langue = in_array($langue, ['fr', 'ar'], true) ? $langue : 'fr';
    }

    public static function langue(): string
    {
        return self::$langue;
    }

    public static function estArabe(): bool
    {
        return self::$langue === 'ar';
    }

    public static function dir(): string
    {
        return self::estArabe() ? 'rtl' : 'ltr';
    }

    public static function t(string $clef, array $remplacements = []): string
    {
        $entree = (self::VITRINE[$clef] ?? self::COMMUNE[$clef] ?? null);
        $texte = $entree === null
            ? $clef
            : ($entree[self::$langue] ?? $entree['fr'] ?? $clef);
        foreach ($remplacements as $symbole => $valeur) {
            $texte = str_replace('{' . $symbole . '}', (string) $valeur, $texte);
        }
        return $texte;
    }
}
