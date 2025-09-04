<?php
declare(strict_types=1);

namespace Tests\Domain\ValueObject;

use App\Domain\ValueObject\Inn;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class InnTest extends TestCase
{
    public function testValid10(): void
    {
        $inn = new Inn('7707083893'); // валидный 10-значный
        $this->assertSame('7707083893', $inn->value);
    }

    public function testValid12(): void
    {
        $inn = new Inn('100000000074'); // валидный 12-значный
        $this->assertSame('100000000074', $inn->value);
    }

    public function testInvalidLength(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Inn('123'); // короткий
    }

    public function testInvalidChecksum(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Inn('7707083890'); // неправильная КС
    }
}
