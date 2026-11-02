<?php
declare(strict_types=1);

namespace CancelStack;

/** Token generation: 10-char base32 (Crockford-ish, no ambiguous chars), CSPRNG. */
final class Token
{
    private const ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ'; // no I, L, O, U

    public static function generate(): string
    {
        $out = '';
        for ($i = 0; $i < 10; $i++) {
            $out .= self::ALPHABET[random_int(0, 31)];
        }
        return $out;
    }

    /** Valid token shape (exact charset + length). */
    public static function valid(string $t): bool
    {
        return strlen($t) === 10
            && preg_match('/^[0-9A-HJKMNP-TV-Z]{10}$/', $t) === 1;
    }
}
