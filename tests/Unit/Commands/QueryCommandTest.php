<?php

declare(strict_types=1);

namespace PhpCfdi\SatWsDescargaMasiva\CLI\Tests\Unit\Commands;

use PhpCfdi\SatWsDescargaMasiva\CLI\Commands\Exceptions\ExecutionException;
use PhpCfdi\SatWsDescargaMasiva\CLI\Commands\Exceptions\InputException;
use PhpCfdi\SatWsDescargaMasiva\CLI\Commands\QueryCommand;
use PhpCfdi\SatWsDescargaMasiva\CLI\Tests\Helpers\TemporaryFile;
use PhpCfdi\SatWsDescargaMasiva\CLI\Tests\TestCase;
use PhpCfdi\SatWsDescargaMasiva\Services\Query\QueryResult;
use PhpCfdi\SatWsDescargaMasiva\Shared\StatusCode;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\Console\Tester\CommandTester;

class QueryCommandTest extends TestCase
{
    /** @return array<string, string> */
    private function buildValidOptions(): array
    {
        return [
            '--efirma' => $this->filePath('fake-fiel/EKU9003173C9-efirma.json'),
            '--certificado' => $this->filePath('fake-fiel/EKU9003173C9.cer'),
            '--llave' => $this->filePath('fake-fiel/EKU9003173C9.key'),
            '--password' => trim($this->fileContents('fake-fiel/EKU9003173C9-password.txt')),
            '--token' => (new TemporaryFile(remove: false))->getPath(),
            '--servicio' => 'cfdi',
            '--desde' => '2020-01-01 00:00:00',
            '--hasta' => '2020-01-31 23:59:59',
            '--tipo' => 'emitidos',
            '--rfc' => 'AAAA010101AAA',
            '--paquete' => 'metadata',
            '--estado' => 'vigentes',
            '--documento' => 'nómina',
            '--complemento' => 'nomina12',
            '--tercero' => 'XXXX010101XXA',
        ];
    }

    public function testHasDefinedOptions(): void
    {
        $command = new QueryCommand();
        $this->assertTrue($command->getDefinition()->hasOption('certificado'));
        $this->assertTrue($command->getDefinition()->hasOption('llave'));
        $this->assertTrue($command->getDefinition()->hasOption('password'));
        $this->assertTrue($command->getDefinition()->hasOption('token'));
        $this->assertTrue($command->getDefinition()->hasOption('servicio'));
        $this->assertTrue($command->getDefinition()->hasOption('desde'));
        $this->assertTrue($command->getDefinition()->hasOption('hasta'));
        $this->assertTrue($command->getDefinition()->hasOption('tipo'));
        $this->assertTrue($command->getDefinition()->hasOption('paquete'));
    }

    #[Group('integration')]
    public function testCommandExecutionWithValidParametersButFakeFiel(): void
    {
        $command = new QueryCommand();
        $tester = new CommandTester($command);
        $validOptions = $this->buildValidOptions();

        $executionException = $this->captureException(
            fn () => $tester->execute($validOptions),
        );

        $expectedDisplay = <<< TEXT
            Consulta:
              Servicio: Cfdi
              Paquete: Metadata
              RFC: EKU9003173C9
              Desde: 2020-01-01 00:00:00
              Hasta: 2020-01-31 23:59:59
              Tipo: Emitidos
              RFC de/para: AAAA010101AAA
              Documentos: Nómina
              Complemento: (nomina12) Nómina 1.2
              Estado: Vigentes
              Tercero: XXXX010101XXA
            Resultado:
              Consulta: 305 - Certificado Inválido
              Identificador de solicitud: (ninguno)

            TEXT;
        $this->assertEquals($expectedDisplay, $tester->getDisplay());

        $this->assertInstanceOf(ExecutionException::class, $executionException);
        $this->assertStringContainsString(
            'La petición no fue aceptada: 305 - Certificado Inválido',
            $executionException->getMessage(),
        );
    }

    #[Group('integration')]
    public function testCommandExecutionWithValidParametersButFakeFielUuid(): void
    {
        $command = new QueryCommand();
        $tester = new CommandTester($command);
        $validOptions = [
            '--efirma' => $this->filePath('fake-fiel/EKU9003173C9-efirma.json'),
            '--certificado' => $this->filePath('fake-fiel/EKU9003173C9.cer'),
            '--llave' => $this->filePath('fake-fiel/EKU9003173C9.key'),
            '--password' => trim($this->fileContents('fake-fiel/EKU9003173C9-password.txt')),
            '--token' => (new TemporaryFile(remove: false))->getPath(),
            '--uuid' => 'b84835d8-2c55-4194-88a1-79edd961e4e7',
        ];

        $executionException = $this->captureException(
            fn () => $tester->execute($validOptions),
        );

        $expectedDisplay = <<< TEXT
            Consulta:
              Servicio: Cfdi
              Paquete: Metadata
              RFC: EKU9003173C9
              UUID: b84835d8-2c55-4194-88a1-79edd961e4e7
            Resultado:
              Consulta: 305 - Certificado Inválido
              Identificador de solicitud: (ninguno)

            TEXT;
        $this->assertEquals($expectedDisplay, $tester->getDisplay());

        $this->assertInstanceOf(ExecutionException::class, $executionException);
        $this->assertStringContainsString(
            'La petición no fue aceptada: 305 - Certificado Inválido',
            $executionException->getMessage(),
        );
    }

    public function testProcessResultWithCorrectResult(): void
    {
        $command = new QueryCommand();
        $requestId = '1E172434-E10B-48FD-990C-6844B509ACA3';
        $queryResult = new QueryResult(
            new StatusCode(5000, 'Solicitud recibida con éxito'),
            $requestId,
        );

        $this->assertSame($command::SUCCESS, $command->processResult($queryResult));
    }

    public function testProcessResultWithInCorrectResult(): void
    {
        $command = new QueryCommand();
        $requestId = '1E172434-E10B-48FD-990C-6844B509ACA3';
        $queryResult = new QueryResult(
            new StatusCode(404, 'Error no controlado'),
            $requestId,
        );

        $this->expectException(ExecutionException::class);
        $this->expectExceptionMessage('Error no controlado');
        $command->processResult($queryResult);
    }

    #[TestWith(['efirma', 'foo bar'])]
    #[TestWith(['certificado', 'foo bar'])]
    #[TestWith(['llave', 'foo bar', 'certificado'])]
    #[TestWith(['servicio', 'foo'])]
    #[TestWith(['desde', 'foo bar'])]
    #[TestWith(['desde', '2020-02-01 00:00:00', 'hasta'])]
    #[TestWith(['hasta', 'foo bar'])]
    #[TestWith(['hasta', '2019-12-31 23:59:59'])]
    #[TestWith(['tipo', 'foo bar'])]
    #[TestWith(['rfc', 'not-rfc'])]
    #[TestWith(['paquete', 'foo bar'])]
    #[TestWith(['estado', 'foo bar'])]
    #[TestWith(['documento', 'foo bar'])]
    #[TestWith(['complemento', 'foo bar'])]
    #[TestWith(['tercero', 'not-rfc'])]
    #[TestWith(['uuid', 'not-uuid'])]
    public function testOptionWithInvalidValue(string $option, string $invalidValue, string $guilty = ''): void
    {
        $guilty = $guilty ?: $option;
        $command = new QueryCommand();
        $tester = new CommandTester($command);

        /** @var InputException|null $expectedException */
        $expectedException = null;
        try {
            $tester->execute(["--$option" => $invalidValue] + $this->buildValidOptions());
        } catch (InputException $catchedException) {
            $expectedException = $catchedException;
        }

        if (null === $expectedException) {
            $this->fail('The exception InputException was not thrown');
        }
        $this->assertSame($guilty, $expectedException->getArgumentName());
    }
}
