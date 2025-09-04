<?php
declare(strict_types=1);

namespace Tests\Domain\ValueObject;

use App\Domain\ValueObject\Ean13;
use PHPUnit\Framework\TestCase;

final class Ean13Test extends TestCase
{
    public function testValid(): void
    {
        $ean = new Ean13('4601234567893'); // корректная КС
        $this->assertSame('4601234567893', $ean->value);
    }

    public function testInvalidLength(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Ean13('123456789012'); // 12 символов
    }

    public function testInvalidChecksum(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Ean13('4601234567890'); // сломанная КС
    }
}
