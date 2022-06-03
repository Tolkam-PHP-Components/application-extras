<?php declare(strict_types=1);

namespace Tolkam\Application\Extras\Http\Middleware\LastResort;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Throwable;
use Tolkam\Application\Extras\Http\Response\ErrorAwareResponseFactoryInterface;
use Tolkam\Utils\HttpHeader;

class LastResortMiddleware implements MiddlewareInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var ResponseFactoryInterface
     */
    protected ResponseFactoryInterface $responseFactory;

    /**
     * Restrict to request mime types that handler should handle
     * @var array|null
     */
    protected ?array $contentTypes = null;

    /**
     * @var HandlerInterface[]
     */
    private array $throwableHandlers = [];

    /**
     * @param ResponseFactoryInterface $defaultResponseFactory
     * @param array|null               $contentTypes
     */
    public function __construct(ResponseFactoryInterface $defaultResponseFactory, array $contentTypes = null)
    {
        $this->responseFactory = $defaultResponseFactory;
        $this->contentTypes = $contentTypes;
    }

    /**
     * @inheritDoc
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {

        try {
            $response = $handler->handle($request);
        } catch (Throwable $t) {

            // response type is not acceptable by requester -
            // rethrow to upper handler
            if (
                $this->contentTypes &&
                !HttpHeader::isAccepted($request->getHeaderLine('Accept'), $this->contentTypes)
            ) {
                throw $t;
            }

            // provide with caught error
            if ($this->responseFactory instanceof ErrorAwareResponseFactoryInterface) {
                $this->responseFactory->setError($t);
            }

            $handled = null;
            $response = $this->responseFactory->createResponse();
            foreach ($this->throwableHandlers as $throwableHandler) {
                if (false === $throwableHandler->canHandle($t)) {
                    continue;
                }

                $response = $throwableHandler->handle($t, $response);
                if ($throwableHandler->shouldLog($t)) {
                    $this->log($t);
                }
                $handled = true;
            }

            // no handler was able to handle
            if ($handled === null) {
                throw $t;
            }
        }

        return $response;
    }

    /**
     * Sets the response factory
     *
     * @param ResponseFactoryInterface $responseFactory
     *
     * @return self
     */
    public function setResponseFactory(ResponseFactoryInterface $responseFactory): self
    {
        $this->responseFactory = $responseFactory;

        return $this;
    }

    /**
     * Sets the content types
     *
     * @param array|null $contentTypes
     *
     * @return self
     */
    public function setContentTypes(?array $contentTypes): self
    {
        $this->contentTypes = $contentTypes;

        return $this;
    }

    /**
     * @param HandlerInterface $throwableHandler
     *
     * @return $this
     */
    public function addHandler(HandlerInterface $throwableHandler): self
    {
        $this->throwableHandlers[] = $throwableHandler;

        return $this;
    }

    /**
     * @param HandlerInterface[] $throwableHandlers
     *
     * @return $this
     */
    public function addHandlers(array $throwableHandlers): self
    {
        foreach ($throwableHandlers as $v) {
            $this->addHandler($v);
        }

        return $this;
    }

    /**
     * @param Throwable $t
     *
     * @return void
     */
    protected function log(Throwable $t): void
    {
        $message = (string) $t;

        if ($this->logger) {
            $this->logger->error($message);
        }
        else {
            error_log($message);
        }
    }
}
