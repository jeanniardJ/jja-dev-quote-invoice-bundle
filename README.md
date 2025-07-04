# JJA Dev Quote Invoice Bundle

**Description courte :** Bundle Symfony pour la gestion complète des devis et factures, incluant la création, l'édition, le suivi, la génération PDF et l'envoi par email, conçu pour s'intégrer facilement dans tout projet CRM ou ERP.

## 🎯 Objectifs

Ce bundle Symfony offre une solution complète pour la gestion des devis et factures destinée aux auto-entrepreneurs et petites entreprises. Il respecte les bonnes pratiques Symfony et s'intègre facilement dans tout projet existant.

## ✨ Fonctionnalités

### Gestion des devis
- ✅ Création, édition, duplication et suppression de devis
- ✅ Conversion d'un devis en facture
- ✅ Génération automatique et personnalisable du numéro de devis
- 🔄 Ajout de conditions générales de vente aux devis
- 🔄 Possibilité de joindre des pièces annexes

### Gestion des factures
- ✅ Création, édition, suppression de factures
- ✅ Génération automatique et personnalisable du numéro de facture
- ✅ Suivi de l'état de paiement, gestion des statuts
- 🔄 Export PDF avec personnalisation du template
- 🔄 Archivage automatique des factures inactives
- 🔄 Gestion des avoirs et notes de crédit
- 🔄 Possibilité de joindre des pièces annexes

### Gestion des clients
- ✅ Création, édition, suppression et archivage de clients
- ✅ Recherche et filtrage avancés sur les clients
- 🔄 Import/export CSV des clients

### Catalogue de produits/services
- ✅ Gestion des produits/services (création, édition, suppression)
- ✅ Gestion des tarifs, descriptions, taux de TVA
- ✅ Support de taxes multiples selon le type de produit/service

### Remises et rabais
- ✅ Application de remises/rabais sur chaque ligne ou globalement

### Recherche et tableau de bord
- ✅ Tableau de bord synthétique avec indicateurs clés
- ✅ Historique des actions réalisées sur chaque entité
- 🔄 Recherche et filtrage multicritères avancés

### Automatisation et notifications
- 🔄 Génération de modèles d'email pour l'envoi des devis/factures
- 🔄 Système de relances automatiques pour les factures impayées
- 🔄 Intégration d'une solution de signature électronique

**Légende :** ✅ Implémenté | 🔄 En cours de développement | ❌ Non implémenté

## 📋 Prérequis

- PHP 8.1 ou supérieur
- Symfony 6.4 ou 7.x
- Doctrine ORM
- Base de données MySQL, PostgreSQL ou SQLite

## 🚀 Installation

### 1. Installation via Composer

```bash
composer require jja-dev/quote-invoice-bundle
```

### 2. Enregistrement du bundle

Ajoutez le bundle dans votre fichier `config/bundles.php` :

```php
return [
    // ...
    JjaDev\QuoteInvoiceBundle\JjaDevQuoteInvoiceBundle::class => ['all' => true],
];
```

### 3. Configuration

Créez un fichier de configuration `config/packages/jja_dev_quote_invoice.yaml` :

```yaml
jja_dev_quote_invoice:
    quote_number_format: 'DEVIS-%06d'
    invoice_number_format: 'FACT-%06d'
    default_currency: 'EUR'
    company_name: 'Votre Entreprise'
    company_address: 'Votre adresse complète'
    
    pdf_settings:
        format: 'A4'
        orientation: 'portrait'
        
    email_settings:
        from_email: 'noreply@votre-domaine.com'
        from_name: 'Votre Entreprise'
        
    features:
        enable_quotes: true
        enable_invoices: true
        enable_clients: true
        enable_products: true
        enable_pdf_export: true
        enable_email_notifications: true
```

### 4. Routes

Ajoutez les routes dans `config/routes.yaml` :

```yaml
jja_dev_quote_invoice:
    resource: '@JjaDevQuoteInvoiceBundle/Controller/'
    type: attribute
    prefix: /admin
```

### 5. Migration de la base de données

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

## 📖 Utilisation

### Accès au tableau de bord

Une fois installé, accédez au tableau de bord via :
```
https://votre-domaine.com/admin/quote-invoice/
```

### Utilisation des services

#### Service de devis

```php
use JjaDev\QuoteInvoiceBundle\Service\QuoteService;
use JjaDev\QuoteInvoiceBundle\Entity\Quote;

class YourController extends AbstractController
{
    public function __construct(
        private QuoteService $quoteService
    ) {}

    public function createQuote(): Response
    {
        $quote = new Quote();
        // Configuration du devis...
        
        $quote = $this->quoteService->createQuote($quote);
        
        return $this->redirectToRoute('quote_show', ['id' => $quote->getId()]);
    }
    
    public function convertToInvoice(Quote $quote): Response
    {
        if ($quote->canBeConverted()) {
            $invoice = $this->quoteService->convertToInvoice($quote);
            $this->addFlash('success', 'Devis converti en facture avec succès');
        }
        
        return $this->redirectToRoute('invoice_show', ['id' => $invoice->getId()]);
    }
}
```

#### Service de facturation

```php
use JjaDev\QuoteInvoiceBundle\Service\InvoiceService;

class PaymentController extends AbstractController
{
    public function markAsPaid(Invoice $invoice, InvoiceService $invoiceService): Response
    {
        $invoiceService->markAsPaid($invoice);
        $this->addFlash('success', 'Facture marquée comme payée');
        
        return $this->redirectToRoute('invoice_list');
    }
}
```

## 🧪 Tests

Lancez les tests avec PHPUnit :

```bash
composer install --dev
vendor/bin/phpunit
```

## 📊 Structure de la base de données

Le bundle crée les tables suivantes :

- `jja_client` : Informations des clients
- `jja_product` : Catalogue des produits/services
- `jja_quote` : Devis
- `jja_quote_item` : Lignes de devis
- `jja_invoice` : Factures
- `jja_invoice_item` : Lignes de factures

## 🔧 Configuration avancée

### Personnalisation des templates

Vous pouvez surcharger les templates en créant les fichiers dans votre dossier `templates/bundles/JjaDevQuoteInvoiceBundle/` :

```
templates/
└── bundles/
    └── JjaDevQuoteInvoiceBundle/
        ├── dashboard/
        │   └── index.html.twig
        ├── quote/
        │   ├── index.html.twig
        │   └── show.html.twig
        └── invoice/
            ├── index.html.twig
            └── show.html.twig
```

### Extension des entités

Vous pouvez étendre les entités du bundle en utilisant l'héritage Doctrine :

```php
use JjaDev\QuoteInvoiceBundle\Entity\Quote as BaseQuote;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Quote extends BaseQuote
{
    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $customField = null;
    
    // Getters et setters...
}
```

## 🤝 Contribution

Les contributions sont les bienvenues ! Pour contribuer :

1. Forkez le repository
2. Créez une branche pour votre fonctionnalité (`git checkout -b feature/ma-fonctionnalite`)
3. Committez vos changements (`git commit -am 'Ajout de ma fonctionnalité'`)
4. Poussez vers la branche (`git push origin feature/ma-fonctionnalite`)
5. Créez une Pull Request

## 📝 Licence

Ce projet est sous licence MIT. Voir le fichier [LICENSE](LICENSE) pour plus de détails.

## 📞 Support

Pour toute question ou problème :

- Créez une issue sur GitHub
- Contactez-nous à : contact@jja-dev.fr

## 🎯 Roadmap

### Version 1.1 (prochaine)
- Interface complète CRUD pour toutes les entités
- Génération PDF avancée
- Système d'email et notifications

### Version 1.2
- API REST complète
- Import/Export CSV
- Système de relances automatiques

### Version 2.0
- Interface React/Vue.js
- Intégration comptable
- Multi-devise
- Rapports avancés