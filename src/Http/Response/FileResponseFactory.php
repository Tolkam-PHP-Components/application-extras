<?php declare(strict_types=1);

namespace Tolkam\Application\Extras\Http\Response;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\Stream;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

class FileResponseFactory implements ResponseFactoryInterface
{
    /**
     * @var array
     */
    private array $options = [
        // filename safe characters pattern
        'safeCharacters' => '\p{Cyrillic}\p{Latin}_\S\-',
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
    public function createResponse(
        int $code = 200,
        string $reasonPhrase = ''
    ): ResponseInterface {
        return $this->makeResponse($code, $reasonPhrase);
    }

    /**
     * @param             $resource
     * @param bool        $download
     * @param string|null $filename
     *
     * @return ResponseInterface
     */
    public function streamFrom(
        $resource,
        bool $download = false,
        string $filename = null
    ): ResponseInterface {
        $stream = new Stream($resource);
        $response = $this->makeResponse()->withBody($stream);

        if ($download) {
            $filename = $this->sanitizeFilename(
                $filename ?? basename($stream->getMetadata('uri'))
            );

            $response = $response
                ->withHeader(
                    'Cache-Control',
                    'private, no-store, no-cache, must-revalidate, max-age=0'
                )
                ->withHeader(
                    'Accept-Ranges',
                    'bytes'
                )
                ->withHeader(
                    'Content-Disposition',
                    'attachment; filename="' . $filename . '"'
                );
        }

        return $response;
    }

    /**
     * @param int    $code
     * @param string $reasonPhrase
     *
     * @return ResponseInterface
     */
    public function error(int $code, string $reasonPhrase = ''): ResponseInterface
    {
        return $this->makeResponse($code, $reasonPhrase);
    }

    /**
     * @param int    $code
     * @param string $reasonPhrase
     *
     * @return ResponseInterface
     */
    private function makeResponse(
        int $code = 200,
        string $reasonPhrase = ''
    ): ResponseInterface {
        return (new Response())->withStatus($code, $reasonPhrase);
    }

    /**
     * @param string $filename
     *
     * @return string
     */
    private function sanitizeFilename(string $filename): string
    {
        return preg_replace(
            '~[^' . $this->options['safeCharacters'] . ']+~u',
            '-',
            $filename
        );
    }
}
