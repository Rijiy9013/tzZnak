<?php
declare(strict_types=1);

namespace Tests\Application\Search;

use App\Application\Search\SearchService;
use App\Infrastructure\Search\Contract\ExternalProductSearch;
use App\Infrastructure\Search\Contract\HealthChecker;
use App\Infrastructure\Search\Contract\InternalProductSearch;
use PHPUnit\Framework\TestCase;

final class SearchServiceTest extends TestCase
{
    public function testFindUsesEsWhenAlive(): void
    {
        $health = $this->createMock(HealthChecker::class);
        $es = $this->createMock(ExternalProductSearch::class);
        $db = $this->createMock(InternalProductSearch::class);

        $health->method('isAlive')->willReturn(true);
        $es->method('search')->with(['name' => 'Milk'], 10, 0)->willReturn([['id' => 1, 'name' => 'Milk']]);

        $svc = new SearchService($health, $es, $db);
        $this->assertSame([['id' => 1, 'name' => 'Milk']], $svc->findProducts(['name' => 'Milk'], 10, 0));
    }

    public function testFindUsesDbWhenEsDown(): void
    {
        $health = $this->createMock(HealthChecker::class);
        $es = $this->createMock(ExternalProductSearch::class);
        $db = $this->createMock(InternalProductSearch::class);

        $health->method('isAlive')->willReturn(false);
        $db->method('search')->with(['name' => 'Milk'], 10, 0)->willReturn([['id' => 2, 'name' => 'Milk DB']]);

        $svc = new SearchService($health, $es, $db);
        $this->assertSame([['id' => 2, 'name' => 'Milk DB']], $svc->findProducts(['name' => 'Milk'], 10, 0));
    }
}
