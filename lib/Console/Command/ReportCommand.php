<?php

/*
 * This file is part of the PHP Bench package
 *
 * (c) Daniel Leech <daniel@dantleech.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpBench\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use PhpBench\Result\Loader\XmlLoader;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ReportCommand extends BaseCommand
{
    public function configure()
    {
        $this->setName('report');
        $this->setDescription('Generate a report from an XML file');
        $this->setHelp(<<<EOT
Generate a report from an existing XML file.

To dump an XML file, use the <info>run</info> command with the
<comment>dumpfile</comment option.
EOT
        );

        $this->addArgument('file', InputArgument::REQUIRED, 'XML file');
        $this->addOption('report', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Report(s) to generate');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $configuration = $this->getApplication()->getConfiguration();
        $this->processReportConfigs($input->getOption('report'));
        $file = $input->getArgument('file');

        if (!$configuration->getReports()) {
            throw new \InvalidArgumentException(
                'You must specify or configure at least one report, e.g.: --report=console_table'
            );
        }

        if (!file_exists($file)) {
            throw new \InvalidArgumentException(sprintf(
                'Could not find file "%s"', $file
            ));
        }

        $loader = new XmlLoader();
        $suiteResult = $loader->load(file_get_contents($file));
        $this->generateReports($output, $suiteResult);
    }
}
