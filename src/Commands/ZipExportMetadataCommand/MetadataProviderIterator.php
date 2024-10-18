<?php

declare(strict_types=1);

namespace PhpCfdi\SatWsDescargaMasiva\CLI\Commands\ZipExportMetadataCommand;

use Eclipxe\XlsxExporter\Providers\ProviderIterator;

final class MetadataProviderIterator extends ProviderIterator
{
    private const DATETIME_KEYS = [
        'fechaEmision',
        'fechaCertificacionSat',
        'fechaCancelacion',
    ];

    /** @return scalar|null */
    public function get(string $key)
    {
        /** @var scalar|null $value */
        $value = parent::get($key);

        if (null === $value) {
            return null;
        }

        if (in_array($key, self::DATETIME_KEYS)) {
            return intval(strtotime((string) $value)) ?: null;
        }

        return $value;
    }
}
