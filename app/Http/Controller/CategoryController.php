<?php
declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\Service\CategoryService;
use App\Http\Response\JsonResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final readonly class CategoryController
{
    public function __construct(
        private CategoryService     $service,
        private JsonResponseFactory $rf
    )
    {
    }

    public function index(ServerRequestInterface $req): ResponseInterface
    {
        return $this->rf->json(['data' => $this->service->list()]);
    }

    public function store(ServerRequestInterface $req): ResponseInterface
    {
        $json = (array)$req->getAttribute('json', []);
        if (empty($json['name'])) {
            return $this->rf->json(['error' => 'missing name'], 422);
        }
        try {
            $id = $this->service->create((string)$json['name']);
            return $this->rf->json(['id' => $id], 201);
        } catch (Throwable $e) {
            return $this->rf->json(['error' => $e->getMessage()], 422);
        }
    }
}
