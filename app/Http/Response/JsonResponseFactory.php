<?php
declare(strict_types=1);

namespace App\Http\Response;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

final readonly class JsonResponseFactory
{
    public function __construct(private ResponseFactoryInterface $response)
    {
    }

    public function json(array $payload, int $code = 200): ResponseInterface
    {
        $res = $this->response->createResponse($code);
        $res->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE));
        return $res
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withHeader('Cache-Control', 'no-store');
    }
}
