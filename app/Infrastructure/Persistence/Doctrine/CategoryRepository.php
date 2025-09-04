<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Repository\CategoryRepositoryInterface;
use Doctrine\DBAL\Connection;

final readonly class CategoryRepository implements CategoryRepositoryInterface
{
    public function __construct(private Connection $db)
    {
    }

    public function all(): array
    {
        return $this->db->createQueryBuilder()
            ->select('id', 'name')
            ->from('categories')
            ->orderBy('name', 'ASC')
            ->fetchAllAssociative();
    }

    public function create(string $name): int
    {
        $this->db->insert('categories', [
            'name' => $name,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        return (int)$this->db->lastInsertId();
    }
}
