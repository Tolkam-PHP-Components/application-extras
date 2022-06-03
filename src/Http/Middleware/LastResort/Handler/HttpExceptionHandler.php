<?php declare(strict_types=1);

namespace Tolkam\Application\Extras\Http\Middleware\LastResort\Handler;

use Psr\Http\Message\ResponseInterface;
use Throwable;
use Tolkam\Application\Extras\Http\Middleware\LastResort\HandlerInterface;
use Tolkam\Application\Http\HttpException;

class HttpExceptionHandler implements HandlerInterface
{
    /**
     * @inheritDoc
     */
    public function canHandle(Throwable $t): bool
    {
        return $t instanceof HttpException;
    }

    /**
     * @inheritDoc
     */
    public function shouldLog(Throwable $t): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function handle(Throwable $t, ResponseInterface $response): ResponseInterface
    {
        /** @type HttpException $t */
        $response = $response->withStatus($t->getCode(), $t->getMessage());

        foreach ($t->getHeaders() as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        return $response;
    }
}
