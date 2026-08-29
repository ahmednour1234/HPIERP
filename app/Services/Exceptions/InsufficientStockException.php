<?php

namespace App\Services\Exceptions;

use RuntimeException;

/**
 * An order line asked for more units than the seller is carrying.
 *
 * Mapped to 422 by App\Exceptions\Handler.
 */
class InsufficientStockException extends RuntimeException
{
}
