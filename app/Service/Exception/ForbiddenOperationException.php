<?php

declare(strict_types=1);

namespace App\Service\Exception;

use RuntimeException;

/**
 * A business rule refused the operation regardless of the caller's raw
 * Permission (e.g. "Admin accounts aren't managed through this screen").
 * Caught alongside App\Core\Http\Exceptions\AuthorizationException in
 * public/index.php and rendered as 403.
 */
final class ForbiddenOperationException extends RuntimeException
{
}
