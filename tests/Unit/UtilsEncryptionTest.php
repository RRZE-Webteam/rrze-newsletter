<?php

declare(strict_types=1);

namespace RRZE\Newsletter\Tests\Unit;

use PHPUnit\Framework\TestCase;
use RRZE\Newsletter\Utils;

/** Compatibility checks for the existing format, not a cryptographic security audit. */
final class UtilsEncryptionTest extends TestCase
{
    private const STORED_PASSWORD = 'WndKT1RzTFlPL2swVVN4dXlKSmRsTW9qaDd3ejRJa0l0em1aOFhoSG9lST0=';

    public function testPasswordWritersKeepTheExistingStorageFormat(): void
    {
        self::assertSame(self::STORED_PASSWORD, Utils::encrypt('smtp-test-password'));
        self::assertSame(self::STORED_PASSWORD, Utils::setPassword('smtp-test-password'));
        self::assertSame(self::STORED_PASSWORD, Utils::sanitizePassword('smtp-test-password'));
    }

    public function testPreviouslyStoredPasswordCanBeRead(): void
    {
        self::assertSame('smtp-test-password', Utils::decrypt(self::STORED_PASSWORD));
        self::assertSame('smtp-test-password', Utils::getPassword(self::STORED_PASSWORD));
    }

    public function testPasswordRoundTripsPreserveUnicodeWhitespaceAndZero(): void
    {
        foreach (['0', '  password with spaces  ', 'Grüße 🔑', "line one\nline two", str_repeat('secret', 100)] as $plain) {
            $encrypted = Utils::setPassword($plain);

            self::assertNotSame($plain, $encrypted);
            self::assertSame($plain, Utils::getPassword($encrypted));
        }
    }

    public function testEmptyValuesRemainEmptyThroughEveryPublicWrapper(): void
    {
        foreach (['encrypt', 'decrypt', 'setPassword', 'getPassword', 'sanitizePassword', 'encryptQueryVar', 'decryptQueryVar'] as $method) {
            self::assertSame('', Utils::$method(''), $method);
        }
    }

    public function testArchiveIdentifierKeepsItsExistingUrlToken(): void
    {
        $token = 'K2oybXhpMENwSEx1RlZWK09TbGhFQT09';

        self::assertSame($token, Utils::encryptQueryVar('42'));
        self::assertSame('42', Utils::decryptQueryVar($token));
    }

    public function testUrlTokensWithDifferentPaddingLengthsCanBeDecoded(): void
    {
        // Fixed fixtures cover base64 lengths requiring zero, one and two '=' pads.
        $fixtures = [
            ['42', 'K2oybXhpMENwSEx1RlZWK09TbGhFQT09'],
            ['smtp-test-password', 'WndKT1RzTFlPL2swVVN4dXlKSmRsTW9qaDd3ejRJa0l0em1aOFhoSG9lST0'],
            [str_repeat('x', 40), 'VXB4Rk05cHVOYW9LYkJ2Nm1QbmREUzRCVWt4MTVnR0FMWjdhSWU3MW85Yy9sUVdqYXRRQW9lMU5sbEZpMnh4eQ'],
        ];
        foreach ($fixtures as [$plain, $token]) {
            self::assertSame($token, Utils::encryptQueryVar($plain));
            self::assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', $token);
            self::assertSame($plain, Utils::decryptQueryVar($token));
        }
    }

    public function testMalformedCiphertextReturnsFalse(): void
    {
        foreach (['!!!!', base64_encode('not an OpenSSL ciphertext')] as $invalid) {
            self::assertFalse(Utils::decrypt($invalid));
            self::assertFalse(Utils::decryptQueryVar($invalid));
        }
    }
}
