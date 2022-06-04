<?php declare(strict_types=1);

namespace Tolkam\Application\Extras\Http\Middleware\LastResort;

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
     * @param Throwable $t
     *
     * @return int
     */
    public function getStatusCode(Throwable $t): int;

    /**
     * @param Throwable $t
     *
     * @return string|null
     */
    public function getReasonPhrase(Throwable $t): ?string;

    /**
     * @param Throwable $t
     *
     * @return array|null
     */
    public function getHeaders(Throwable $t): ?array;
}
