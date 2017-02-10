<?php
/*
 * This file is part of Chanmix51’s ParameterJuicer package.
 *
 * (c) 2017 Grégoire HUBERT <hubert.greg@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Chanmix51\ParameterJuicer\Exception;

/**
 * ValidationException
 *
 * Store exceptions from the validation process.
 *
 * @package     ParameterJuicer
 * @copyright   2017 Grégoire HUBERT
 * @author      Grégoire HUBERT <hubert.greg@gmail.com>
 * @license     X11 {@link http://opensource.org/licenses/mit-license.php}
 *
 * @see         \Exception
 */
class ValidationException extends ParameterJuicerException
{
    /** @var  bool  Indicate if messages have been set in the current exception.*/
    private $exceptions = [];

    /**
     * addMessage
     *
     * Add a new message to the exception.
     */
    public function addException(string $field, ValidationException $exception): self
    {
        $this->exceptions[$field][] = $exception;

        return $this;
    }

    /**
     * hasExceptions
     *
     * Indicates if yes or no some exceptions have been set.
     */
    public function hasExceptions(): int
    {
        return (bool) (count($this->exceptions) > 0);
    }

    /**
     * getExceptions
     *
     * Return the list of exceptions.
     */
    public function getExceptions(): array
    {
        return $this->exceptions;
    }

    /**
     * getFancyMessage
     *
     * Output a nicely formatted validation error messages.
     */
    public function getFancyMessage(): string
    {
        return sprintf("%s\n", $this->getMessage()) . $this->getSubFancyMessage();
    }

    /**
     * getSubFancyMessage
     *
     * Subrouting to display validation errors.
     */
    public function getSubFancyMessage(int $level = 0): string
    {
        $output = '';

        foreach ($this->exceptions as $field => $exceptions) {
            $output .= sprintf(
                "%s[%s] - %s\n",
                str_repeat(' ', $level * 4 + 2),
                $field,
                join(
                    ' | ',
                    array_map(
                        function(ValidationException $e) { return $e->getMessage(); },
                        $exceptions
                    )
                )
            );
            foreach ($exceptions as $exception) {
                if ($exception->hasExceptions()) {
                    $output .= $exception->getSubFancyMessage($level + 1);
                }
            }
        }

        return $output;
    }

    /**
     * __toString
     *
     * String representation of this exception.
     */
    public function __toString(): string
    {
        return $this->getFancyMessage();
    }
}
