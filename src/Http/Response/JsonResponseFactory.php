<?php declare(strict_types=1);

namespace Tolkam\Application\Extras\Http\Response;

use Laminas\Diactoros\Response;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

class JsonResponseFactory implements ResponseFactoryInterface
{
    /**
     * @var array
     */
    private array $options = [
        'contentType' => 'application/json',
        'jsonOptions' => JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE
            | JSON_THROW_ON_ERROR,
    ];
    
    /**
     * @param array|null $options
     */
    public function __construct(array $options = null)
    {
        if ($options) {
            $this->options = array_replace($this->options, $options);
        }
    }
    
    /**
     * @inheritDoc
     */
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        return $this->makeResponse($code, $reasonPhrase);
    }
    
    /**
     * @param null     $payload
     * @param int|null $jsonOptions
     *
     * @return ResponseInterface
     */
    public function success($payload = null, int $jsonOptions = null): ResponseInterface
    {
        return $this->withPayload(
            $this->makeResponse(),
            $payload,
            $jsonOptions
        );
    }
    
    /**
     * @param int $code
     *
     * @return ResponseInterface
     */
    public function error(int $code): ResponseInterface
    {
        return $this->makeResponse($code);
    }
    
    /**
     * @param ResponseInterface $response
     * @param                   $payload
     * @param int|null          $jsonOptions
     *
     * @return ResponseInterface
     */
    private function withPayload(
        ResponseInterface $response,
        $payload,
        int $jsonOptions = null
    ): ResponseInterface {
        $jsonOptions = $jsonOptions ?? (int) $this->options['jsonOptions'];
        
        $response
            ->getBody()
            ->write(json_encode($payload, $jsonOptions));
        
        return $response;
    }
    
    /**
     * @param int    $code
     * @param string $reasonPhrase
     *
     * @return ResponseInterface
     */
    private function makeResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        return (new Response)
            ->withStatus($code, $reasonPhrase)
            ->withHeader('Content-Type', (string) $this->options['contentType']);
    }
}
