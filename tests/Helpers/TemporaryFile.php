<?php

declare(strict_types=1);

namespace PhpCfdi\SatWsDescargaMasiva\CLI\Tests\Helpers;

use RuntimeException;

/**
 * Class to create a temporary file and remove it on object destruction
 * @internal
 */
final readonly class TemporaryFile
{
    private string $path;

    public function __construct(string $prefix = '', string $directory = '', private bool $remove = true)
    {
        $tempnam = tempnam($directory, $prefix);
        if (false === $tempnam) {
            throw new RuntimeException('Unable to create a temporary file'); // @codeCoverageIgnore
        }
        $this->path = $tempnam;
    }

    public function __destruct()
    {
        if ($this->remove) {
            $this->delete();
        }
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getContents(): string
    {
        return (string) file_get_contents($this->path);
    }

    public function putContents(string $data): void
    {
        file_put_contents($this->path, $data);
    }

    public function delete(): void
    {
        if (file_exists($this->path)) {
            /** @noinspection PhpUsageOfSilenceOperatorInspection */
            @unlink($this->path);
        }
    }
}
