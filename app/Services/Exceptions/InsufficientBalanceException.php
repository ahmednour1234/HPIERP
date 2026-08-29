<?php

namespace App\Services\Exceptions;

use RuntimeException;

/**
 * A payment was attempted against an account without the funds to cover it.
 *
 * Mapped to 422 by App\Exceptions\Handler so services can reject the operation
 * without knowing anything about HTTP.
 */
class InsufficientBalanceException extends RuntimeException
{
}
