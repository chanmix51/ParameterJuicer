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
    public function addException(ValidationException $exception): self
    {
        $this->exceptions[] = $exception;
        $this->message = sprintf(
            "%s\n%s",
            $this->message,
            $exception->getMessage()
        );

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
     * getMessages
     *
     * Return an array of the embeded exceptions’ messages.
     */
    public function getMessages(): array
    {
        return array_map(
            $this->exceptions,
            function(\Exception $e) {
                return $e->getMessage();
            }
        );
    }
}
