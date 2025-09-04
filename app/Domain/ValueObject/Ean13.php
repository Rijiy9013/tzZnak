<?php
declare(strict_types=1);

namespace App\Domain\ValueObject;

use InvalidArgumentException;

final readonly class Ean13
{
    private const int LENGTH = 13;
    private const int WEIGHT_ODD = 1;
    private const int WEIGHT_EVEN = 3;

    public function __construct(public string $value)
    {
        if (strlen($this->value) !== self::LENGTH || !ctype_digit($this->value)) {
            throw new \InvalidArgumentException('EAN-13 должен состоять из ' . self::LENGTH . ' цифр');
        }
        if (!$this->isValidChecksum($this->value)) {
            throw new InvalidArgumentException('Некорректный EAN-13 (контрольная сумма)');
        }
    }

    private function isValidChecksum(string $e): bool
    {
        $d = array_map('intval', str_split($e));
        $sum = 0;
        for ($i = 0; $i < self::LENGTH - 1; $i++) {
            $sum += $d[$i] * (($i % 2) ? self::WEIGHT_EVEN : self::WEIGHT_ODD);
        }
        $chk = (10 - ($sum % 10)) % 10;
        return $chk === $d[self::LENGTH - 1];
    }
}
