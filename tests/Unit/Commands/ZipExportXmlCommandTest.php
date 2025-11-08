<?php

declare(strict_types=1);

namespace PhpCfdi\SatWsDescargaMasiva\CLI\Tests\Unit\Commands;

use PhpCfdi\SatWsDescargaMasiva\CLI\Commands\Exceptions\ExecutionException;
use PhpCfdi\SatWsDescargaMasiva\CLI\Commands\ZipExportXmlCommand;
use PhpCfdi\SatWsDescargaMasiva\CLI\Tests\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class ZipExportXmlCommandTest extends TestCase
{
    public function testCommandHasExpectedArguments(): void
    {
        $command = new ZipExportXmlCommand();
        $this->assertTrue($command->getDefinition()->hasArgument('destino'));
        $this->assertTrue($command->getDefinition()->hasArgument('paquete'));
    }

    public function testCommandExecutionWithValidParameters(): void
    {
        $sourceFile = $this->filePath('cfdi.zip');
        $destinationPath = sys_get_temp_dir();
        $expectedFiles = [
            "$destinationPath/11111111-2222-3333-4444-000000000001.xml",
            "$destinationPath/11111111-2222-3333-4444-000000000002.xml",
        ];

        foreach ($expectedFiles as $expectedFile) {
            if (file_exists($expectedFile)) {
                unlink($expectedFile);
            }
        }

        $command = new ZipExportXmlCommand();
        $tester = new CommandTester($command);
        $validOptions = [
            'paquete' => $sourceFile,
            'destino' => $destinationPath,
        ];
        $exitCode = $tester->execute($validOptions);
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Exportados 2 archivos', $tester->getDisplay());

        foreach ($expectedFiles as $expectedFile) {
            $this->assertFileExists($expectedFile);
        }
    }

    public function testCommandExecutionWithInvalidSourceFile(): void
    {
        $sourceFile = __DIR__ . '/non-existent.zip';
        $destinationFile = __DIR__ . '/destination-file.xlsx';

        $command = new ZipExportXmlCommand();
        $tester = new CommandTester($command);
        $validOptions = [
            'destino' => $destinationFile,
            'paquete' => $sourceFile,
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

        $command = new ZipExportXmlCommand();
        $tester = new CommandTester($command);
        $validOptions = [
            'destino' => $destinationFile,
            'paquete' => $sourceFile,
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

        $command = new ZipExportXmlCommand();
        $tester = new CommandTester($command);
        $validOptions = [
            'destino' => $destinationFile,
            'paquete' => $sourceFile,
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
        $sourceFile = $this->filePath('cfdi.zip');
        $destinationFile = __DIR__ . '/non-existent-dir/';

        $command = new ZipExportXmlCommand();
        $tester = new CommandTester($command);
        $validOptions = [
            'destino' => $destinationFile,
            'paquete' => $sourceFile,
        ];

        $this->expectException(ExecutionException::class);
        $this->expectExceptionMessage('no existe');
        $tester->execute($validOptions);
    }
}
