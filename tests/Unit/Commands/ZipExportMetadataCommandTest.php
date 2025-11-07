<?php

declare(strict_types=1);

namespace PhpCfdi\SatWsDescargaMasiva\CLI\Tests\Unit\Commands;

use PhpCfdi\SatWsDescargaMasiva\CLI\Commands\Exceptions\ExecutionException;
use PhpCfdi\SatWsDescargaMasiva\CLI\Commands\ZipExportMetadataCommand;
use PhpCfdi\SatWsDescargaMasiva\CLI\Tests\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class ZipExportMetadataCommandTest extends TestCase
{
    public function testCommandHasExpectedArguments(): void
    {
        $command = new ZipExportMetadataCommand();
        $this->assertTrue($command->getDefinition()->hasArgument('metadata'));
        $this->assertTrue($command->getDefinition()->hasArgument('destino'));
    }

    public function testCommandExecutionWithValidParameters(): void
    {
        $sourceFile = $this->filePath('metadata.zip');
        $destinationFile = $this->createTemporaryName();

        $command = new ZipExportMetadataCommand();
        $tester = new CommandTester($command);
        $validOptions = [
            'metadata' => $sourceFile,
            'destino' => $destinationFile,
        ];
        $exitCode = $tester->execute($validOptions);
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertFileExists($destinationFile);
        $this->assertGreaterThan(0, filesize($destinationFile));
    }

    public function testCommandExecutionWithInvalidSourceFile(): void
    {
        $sourceFile = __DIR__ . '/non-existent.zip';
        $destinationFile = __DIR__ . '/destination-file.xlsx';

        $command = new ZipExportMetadataCommand();
        $tester = new CommandTester($command);
        $validOptions = [
            'metadata' => $sourceFile,
            'destino' => $destinationFile,
        ];

        $executionException = $this->captureException(
            fn (): int => $tester->execute($validOptions),
        );

        $this->assertInstanceOf(ExecutionException::class, $executionException);
        $this->assertStringContainsString('no se pudo abrir', $executionException->getMessage());
        $this->assertFileDoesNotExist($destinationFile);
    }

    public function testCommandExecutionWithSourceFileAsDirectory(): void
    {
        $sourceFile = __DIR__;
        $destinationFile = __DIR__ . '/destination-file.xlsx';

        $command = new ZipExportMetadataCommand();
        $tester = new CommandTester($command);
        $validOptions = [
            'metadata' => $sourceFile,
            'destino' => $destinationFile,
        ];

        $executionException = $this->captureException(
            fn (): int => $tester->execute($validOptions),
        );

        $this->assertInstanceOf(ExecutionException::class, $executionException);
        $this->assertStringContainsString('no se pudo abrir', $executionException->getMessage());
        $this->assertFileDoesNotExist($destinationFile);
    }

    public function testCommandExecutionWithSourceFileAsNonZip(): void
    {
        $sourceFile = __FILE__;
        $destinationFile = __DIR__ . '/destination-file.xlsx';

        $command = new ZipExportMetadataCommand();
        $tester = new CommandTester($command);
        $validOptions = [
            'metadata' => $sourceFile,
            'destino' => $destinationFile,
        ];

        $executionException = $this->captureException(
            fn (): int => $tester->execute($validOptions),
        );

        $this->assertInstanceOf(ExecutionException::class, $executionException);
        $this->assertStringContainsString('no se pudo abrir', $executionException->getMessage());
        $this->assertFileDoesNotExist($destinationFile);
    }

    public function testCommandExecutionWithInvalidDestinationFile(): void
    {
        $sourceFile = $this->filePath('metadata.zip');
        $destinationFile = __DIR__ . '/non-existent-dir/destination-file.xlsx';

        $command = new ZipExportMetadataCommand();
        $tester = new CommandTester($command);
        $validOptions = [
            'metadata' => $sourceFile,
            'destino' => $destinationFile,
        ];

        $executionException = $this->captureException(
            fn (): int => $tester->execute($validOptions),
        );

        $this->assertInstanceOf(ExecutionException::class, $executionException);
        $this->assertStringContainsString('no se pudo escribir', $executionException->getMessage());
        $this->assertFileDoesNotExist($destinationFile);
    }
}
