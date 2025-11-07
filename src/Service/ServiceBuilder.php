<?php

declare(strict_types=1);

namespace PhpCfdi\SatWsDescargaMasiva\CLI\Service;

use JsonException;
use PhpCfdi\SatWsDescargaMasiva\CLI\Commands\Exceptions;
use PhpCfdi\SatWsDescargaMasiva\CLI\Internal\Filesystem;
use PhpCfdi\SatWsDescargaMasiva\RequestBuilder\FielRequestBuilder\Fiel;
use PhpCfdi\SatWsDescargaMasiva\RequestBuilder\FielRequestBuilder\FielRequestBuilder;
use PhpCfdi\SatWsDescargaMasiva\Service;
use PhpCfdi\SatWsDescargaMasiva\Shared\ServiceEndpoints;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use UnexpectedValueException;

final class ServiceBuilder
{
    private ?Fiel $fiel = null;

    private ?ServiceEndpoints $serviceEndPoints = null;

    private ?StorageToken $storageToken = null;

    private ?LoggerInterface $logger = null;

    private ?Service $service = null;

    private ?ConfigValues $configValues = null;

    public function __construct(private readonly InputInterface $input, private readonly OutputInterface $output)
    {
    }

    public function obtainFiel(): Fiel
    {
        return $this->fiel ??= $this->buildFielFromInput($this->input);
    }

    public function obtainServiceEndPoints(): ServiceEndpoints
    {
        return $this->serviceEndPoints ??= $this->buildServiceEndpointsFromInput($this->input);
    }

    public function obtainStorageToken(): StorageToken
    {
        return $this->storageToken ??= $this->buildStorageTokenFromInput($this->input);
    }

    public function obtainLogger(): LoggerInterface
    {
        return $this->logger ??= (($this->output->isQuiet()) ? new NullLogger() : new ConsoleLogger($this->output));
    }

    public function obtainRfc(): string
    {
        return $this->obtainFiel()->getRfc();
    }

    public function obtainService(): Service
    {
        $fiel = $this->obtainFiel();
        $logger = $this->obtainLogger();
        $serviceEndpoints = $this->obtainServiceEndPoints();
        $storageToken = $this->obtainStorageToken();
        return $this->service ??= $this->buildService($fiel, $logger, $serviceEndpoints, $storageToken);
    }

    public function buildFielFromInput(InputInterface $input): Fiel
    {
        $configValues = $this->obtainConfigValues();

        /** @var string $certificateInput */
        $certificateInput = $input->getOption('certificado') ?: $configValues->certificate;
        if ('' === $certificateInput) {
            throw new Exceptions\InputException('La opción "certificado" no es válida', 'certificado');
        }

        /** @var string $primaryKeyInput */
        $primaryKeyInput = $input->getOption('llave') ?: $configValues->privateKey;
        if ('' === $primaryKeyInput) {
            throw new Exceptions\InputException('La opción "llave" no es válida', 'llave');
        }

        if (isset($_SERVER['EFIRMA_PASSPHRASE']) && is_scalar($_SERVER['EFIRMA_PASSPHRASE'])) {
            $password = strval($_SERVER['EFIRMA_PASSPHRASE']);
        } else {
            /** @var string $password */
            $password = $input->getOption('password') ?: $configValues->passPhrase;
        }

        $fs = new Filesystem();
        try {
            return Fiel::create(
                $fs->read($certificateInput),
                $fs->read($primaryKeyInput),
                $password,
            );
        } catch (Throwable $exception) {
            throw new Exceptions\InputException('No fue posible crear la eFirma', 'certificado', $exception);
        }
    }

    public function buildServiceEndpointsFromInput(InputInterface $input): ServiceEndpoints
    {
        /** @var string $serviceInput */
        $serviceInput = $input->getOption('servicio');
        $serviceInput = strtolower($serviceInput);
        return match ($serviceInput) {
            'cfdi' => ServiceEndpoints::cfdi(),
            'retenciones' => ServiceEndpoints::retenciones(),
            default => throw new Exceptions\InputException(
                'La opción "servicio" no es válida, debe ser "cfdi" o "retenciones"',
                'servicio',
            ),
        };
    }

    public function buildStorageTokenFromInput(InputInterface $input): StorageToken
    {
        $configValues = $this->obtainConfigValues();

        /** @var string $tokenInput */
        $tokenInput = $input->getOption('token') ?: $configValues->tokenFile;

        return new StorageToken($tokenInput);
    }

    public function buildService(
        Fiel $fiel,
        LoggerInterface $logger,
        ServiceEndpoints $endPoints,
        StorageToken $storageToken,
    ): Service {
        $fielRequestBuilder = new FielRequestBuilder($fiel);
        $webClient = GuzzleWebClientWithLogger::createDefault($logger);
        return new ServiceWithStorageToken($fielRequestBuilder, $webClient, $storageToken, $endPoints);
    }

    public function obtainConfigValues(): ConfigValues
    {
        return $this->configValues ??= $this->buildConfigValues();
    }

    public function buildConfigValues(): ConfigValues
    {
        /** @var string $efirmaInput */
        $efirmaInput = $this->input->getOption('efirma');
        if ('' === $efirmaInput) {
            return ConfigValues::empty();
        }
        return $this->readConfigValues($efirmaInput);
    }

    public function readConfigValues(string $configFile): ConfigValues
    {
        $fs = new Filesystem();
        try {
            $contents = $fs->read($configFile);
        } catch (Throwable $exception) {
            throw new Exceptions\InputException(
                "El archivo de configuración de eFirma '$configFile' no se pudo abrir",
                'efirma',
                $exception,
            );
        }

        try {
            $values = json_decode($contents, associative: true, flags: JSON_THROW_ON_ERROR);
            if (! is_array($values)) {
                throw new UnexpectedValueException('JSON content is not an object');
            }
        } catch (JsonException | UnexpectedValueException $exception) {
            throw new Exceptions\InputException(
                sprintf('El archivo de configuración de eFirma "%s" no se pudo interpretar como JSON', $configFile),
                'efirma',
                $exception,
            );
        }

        $values = array_filter($values, 'is_string');

        $relativeTo = dirname($configFile);

        return new ConfigValues(
            $fs->pathAbsoluteOrRelative($values['certificateFile'] ?? '', $relativeTo),
            $fs->pathAbsoluteOrRelative($values['privateKeyFile'] ?? '', $relativeTo),
            $values['passPhrase'] ?? '',
            $fs->pathAbsoluteOrRelative($values['tokenFile'] ?? '', $relativeTo),
        );
    }
}
