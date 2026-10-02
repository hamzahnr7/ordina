<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Config;
use PHPUnit\Framework\TestCase;

/**
 * Config is process-wide static state (tests/bootstrap.php already loaded the
 * real .env), so every key used here is prefixed CONFIG_TEST_ to never
 * shadow a real setting for the rest of the suite.
 */
final class ConfigTest extends TestCase
{
    private string $envFile;

    protected function setUp(): void
    {
        $this->envFile = (string) tempnam(sys_get_temp_dir(), 'ordina-env-');
        file_put_contents($this->envFile, implode(PHP_EOL, [
            '# comment line is ignored',
            '',
            'CONFIG_TEST_LINE_WITHOUT_EQUALS',
            'CONFIG_TEST_NAME = Ordina ',
            'CONFIG_TEST_DSN=mysql:host=db;port=3306',
        ]));
    }

    protected function tearDown(): void
    {
        @unlink($this->envFile);
        putenv('CONFIG_TEST_FROM_ENV');
    }

    public function test_loads_trimmed_key_value_pairs_and_skips_comments_and_junk(): void
    {
        Config::load($this->envFile);

        self::assertSame('Ordina', Config::get('CONFIG_TEST_NAME'));
        self::assertNull(Config::get('CONFIG_TEST_LINE_WITHOUT_EQUALS'));
    }

    public function test_only_the_first_equals_sign_splits_key_from_value(): void
    {
        Config::load($this->envFile);

        self::assertSame('mysql:host=db;port=3306', Config::get('CONFIG_TEST_DSN'));
    }

    public function test_falls_back_to_process_environment_then_default(): void
    {
        putenv('CONFIG_TEST_FROM_ENV=from-env');

        self::assertSame('from-env', Config::get('CONFIG_TEST_FROM_ENV'));
        self::assertSame('fallback', Config::get('CONFIG_TEST_MISSING', 'fallback'));
        self::assertNull(Config::get('CONFIG_TEST_MISSING'));
    }

    public function test_unreadable_file_is_silently_ignored(): void
    {
        Config::load($this->envFile . '.does-not-exist');

        self::assertSame('fallback', Config::get('CONFIG_TEST_NEVER_SET', 'fallback'));
    }
}
