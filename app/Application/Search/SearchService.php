<?php
declare(strict_types=1);

namespace App\Application\Search;

use App\Infrastructure\Search\Contract\HealthChecker;
use App\Infrastructure\Search\Contract\ProductSearch;

final readonly class SearchService
{
    public function __construct(
        private readonly HealthChecker $health,
        private readonly ProductSearch $es,
        private readonly ProductSearch $db
    )
    {
    }


    public function findProducts(array $filters, int $limit = 50, int $offset = 0): array
    {
        return $this->health->isAlive()
            ? $this->es->search($filters, $limit, $offset)
            : $this->db->search($filters, $limit, $offset);
    }

    public function getProductById(int $id): ?array
    {
        if ($this->health->isAlive()) {
            $found = $this->es->getOne($id);
            if ($found) return $found;
        }
        return $this->db->getOne($id);
    }
}
