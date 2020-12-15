<?php declare(strict_types=1);

namespace Tolkam\Application\Extras\Http\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Throwable;
use Tolkam\Application\Extras\Http\Response\ErrorAwareResponseFactoryInterface;
use Tolkam\Application\Http\HttpException;
use Tolkam\Routing\Exception\NotAcceptedException;
use Tolkam\Routing\Exception\NotAllowedException;
use Tolkam\Routing\Exception\NotFoundException;
use Tolkam\Utils\HttpHeader;

class ErrorHandlerMiddleware implements MiddlewareInterface, LoggerAwareInterface
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
     * @var int
     */
    private static int $defaultCode = 500;
    
    /**
     * @param ResponseFactoryInterface $factory
     * @param array                    $contentTypes
     */
    public function __construct(ResponseFactoryInterface $factory, array $contentTypes = null)
    {
        $this->responseFactory = $factory;
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
            
            // provide caught error
            if ($this->responseFactory instanceof ErrorAwareResponseFactoryInterface) {
                $this->responseFactory->setError($t);
            }
            
            $response = $this->responseFactory->createResponse(
                $this->getStatusCode($t),
                $this->getReasonPhrase($t)
            );
            
            foreach ($this->getHeaders($t) as $k => $v) {
                $response = $response->withHeader($k, $v);
            }
            
            // status is still default one - log
            if ($response->getStatusCode() === self::$defaultCode) {
                $message = (string) $t;
                
                if ($this->logger) {
                    $this->logger->error($message);
                }
                else {
                    error_log($message);
                }
            }
        }
        
        return $response;
    }
    
    /**
     * Gets http status code from exception
     *
     * @param Throwable $t
     *
     * @return int
     */
    protected function getStatusCode(Throwable $t): int
    {
        $code = self::$defaultCode;
        
        switch (true) {
            case($t instanceof HttpException):
                $code = $t->getCode();
                break;
            case($t instanceof NotFoundException):
                $code = 404;
                break;
            case($t instanceof NotAllowedException):
                $code = 405;
                break;
            case($t instanceof NotAcceptedException):
                $code = 406;
                break;
        }
        
        return $code;
    }
    
    /**
     * Gets http status message from exception
     *
     * @param Throwable $t
     *
     * @return string
     */
    protected function getReasonPhrase(Throwable $t): string
    {
        $message = '';
        
        if ($t instanceof HttpException) {
            $message = $t->getMessage();
        }
        
        return $message;
    }
    
    /**
     * Gets response headers
     *
     * @param Throwable $t
     *
     * @return array
     */
    protected function getHeaders(Throwable $t): array
    {
        $headers = [];
        
        /** @var mixed $t */
        if ($t instanceof NotAllowedException) {
            $headers['Allow'] = $t->getAllowed();
        }
        
        return $headers;
    }
}
