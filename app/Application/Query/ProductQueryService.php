<?php
declare(strict_types=1);

namespace App\Application\Query;

use App\Application\Search\SearchService;

final readonly class ProductQueryService
{
    public function __construct(private SearchService $search)
    {
    }

    public function list(ProductFilters $f): array
    {
        $data = $this->search->findProducts($f->toArray(), $f->limit, $f->offset);
        return [
            'data' => $data,
            'meta' => ['limit' => $f->limit, 'offset' => $f->offset, 'count' => count($data)],
        ];
    }

    public function get(int $id): ?array
    {
        return $this->search->getProductById($id);
    }
}
