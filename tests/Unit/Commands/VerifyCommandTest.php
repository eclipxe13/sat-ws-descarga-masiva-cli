<?php

declare(strict_types=1);

namespace PhpCfdi\SatWsDescargaMasiva\CLI\Tests\Unit\Commands;

use PhpCfdi\SatWsDescargaMasiva\CLI\Commands\Exceptions\ExecutionException;
use PhpCfdi\SatWsDescargaMasiva\CLI\Commands\Exceptions\InputException;
use PhpCfdi\SatWsDescargaMasiva\CLI\Commands\VerifyCommand;
use PhpCfdi\SatWsDescargaMasiva\CLI\Tests\TestCase;
use PhpCfdi\SatWsDescargaMasiva\Services\Verify\VerifyResult;
use PhpCfdi\SatWsDescargaMasiva\Shared\CodeRequest;
use PhpCfdi\SatWsDescargaMasiva\Shared\StatusCode;
use PhpCfdi\SatWsDescargaMasiva\Shared\StatusRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\Console\Tester\CommandTester;

class VerifyCommandTest extends TestCase
{
    /** @return array<string, string> */
    private function buildValidOptions(): array
    {
        return [
            'solicitud' => 'b9a869bf-8c6c-49f4-945e-126992b3b3e7',

            '--certificado' => $this->filePath('fake-fiel/EKU9003173C9.cer'),
            '--llave' => $this->filePath('fake-fiel/EKU9003173C9.key'),
            '--password' => trim($this->fileContents('fake-fiel/EKU9003173C9-password.txt')),
        ];
    }

    public function testHasDefinedOptions(): void
    {
        $command = new VerifyCommand();
        $this->assertTrue($command->getDefinition()->hasOption('certificado'));
        $this->assertTrue($command->getDefinition()->hasOption('llave'));
        $this->assertTrue($command->getDefinition()->hasOption('password'));
    }

    public function testReceiveOnlyOneArgument(): void
    {
        $command = new VerifyCommand();
        $this->assertTrue($command->getDefinition()->hasArgument('solicitud'));
        $this->assertSame(1, $command->getDefinition()->getArgumentCount());
    }

    #[Group('integration')]
    public function testCommandExecutionWithValidParametersButFakeFiel(): void
    {
        $command = new VerifyCommand();
        $tester = new CommandTester($command);
        $validOptions = $this->buildValidOptions();

        $this->expectException(ExecutionException::class);
        $this->expectExceptionMessage('La petición no fue aceptada: 305 - Certificado Inválido');
        $tester->execute($validOptions);
    }

    public function testProcessResultWithValidResult(): void
    {
        $command = new VerifyCommand();
        $result = new VerifyResult(
            new StatusCode(5000, 'Solicitud recibida con éxito'),
            new StatusRequest(1),
            new CodeRequest(5000),
            99,
            ...['1E172434-E10B-48FD-990C-6844B509ACA3_01', '1E172434-E10B-48FD-990C-6844B509ACA3_02'],
        );

        $this->assertSame(VerifyCommand::SUCCESS, $command->processResult($result));
    }

    public function testProcessResultWithInvalidResultStatusCode(): void
    {
        $command = new VerifyCommand();
        $result = new VerifyResult(
            new StatusCode(404, 'Error no controlado'),
            new StatusRequest(1),
            new CodeRequest(5000),
            99,
            ...['1E172434-E10B-48FD-990C-6844B509ACA3_01', '1E172434-E10B-48FD-990C-6844B509ACA3_02'],
        );

        $this->expectException(ExecutionException::class);
        $this->expectExceptionMessage('Error no controlado');
        $command->processResult($result);
    }

    /** @return array<string, array<StatusRequest>> */
    public static function providerProcessResultWithInvalidResultStatusRequest(): array
    {
        return [
            'Failure' => [new StatusRequest(4)],
            'Rejected' => [new StatusRequest(5)],
            'Expired' => [new StatusRequest(6)],
        ];
    }

    #[DataProvider('providerProcessResultWithInvalidResultStatusRequest')]
    public function testProcessResultWithInvalidResultStatusRequest(StatusRequest $statusRequest): void
    {
        $command = new VerifyCommand();
        $result = new VerifyResult(
            new StatusCode(5000, 'Solicitud recibida con éxito'),
            $statusRequest,
            new CodeRequest(5000),
            99,
            ...['1E172434-E10B-48FD-990C-6844B509ACA3_01', '1E172434-E10B-48FD-990C-6844B509ACA3_02'],
        );

        $this->expectException(ExecutionException::class);
        $this->expectExceptionMessage(sprintf(
            'El estado de solicitud de la descarga no es correcto: %s - %s',
            $statusRequest->getValue(),
            $statusRequest->getMessage(),
        ));
        $command->processResult($result);
    }

    /** @return array<string, array<CodeRequest>> */
    public static function providerProcessResultWithInvalidResultCodeRequest(): array
    {
        return [
            'Exhausted' => [new CodeRequest(5002)],
            'MaximumLimitReaded' => [new CodeRequest(5003)],
            'Duplicated' => [new CodeRequest(5005)],
        ];
    }

    #[DataProvider('providerProcessResultWithInvalidResultCodeRequest')]
    public function testProcessResultWithInvalidResultCodeRequest(CodeRequest $CodeRequest): void
    {
        $command = new VerifyCommand();
        $result = new VerifyResult(
            new StatusCode(5000, 'Solicitud recibida con éxito'),
            new StatusRequest(1),
            $CodeRequest,
            99,
            ...['1E172434-E10B-48FD-990C-6844B509ACA3_01', '1E172434-E10B-48FD-990C-6844B509ACA3_02'],
        );

        $this->expectException(ExecutionException::class);
        $this->expectExceptionMessage(sprintf(
            'El código de estado de la solicitud de descarga no es correcto: %s - %s',
            $CodeRequest->getValue(),
            $CodeRequest->getMessage(),
        ));
        $command->processResult($result);
    }

    #[TestWith(['certificado', 'foo bar'])]
    #[TestWith(['llave', 'foo bar', 'certificado'])]
    public function testOptionWithInvalidValue(string $option, string $invalidValue, string $guilty = ''): void
    {
        $guilty = $guilty ?: $option;
        $command = new VerifyCommand();
        $tester = new CommandTester($command);

        /** @var InputException|null $expectedException */
        $expectedException = null;
        try {
            $tester->execute(["--$option" => $invalidValue] + $this->buildValidOptions());
        } catch (InputException $cachedException) {
            $expectedException = $cachedException;
        }

        if (null === $expectedException) {
            $this->fail('The exception InputException was not thrown');
        }
        $this->assertSame($guilty, $expectedException->getArgumentName());
    }
}
