<?php

declare(strict_types=1);

namespace PhpCfdi\SatWsDescargaMasiva\CLI\Commands\QueryCommand;

use Eclipxe\Enum\Exceptions\EnumExceptionInterface;
use InvalidArgumentException;
use PhpCfdi\SatWsDescargaMasiva\CLI\Commands\Exceptions\InputException;
use PhpCfdi\SatWsDescargaMasiva\CLI\Commands\QueryCommand;
use PhpCfdi\SatWsDescargaMasiva\Services\Query\QueryParameters;
use PhpCfdi\SatWsDescargaMasiva\Shared\ComplementoCfdi;
use PhpCfdi\SatWsDescargaMasiva\Shared\ComplementoInterface;
use PhpCfdi\SatWsDescargaMasiva\Shared\ComplementoRetenciones;
use PhpCfdi\SatWsDescargaMasiva\Shared\DateTime;
use PhpCfdi\SatWsDescargaMasiva\Shared\DateTimePeriod;
use PhpCfdi\SatWsDescargaMasiva\Shared\DocumentStatus;
use PhpCfdi\SatWsDescargaMasiva\Shared\DocumentType;
use PhpCfdi\SatWsDescargaMasiva\Shared\DownloadType;
use PhpCfdi\SatWsDescargaMasiva\Shared\RequestType;
use PhpCfdi\SatWsDescargaMasiva\Shared\RfcMatch;
use PhpCfdi\SatWsDescargaMasiva\Shared\RfcOnBehalf;
use PhpCfdi\SatWsDescargaMasiva\Shared\ServiceType;
use PhpCfdi\SatWsDescargaMasiva\Shared\Uuid;
use Symfony\Component\Console\Input\InputInterface;
use Throwable;

final readonly class QueryBuilder
{
    public function __construct(private InputInterface $input, private ServiceType $serviceType)
    {
    }

    /** @see QueryCommand::configure() */
    public function build(): QueryParameters
    {
        $period = $this->buildPeriod();
        $requestType = $this->buildRequestType();
        $downloadType = $this->buildDownloadType();
        $rfcMatch = $this->buildRfcMatch();
        $documentStatus = $this->buildDocumentStatus();
        $documentType = $this->buildDocumentType();
        $uuid = $this->buildUuid();
        $complement = $this->buildComplement();
        $rfcOnBehalf = $this->buildRfcOnBehalf();

        return QueryParameters::create($period)
            ->withRequestType($requestType)
            ->withDownloadType($downloadType)
            ->withRfcMatch($rfcMatch)
            ->withDocumentStatus($documentStatus)
            ->withDocumentType($documentType)
            ->withComplement($complement)
            ->withUuid($uuid)
            ->withRfcOnBehalf($rfcOnBehalf)
        ;
    }

    public function buildPeriod(): DateTimePeriod
    {
        $since = $this->buildSince();
        $until = $this->buildUntil();
        try {
            return DateTimePeriod::create($since, $until);
        } catch (Throwable $exception) {
            throw new InputException('El periodo de fechas desde y hasta no es válido', 'hasta', $exception);
        }
    }

    public function buildSince(): DateTime
    {
        return $this->buildDateTime('desde');
    }

    public function buildUntil(): DateTime
    {
        return $this->buildDateTime('hasta');
    }

    public function buildDateTime(string $optionName): DateTime
    {
        try {
            return new DateTime($this->getStringOption($optionName));
        } catch (Throwable $exception) {
            throw new InputException(
                sprintf('La opción "%s" no se pudo interpretar como fecha', $optionName),
                $optionName,
                $exception,
            );
        }
    }

    public function buildDownloadType(): DownloadType
    {
        $downloadTypeInput = strtolower($this->getStringOption('tipo'));
        return match ($downloadTypeInput) {
            'recibidos' => DownloadType::received(),
            'emitidos' => DownloadType::issued(),
            default => throw new InputException('La opción "tipo" debe ser "recibidos" o "emitidos"', 'tipo'),
        };
    }

    public function buildRfcMatch(): RfcMatch
    {
        $rfc = $this->getStringOption('rfc');
        if ('' === $rfc) {
            return RfcMatch::empty();
        }
        try {
            return RfcMatch::create($rfc);
        } catch (InvalidArgumentException $exception) {
            throw new InputException('La opción "rfc" tiene un valor inválido', 'rfc', $exception);
        }
    }

    public function buildRequestType(): RequestType
    {
        $requestTypeInput = strtolower($this->getStringOption('paquete'));
        return match ($requestTypeInput) {
            'metadata' => RequestType::metadata(),
            'xml' => RequestType::xml(),
            default => throw new InputException('La opción "paquete" debe ser "xml" o "metadata"', 'paquete'),
        };
    }

    public function buildDocumentStatus(): DocumentStatus
    {
        $documentStatusInput = strtolower($this->getStringOption('estado'));
        return match ($documentStatusInput) {
            '' => DocumentStatus::undefined(),
            'vigentes' => DocumentStatus::active(),
            'canceladas' => DocumentStatus::cancelled(),
            default => throw new InputException(
                'Si se especifica, la opción "estado" debe ser "vigentes" o "canceladas"',
                'estado',
            ),
        };
    }

    public function buildDocumentType(): DocumentType
    {
        $documentTypeInput = strtolower(str_replace(['ó', 'Ó'], 'o', $this->getStringOption('documento')));
        return match ($documentTypeInput) {
            '' => DocumentType::undefined(),
            'ingreso' => DocumentType::ingreso(),
            'egreso' => DocumentType::egreso(),
            'traslado' => DocumentType::traslado(),
            'pago' => DocumentType::pago(),
            'nomina' => DocumentType::nomina(),
            default => throw new InputException(
                'Si se especifica la opción "documento" debe ser "ingreso", "egreso", "traslado", "pago" o "nómina"',
                'documento',
            ),
        };
    }

    public function buildUuid(): Uuid
    {
        $uuid = $this->getStringOption('uuid');
        if (! $uuid) {
            return Uuid::empty();
        }
        try {
            return Uuid::create($uuid);
        } catch (InvalidArgumentException $exception) {
            throw new InputException(
                'Si se especifica la opción "uuid" debe contener un UUID válido',
                'uuid',
                $exception,
            );
        }
    }

    public function buildComplement(): ComplementoInterface
    {
        $isCfdi = $this->serviceType->isCfdi();
        $complement = $this->getStringOption('complemento');
        try {
            return $isCfdi ? new ComplementoCfdi($complement) : new ComplementoRetenciones($complement);
        } catch (EnumExceptionInterface $exception) {
            $message = sprintf(
                'La opción "complemento" de %s tiene un valor inválido',
                ($isCfdi) ? 'Cfdi' : 'Retenciones',
            );
            throw new InputException($message, 'complemento', $exception);
        }
    }

    public function buildRfcOnBehalf(): RfcOnBehalf
    {
        $rfc = $this->getStringOption('tercero');
        if (! $rfc) {
            return RfcOnBehalf::empty();
        }
        try {
            return RfcOnBehalf::create($rfc);
        } catch (InvalidArgumentException $exception) {
            throw new InputException(
                'La opción "tercero" tiene un valor inválido',
                'tercero',
                $exception,
            );
        }
    }

    private function getStringOption(string $name): string
    {
        /** @phpstan-var string $value */
        $value = $this->input->getOption($name) ?? '';
        return $value;
    }
}
