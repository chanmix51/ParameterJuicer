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

/**
 * PokemonJuicer
 *
 * Juicer test class for embeded validators.
 *
 * @package   ParameterJuicer
 * @copyright 2017 Grégoire HUBERT
 * @author    Grégoire HUBERT <hubert.greg@gmail.com>
 * @license   X11 {@link http://opensource.org/licenses/mit-license.php}
 *
 * @see      Juicer
 */
class PokemonJuicer extends Juicer
{
    /**
     * __construct
     *
     * Juicer definition
     */
    public function __construct()
    {
        $this
            ->addField('pokemon_id')
                ->addCleaner('pokemon_id', function($v) { return (int) $v; })
            ->addField('pika_chu')
                ->addValidator('pika_chu', function($k, $v) {
                    if ($v <= 0)
                        throw new ValidationException(
                            sprintf(
                                "'%s' must be strictly greater than 0. (%d given).",
                                $k,
                                $v
                            )
                        );
                    }
                )
            ->addField('pika_chu')
                ->addJuicer('pika_chu', new PikaChuJuicer, Juicer::STRATEGY_REFUSE_EXTRA_VALUES)
            ;
    }
}
