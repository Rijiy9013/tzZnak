<?php
declare(strict_types=1);

namespace App\Domain\Repository;

interface CategoryRepositoryInterface
{
    public function all(): array;

    public function create(string $name): int;
}
