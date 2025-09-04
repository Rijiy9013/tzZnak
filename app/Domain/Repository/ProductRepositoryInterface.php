<?php
declare(strict_types=1);

namespace App\Domain\Repository;

interface ProductRepositoryInterface
{
    public function search(array $filters, int $limit = 50, int $offset = 0): array;

    public function find(int $id): ?array;

    public function create(array $data, array $categoryIds): int;

    public function update(int $id, array $data, array $categoryIds): void;

    public function delete(int $id): void;
}
