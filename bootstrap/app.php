<?php
declare(strict_types=1);

use App\Http\Middleware\BearerAuthMiddleware;
use App\Http\Response\JsonResponseFactory;
use App\Infrastructure\Search\Contract\HealthChecker;
use App\Infrastructure\Search\Contract\ProductIndexer;
use App\Infrastructure\Search\Contract\ProductSearch;
use DI\ContainerBuilder;
use Doctrine\DBAL\DriverManager;
use Dotenv\Dotenv;
use Elastic\Elasticsearch\ClientBuilder;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;

$root = dirname(__DIR__);
Dotenv::createImmutable($root)->safeLoad();

$builder = new ContainerBuilder();
$builder->addDefinitions([
    Doctrine\DBAL\Connection::class => function () {
        return DriverManager::getConnection([
            'dbname' => $_ENV['DB_DATABASE'],
            'user' => $_ENV['DB_USERNAME'],
            'password' => $_ENV['DB_PASSWORD'],
            'host' => $_ENV['DB_HOST'],
            'driver' => 'pdo_mysql',
            'charset' => 'utf8mb4',
        ]);
    },

    CacheInterface::class => function () use ($root) {
        $pool = new FilesystemAdapter(namespace: 'cache', defaultLifetime: 3600, directory: "$root/var/cache");
        return new Psr16Cache($pool);
    },

    Psr\Log\LoggerInterface::class => function () use ($root) {
        $logger = new Logger('app');
        @mkdir("$root/var/log", 0777, true);
        $logger->pushHandler(new StreamHandler("$root/var/log/app.log", Logger::DEBUG));
        return $logger;
    },

    Elastic\Elasticsearch\Client::class => function () {
        return ClientBuilder::create()->setHosts([$_ENV['ELASTIC_HOST'] ?? 'http://elastic:9200'])->build();
    },

    GuzzleHttp\ClientInterface::class => function () {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
        if (!empty($_ENV['DADATA_API_KEY'])) {
            $headers['Authorization'] = 'Token ' . $_ENV['DADATA_API_KEY'];
        }
        return new GuzzleHttp\Client([
            'base_uri' => 'https://suggestions.dadata.ru/suggestions/api/4_1/rs/',
            'timeout' => 4.0,
            'headers' => $headers,
        ]);
    },

    ResponseFactoryInterface::class => DI\factory(fn() => new Psr17Factory()),
    \App\Http\Response\JsonResponseFactory::class => DI\autowire(),

    // Репозитории
    App\Domain\Repository\ProductRepositoryInterface::class => DI\autowire(App\Infrastructure\Persistence\Doctrine\ProductRepository::class),
    App\Domain\Repository\CategoryRepositoryInterface::class => DI\autowire(App\Infrastructure\Persistence\Doctrine\CategoryRepository::class),

    // DaData chain: FindPartyStrategy -> DadataInnValidator -> CachedDadataClient
    App\Infrastructure\Dadata\Strategy\InnCheckStrategyInterface::class =>
        DI\autowire(App\Infrastructure\Dadata\Strategy\FindPartyStrategy::class),

    App\Infrastructure\Dadata\DadataClientInterface::class => function (\Psr\Container\ContainerInterface $c) {
        $inner = new App\Infrastructure\Dadata\Adapter\DadataInnValidator(
            $c->get(App\Infrastructure\Dadata\Strategy\InnCheckStrategyInterface::class)
        );
        return new App\Infrastructure\Dadata\Cache\CachedDadataClient(
            $inner,
            $c->get(CacheInterface::class)
        );
    },

    App\Application\Query\ProductQueryService::class => DI\autowire(),

    // Поиск
    \App\Application\Search\SearchService::class => DI\autowire(),
    \App\Application\Search\ElasticHealthChecker::class => DI\autowire(),
    \App\Application\Search\ElasticProductSearch::class => DI\autowire(),
    \App\Application\Search\DbProductSearch::class => DI\autowire(),

    // Контроллеры
    App\Http\Controller\ProductController::class => DI\autowire(),
    App\Http\Controller\CategoryController::class => DI\autowire(),

    // Сервисы
    App\Application\Service\CategoryService::class => DI\autowire(),

    HealthChecker::class => DI\get(\App\Application\Search\ElasticHealthChecker::class),
    ProductSearch::class => DI\get(\App\Application\Search\DbProductSearch::class),
    ProductIndexer::class => DI\get(\App\Application\Search\ElasticProductSearch::class),

    // Middleware
    BearerAuthMiddleware::class => DI\factory(function (\Psr\Container\ContainerInterface $c) {
        return new BearerAuthMiddleware(
            $c->get(JsonResponseFactory::class),
            $_ENV['API_TOKEN']
        );
    }),

]);

return $builder->build();
