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
                ->addCleaner('pika', function($v) { return strtolower(trim($v)); })
                ->addValidator('pika', function($k, $v) {
                    if (strlen($v) === 0) {
                        throw new ValidationException(
                            sprintf(
                                "Field '%s' is an empty string.",
                                $k
                            )
                        );
                    }
                })
            ->addField('chu', false)
                ->addCleaner('chu', function($v) { return $v + 0; })
                ->addValidator('chu', function($k, $v) {
                    if ($v <= 0) {
                        throw new ValidationException(
                            sprintf(
                                "Field '%s' must be strictly positive (%f given).",
                                $k,
                                $v
                            )
                        );
                    }
                })
        ;
    }
}
