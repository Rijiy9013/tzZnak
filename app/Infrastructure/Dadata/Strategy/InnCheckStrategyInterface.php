<?php
declare(strict_types=1);

namespace App\Infrastructure\Dadata\Strategy;

interface InnCheckStrategyInterface
{
    public function check(string $inn): bool;
}
