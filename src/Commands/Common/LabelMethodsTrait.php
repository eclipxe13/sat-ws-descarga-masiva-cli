<?php

declare(strict_types=1);

namespace PhpCfdi\SatWsDescargaMasiva\CLI\Commands\Common;

use LogicException;
use PhpCfdi\SatWsDescargaMasiva\Shared\ComplementoInterface;
use PhpCfdi\SatWsDescargaMasiva\Shared\DocumentStatus;
use PhpCfdi\SatWsDescargaMasiva\Shared\DocumentType;
use PhpCfdi\SatWsDescargaMasiva\Shared\DownloadType;
use PhpCfdi\SatWsDescargaMasiva\Shared\RequestType;
use PhpCfdi\SatWsDescargaMasiva\Shared\RfcMatch;
use PhpCfdi\SatWsDescargaMasiva\Shared\RfcOnBehalf;
use PhpCfdi\SatWsDescargaMasiva\Shared\ServiceType;
use PhpCfdi\SatWsDescargaMasiva\Shared\Uuid;

trait LabelMethodsTrait
{
    public function getDownloadTypeLabel(DownloadType $downloadType): string
    {
        return match (true) {
            $downloadType->isIssued() => 'Emitidos',
            $downloadType->isReceived() => 'Recibidos',
            default => throw new LogicException(
                sprintf("Don't know the label for DownloadType %s", $downloadType->value()),
            ),
        };
    }

    public function getServiceTypeLabel(ServiceType $serviceType): string
    {
        return match (true) {
            $serviceType->isCfdi() => 'Cfdi',
            $serviceType->isRetenciones() => 'Retenciones',
            default => throw new LogicException(
                sprintf("Don't know the label for ServiceType %s", $serviceType->value()),
            ),
        };
    }

    public function getRequestTypeLabel(RequestType $requestType): string
    {
        return match (true) {
            $requestType->isMetadata() => 'Metadata',
            $requestType->isXml() => 'XML',
            default => throw new LogicException(
                sprintf("Don't know the label for RequestType %s", $requestType->value()),
            ),
        };
    }

    public function getRfcMatchLabel(RfcMatch $rfcMatch): string
    {
        if ($rfcMatch->isEmpty()) {
            return '(cualquiera)';
        }

        return $rfcMatch->getValue();
    }

    public function getDocumentTypeLabel(DocumentType $documentType): string
    {
        return match (true) {
            $documentType->isIngreso() => 'Ingreso',
            $documentType->isEgreso() => 'Egreso',
            $documentType->isNomina() => 'Nómina',
            $documentType->isPago() => 'Pago',
            $documentType->isTraslado() => 'Traslado',
            $documentType->isUndefined() => '(cualquiera)',
            default => throw new LogicException(
                sprintf("Don't know the label for DocumentType %s", $documentType->value()),
            ),
        };
    }

    public function getComplementLabel(ComplementoInterface $complement): string
    {
        if (! $complement->value()) {
            return '(cualquiera)';
        }
        return sprintf('(%s) %s', $complement->value(), $complement->label());
    }

    public function getDocumentStatusLabel(DocumentStatus $documentStatus): string
    {
        return match (true) {
            $documentStatus->isActive() => 'Vigentes',
            $documentStatus->isCancelled() => 'Canceladas',
            default => '(cualquiera)',
        };
    }

    public function getOnBehalfLabel(RfcOnBehalf $rfcOnBehalf): string
    {
        if ($rfcOnBehalf->isEmpty()) {
            return '(cualquiera)';
        }

        return $rfcOnBehalf->getValue();
    }

    public function getUuidLabel(Uuid $uuid): string
    {
        if ($uuid->isEmpty()) {
            return '(cualquiera)';
        }
        return $uuid->getValue();
    }
}
