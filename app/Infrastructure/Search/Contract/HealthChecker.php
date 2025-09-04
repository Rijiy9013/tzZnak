<?php

namespace App\Infrastructure\Search\Contract;
interface HealthChecker
{
    public function isAlive(): bool;
}
