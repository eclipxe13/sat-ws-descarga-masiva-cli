<?php

declare(strict_types=1);

namespace PhpCfdi\SatWsDescargaMasiva\CLI\Service;

use PhpCfdi\SatWsDescargaMasiva\RequestBuilder\RequestBuilderInterface;
use PhpCfdi\SatWsDescargaMasiva\Service;
use PhpCfdi\SatWsDescargaMasiva\Shared\ServiceEndpoints;
use PhpCfdi\SatWsDescargaMasiva\Shared\Token;
use PhpCfdi\SatWsDescargaMasiva\WebClient\WebClientInterface;

final class ServiceWithStorageToken extends Service
{
    private readonly StorageToken $storageToken;

    public function __construct(
        RequestBuilderInterface $requestBuilder,
        WebClientInterface $webclient,
        StorageToken $storageToken,
        ServiceEndpoints $endpoints = null
    ) {
        parent::__construct($requestBuilder, $webclient, $storageToken->current(), $endpoints);
        $this->storageToken = $storageToken;
    }

    public function obtainCurrentToken(): Token
    {
        $current = parent::obtainCurrentToken();
        $stored = $this->storageToken->current();
        if (null === $stored || ! $this->tokensAreEqual($stored, $current)) {
            $this->storageToken->store($current);
        }

        return $current;
    }

    private function tokensAreEqual(Token $first, Token $second): bool
    {
        return $this->storageToken->serializeToken($first) === $this->storageToken->serializeToken($second);
    }
}
