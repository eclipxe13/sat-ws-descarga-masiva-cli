<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace PhpCfdi\SatWsDescargaMasiva\CLI\Tests\Unit\Service;

use JsonException;
use PhpCfdi\SatWsDescargaMasiva\CLI\Service\StorageToken;
use PhpCfdi\SatWsDescargaMasiva\CLI\Tests\Helpers\TemporaryFile;
use PhpCfdi\SatWsDescargaMasiva\CLI\Tests\TestCase;
use PhpCfdi\SatWsDescargaMasiva\Shared\DateTime;
use PhpCfdi\SatWsDescargaMasiva\Shared\Token;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;

final class StorageTokenTest extends TestCase
{
    public function testCreateUsingEmptyPath(): void
    {
        $filename = '';
        $storageToken = new StorageToken($filename);
        $this->assertSame($filename, $storageToken->filename);
        $this->assertNull($storageToken->current());
    }

    public function testCreateUsingNonExistentFile(): void
    {
        $temporaryFile = new TemporaryFile();
        $filename = $temporaryFile->getPath();
        $temporaryFile->delete();

        $storageToken = new StorageToken($filename);
        $this->assertSame($filename, $storageToken->filename);
        $this->assertNull($storageToken->current());
    }

    public function testCreateUsingEmptyContent(): void
    {
        $temporaryFile = new TemporaryFile();
        $storageToken = new StorageToken($temporaryFile->getPath());
        $this->assertSame($temporaryFile->getPath(), $storageToken->filename);
        $this->assertNull($storageToken->current());
    }

    public function testCreateUsingKnownContent(): void
    {
        $temporaryFile = new TemporaryFile();
        $storageToken = new StorageToken($temporaryFile->getPath());
        $this->assertSame($temporaryFile->getPath(), $storageToken->filename);
        $this->assertNull($storageToken->current());
    }

    public function testReadToken(): void
    {
        $token = new Token(DateTime::create(time() - 10), DateTime::create(time()), 'x-token');
        $temporaryFile = new TemporaryFile();
        $contents = StorageToken::serializeToken($token);
        $temporaryFile->putContents($contents);

        $storageToken = new StorageToken($temporaryFile->getPath());
        $this->assertEquals($token, $storageToken->current());
    }

    public function testStoreToken(): void
    {
        $token = new Token(DateTime::create(time() - 10), DateTime::create(time()), 'x-token');
        $temporaryFile = new TemporaryFile();

        $storageToken = new StorageToken($temporaryFile->getPath());
        $storageToken->store($token);
        $this->assertEquals($token, $storageToken->current());
    }

    public function testStoreTokenWithEmptyFile(): void
    {
        $token = new Token(DateTime::create(time() - 10), DateTime::create(time()), 'x-token');

        $storageToken = new StorageToken('');
        $this->assertNull($storageToken->current());

        $storageToken->store($token);
        $this->assertSame(
            $token,
            $storageToken->current(),
            'When no file path is defined, the stored and current token must be the same object'
        );
    }

    public function testUnserializeTokenWithInvalidJson(): void
    {
        $this->expectException(JsonException::class);
        StorageToken::unserializeToken('');
    }

    public function testCurrentWithInvalidContent(): void
    {
        $temporaryFile = new TemporaryFile();
        $temporaryFile->putContents('invalid json');
        $storageToken = new StorageToken($temporaryFile->getPath());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to create token from file');
        $storageToken->current();
    }

    public function testStoreWithInvalidFilename(): void
    {
        $token = new Token(DateTime::create(time() - 10), DateTime::create(time()), 'x-token');
        $filename = __FILE__ . '/foo/bar/baz.txt';
        $storageToken = new StorageToken($filename);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf('Unable to write contents on "%s"', $filename));
        $storageToken->store($token);
    }

    /** @return array<string, array{string, string}> */
    public static function providerUnserializeTokenWithInvalidJsonStructure(): array
    {
        return [
            'not array' => [
                'null',
                'Unexpected JSON contents from token',
            ],
            'without created' => [
                (string) json_encode([]),
                'Invalid JSON value on key "created"',
            ],
            'invalid created' => [
                (string) json_encode(['created' => '']),
                'Invalid JSON value on key "created"',
            ],
            'without expires' => [
                (string) json_encode(['created' => time()]),
                'Invalid JSON value on key "expires"',
            ],
            'invalid expires' => [
                (string) json_encode(['created' => time(), 'expires' => '']),
                'Invalid JSON value on key "expires"',
            ],
            'without token' => [
                (string) json_encode(['created' => time(), 'expires' => time()]),
                'Invalid JSON value on key "token"',
            ],
            'invalid token' => [
                (string) json_encode(['created' => time(), 'expires' => time(), 'token' => 0]),
                'Invalid JSON value on key "token"',
            ],
            'empty token' => [
                (string) json_encode(['created' => time(), 'expires' => time(), 'token' => '']),
                'Invalid JSON value on key "token"',
            ],
        ];
    }

    #[DataProvider('providerUnserializeTokenWithInvalidJsonStructure')]
    public function testUnserializeTokenWithInvalidJsonStructure(string $json, string $message): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($message);
        StorageToken::unserializeToken($json);
    }
}
