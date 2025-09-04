<?php

namespace App\Infrastructure\Search\Contract;
interface ProductIndexer
{
    public function indexOne(array $p): void;

    public function deleteOne(int $id): void;
}
