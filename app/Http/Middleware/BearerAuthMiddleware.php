<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Response\JsonResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class BearerAuthMiddleware
{
    public function __construct(
        private JsonResponseFactory $rf,
        private string              $token
    )
    {
    }

    public function handle(ServerRequestInterface $req, callable $next): ResponseInterface
    {
        $auth = $req->getHeaderLine('Authorization');

        if (!\preg_match('/^Bearer\s+(.+)$/i', $auth, $m) || trim($m[1]) !== $this->token) {
            $res = $this->rf->json(['error' => 'Unauthorized'], 401);
            return $res->withHeader('WWW-Authenticate', 'Bearer');
        }

        return $next($req);
    }
}
