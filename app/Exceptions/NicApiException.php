<?php

namespace App\Exceptions;

use Exception;

/**
 * NIC / GSP HTTP API failure.
 */
class NicApiException extends Exception
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        string $message,
        public readonly int $httpStatus = 0,
        public readonly array $context = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }
}
