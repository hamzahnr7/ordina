<?php

declare(strict_types=1);

namespace App\Core\Http\Exceptions;

use RuntimeException;

/** Thrown when a logged-in user lacks the required Permission; renders 403 (ERR-01). */
final class AuthorizationException extends RuntimeException
{
}
