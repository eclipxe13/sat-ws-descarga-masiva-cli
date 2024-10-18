<?php

declare(strict_types=1);

namespace PhpCfdi\SatWsDescargaMasiva\CLI\Tests;

use Closure;
use RuntimeException;
use Throwable;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    /** @var string[] */
    protected array $temporaryFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->temporaryFiles = [];
    }

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $temporaryFile) {
            unlink($temporaryFile);
        }
        parent::tearDown();
    }

    public static function createTemporaryName(): string
    {
        $tempnam = tempnam('', '');
        if (false === $tempnam) {
            throw new RuntimeException('Unable to create a temporary file name');
        }
        return $tempnam;
    }

    public static function filePath(string $path): string
    {
        return __DIR__ . '/_files/' . $path;
    }

    public static function fileContents(string $path): string
    {
        return file_get_contents(static::filePath($path)) ?: '';
    }

    public static function captureException(Closure $function): Throwable
    {
        try {
            $function();
        } catch (Throwable $exception) {
            return $exception;
        }
        throw new RuntimeException('No exception was thrown');
    }
}
