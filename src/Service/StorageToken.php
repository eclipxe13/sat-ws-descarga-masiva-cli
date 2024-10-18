<?php

declare(strict_types=1);

namespace PhpCfdi\SatWsDescargaMasiva\CLI\Service;

use JsonException;
use PhpCfdi\SatWsDescargaMasiva\CLI\Internal\Filesystem;
use PhpCfdi\SatWsDescargaMasiva\Shared\DateTime;
use PhpCfdi\SatWsDescargaMasiva\Shared\Token;
use RuntimeException;
use Throwable;

final class StorageToken
{
    private ?Token $inMemoryToken = null;

    private readonly Filesystem $fs;

    public function __construct(public readonly string $filename)
    {
        $this->fs = new Filesystem();
    }

    /**
     * @return Token|null
     * @throws RuntimeException
     */
    public function current(): ?Token
    {
        if ('' === $this->filename) {
            return $this->inMemoryToken;
        }

        $contents = $this->readContents();
        if ('' === $contents) {
            return null;
        }

        try {
            return self::unserializeToken($contents);
        } catch (Throwable $exception) {
            throw new RuntimeException(
                sprintf('Unable to create token from file %s', $this->filename),
                previous: $exception
            );
        }
    }

    public function store(Token $token): void
    {
        if ('' === $this->filename) {
            $this->inMemoryToken = $token;
            return;
        }

        $content = self::serializeToken($token);
        $this->storeContents($content);
    }

    /**
     * @throws JsonException
     * @throws RuntimeException
     */
    public static function unserializeToken(string $contents): Token
    {
        $values = json_decode($contents, associative: true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($values)) {
            throw new RuntimeException('Unexpected JSON contents from token');
        }

        if (! isset($values['created']) || ! is_int($values['created'])) {
            throw new RuntimeException('Invalid JSON value on key "created"');
        }
        $created = DateTime::create($values['created']);

        if (! isset($values['expires']) || ! is_int($values['expires'])) {
            throw new RuntimeException('Invalid JSON value on key "expires"');
        }
        $expires = DateTime::create($values['expires']);

        if (! isset($values['token']) || ! is_string($values['token']) || '' === $values['token']) {
            throw new RuntimeException('Invalid JSON value on key "token"');
        }
        $value = $values['token'];

        return new Token($created, $expires, $value);
    }

    public static function serializeToken(Token $token): string
    {
        $values = [
            'created' => (int) $token->getCreated()->format('U'),
            'expires' => (int) $token->getExpires()->format('U'),
            'token' => $token->getValue(),
        ];

        return (string) json_encode($values);
    }

    private function readContents(): string
    {
        try {
            return $this->fs->read($this->filename);
        } catch (Throwable) {
            return '';
        }
    }

    private function storeContents(string $content): void
    {
        try {
            $this->fs->write($this->filename, $content);
        } catch (RuntimeException $exception) {
            throw new RuntimeException(
                sprintf('Unable to write contents on "%s"', $this->filename),
                previous: $exception
            );
        }
    }
}
