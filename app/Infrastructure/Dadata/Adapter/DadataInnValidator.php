<?php
declare(strict_types=1);

namespace App\Infrastructure\Dadata\Adapter;

use App\Infrastructure\Dadata\DadataClientInterface;
use App\Infrastructure\Dadata\Strategy\InnCheckStrategyInterface;

final readonly class DadataInnValidator implements DadataClientInterface
{
    public function __construct(private InnCheckStrategyInterface $strategy)
    {
    }

    public function innExists(string $inn): bool
    {
        return $this->strategy->check($inn);
    }
}
