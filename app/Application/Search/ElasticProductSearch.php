<?php
declare(strict_types=1);

namespace App\Application\Search;

use App\Infrastructure\Search\Contract\ProductIndexer;
use App\Infrastructure\Search\Contract\ProductSearch;
use Elastic\Elasticsearch\Client;
use Throwable;

final class ElasticProductSearch implements ProductSearch, ProductIndexer
{
    private string $index = 'products';

    public function __construct(private readonly Client $es)
    {
    }

    public function indexOne(array $p): void
    {
        $this->es->index([
            'index' => $this->index,
            'id' => (string)$p['id'],
            'body' => $p,
            'refresh' => true,
        ]);
    }

    public function deleteOne(int $id): void
    {
        $this->es->delete(['index' => $this->index, 'id' => (string)$id, 'refresh' => true]);
    }

    public function search(array $filters, int $limit = 50, int $offset = 0): array
    {
        $must = [];
        if (!empty($filters['name'])) $must[] = ['match' => ['name' => $filters['name']]];
        if (!empty($filters['inn'])) $must[] = ['term' => ['inn' => $filters['inn']]];
        if (!empty($filters['ean13'])) $must[] = ['term' => ['ean13' => $filters['ean13']]];
        if (!empty($filters['category_ids'])) {
            $must[] = ['terms' => ['category_ids' => array_map('intval', (array)$filters['category_ids'])]];
        }
        if (!empty($filters['id'])) {
            $must[] = ['term' => ['id' => (int)$filters['id']]];
        }

        $res = $this->es->search([
            'index' => $this->index,
            'from' => $offset,
            'size' => $limit,
            'body' => ['query' => ['bool' => ['must' => $must]]],
        ]);

        return array_map(fn($h) => $h['_source'], $res['hits']['hits'] ?? []);
    }

    public function getOne(int $id): ?array
    {
        try {
            $r = $this->es->get(['index' => $this->index, 'id' => (string)$id]);
            return $r['_source'] ?? null;
        } catch (Throwable) {
            return null;
        }
    }
}
