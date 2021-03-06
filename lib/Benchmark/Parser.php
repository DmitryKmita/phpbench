<?php

/*
 * This file is part of the PHP Bench package
 *
 * (c) Daniel Leech <daniel@dantleech.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpBench\Benchmark;

use PhpBench\Exception\InvalidArgumentException;

class Parser
{
    public function parseDoc($doc, array $defaults = array())
    {
        $lines = explode(PHP_EOL, $doc);

        $meta = array(
            'beforeMethod' => array(),
            'paramProvider' => array(),
            'iterations' => array(),
            'description' => array(),
            'processIsolation' => array(),
            'group' => array(),
            'revs' => array(),
        );

        // singular annotations
        foreach (array('description', 'iterations', 'processIsolation') as $key) {
            if (isset($defaults[$key]) && $defaults[$key]) {
                $meta[$key][] = $defaults[$key];
            }
        }

        // plural annotations
        foreach (array('beforeMethod', 'paramProvider', 'revs', 'group') as $key) {
            if (isset($defaults[$key]) && $defaults[$key]) {
                $meta[$key] = $defaults[$key];
            }
        }

        foreach ($lines as $line) {
            if (!preg_match('{@([a-zA-Z0-9]+)\s+(.*)$}', $line, $matches)) {
                continue;
            }

            $annotationName = $matches[1];
            $annotationValue = $matches[2];

            if (!isset($meta[$annotationName])) {
                throw new InvalidArgumentException(sprintf(
                    'Unknown annotation "%s"',
                    $annotationName
                ));
            }

            $meta[$annotationName][] = $annotationValue;
        }

        // Do not allow these annotations to be redelared twice in the same docblock
        foreach (array('description', 'iterations', 'processIsolation') as $key) {
            // allow overriding single values
            if (count($meta[$key] == 2) && !empty($defaults[$key]) && count($defaults[$key]) == 1) {
                $value = array_pop($meta[$key]);
                $meta[$key] = array($value);
            }

            if (count($meta[$key]) > 1) {
                throw new InvalidArgumentException(sprintf(
                    'Cannot have more than one "@%s" annotation', $key
                ));
            }
        }

        $meta['description'] = reset($meta['description']);
        $meta['processIsolation'] = reset($meta['processIsolation']);
        $iterations = $meta['iterations'];
        $meta['iterations'] = empty($iterations) ? 1 : (int) reset($iterations);
        $revs = $meta['revs'];
        $meta['revs'] = empty($revs) ? array(1) : $revs;

        if ($meta['processIsolation']) {
            Runner::validateProcessIsolation($meta['processIsolation']);
        }

        return $meta;
    }
}
