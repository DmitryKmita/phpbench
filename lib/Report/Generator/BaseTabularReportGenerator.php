<?php

/*
 * This file is part of the PHP Bench package
 *
 * (c) Daniel Leech <daniel@dantleech.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpBench\Report\Generator;

use Symfony\Component\OptionsResolver\OptionsResolver;
use PhpBench\Result\SuiteResult;
use PhpBench\ReportGenerator;
use DTL\Cellular\Table;
use Symfony\Component\Console\Output\OutputInterface;
use PhpBench\Report\Cellular\CellularConverter;
use PhpBench\Report\Cellular\Step\RpsStep;
use PhpBench\Report\Cellular\Step\DeviationStep;
use PhpBench\Report\Cellular\Step\FooterStep;
use PhpBench\Report\Cellular\Step\FilterColsStep;
use PhpBench\Report\Cellular\Step\AggregateSubjectStep;
use PhpBench\Report\Cellular\Step\SortStep;
use PhpBench\Report\Cellular\Step\AggregateRunStep;

/**
 * This base class generates a table (a data table, not a UI table) with
 * calculated values.
 */
abstract class BaseTabularReportGenerator implements ReportGenerator
{
    /**
     * Columns which are available.
     */
    private $availableCols = array(
        'class',
        'subject',
        'description',
        'run',
        'iter',
        'params',
        'time',
        'memory',
        'memory_diff',
        'memory_inc',
        'memory_diff_inc',
        'rps',
        'revs',
        'deviation',
    );

    /**
     * See the README file for a detailed description of the options.
     */
    public function configure(OptionsResolver $options)
    {
        $defaults = array();

        $options->setDefaults(array_merge(
            $defaults,
            array(
                'aggregate' => 'none',
                'aggregate_funcs' => array('mean'),
                'deviation_funcs' => array('min'),
                'footer_funcs' => array(),
                'cols' => $this->availableCols,
                'precision' => 6,
                'time_format' => 'fraction',
                'sort' => null,
                'sort_dir' => 'asc',
                'groups' => array(),
            )
        ));

        $colsValidator = function ($cols) {
            $intersect = array_intersect($cols, $this->availableCols);
            if (count($intersect) !== count($cols)) {
                throw new \InvalidArgumentException(sprintf(
                    'Invalid columns: "%s". Valid columns are: "%s" ',
                    implode('", "', array_diff($cols, $intersect)),
                    implode('", "', $this->availableCols)
                ));
            }

            return true;
        };

        $functionsValidator = function ($funcs) use ($colsValidator) {
            $availableFuncs = array('sum', 'mean', 'min', 'max', 'median');
            foreach ($funcs as $function => $cols) {
                if (!is_array($cols)) {
                    $function = $cols;
                    $cols = $this->availableCols;
                }

                $colsValidator($cols);

                if (!in_array($function, $availableFuncs)) {
                    throw new \InvalidArgumentException(sprintf(
                        'Invalid function: "%s". Valid functions are "%s"',
                        $function,
                        implode('", "', $availableFuncs)
                    ));
                }
            }

            return true;
        };

        $functionsNormalizer = function ($resolver, $funcs) {
            $normalized = array();
            foreach ($funcs as $function => $cols) {
                if (!is_array($cols)) {
                    $normalized[$cols] = $this->availableCols;
                    continue;
                }

                $normalized[$function] = $cols;
            }

            return $normalized;
        };

        $options->setAllowedValues('time_format', array('integer', 'fraction'));
        $options->setAllowedValues('deviation_funcs', $functionsValidator);
        $options->setAllowedValues('aggregate', array('none', 'run', 'subject'));
        $options->setAllowedValues('aggregate_funcs', $functionsValidator);
        $options->setAllowedValues('footer_funcs', $functionsValidator);
        $options->setAllowedValues('cols', $colsValidator);
        $options->setAllowedValues('sort_dir', array('asc', 'desc'));
        $options->setAllowedTypes('footer_funcs', 'array');
        $options->setAllowedTypes('aggregate', 'string');
        $options->setAllowedTypes('aggregate_funcs', 'array');
        $options->setAllowedTypes('precision', 'int');
        $options->setNormalizer('sort_dir', function ($resolver, $value) { return strtolower($value); });
        $options->setNormalizer('aggregate_funcs', $functionsNormalizer);
        $options->setNormalizer('footer_funcs', $functionsNormalizer);
        $options->setNormalizer('deviation_funcs', $functionsNormalizer);
    }

    public function generate(SuiteResult $suite, OutputInterface $output, array $options)
    {
        $this->precision = $options['precision'];

        $workspace = CellularConverter::suiteToWorkspace($suite);

        // TODO: Move this into a step
        if ($options['groups']) {
            $workspace = $workspace->filter(function ($table) use ($options) {
                if (0 === count(array_intersect($table->getAttribute('groups'), $options['groups']))) {
                    return false;
                }

                return true;
            });
        }

        $steps = array();

        if (in_array('rps', $options['cols'])) {
            $steps[] = new RpsStep();
        }

        if ($options['aggregate'] === 'run') {
            $steps[] = new AggregateRunStep($options['aggregate_funcs']);
        }

        if ($options['aggregate'] === 'subject') {
            $steps[] = new AggregateSubjectStep($options['aggregate_funcs']);
        }

        if (in_array('deviation', $options['cols'])) {
            $steps[] = new DeviationStep('time', $options['deviation_funcs']);
        }

        if ($options['sort']) {
            $steps[] = new SortStep($options['sort'], $options['sort_dir']);
        }

        $steps[] = new FilterColsStep(array_diff($this->availableCols, $options['cols']));

        if (false === empty($options['footer_funcs'])) {
            $steps[] = new FooterStep($options['footer_funcs']);
        }

        foreach ($steps as $step) {
            $step->step($workspace);
        }

        // align all the tables (fill in missing columns)
        $workspace->each(function (Table $table) {
            $table->align();
        });

        return $this->doGenerate($workspace, $output, $options);
    }
}
