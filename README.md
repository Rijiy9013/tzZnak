# Копируем .env
cp .env.example .env
# API_TOKEN и DADATA_TOKEN поставить

# Поднять контейнеры
docker compose up -d --build

# Установить PHP-зависимости
docker compose exec php composer install

# Применить миграции
docker compose exec php php vendor/bin/doctrine-migrations migrate -n

# Сидим данные
docker compose exec php php bin/console app:seed-demo --purge --count=20

# Проиндексировать Elasticsearch
docker compose exec php php bin/console app:reindex-products

# Тесты
docker compose exec php ./vendor/bin/phpunit

API доступно на: http://localhost:8080
Все эндпоинты требуют заголовок: Authorization: Bearer <API_TOKEN> (по умолчанию 123 в .env)
