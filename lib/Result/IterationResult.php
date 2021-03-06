<?php

/*
 * This file is part of the PHP Bench package
 *
 * (c) Daniel Leech <daniel@dantleech.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpBench\Result;

class IterationResult
{
    private $statistics;

    public function __construct(array $statistics)
    {
        $this->statistics = $statistics;
    }

    public function getStatistics()
    {
        return $this->statistics;
    }

    public function get($statisticName)
    {
        return $this->statistics[$statisticName];
    }
}
