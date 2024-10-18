<?php

declare(strict_types=1);

namespace PhpCfdi\SatWsDescargaMasiva\CLI\Service;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use PhpCfdi\SatWsDescargaMasiva\WebClient\GuzzleWebClient;
use PhpCfdi\SatWsDescargaMasiva\WebClient\Request;
use PhpCfdi\SatWsDescargaMasiva\WebClient\Response;
use Psr\Log\LoggerInterface;

final class GuzzleWebClientWithLogger extends GuzzleWebClient
{
    public function __construct(?Client $client, private readonly LoggerInterface $logger)
    {
        parent::__construct(
            $client,
            function (Request $request): void {
                $this->logRequest($request);
            },
            function (Response $response): void {
                $this->logResponse($response);
            }
        );
    }

    public static function createDefault(LoggerInterface $logger): self
    {
        return new self(
            new Client([
                RequestOptions::CONNECT_TIMEOUT => 10,
                RequestOptions::TIMEOUT => 30,
            ]),
            $logger
        );
    }

    private function logRequest(Request $request): void
    {
        $this->logger->debug(implode(PHP_EOL, [
            'Request: ' . $request->getMethod() . ' ' . $request->getUri(),
            'Headers: ' . $this->headersToStrings($request->getHeaders()),
            'Body: ' . $request->getBody(),
        ]));
    }

    private function logResponse(Response $response): void
    {
        $this->logger->debug(implode(PHP_EOL, [
            'Response: ' . $response->getStatusCode(),
            'Headers: ' . $this->headersToStrings($response->getHeaders()),
            'Body: ' . $response->getBody(),
        ]));
    }

    /** @param array<string, string> $headers */
    private function headersToStrings(array $headers): string
    {
        $contents = [];
        foreach ($headers as $header => $content) {
            $contents[] = $header . ': ' . $content;
        }
        return implode(PHP_EOL . '  ', $contents);
    }
}
