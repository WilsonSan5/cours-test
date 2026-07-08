# cours-test -  TodoList


CHOUMITZKY Ilia , 
SAN Wilson ,  ABBAS Mohed



## Lancer le projet avec Docker

```bash
# 1. Construire et démarrer l'application + PostgreSQL + Adminer
docker compose up -d --build

# 2. C'est prêt !
```

| Service       | URL                    |
|---------------|------------------------|
| Application   | http://localhost:8000  |
| Adminer (BDD) | http://localhost:8080  |
| TodoList | http://localhost:8000/todo/list  |



```bash
# Arrêter
docker compose down
```

### Sans Docker

```bash
composer install
php bin/console doctrine:migrations:migrate
symfony serve             
```

---

## Lancer les tests
```bash
php bin/phpunit
PANTHER_NO_HEADLESS=1  bin/phpunit
```
