<?php

namespace Islandora\Tuque\Exception;

/**
 * This is thrown when there is an error parsing XML.
 */
class RepositoryXmlError extends RepositoryException
{
    public $errors;

    /**
     * Same as the default exception constructor except it takes another
     * parameter errors, this is the error returned by the xml parser.
     *
     * @param string $message
     * @param int $code
     * @param string $errors
     * @param \Throwable $previous
     */
    public function __construct($message, $code, $errors, $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
    }
}
