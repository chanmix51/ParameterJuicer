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

interface ParameterJuicerInterface
{
    /**
     * squash
     *
     * Clean & validate data.
     *
     * @throws  ValidationException
     */
    public function squash(array $values, int $strategy): array;

    /**
     * validate
     *
     * Trigger validation and extra fields strategies.
     *
     * @throws  ValidationException
     */
    public function validate(string $name, array $values, int $strategy): array;

    /**
     * clean
     *
     * Returned clean data for validation.
     */
    public function clean(array $values): array;
}
