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

/**
 * ValidationException
 *
 * Store all validation error messages.
 *
 * @package     ParameterJuicer
 * @copyright   2017 Grégoire HUBERT
 * @author      Grégoire HUBERT <hubert.greg@gmail.com>
 * @license     X11 {@link http://opensource.org/licenses/mit-license.php}
 *
 * @see         \Exception
 */
class ValidationException extends \Exception
{
    /** @var  bool  Indicate if messages have been set in the current exception.*/
    private $messages = [];

    /**
     * addMessage
     *
     * Add a new message to the exception.
     */
    public function addMessage(string $message): self
    {
        $this->messages[] = $message;
        $this->message = $this->message . "\n" . $message;

        return $this;
    }

    /**
     * hasMessages
     *
     * Indicates if yes or no some messages have been set.
     */
    public function hasMessages(): bool
    {
        return (bool) (count($this->messages) > 0);
    }

    /**
     * getMessages
     *
     * Return the list of messages.
     */
    public function getMessages(): array
    {
        return $this->messages;
    }
}
