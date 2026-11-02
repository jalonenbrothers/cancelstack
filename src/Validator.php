<?php
declare(strict_types=1);

namespace CancelStack;

/** Input validation for case + attempt payloads. Returns clean array or throws. */
final class Validator
{
    public const CHANNELS = ['phone', 'email', 'web portal', 'chat', 'app', 'other'];
    public const OUTCOMES = ['success', 'spoke-to-human', 'retention-offer-received',
                              'no-confirmation', 'still-charged', 'other'];
    public const CYCLES   = ['monthly', 'annual', 'other'];

    /**
     * Hostile-input guard: fetch a field as a trimmed string, rejecting
     * non-scalar values (arrays/objects) instead of casting them —
     * `(string)['x']` emits a PHP warning, leaks it into the response
     * body, and sends headers before http_response_code() (BUG t_b0bb43a1).
     */
    private static function str(array $in, string $key, string $label): string
    {
        $v = $in[$key] ?? '';
        if (!is_scalar($v)) {
            throw new \InvalidArgumentException("$label must be text.");
        }
        return trim((string)$v);
    }

    public static function case(array $in): array
    {
        $merchant = self::str($in, 'merchant', 'Merchant name');
        if ($merchant === '' || mb_strlen($merchant) > 120) {
            throw new \InvalidArgumentException('Merchant name is required (max 120 chars).');
        }
        $amountRaw = self::str($in, 'amount', 'Amount');
        $amount = ($amountRaw !== '') ? (float)$amountRaw : null;
        if ($amount !== null && ($amount < 0 || $amount > 9999999.99 || !is_finite($amount))) {
            throw new \InvalidArgumentException('Amount must be a non-negative number.');
        }
        $cycleRaw = self::str($in, 'cycle', 'Billing cycle');
        $cycle = $cycleRaw !== '' ? $cycleRaw : null;
        if ($cycle !== null && !in_array($cycle, self::CYCLES, true)) {
            throw new \InvalidArgumentException('Invalid billing cycle.');
        }
        $started = self::str($in, 'started', 'Subscription start');
        if ($started !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $started)) {
            throw new \InvalidArgumentException('Subscription start must be a valid date.');
        }
        $plan = self::str($in, 'plan', 'Plan');
        $ref  = self::str($in, 'ref', 'Reference');
        $tried = self::str($in, 'tried', 'Already tried');
        return [
            'merchant' => $merchant,
            'plan'     => $plan !== '' ? substr($plan, 0, 120) : null,
            'amount'   => $amount,
            'cycle'    => $cycle,
            'started'  => $started !== '' ? $started : null,
            'ref'      => $ref !== '' ? substr($ref, 0, 120) : null,
            'tried'    => $tried !== '' ? $tried : null,
        ];
    }

    public static function attempt(array $in): array
    {
        $date = self::str($in, 'date', 'Date');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || $date > date('Y-m-d')) {
            throw new \InvalidArgumentException('Date is required and cannot be in the future.');
        }
        $channel = self::str($in, 'channel', 'Channel');
        if (!in_array($channel, self::CHANNELS, true)) {
            throw new \InvalidArgumentException('Invalid channel.');
        }
        $outcome = self::str($in, 'outcome', 'Outcome');
        if (!in_array($outcome, self::OUTCOMES, true)) {
            throw new \InvalidArgumentException('Invalid outcome.');
        }
        $conf = self::str($in, 'confirmation', 'Confirmation');
        $note = self::str($in, 'note', 'Note');
        $url  = self::str($in, 'screenshot_url', 'Screenshot link');
        if ($url !== '' && filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new \InvalidArgumentException('Screenshot link must be a valid URL.');
        }
        return [
            'date'          => $date,
            'channel'       => $channel,
            'outcome'       => $outcome,
            'confirmation'  => $conf !== '' ? substr($conf, 0, 120) : null,
            'note'          => $note !== '' ? $note : null,
            'screenshot_url'=> $url !== '' ? substr($url, 0, 500) : null,
        ];
    }
}
