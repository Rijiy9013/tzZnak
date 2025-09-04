<?php
declare(strict_types=1);

namespace App\Domain\ValueObject;

use InvalidArgumentException;

final readonly class Inn
{
    private const int LEN_10 = 10;
    private const int LEN_12 = 12;

    private const array WEIGHTS_10 = [2, 4, 10, 3, 5, 9, 4, 6, 8];

    private const array WEIGHTS_12_1 = [7, 2, 4, 10, 3, 5, 9, 4, 6, 8, 0, 0];

    private const array WEIGHTS_12_2 = [3, 7, 2, 4, 10, 3, 5, 9, 4, 6, 8, 0];

    public function __construct(public string $value)
    {
        $len = strlen($this->value);

        if (!\ctype_digit($this->value) || ($len !== self::LEN_10 && $len !== self::LEN_12)) {
            throw new InvalidArgumentException("ИНН должен быть {self::LEN_10} или 12 цифр");
        }
        if (!$this->isValidChecksum($this->value)) {
            throw new InvalidArgumentException('Некорректный ИНН (контрольная сумма)');
        }
    }

    private function isValidChecksum(string $inn): bool
    {
        $d = array_map('intval', str_split($inn));
        $calc = function (array $weights) use ($d): int {
            $s = 0;
            foreach ($weights as $i => $w) {
                $s += $d[$i] * $w;
            }
            return ($s % 11) % 10;
        };

        $len = strlen($inn);

        if ($len === self::LEN_10) {
            return $d[9] === $calc(self::WEIGHTS_10);
        }

        if ($len === self::LEN_12) {
            $n11 = $calc(self::WEIGHTS_12_1);
            $n12 = $calc(self::WEIGHTS_12_2);
            return $d[10] === $n11 && $d[11] === $n12;
        }

        return false;
    }
}
