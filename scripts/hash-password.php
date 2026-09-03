<?php

declare(strict_types=1);

// Utility for generating password_hash() values to paste into database/schema-and-seed.sql.
// Usage: docker compose exec web php scripts/hash-password.php "SomePlaintextPassword"

if ($argc < 2) {
    fwrite(STDERR, "Usage: php scripts/hash-password.php <plaintext-password>\n");
    exit(1);
}

echo password_hash($argv[1], PASSWORD_DEFAULT) . "\n";
