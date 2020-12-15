<?php declare(strict_types=1);

namespace Tolkam\Application\Extras\Http\Response;

use Throwable;

interface ErrorAwareResponseFactoryInterface
{
    /**
     * Sets error object
     *
     * @param Throwable $t
     *
     * @return mixed
     */
    public function setError(Throwable $t);
}
