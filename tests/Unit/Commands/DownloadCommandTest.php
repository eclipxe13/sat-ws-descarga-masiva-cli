<?php

declare(strict_types=1);

namespace PhpCfdi\SatWsDescargaMasiva\CLI\Tests\Unit\Commands;

use PhpCfdi\SatWsDescargaMasiva\CLI\Commands\DownloadCommand;
use PhpCfdi\SatWsDescargaMasiva\CLI\Commands\Exceptions\ExecutionException;
use PhpCfdi\SatWsDescargaMasiva\CLI\Commands\WithFielAbstractCommand;
use PhpCfdi\SatWsDescargaMasiva\CLI\Tests\TestCase;
use PhpCfdi\SatWsDescargaMasiva\Services\Download\DownloadResult;
use PhpCfdi\SatWsDescargaMasiva\Shared\StatusCode;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;

class DownloadCommandTest extends TestCase
{
    /** @return array<string, string> */
    private function buildValidOptions(): array
    {
        return [
            'paquete' => 'b9a869bf-8c6c-49f4-945e-126992b3b3e7_01',
            '--destino' => (string) getcwd(),
            '--efirma' => $this->filePath('fake-fiel/EKU9003173C9-efirma.json'),
        ];
    }

    public function testHasDefinedOptions(): void
    {
        $command = new DownloadCommand();
        $this->assertInstanceOf(WithFielAbstractCommand::class, $command);
        $this->assertTrue($command->getDefinition()->hasOption('destino'));
    }

    public function testReceiveArguments(): void
    {
        $command = new DownloadCommand();
        $this->assertTrue($command->getDefinition()->hasArgument('paquete'));
        $this->assertSame(1, $command->getDefinition()->getArgumentCount());
    }

    #[Group('integration')]
    public function testCommandExecutionWithValidParametersButFakeFiel(): void
    {
        $command = new DownloadCommand();
        $tester = new CommandTester($command);
        $validOptions = $this->buildValidOptions();

        $this->expectException(ExecutionException::class);
        $this->expectExceptionMessage('La petición no fue aceptada: 305 - Certificado Inválido');
        $tester->execute($validOptions);
    }

    public function testProcessResultWithValidResult(): void
    {
        $command = new DownloadCommand();
        $result = new DownloadResult(
            new StatusCode(5000, 'Solicitud recibida con éxito'),
            'package-content',
        );

        $destinationFile = $this->createTemporaryName();

        $this->assertSame(DownloadCommand::SUCCESS, $command->processResult($result, $destinationFile));
        $this->assertFileExists($destinationFile);
        $this->assertStringEqualsFile($destinationFile, $result->getPackageContent());
    }

    public function testProcessResultWithInvalidResultStatusCode(): void
    {
        $command = new DownloadCommand();
        $result = new DownloadResult(
            new StatusCode(404, 'Error no controlado'),
            'package-content',
        );
        $destinationFile = __DIR__ . '/file-must-not-exists';

        $executionException = $this->captureException(
            fn (): int => $command->processResult($result, $destinationFile),
        );

        $this->assertInstanceOf(ExecutionException::class, $executionException);
        $this->assertStringContainsString('Error no controlado', $executionException->getMessage());
        $this->assertFileDoesNotExist($destinationFile);
    }

    public function testProcessResultWithInvalidDestinationFile(): void
    {
        $command = new DownloadCommand();
        $result = new DownloadResult(
            new StatusCode(5000, 'Solicitud recibida con éxito'),
            'package-content',
        );
        $destinationFile = __DIR__;

        $this->expectException(ExecutionException::class);
        $this->expectExceptionMessage('No se ha podido escribir el archivo');
        $command->processResult($result, $destinationFile);
    }

    public function testArgumentPaqueteMissing(): void
    {
        $command = new DownloadCommand();
        $tester = new CommandTester($command);
        $options = $this->buildValidOptions();
        unset($options['paquete']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('paquete');
        $tester->execute($options);
    }

    public function testOptionDestinoIsNotDirectory(): void
    {
        $command = new DownloadCommand();
        $tester = new CommandTester($command);
        $options = $this->buildValidOptions();
        $options['--destino'] = __FILE__;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('destino');
        $tester->execute($options);
    }
}
