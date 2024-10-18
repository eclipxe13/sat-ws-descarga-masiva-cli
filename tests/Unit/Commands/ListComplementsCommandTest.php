<?php

declare(strict_types=1);

namespace PhpCfdi\SatWsDescargaMasiva\CLI\Tests\Unit\Commands;

use PhpCfdi\SatWsDescargaMasiva\CLI\Commands\ListComplementsCommand;
use PhpCfdi\SatWsDescargaMasiva\CLI\Tests\TestCase;
use PhpCfdi\SatWsDescargaMasiva\Shared\ComplementoCfdi;
use Symfony\Component\Console\Tester\CommandTester;

class ListComplementsCommandTest extends TestCase
{
    public function testHasDefinedOptions(): void
    {
        $command = new ListComplementsCommand();
        $this->assertTrue($command->getDefinition()->hasOption('servicio'));
        $this->assertCount(1, $command->getDefinition()->getOptions());
    }

    public function testReceiveArguments(): void
    {
        $command = new ListComplementsCommand();
        $this->assertSame(0, $command->getDefinition()->getArgumentCount());
    }

    public function testCommandExecutionWithServicioCfdi(): void
    {
        $command = new ListComplementsCommand();
        $tester = new CommandTester($command);
        $tester->execute(['--servicio' => 'cfdi']);
        $display = $tester->getDisplay();
        foreach (ComplementoCfdi::getLabels() as $code => $label) {
            $pattern = sprintf('/| %s.*| %s.*|/', preg_quote($code), preg_quote($label));
            $this->assertMatchesRegularExpression($pattern, $display);
        }
    }

    public function testCommandExecutionWithServicioRetenciones(): void
    {
        $command = new ListComplementsCommand();
        $tester = new CommandTester($command);
        $tester->execute(['--servicio' => 'retenciones']);
        $display = $tester->getDisplay();
        foreach (ComplementoCfdi::getLabels() as $code => $label) {
            $pattern = sprintf('/| %s.*| %s.*|/', preg_quote($code), preg_quote($label));
            $this->assertMatchesRegularExpression($pattern, $display);
        }
    }
}
