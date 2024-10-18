<?php

declare(strict_types=1);

namespace PhpCfdi\SatWsDescargaMasiva\CLI\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;

abstract class WithFielAbstractCommand extends Command
{
    protected function configure(): void
    {
        $this->addOption('efirma', '', InputOption::VALUE_REQUIRED, 'Archivo de configuración de eFirma', '');
        $this->addOption('certificado', '', InputOption::VALUE_REQUIRED, 'Archivo de certificado de eFirma', '');
        $this->addOption('llave', '', InputOption::VALUE_REQUIRED, 'Archivo de llave primaria de eFirma', '');
        $this->addOption('password', '', InputOption::VALUE_REQUIRED, 'Contraseña de llave primaria de eFirma', '');
        $this->addOption('token', '', InputOption::VALUE_REQUIRED, 'Archivo de almacenamiento temporal del token', '');
        $this->addOption('servicio', '', InputOption::VALUE_REQUIRED, 'Cfdi o Retenciones', 'Cfdi');
    }
}
