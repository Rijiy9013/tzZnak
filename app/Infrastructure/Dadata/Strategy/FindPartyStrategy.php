<?php
declare(strict_types=1);

namespace App\Infrastructure\Dadata\Strategy;

use GuzzleHttp\ClientInterface;

final readonly class FindPartyStrategy implements InnCheckStrategyInterface
{
    public function __construct(private ClientInterface $http)
    {
    }

    public function check(string $inn): bool
    {
        $resp = $this->http->request('POST', 'findById/party', [
            'json' => ['query' => $inn],
        ]);
        $data = json_decode((string)$resp->getBody(), true);
        return !empty($data['suggestions']);
    }
}
