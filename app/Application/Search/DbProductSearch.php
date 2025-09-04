<?php
declare(strict_types=1);

namespace App\Application\Search;

use App\Domain\Repository\ProductRepositoryInterface;

final readonly class DbProductSearch
{
    public function __construct(private ProductRepositoryInterface $repo)
    {
    }

    public function search(array $filters, int $limit = 50, int $offset = 0): array
    {
        return $this->repo->search($filters, $limit, $offset);
    }

    public function getOne(int $id): ?array
    {
        return $this->repo->find($id);
    }
}
