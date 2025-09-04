<?php
declare(strict_types=1);

namespace App\Infrastructure\Dadata;

interface DadataClientInterface
{
    public function innExists(string $inn): bool;
}
