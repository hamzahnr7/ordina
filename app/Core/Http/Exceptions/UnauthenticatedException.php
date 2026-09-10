<?php

declare(strict_types=1);

namespace App\Core\Http\Exceptions;

use RuntimeException;

/** Thrown by Controller::authorize()/requireLogin(); caught centrally in public/index.php (ERR-01). */
final class UnauthenticatedException extends RuntimeException
{
}
