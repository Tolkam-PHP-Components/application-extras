<?php declare(strict_types=1);

namespace Tolkam\Application\Extras\Http\Middleware\LastResort;

use Psr\Http\Message\ResponseInterface;
use Throwable;

interface HandlerInterface
{
    /**
     * @param Throwable $t
     *
     * @return bool
     */
    public function canHandle(Throwable $t): bool;

    /**
     * @param Throwable $t
     *
     * @return bool
     */
    public function shouldLog(Throwable $t): bool;

    /**
     * @param Throwable         $t
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    public function handle(Throwable $t, ResponseInterface $response): ResponseInterface;
}
