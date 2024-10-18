<?php

declare(strict_types=1);

namespace PhpCfdi\SatWsDescargaMasiva\CLI\Service;

final readonly class ConfigValues
{
    public function __construct(
        public string $certificate,
        public string $privateKey,
        public string $passPhrase,
        public string $tokenFile,
    ) {
    }

    public static function empty(): self
    {
        return new self('', '', '', '');
    }
}
