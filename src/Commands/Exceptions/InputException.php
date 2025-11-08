<?php

declare(strict_types=1);

namespace PhpCfdi\SatWsDescargaMasiva\CLI\Commands\Exceptions;

use Symfony\Component\Console\Exception\RuntimeException;
use Throwable;

class InputException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly string $argumentName,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, previous: $previous);
    }

    public function getArgumentName(): string
    {
        return $this->argumentName;
    }
}
