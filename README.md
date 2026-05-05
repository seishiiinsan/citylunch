# CityLunch

Application web de commande de plats du jour, développée avec Symfony 8.

Les clients peuvent consulter le menu du jour, créer un compte, se connecter et composer leur panier.

---

## Prérequis

| Outil | Version minimale |
|---|---|
| Docker | 24+ |
| Docker Compose | 2.20+ |
| PHP (hors Docker) | 8.4+ |
| Composer | 2+ |

> Le projet fonctionne entièrement via Docker. PHP et Composer en local ne sont nécessaires que si tu veux exécuter des commandes hors container.

---

## Installation et démarrage

### 1. Cloner le dépôt

```bash
git clone https://github.com/seishiiinsan/citylunch
cd citylunch
```

### 2. Variables d'environnement

Créer un fichier `.env.local` à la racine :

```dotenv
APP_SECRET='<générer avec : php -r "echo bin2hex(random_bytes(16));"> '
```

> Les variables `DATABASE_URL` et `REDIS_URL` sont injectées automatiquement par Docker Compose dans le container PHP. Le `.env.local` ne sert qu'à fournir `APP_SECRET`.

### 3. Démarrer les containers

```bash
docker compose up -d --build
```

Cela démarre 5 services :
- **nginx** — reverse proxy sur `http://localhost`
- **php** — PHP 8.4-FPM avec extensions `pdo_pgsql`, `intl`, `opcache`, `redis`
- **database** — PostgreSQL 16
- **redis** — Redis 7 (sessions)
- **mailer** — Mailpit (emails de dev sur `http://localhost:8025`)

### 4. Initialiser la base de données

```bash
# Créer le schéma
docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction

# Charger les données de démonstration (3 plats + 2 desserts du jour)
docker compose exec php php bin/console doctrine:fixtures:load --no-interaction
```

### 5. Accéder à l'application

| URL | Description |
|---|---|
| `http://localhost` | Application |
| `http://localhost/menu` | Menu du jour |
| `http://localhost/register` | Créer un compte |
| `http://localhost:8025` | Mailpit (emails dev) |

---

## Commandes utiles

```bash
# Vider le cache Symfony
docker compose exec php php bin/console cache:clear

# Vérifier la synchronisation base / entités
docker compose exec php php bin/console doctrine:schema:validate

# Générer une migration après modification d'entité
docker compose exec php php bin/console doctrine:migrations:diff

# Inspecter les sessions Redis
docker compose exec redis redis-cli KEYS "sf_s*"

# Consulter les logs Symfony
docker compose exec php tail -f /var/www/html/var/log/dev.log
```

---

## Stack technique

### PHP / Symfony

- **Symfony 8** — framework PHP
- **Doctrine ORM** — mapping objet-relationnel
- **Twig** — moteur de templates
- **Symfony Security** — authentification stateful par formulaire
- **Bootstrap 5** + **Bootstrap Icons** — UI

### Infrastructure Docker

```
nginx:alpine       → reverse proxy, port 80
php:8.4-fpm-alpine → application Symfony
postgres:16-alpine → base de données relationnelle
redis:7-alpine     → stockage des sessions
axllent/mailpit    → serveur SMTP de développement
```

---

## Pourquoi deux bases de données ?

### PostgreSQL — données applicatives

PostgreSQL est utilisé pour stocker toutes les données métier : clients, produits, paniers, lignes de panier.

**Raisons du choix :**
- Relationnel avec intégrité référentielle (clés étrangères entre `cart`, `cart_item`, `customer`, `product`)
- Typage fort (`DATE`, `INT`, `JSON`) qui correspond aux entités Doctrine
- Performances et fiabilité éprouvées pour des données transactionnelles
- Intégration native avec Symfony/Doctrine via `pdo_pgsql`

### Redis — sessions utilisateurs

Redis est utilisé comme backend de sessions Symfony via `RedisSessionHandler`.

**Raisons du choix :**
- Stockage clé-valeur en mémoire : lecture/écriture de session en O(1)
- TTL natif sur les clés : les sessions expirent automatiquement sans nettoyage manuel
- Découplage des sessions du stockage relationnel : une panne PostgreSQL ne déconnecte pas les utilisateurs actifs
- Adapté aux données éphémères et non structurées (token CSRF, panier temporaire, flash messages)

---

## Stratégie de sauvegarde

### PostgreSQL

**Sauvegarde logique quotidienne avec `pg_dump` :**

```bash
# Exemple de script cron (0h00 chaque nuit)
0 0 * * * docker compose exec -T database \
  pg_dump -U app app | gzip > /backups/citylunch_$(date +\%Y\%m\%d).sql.gz

# Purger les sauvegardes de plus de 30 jours
find /backups -name "citylunch_*.sql.gz" -mtime +30 -delete
```

**Points clés :**
- Conserver **7 sauvegardes quotidiennes** + **4 hebdomadaires** + **12 mensuelles** (stratégie grand-père-père-fils)
- Tester la restauration régulièrement : `gunzip < backup.sql.gz | psql -U app app`
- Stocker les sauvegardes sur un volume externe au serveur (S3, NFS, etc.)

### Redis

Les sessions étant éphémères, la priorité est la **disponibilité** plutôt que la durabilité :

- Activer la **persistence RDB** (snapshot toutes les 15 minutes) dans `redis.conf` :
  ```
  save 900 1
  save 300 10
  ```
- En production, envisager le mode **AOF** (`appendonly yes`) pour une durabilité accrue
- Une perte de sessions Redis en cas de crash est acceptable : les utilisateurs se reconnectent simplement

---

## Recommandation SSL

### Contexte du projet

CityLunch est une application multi-tenant avec un seul domaine (ex. `citylunch.fr`). Le certificat SSL doit être :
- Gratuit et automatiquement renouvelable
- Compatible avec Nginx
- Opérationnel en production sur un VPS

### Solution recommandée : Let's Encrypt + Certbot

```bash
# Installation de Certbot (Debian/Ubuntu)
apt install certbot python3-certbot-nginx

# Obtenir et installer le certificat
certbot --nginx -d citylunch.fr -d www.citylunch.fr

# Le renouvellement est automatique via un timer systemd
# Vérifier avec :
systemctl status certbot.timer
```

**Configuration Nginx résultante :**

```nginx
server {
    listen 443 ssl;
    server_name citylunch.fr www.citylunch.fr;

    ssl_certificate     /etc/letsencrypt/live/citylunch.fr/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/citylunch.fr/privkey.pem;

    # Redirection HTTP → HTTPS
    # (gérée automatiquement par Certbot)
}
```

**Pourquoi Let's Encrypt plutôt qu'un certificat payant ?**
- Certificats valides 90 jours, renouvelés automatiquement (aucun oubli possible)
- Reconnus par tous les navigateurs modernes
- Suffisant pour un projet sans contrainte de certificat EV (validation étendue)

> En environnement Docker en production, utiliser [Traefik](https://traefik.io) comme reverse proxy avec le plugin Let's Encrypt intégré est une alternative plus adaptée qu'un Nginx + Certbot géré manuellement.

---

## Configuration IDE recommandée

### PhpStorm

| Plugin | Utilité |
|---|---|
| **Symfony Support** | Autocomplétion des routes, services, templates Twig |
| **Twig** | Coloration syntaxique et autocomplétion Twig |
| **PHP Annotations** | Support des attributs PHP 8 (`#[Route]`, `#[ORM\Entity]`) |

**Configuration recommandée :**
- Pointer le CLI PHP vers celui du container : `docker compose exec php php`
- Activer le serveur Symfony dans les Run Configurations pour utiliser `bin/console`
- Configurer le data source sur `localhost:5432` (base `app`, user `app`, mdp `!ChangeMe!`)

---

## Dépôt GitHub

https://github.com/seishiiinsan/citylunch
