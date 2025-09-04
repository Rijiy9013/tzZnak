<?php
declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Repository\CategoryRepositoryInterface;
use Doctrine\DBAL\Connection;
use InvalidArgumentException;

final readonly class CategoryService
{
    public function __construct(
        private CategoryRepositoryInterface $repo,
        private Connection                  $db,
    )
    {
    }

    public function list(): array
    {
        return $this->repo->all();
    }

    public function create(string $rawName): int
    {
        $name = $this->normalizeName($rawName);
        if ($name === '') {
            throw new InvalidArgumentException('name is required');
        }

        $existingId = $this->db->fetchOne(
            'SELECT id FROM categories WHERE name = ? LIMIT 1',
            [$name]
        );
        if ($existingId) {
            return (int)$existingId;
        }

        return $this->db->transactional(function () use ($name) {
            return $this->repo->create($name);
        });
    }

    private function normalizeName(string $v): string
    {
        return trim($v);
    }
}
