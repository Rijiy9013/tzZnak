<?php
declare(strict_types=1);

use App\Http\Response\JsonResponseFactory;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

require __DIR__ . '/../vendor/autoload.php';
$container = require __DIR__ . '/../bootstrap/app.php';

$psr17 = new Psr17Factory();
$creator = new ServerRequestCreator($psr17, $psr17, $psr17, $psr17);
$request = $creator->fromGlobals();

$routes = require __DIR__ . '/../config/routes.php';
$dispatcher = FastRoute\simpleDispatcher($routes);

$responseFactory = new JsonResponseFactory($psr17);

$core = function (ServerRequestInterface $request) use ($dispatcher, $responseFactory, $container): ResponseInterface {
    try {
        $routeInfo = $dispatcher->dispatch($request->getMethod(), $request->getUri()->getPath());
        switch ($routeInfo[0]) {
            case FastRoute\Dispatcher::NOT_FOUND:
                return $responseFactory->json(['error' => 'Not Found'], 404);

            case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                return $responseFactory->json(['error' => 'Method Not Allowed'], 405);

            case FastRoute\Dispatcher::FOUND:
                [$class, $method] = $routeInfo[1];
                $vars = $routeInfo[2] ?? [];
                $controller = $container->get($class);
                $body = (string)$request->getBody();
                $parsed = $body !== '' ? json_decode($body, true, 512, JSON_THROW_ON_ERROR) : [];
                $request = $request->withAttribute('json', $parsed);
                return $controller->$method($request, $vars);
        }
    } catch (\JsonException) {
        return $responseFactory->json(['error' => 'Invalid JSON'], 400);
    } catch (\Throwable $e) {
        $container->get(Psr\Log\LoggerInterface::class)->error('Unhandled exception', ['e' => $e]);
        return $responseFactory->json(['error' => 'Internal Server Error'], 500);
    }

    return $responseFactory->json(['error' => 'Routing error'], 500);
};

$middlewares = [
    $container->get(\App\Http\Middleware\BearerAuthMiddleware::class),
];

$runner = array_reduce(
    array_reverse($middlewares),
    fn(callable $next, $mw) => fn(ServerRequestInterface $req) => $mw->handle($req, $next),
    $core
);

$response = $runner($request);

http_response_code($response->getStatusCode());
foreach ($response->getHeaders() as $n => $vals) {
    foreach ($vals as $v) header("$n: $v", false);
}
echo (string)$response->getBody();
