<?php
declare(strict_types=1);

namespace App\Application\Search;

use App\Infrastructure\Search\Contract\HealthChecker;
use Elastic\Elasticsearch\Client;
use Throwable;

final readonly class ElasticHealthChecker implements HealthChecker
{
    public function __construct(private Client $es)
    {
    }

    public function isAlive(): bool
    {
        try {
            $this->es->ping();
            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
