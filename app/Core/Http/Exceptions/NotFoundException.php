<?php

declare(strict_types=1);

namespace App\Core\Http\Exceptions;

use RuntimeException;

/** Thrown when a resolved route references a resource that doesn't exist; renders 404 (ERR-01). */
final class NotFoundException extends RuntimeException
{
}
