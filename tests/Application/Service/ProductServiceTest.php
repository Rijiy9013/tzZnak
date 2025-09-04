<?php
declare(strict_types=1);

namespace Tests\Application\Service;

use App\Application\Service\ProductService;
use App\Domain\Repository\ProductRepositoryInterface;
use App\Infrastructure\Dadata\DadataClientInterface;
use App\Infrastructure\Search\Contract\HealthChecker;
use App\Infrastructure\Search\Contract\ProductIndexer;
use PHPUnit\Framework\TestCase;

final class ProductServiceTest extends TestCase
{
    private ProductRepositoryInterface $repo;
    private DadataClientInterface $dadata;
    private ProductIndexer $indexer;
    private HealthChecker $health;

    protected function setUp(): void
    {
        $this->repo = $this->createMock(ProductRepositoryInterface::class);
        $this->dadata = $this->createMock(DadataClientInterface::class);
        $this->indexer = $this->createMock(ProductIndexer::class);
        $this->health = $this->createMock(HealthChecker::class);
    }

    private function service(): ProductService
    {
        return new ProductService($this->repo, $this->dadata, $this->indexer, $this->health);
    }

    public function testCreateSuccessIndexesWhenEsAlive(): void
    {
        $payload = [
            'name' => 'Milk',
            'inn' => '7707083893',
            'ean13' => '4601234567893',
            'description' => 'demo',
            'category_ids' => [1, 2],
        ];

        $this->dadata->method('innExists')->with('7707083893')->willReturn(true);
        $this->repo->expects($this->once())->method('create')->willReturn(42);
        $this->health->method('isAlive')->willReturn(true);
        $this->repo->method('find')->with(42)->willReturn(['id' => 42, 'name' => 'Milk', 'inn' => '7707083893', 'ean13' => '4601234567893', 'category_ids' => [1, 2]]);
        $this->indexer->expects($this->once())->method('indexOne')->with($this->arrayHasKey('id'));

        $id = $this->service()->create($payload);
        $this->assertSame(42, $id);
    }

    public function testCreateFailsWhenDadataSaysNo(): void
    {
        $this->dadata->method('innExists')->willReturn(false);
        $this->expectException(\RuntimeException::class);
        $this->service()->create([
            'name' => 'X', 'inn' => '7707083893', 'ean13' => '4601234567893'
        ]);
    }

    public function testDeleteRemovesFromEsWhenAlive(): void
    {
        $this->repo->expects($this->once())->method('delete')->with(7);
        $this->health->method('isAlive')->willReturn(true);
        $this->indexer->expects($this->once())->method('deleteOne')->with(7);
        $this->service()->delete(7);
    }

    public function testDeleteSkipsEsWhenDown(): void
    {
        $this->repo->expects($this->once())->method('delete')->with(7);
        $this->health->method('isAlive')->willReturn(false);
        $this->indexer->expects($this->never())->method('deleteOne');
        $this->service()->delete(7);
    }
}
