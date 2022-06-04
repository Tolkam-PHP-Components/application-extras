<?php declare(strict_types=1);

namespace Tolkam\Application\Extras\Http\Middleware\LastResort\Handler;

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
    public function getStatusCode(Throwable $t): int
    {
        /** @type HttpException $t */
        return $t->getCode();
    }

    /**
     * @inheritDoc
     */
    public function getReasonPhrase(Throwable $t): ?string
    {
        /** @type HttpException $t */
        return $t->getMessage();
    }

    /**
     * @inheritDoc
     */
    public function getHeaders(Throwable $t): ?array
    {
        /** @type HttpException $t */
        return $t->getHeaders();
    }
}
