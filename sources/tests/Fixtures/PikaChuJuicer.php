<?php
/*
 * This file is part of Chanmix51’s ParameterJuicer package.
 *
 * (c) 2017 Grégoire HUBERT <hubert.greg@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Chanmix51\ParameterJuicer\Tests\Fixtures;

use Chanmix51\ParameterJuicer\Exception\ValidationException;
use Chanmix51\ParameterJuicer\ParameterJuicer as Juicer;

class PikaChuJuicer extends Juicer
{
    /**
     * __construct
     *
     * Definition of PikaChuJuicer.
     */
    public function __construct()
    {
        $this
            ->addField('pika')
                ->addCleaner('pika', [$this, 'doTrimAndLowerString'])
                ->addValidator('pika', [$this, 'mustNotBeEmptyString'])
            ->addField('chu', false)
                ->addCleaner('chu', function($v) { return $v + 0; })
                ->addValidator('chu', [$this, 'mustBeANumberStrictlyPositive'])
        ;
    }

    protected function doTrimAndLowerString($value): string
    {
        return strtolower(trim($value));
    }

    protected function mustNotBeEmptyString($value)
    {
        if (strlen($value) === 0) {
            throw new ValidationException("cannot be empty.");
        }
    }

    protected function mustBeANumberStrictlyPositive($value)
    {
        if ($value <= 0) {
            throw new ValidationException(
                sprintf(
                    "must be strictly positive (%f given).",
                    $value
                )
            );
        }
    }
}
