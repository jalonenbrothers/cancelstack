<?php
declare(strict_types=1);

namespace CancelStack;

/** Case status is DERIVED from attempt outcomes — never stored. */
final class Status
{
    public const IN_PROGRESS = 'In progress';
    public const CANCELLED    = 'Cancelled';
    public const DISPUTED     = 'Disputed';

    /**
     * Rules (per UX plan):
     * - still-charged after a success flips the case to Disputed
     * - any success (without a later still-charged) => Cancelled
     * - otherwise In progress
     * $outcomes: list of outcome strings, in chronological order (oldest first).
     */
    public static function fromOutcomes(array $outcomes): string
    {
        $outcomes = array_values($outcomes);
        if (in_array('still-charged', $outcomes, true)) {
            return self::DISPUTED;
        }
        if (in_array('success', $outcomes, true)) {
            return self::CANCELLED;
        }
        return self::IN_PROGRESS;
    }
}
