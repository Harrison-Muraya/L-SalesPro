## System Requirements
- PHP 8.2+
- Composer 2.9
- MySQL 8.0+ or PostgreSQL 13+
- Redis 6.0+
- code editor(VS Code)

#3  Development tools
- postman installed

## Required PHP Extensions
```bash
php -m | grep -E 'pdo_mysql|mbstring|openssl|tokenizer|xml|ctype|json|bcmath|redis|zip|xsl|fileinfo|gd|curl'
```

## Installation
```bash
git clone https://github.com/Harrison-Muraya/L-SalesPro.git lsalespro-api
```
```
cd lsalespro-api
```
## install Dependencies
```bash
composer install
```
## Environment Configuration
```bash
cp .env.example .env
```
```
php artisan key:generate
```
## Configure Environment Variables
```
APP_NAME="L-SalesPro API"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lsalespro
DB_USERNAME=root
DB_PASSWORD=your_password

REDIS_HOST=127.0.0.1
REDIS_PORT=6379

CACHE_DRIVER=redis
QUEUE_CONNECTION=database
```
## Database Setup
Create the database and run migrations
```bash
php artisan migrate
```
Seed database with sample data
```bash
php artisan db:seed
```
## Start the Development Server
```bash
php artisan serve
```

The API will be available at:
(http://localhost:8000)

## Quick Start with Postman
You can explore all API endpoints instantly using the official Postman collection.

