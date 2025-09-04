<?php

namespace App\Infrastructure\Search\Contract;

interface ProductSearch
{
    public function search(array $filters, int $limit = 50, int $offset = 0): array;

    public function getOne(int $id): ?array;
}
