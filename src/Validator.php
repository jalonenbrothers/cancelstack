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

    public static function case(array $in): array
    {
        $merchant = trim((string)($in['merchant'] ?? ''));
        if ($merchant === '' || mb_strlen($merchant) > 120) {
            throw new \InvalidArgumentException('Merchant name is required (max 120 chars).');
        }
        $amountRaw = trim((string)($in['amount'] ?? ''));
        $amount = ($amountRaw !== '') ? (float)$amountRaw : null;
        if ($amount !== null && ($amount < 0 || $amount > 9999999.99 || !is_finite($amount))) {
            throw new \InvalidArgumentException('Amount must be a non-negative number.');
        }
        $cycle = ($in['cycle'] ?? '') !== '' ? (string)$in['cycle'] : null;
        if ($cycle !== null && !in_array($cycle, self::CYCLES, true)) {
            throw new \InvalidArgumentException('Invalid billing cycle.');
        }
        $started = trim((string)($in['started'] ?? ''));
        if ($started !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $started)) {
            throw new \InvalidArgumentException('Subscription start must be a valid date.');
        }
        $plan = trim((string)($in['plan'] ?? ''));
        $ref  = trim((string)($in['ref'] ?? ''));
        $tried = trim((string)($in['tried'] ?? ''));
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
        $date = trim((string)($in['date'] ?? ''));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || $date > date('Y-m-d')) {
            throw new \InvalidArgumentException('Date is required and cannot be in the future.');
        }
        $channel = (string)($in['channel'] ?? '');
        if (!in_array($channel, self::CHANNELS, true)) {
            throw new \InvalidArgumentException('Invalid channel.');
        }
        $outcome = (string)($in['outcome'] ?? '');
        if (!in_array($outcome, self::OUTCOMES, true)) {
            throw new \InvalidArgumentException('Invalid outcome.');
        }
        $conf = trim((string)($in['confirmation'] ?? ''));
        $note = trim((string)($in['note'] ?? ''));
        $url  = trim((string)($in['screenshot_url'] ?? ''));
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
