<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Service\Exception\ValidationException;
use App\Service\ProductImageUploader;
use PHPUnit\Framework\Attributes\RequiresOperatingSystemFamily;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * PRD-01 upload rules: type is decided from the file's real content (not the
 * client's name/type), max 2MB, stored under a random name. In CLI there is
 * no real HTTP upload, so the uploader's rename() fallback is what runs here.
 */
final class ProductImageUploaderTest extends TestCase
{
    /** 1x1 transparent PNG. */
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    /** @var list<string> */
    private array $cleanup = [];

    protected function tearDown(): void
    {
        foreach (array_reverse($this->cleanup) as $path) {
            is_dir($path) ? @rmdir($path) : @unlink($path);
        }
    }

    private function tempFile(string $contents): string
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'ordina-upload-');
        file_put_contents($path, $contents);
        $this->cleanup[] = $path;

        return $path;
    }

    /** @return array{name:string, type:string, tmp_name:string, error:int, size:int} */
    private function pngUpload(): array
    {
        $tmp = $this->tempFile((string) base64_decode(self::PNG));

        // Client-supplied name/type are deliberately misleading - neither may be trusted.
        return ['name' => 'evil.php', 'type' => 'text/plain', 'tmp_name' => $tmp, 'error' => UPLOAD_ERR_OK, 'size' => (int) filesize($tmp)];
    }

    public function test_no_file_submitted_returns_null(): void
    {
        $uploader = new ProductImageUploader(sys_get_temp_dir());

        self::assertNull($uploader->upload(null));
        self::assertNull($uploader->upload(['error' => UPLOAD_ERR_NO_FILE]));
        self::assertNull($uploader->upload([]));
    }

    public function test_failed_upload_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        (new ProductImageUploader(sys_get_temp_dir()))->upload(['error' => UPLOAD_ERR_PARTIAL, 'tmp_name' => '', 'size' => 0]);
    }

    public function test_file_over_2mb_is_rejected(): void
    {
        $file = $this->pngUpload();
        $file['size'] = 2 * 1024 * 1024 + 1;

        try {
            (new ProductImageUploader(sys_get_temp_dir()))->upload($file);
            self::fail('Oversized file should be rejected.');
        } catch (ValidationException $e) {
            self::assertSame('Ukuran gambar maksimal 2MB.', $e->errors()['image']);
        }
    }

    public function test_non_image_content_is_rejected_even_with_an_image_name(): void
    {
        $tmp = $this->tempFile('<?php echo "not an image";');

        try {
            (new ProductImageUploader(sys_get_temp_dir()))->upload(
                ['name' => 'photo.png', 'type' => 'image/png', 'tmp_name' => $tmp, 'error' => UPLOAD_ERR_OK, 'size' => 30]
            );
            self::fail('Non-image content should be rejected.');
        } catch (ValidationException $e) {
            self::assertSame('Tipe file harus JPG, PNG, atau WEBP.', $e->errors()['image']);
        }
    }

    public function test_valid_png_is_stored_under_a_random_name_in_a_created_directory(): void
    {
        $uploadDir = sys_get_temp_dir() . '/ordina-uploads-' . bin2hex(random_bytes(4));
        $file = $this->pngUpload();

        $path = (new ProductImageUploader($uploadDir, 'uploads/products'))->upload($file);

        self::assertNotNull($path);
        self::assertMatchesRegularExpression('#^uploads/products/[0-9a-f]{32}\.png$#', $path);

        $stored = $uploadDir . '/' . basename($path);
        $this->cleanup[] = $uploadDir;
        $this->cleanup[] = $stored;

        self::assertFileExists($stored);
        self::assertFileDoesNotExist($file['tmp_name'], 'The temp file is moved, not copied.');
    }

    public function test_upload_directory_that_cannot_be_created_is_an_error(): void
    {
        $blocker = $this->tempFile('a regular file, so nothing can be created beneath it');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Upload directory could not be created.');

        $this->withoutPhpWarnings(fn () => (new ProductImageUploader($blocker . '/products'))->upload($this->pngUpload()));
    }

    #[RequiresOperatingSystemFamily('Linux')]
    public function test_file_that_cannot_be_moved_is_an_error(): void
    {
        // /proc exists but nothing can be written into it, even as root.
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to store uploaded file.');

        $this->withoutPhpWarnings(fn () => (new ProductImageUploader('/proc'))->upload($this->pngUpload()));
    }

    /** mkdir()/rename() also raise a PHP warning on failure - the exception is what's under test. */
    private function withoutPhpWarnings(callable $callback): void
    {
        set_error_handler(static fn (): bool => true);

        try {
            $callback();
        } finally {
            restore_error_handler();
        }
    }
}
