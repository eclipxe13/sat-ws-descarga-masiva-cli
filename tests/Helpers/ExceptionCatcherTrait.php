<?php

declare(strict_types=1);

namespace PhpCfdi\SatWsDescargaMasiva\CLI\Tests\Helpers;

use Closure;
use RuntimeException;
use Throwable;

trait ExceptionCatcherTrait
{
    public function catch(Closure $function): Throwable
    {
        try {
            $function();
            throw new RuntimeException('No exception was thrown');
        } catch (Throwable $exception) {
            return $exception;
        }
    }
}
