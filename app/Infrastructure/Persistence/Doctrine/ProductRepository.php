<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Repository\ProductRepositoryInterface;
use Doctrine\DBAL\Connection;

final readonly class ProductRepository implements ProductRepositoryInterface
{
    public function __construct(private Connection $db)
    {
    }

    public function search(array $filters, int $limit = 50, int $offset = 0): array
    {
        $qb = $this->db->createQueryBuilder()
            ->select('p.*', 'GROUP_CONCAT(pc.category_id) AS category_ids')
            ->from('products', 'p')
            ->leftJoin('p', 'product_category', 'pc', 'pc.product_id = p.id')
            ->groupBy('p.id')
            ->setMaxResults($limit)->setFirstResult($offset);

        if (!empty($filters['id'])) {
            $qb->andWhere('p.id = :id')->setParameter('id', (int)$filters['id']);
        }
        if (!empty($filters['name'])) {
            $qb->andWhere('p.name LIKE :name')->setParameter('name', '%' . $filters['name'] . '%');
        }
        if (!empty($filters['inn'])) {
            $qb->andWhere('p.inn = :inn')->setParameter('inn', $filters['inn']);
        }
        if (!empty($filters['ean13'])) {
            $qb->andWhere('p.ean13 = :ean')->setParameter('ean', $filters['ean13']);
        }
        if (!empty($filters['category_ids'])) {
            $ids = array_map('intval', (array)$filters['category_ids']);
            $qb->andWhere('pc.category_id IN (:cids)')->setParameter('cids', $ids, Connection::PARAM_INT_ARRAY);
        }

        $rows = $qb->fetchAllAssociative();
        foreach ($rows as &$r) {
            $r['category_ids'] = $r['category_ids'] === null || $r['category_ids'] === ''
                ? []
                : array_map('intval', array_filter(explode(',', (string)$r['category_ids'])));
        }
        return $rows;
    }

    public function find(int $id): ?array
    {
        $row = $this->db->createQueryBuilder()
            ->select('p.*', 'GROUP_CONCAT(pc.category_id) AS category_ids')
            ->from('products', 'p')
            ->leftJoin('p', 'product_category', 'pc', 'pc.product_id = p.id')
            ->where('p.id = :id')
            ->groupBy('p.id')
            ->setParameter('id', $id)
            ->fetchAssociative();

        if (!$row) return null;
        $row['category_ids'] = $row['category_ids'] === null || $row['category_ids'] === ''
            ? []
            : array_map('intval', array_filter(explode(',', (string)$row['category_ids'])));
        return $row;
    }

    public function create(array $data, array $categoryIds): int
    {
        $this->db->insert('products', [
            'name' => $data['name'],
            'inn' => $data['inn'],
            'ean13' => $data['ean13'],
            'description' => $data['description'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $id = (int)$this->db->lastInsertId();
        $this->syncCategories($id, $categoryIds);
        return $id;
    }

    public function update(int $id, array $data, array $categoryIds): void
    {
        $this->db->update('products', [
            'name' => $data['name'],
            'inn' => $data['inn'],
            'ean13' => $data['ean13'],
            'description' => $data['description'] ?? null,
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $id]);

        $this->syncCategories($id, $categoryIds);
    }

    public function delete(int $id): void
    {
        $this->db->delete('product_category', ['product_id' => $id]);
        $this->db->delete('products', ['id' => $id]);
    }

    private function syncCategories(int $productId, array $categoryIds): void
    {
        $this->db->delete('product_category', ['product_id' => $productId]);
        foreach (array_unique(array_map('intval', $categoryIds)) as $cid) {
            $this->db->insert('product_category', ['product_id' => $productId, 'category_id' => $cid]);
        }
    }
}
