<?php

declare(strict_types=1);

namespace PhpCfdi\SatWsDescargaMasiva\CLI\Commands\Exceptions;

use Symfony\Component\Console\Exception\RuntimeException;
use Throwable;

final class ExecutionException extends RuntimeException
{
    public static function make(string $message, ?Throwable $previous = null): self
    {
        return new self($message, previous: $previous);
    }
}
