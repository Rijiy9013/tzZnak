<?php
declare(strict_types=1);

use App\Http\Controller\CategoryController;
use App\Http\Controller\ProductController;

return function (FastRoute\RouteCollector $r) {
    $r->addGroup('/api', function (FastRoute\RouteCollector $r) {
        // Products
        $r->addRoute('GET', '/products', [ProductController::class, 'index']);
        $r->addRoute('POST', '/products', [ProductController::class, 'store']);
        $r->addRoute('GET', '/products/{id:\d+}', [ProductController::class, 'show']);
        $r->addRoute('PUT', '/products/{id:\d+}', [ProductController::class, 'update']);
        $r->addRoute('DELETE', '/products/{id:\d+}', [ProductController::class, 'destroy']);

        // Categories
        $r->addRoute('GET', '/categories', [CategoryController::class, 'index']);
        $r->addRoute('POST', '/categories', [CategoryController::class, 'store']);
    });
};
