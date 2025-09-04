<?php
declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\Query\ProductFilters;
use App\Application\Query\ProductQueryService;
use App\Application\Service\ProductService;
use App\Http\Response\JsonResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ProductController
{
    public function __construct(
        private ProductService $service,
        private ProductQueryService $queries,
        private JsonResponseFactory $rf
    )
    {
    }

    public function index(ServerRequestInterface $req): ResponseInterface
    {
        $filters = ProductFilters::fromQuery($req->getQueryParams());
        $res = $this->queries->list($filters);
        return $this->rf->json($res);
    }

    public function show(ServerRequestInterface $req, array $args): ResponseInterface
    {
        $id = (int)$args['id'];
        $p = $this->queries->get($id);
        return $p ? $this->rf->json(['data' => $p]) : $this->rf->json(['error' => 'Not Found'], 404);
    }

    public function store(ServerRequestInterface $req): ResponseInterface
    {
        $json = (array)$req->getAttribute('json', []);
        try {
            foreach (['name', 'inn', 'ean13'] as $r) {
                if (!array_key_exists($r, $json)) throw new \InvalidArgumentException("missing '$r'");
            }
            $id = $this->service->create($json);
            return $this->rf->json(['id' => $id], 201);
        } catch (\Throwable $e) {
            return $this->rf->json(['error' => $e->getMessage()], 422);
        }
    }

    public function update(ServerRequestInterface $req, array $args): ResponseInterface
    {
        $id = (int)$args['id'];
        $json = (array)$req->getAttribute('json', []);
        try {
            foreach (['name', 'inn', 'ean13'] as $r) {
                if (!array_key_exists($r, $json)) throw new \InvalidArgumentException("missing '$r'");
            }
            $this->service->update($id, $json);
            return $this->rf->json(['ok' => true]);
        } catch (\Throwable $e) {
            return $this->rf->json(['error' => $e->getMessage()], 422);
        }
    }

    public function destroy(ServerRequestInterface $req, array $args): ResponseInterface
    {
        $id = (int)$args['id'];
        $this->service->delete($id);
        return $this->rf->json(['ok' => true]);
    }
}
