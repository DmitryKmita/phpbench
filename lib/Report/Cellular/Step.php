<?php

/*
 * This file is part of the PHP Bench package
 *
 * (c) Daniel Leech <daniel@dantleech.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpBench\Report\Cellular;

use DTL\Cellular\Workspace;

interface Step
{
    /**
     * Apply a transformation step to the workspace.
     *
     * @param Workspace $workspace
     */
    public function step(Workspace $workspace);
}
