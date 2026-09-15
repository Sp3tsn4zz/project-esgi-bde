# Projet BDE — Gestion des événements

## Présentation du projet

Ce projet consiste à développer un site web de gestion pour le Bureau des Étudiants (BDE).

Le site permet aux membres du BDE de gérer les événements de l'école, les inscriptions des étudiants et les informations concernant les étudiants.

L'accès au site est réservé aux membres du BDE grâce à un système de connexion.

---

## Objectifs

Le site permet de :

- Se connecter à l'espace BDE
- Consulter le planning des événements
- Créer un événement
- Modifier un événement
- Supprimer un événement
- Archiver les anciens événements
- Voir les participants à un événement
- Ajouter un étudiant à un événement
- Retirer un étudiant d'un événement
- Exporter la liste des participants au format CSV
- Consulter la liste des étudiants
- Consulter la fiche d'un étudiant
- Voir si la scolarité d'un étudiant est payée
- Voir si un étudiant est membre du BDE

---

## Technologies utilisées

| Technologie | Utilisation |
|---|---|
| Laravel | Framework PHP utilisé pour développer le site |
| PHP | Langage de programmation |
| Blade | Système de templates de Laravel |
| HTML | Structure des pages |
| CSS | Mise en forme du site |
| MySQL | Base de données |
| SQL | Gestion et interrogation de la base de données |
| Composer | Gestion des dépendances PHP |

---

## Architecture du projet

Le projet utilise l'architecture MVC (Model - View - Controller) de Laravel.

```text
Utilisateur
     |
     v
   Route
     |
     v
 Controller
     |
     v
   Model
     |
     v
 MySQL
     |
     v
 Controller
     |
     v
   Blade
     |
     v
 HTML / CSS
```

### Model

Les modèles permettent de communiquer avec la base de données.

Les principaux modèles sont :

```text
User.php
Etudiant.php
Evenement.php
Participation.php
```

### View

Les vues correspondent aux pages affichées à l'utilisateur.

Elles utilisent Blade, le système de templates de Laravel.

Exemples :

```text
login.blade.php
dashboard.blade.php
index.blade.php
show.blade.php
create.blade.php
edit.blade.php
```

### Controller

Les contrôleurs contiennent la logique du site.

Exemples :

```text
AuthController.php
DashboardController.php
EvenementController.php
EtudiantController.php
```

---

## Organisation des dossiers

```text
bde/
│
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       ├── AuthController.php
│   │       ├── DashboardController.php
│   │       ├── EvenementController.php
│   │       └── EtudiantController.php
│   │
│   └── Models/
│       ├── User.php
│       ├── Etudiant.php
│       ├── Evenement.php
│       └── Participation.php
│
├── database/
│   ├── migrations/
│   │   ├── create_users_table.php
│   │   ├── create_etudiants_table.php
│   │   ├── create_evenements_table.php
│   │   └── create_participations_table.php
│   │
│   └── seeders/
│       └── DatabaseSeeder.php
│
├── public/
│   └── css/
│       └── style.css
│
├── resources/
│   └── views/
│       ├── layouts/
│       │   └── app.blade.php
│       │
│       ├── auth/
│       │   └── login.blade.php
│       │
│       ├── dashboard.blade.php
│       │
│       ├── evenements/
│       │   ├── index.blade.php
│       │   ├── create.blade.php
│       │   ├── edit.blade.php
│       │   └── show.blade.php
│       │
│       └── etudiants/
│           ├── index.blade.php
│           ├── edit.blade.php
│           └── show.blade.php
│
├── routes/
│   └── web.php
│
├── .env
├── artisan
├── composer.json
└── README.md
```

---

# Base de données

La base de données contient principalement quatre tables :

- `users`
- `etudiants`
- `evenements`
- `participations`

---

## Table users

Cette table contient les utilisateurs autorisés à se connecter au site.

```text
users
├── id
├── name
├── email
├── password
├── role
└── timestamps
```

Le champ `role` permet de définir le type d'utilisateur.

Exemple :

```text
role = bde
```

---

## Table etudiants

Cette table contient les informations des étudiants.

```text
etudiants
├── id
├── nom
├── prenom
├── email
├── telephone
├── scolarite_payee
├── membre_bde
└── timestamps
```

### scolarite_payee

Ce champ permet de savoir si l'étudiant a payé sa scolarité.

```text
true  = scolarité payée
false = scolarité non payée
```

### membre_bde

Ce champ permet de savoir si l'étudiant est membre du BDE.

```text
true  = membre du BDE
false = non membre
```

---

## Table evenements

Cette table contient les événements organisés par le BDE.

```text
evenements
├── id
├── nom
├── description
├── date
├── heure
├── lieu
├── archive
├── user_id
└── timestamps
```

Le champ `archive` permet de conserver les anciens événements dans la base de données sans les afficher dans le planning principal.

```text
archive = false
```

L'événement est actif.

```text
archive = true
```

L'événement est archivé.

Le champ `user_id` permet de connaître le membre du BDE qui a créé l'événement.

---

## Table participations

Cette table permet de faire le lien entre les étudiants et les événements.

```text
participations
├── id
├── etudiant_id
├── evenement_id
└── timestamps
```

Elle permet de savoir quels étudiants participent à quels événements.

Exemple :

```text
Alice  -> Soirée BDE
Lucas  -> Soirée BDE
Emma   -> Tournoi FIFA
```

---

# Relations entre les tables

Le modèle de données fonctionne de la manière suivante :

```text
USERS
  |
  | 1,N
  | crée
  v
EVENEMENTS
  |
  | 1,N
  v
PARTICIPATIONS
  ^
  | N,1
  |
ETUDIANTS
```

Un membre du BDE peut créer plusieurs événements.

Un événement peut avoir plusieurs participants.

Un étudiant peut participer à plusieurs événements.

La table `participations` permet donc de gérer la relation entre les étudiants et les événements.

---

# Authentification

L'accès au site est protégé par le système d'authentification de Laravel.

La page de connexion se trouve dans :

```text
resources/views/auth/login.blade.php
```

L'utilisateur doit renseigner :

- son adresse email ;
- son mot de passe.

Si les informations sont correctes, il est redirigé vers le tableau de bord.

Les pages principales sont protégées avec le middleware `auth` :

```php
Route::middleware('auth')->group(function () {
    // Routes protégées
});
```

Un utilisateur qui n'est pas connecté ne peut donc pas accéder aux pages du BDE.

---

# Planning

Le tableau de bord permet de consulter les événements actifs.

Les événements sont récupérés depuis la base de données grâce au modèle `Evenement`.

```php
$evenements = Evenement::where('archive', false)
    ->orderBy('date')
    ->get();
```

Les événements archivés ne sont pas affichés dans le planning principal.

Un clic sur un événement permet d'accéder à sa page détaillée.

---

# Gestion des événements

Les membres du BDE peuvent effectuer plusieurs opérations sur les événements.

## Ajouter un événement

Il est possible de créer un événement avec :

- un nom ;
- une description ;
- une date ;
- une heure ;
- un lieu.

## Modifier un événement

Les informations d'un événement existant peuvent être modifiées.

## Supprimer un événement

Un événement peut être supprimé définitivement de la base de données.

## Archiver un événement

Un événement peut être archivé lorsqu'il est terminé.

L'événement reste enregistré dans la base de données mais n'apparaît plus dans le planning principal.

---

# Gestion des participants

Depuis la page d'un événement, un membre du BDE peut :

- consulter les participants ;
- ajouter un étudiant ;
- retirer un étudiant ;
- voir le nombre de participants ;
- exporter la liste des participants.

La relation Laravel utilisée est :

```php
$evenement->etudiants()
```

---

# Export des participants

La liste des participants peut être exportée au format CSV.

Le fichier contient les informations suivantes :

```text
Nom
Prénom
Email
Téléphone
```

Le fichier peut ensuite être ouvert avec Excel ou un autre logiciel de tableur.

---

# Gestion des étudiants

La page des étudiants permet de :

- consulter tous les étudiants ;
- consulter les informations d'un étudiant ;
- modifier les informations d'un étudiant ;
- vérifier le paiement de la scolarité ;
- vérifier si l'étudiant est membre du BDE ;
- voir les événements auxquels l'étudiant participe.

---

# Installation du projet

## 1. Récupérer le projet

```bash
git clone [URL_DU_PROJET]
```

Puis :

```bash
cd bde
```

---

## 2. Installer les dépendances

```bash
composer install
```

---

## 3. Créer le fichier .env

Copier le fichier `.env.example` et le renommer en `.env`.

Sous Linux ou macOS :

```bash
cp .env.example .env
```

Sous Windows, le fichier peut être copié et renommé manuellement.

---

## 4. Générer la clé Laravel

```bash
php artisan key:generate
```

---

## 5. Configurer MySQL

Créer une base de données appelée :

```text
bde
```

Puis modifier le fichier `.env` :

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bde
DB_USERNAME=root
DB_PASSWORD=
```

Si un mot de passe est configuré pour MySQL, il doit être indiqué dans `DB_PASSWORD`.

---

## 6. Créer les tables

Exécuter :

```bash
php artisan migrate
```

Cette commande crée les tables nécessaires dans la base de données.

---

## 7. Ajouter les données de test

Exécuter :

```bash
php artisan db:seed
```

Cette commande permet de créer un compte BDE et plusieurs étudiants de test.

---

## 8. Lancer le serveur

Exécuter :

```bash
php artisan serve
```

Puis ouvrir dans un navigateur :

```text
http://127.0.0.1:8000
```

---

# Compte de test

Un compte BDE est créé avec le seeder.

```text
Email :
bde@ecole.fr

Mot de passe :
password123
```

Ce compte est uniquement destiné aux tests du projet.

---

# Tests à effectuer

## Connexion

- [ ] Connexion avec un compte BDE
- [ ] Refus d'un mauvais mot de passe
- [ ] Déconnexion
- [ ] Accès impossible aux pages sans connexion

## Événements

- [ ] Afficher les événements
- [ ] Ajouter un événement
- [ ] Modifier un événement
- [ ] Supprimer un événement
- [ ] Archiver un événement
- [ ] Consulter le détail d'un événement

## Participants

- [ ] Ajouter un étudiant
- [ ] Afficher les participants
- [ ] Retirer un participant
- [ ] Exporter les participants en CSV

## Étudiants

- [ ] Afficher la liste des étudiants
- [ ] Consulter une fiche étudiant
- [ ] Modifier un étudiant
- [ ] Vérifier le paiement de la scolarité
- [ ] Vérifier si l'étudiant est membre du BDE

---

# Évolutions possibles

Plusieurs fonctionnalités peuvent être ajoutées par la suite :

- Calendrier mensuel interactif
- Recherche d'étudiants
- Recherche d'événements
- Statistiques du BDE
- Nombre de participants par événement
- Envoi d'emails aux participants
- Adaptation du site aux téléphones
- Différents niveaux de droits pour les membres du BDE
- Ajout d'une image à un événement
- Export au format Excel
- Historique des modifications

---

# Objectifs pédagogiques

Ce projet permet de mettre en pratique plusieurs notions de développement web :

- PHP
- Laravel
- Blade
- HTML
- CSS
- SQL
- MySQL
- Architecture MVC
- Programmation orientée objet
- Relations entre les tables
- Authentification
- Gestion des formulaires
- CRUD (Create, Read, Update, Delete)
- Gestion des sessions
- Export de données

Le projet permet également de mettre en pratique la modélisation d'une base de données à partir d'un MCD.