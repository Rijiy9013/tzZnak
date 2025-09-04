<?php
declare(strict_types=1);

namespace App\Application\Query;

final readonly class ProductFilters
{
    public function __construct(
        public ?int    $id,
        public ?string $name,
        public ?string $inn,
        public ?string $ean13,
        public array   $categoryIds,
        public int     $limit,
        public int     $offset,
    )
    {
    }

    public static function fromQuery(array $q): self
    {
        $ids = [];
        if (!empty($q['category_ids'])) {
            $ids = is_array($q['category_ids'])
                ? array_map('intval', $q['category_ids'])
                : array_filter(array_map('intval', explode(',', (string)$q['category_ids'])));
        }
        return new self(
            id: isset($q['id']) ? (int)$q['id'] : null,
            name: isset($q['name']) ? (string)$q['name'] : null,
            inn: isset($q['inn']) ? (string)$q['inn'] : null,
            ean13: isset($q['ean13']) ? (string)$q['ean13'] : null,
            categoryIds: $ids,
            limit: max(1, min(1000, (int)($q['limit'] ?? 50))),
            offset: max(0, (int)($q['offset'] ?? 0)),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'name' => $this->name,
            'inn' => $this->inn,
            'ean13' => $this->ean13,
            'category_ids' => $this->categoryIds,
        ], static fn($v) => $v !== null && $v !== []);
    }
}
