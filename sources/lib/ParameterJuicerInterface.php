<?php
/*
 * This file is part of Chanmix51’s ParameterJuicer package.
 *
 * (c) 2017 Grégoire HUBERT <hubert.greg@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Chanmix51\ParameterJuicer;

use Chanmix51\ParameterJuicer\Exception\ValidationException;

/**
 * ParameterJuicerInterface
 *
 * Inteface to embed juicers.
 *
 * @package     ParameterJuicer
 * @copyright   2017 Grégoire HUBERT
 * @author      Grégoire HUBERT <hubert.greg@gmail.com>
 * @license     X11 {@link http://opensource.org/licenses/mit-license.php}
 */
interface ParameterJuicerInterface
{
    /**
     * clean
     *
     * Filter the data prior to validation.
     */
    public function clean(array $values): array;

    /**
     * validate
     *
     * Trigger validation.
     *
     * @throws  ValidationException
     */
    public function validate(array $values);
}
