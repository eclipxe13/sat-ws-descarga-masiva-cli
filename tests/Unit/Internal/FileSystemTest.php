<?php

declare(strict_types=1);

namespace PhpCfdi\SatWsDescargaMasiva\CLI\Tests\Unit\Internal;

use PhpCfdi\SatWsDescargaMasiva\CLI\Internal\Filesystem;
use PhpCfdi\SatWsDescargaMasiva\CLI\Tests\TestCase;
use RuntimeException;

final class FileSystemTest extends TestCase
{
    public function testReadWithEmptyPathThrowsException(): void
    {
        $fs = new Filesystem();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Path cannot be empty');
        $fs->read('');
    }

    public function testWriteWithEmptyPathThrowsException(): void
    {
        $fs = new Filesystem();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Path cannot be empty');
        $fs->write('', '');
    }

    public function testIsDirectoryWithEmptyPathThrowsException(): void
    {
        $fs = new Filesystem();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Path cannot be empty');
        $fs->isDirectory('');
    }

    public function testIsWritableWithEmptyPathThrowsException(): void
    {
        $fs = new Filesystem();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Path cannot be empty');
        $fs->isWritable('');
    }

    public function testExistsWithEmptyPathThrowsException(): void
    {
        $fs = new Filesystem();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Path cannot be empty');
        $fs->exists('');
    }

    public function testWriteToDirectoryThrowsError(): void
    {
        $fs = new Filesystem();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('is a directory');
        $fs->write(__DIR__, 'foo');
    }

    public function testPathAbsoluteOrRelativeWithEmptyStringReturnEmptyString(): void
    {
        $fs = new Filesystem();
        $this->assertSame('', $fs->pathAbsoluteOrRelative('', ''));
    }

    public function testPathAbsoluteOrRelativeWithAbsolutePathReturnSameAbsolutePath(): void
    {
        $fs = new Filesystem();
        $this->assertSame('/foo', $fs->pathAbsoluteOrRelative('/foo', ''));
    }
}
