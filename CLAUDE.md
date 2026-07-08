# cours-test — Guide de développement

Application **Symfony 8** (PHP 8.4) de gestion de **TodoList** : un `User` possède une `TodoList` qui contient des `ListItem`. API Platform + Doctrine ORM sur **PostgreSQL**.

## Stack

| Domaine | Techno |
|---------|--------|
| Framework | Symfony 8.0, PHP ≥ 8.4 |
| API | API Platform 4.3 |
| ORM | Doctrine ORM 3, Migrations |
| Base de données | PostgreSQL 16 (Docker) |
| Front | Twig, AssetMapper, Stimulus, Turbo |
| Tests | PHPUnit 13 (`WebTestCase`), Symfony Panther (E2E) |
| Fixtures | nelmio/alice |

## Structure

```
src/
  Controller/   ListItemController, TodoListController, UserController
  Entity/       User, TodoList, ListItem
  Form/         *Type
  Repository/   *Repository
migrations/     VersionYYYYMMDDHHMMSS.php
templates/      vues Twig
tests/          Controller/ + tests unitaires
config/         packages, routes, services
```

## Commandes

```bash
# Environnement (PostgreSQL + Adminer sur :8080)
docker compose up -d

# Serveur de dev
symfony serve            # ou php -S localhost:8000 -t public

# Migrations
php bin/console make:migration
php bin/console doctrine:migrations:migrate

# Fixtures
php bin/console doctrine:fixtures:load

# Tests
php bin/console doctrine:database:create --env=test --if-not-exists
php bin/console doctrine:schema:update --force --env=test
vendor/bin/phpunit
```

## Conventions

- **Générer via `bin/console`** : entités, contrôleurs, formulaires, migrations et tests se créent avec les commandes `make:*` du MakerBundle — ne pas écrire ces fichiers à la main.
  ```bash
  php bin/console make:entity
  php bin/console make:controller
  php bin/console make:form
  php bin/console make:test
  ```
- **Français partout** : toutes les chaînes visibles (messages flash, statuts, descriptions, libellés) doivent être en français.
- **Pas de commentaires** : code sans commentaires, compact et lisible par lui-même.
- Indentation 4 espaces, PSR-12, types stricts sur toutes les propriétés et signatures.

## Tests

- **Intégration / fonctionnel** : `WebTestCase` dans `tests/Controller/`. Chaque test nettoie ses données dans `setUp()`.
- **End to end** : **Symfony Panther**. Installer et générer un test via bin/console :
  ```bash
  composer require --dev symfony/panther
  php bin/console make:test   # choisir PantherTestCase
  ```
- Base de test PostgreSQL configurée dans `.env.test` (`app_test`).

### Prérequis E2E : un navigateur (Chromium)

Les tests Panther pilotent un vrai Chromium. Deux façons de l'avoir selon les droits :

- **Sur l'hôte** (droits sudo requis, une fois par machine) :
  ```bash
  sudo apt install -y chromium chromium-driver   # ou : sudo snap install chromium
  vendor/bin/phpunit
  ```
- **Dans Docker**:
  ```bash
  docker compose up -d --build
  docker compose exec app php bin/console doctrine:database:create --env=test --if-not-exists
  docker compose exec app php bin/console doctrine:migrations:migrate --env=test --no-interaction
  docker compose exec app vendor/bin/phpunit
  ```

## Base de données

Connexion définie dans `.env` (`DATABASE_URL`, PostgreSQL). Ne pas committer de secrets — utiliser `.env.local`.

## Git

- Messages de commit et PR **en français**, concis, à l'impératif.
- **Aucune mention d'IA / d'assistant** dans les commits, PR ou le code.
