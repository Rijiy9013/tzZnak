<?php
declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Repository\ProductRepositoryInterface;
use App\Domain\ValueObject\Ean13;
use App\Domain\ValueObject\Inn;
use App\Infrastructure\Dadata\DadataClientInterface;
use App\Infrastructure\Search\Contract\HealthChecker;
use App\Infrastructure\Search\Contract\ProductIndexer;
use RuntimeException;

final readonly class ProductService
{
    public function __construct(
        private readonly ProductRepositoryInterface $repo,
        private readonly DadataClientInterface      $dadata,
        private readonly ProductIndexer             $indexer,
        private readonly HealthChecker              $health
    )
    {
    }


    public function list(array $filters, int $limit = 50, int $offset = 0): array
    {
        return $this->repo->search($filters, $limit, $offset);
    }

    public function create(array $payload): int
    {
        $inn = (new Inn((string)$payload['inn']))->value;
        $ean = (new Ean13((string)$payload['ean13']))->value;

        if (!$this->dadata->innExists($inn)) {
            throw new RuntimeException('ИНН отсутствует в госреестре');
        }

        $id = $this->repo->create([
            'name' => (string)$payload['name'],
            'inn' => $inn,
            'ean13' => $ean,
            'description' => $payload['description'] ?? null,
        ], (array)($payload['category_ids'] ?? []));

        if ($this->health->isAlive()) {
            $p = $this->repo->find($id);
            if ($p) $this->indexer->indexOne($p);
        }
        return $id;
    }

    public function update(int $id, array $payload): void
    {
        $inn = (new Inn((string)$payload['inn']))->value;
        $ean = (new Ean13((string)$payload['ean13']))->value;

        if (!$this->dadata->innExists($inn)) {
            throw new RuntimeException('ИНН отсутствует в госреестре');
        }

        $this->repo->update($id, [
            'name' => (string)$payload['name'],
            'inn' => $inn,
            'ean13' => $ean,
            'description' => $payload['description'] ?? null,
        ], (array)($payload['category_ids'] ?? []));

        if ($this->health->isAlive()) {
            $p = $this->repo->find($id);
            if ($p) $this->indexer->indexOne($p);
        }
    }

    public function delete(int $id): void
    {
        $this->repo->delete($id);
        if ($this->health->isAlive()) {
            $this->indexer->deleteOne($id);
        }
    }
}
