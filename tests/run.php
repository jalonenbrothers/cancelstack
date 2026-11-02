<?php
declare(strict_types=1);

/** Tiny test runner: php tests/run.php (unit tests, no DB needed) */

$fail = 0; $pass = 0;
function check(bool $cond, string $name): void {
    global $fail, $pass;
    if ($cond) { $pass++; echo "ok  - $name\n"; }
    else { $fail++; echo "FAIL - $name\n"; }
}

require __DIR__ . '/../src/Token.php';
require __DIR__ . '/../src/Status.php';
require __DIR__ . '/../src/Validator.php';

use CancelStack\Token;
use CancelStack\Status;
use CancelStack\Validator;

// --- Token ---
$t = Token::generate();
check(strlen($t) === 10, 'token length 10');
check(preg_match('/^[0-9A-HJKMNP-TV-Z]{10}$/', $t) === 1, 'token charset base32, no I/L/O/U');
check(Token::valid($t), 'token valid() accepts own output');
check(!Token::valid('ABCDEFGHIJ'), 'token valid() rejects ambiguous chars');
check(!Token::valid('ABC123'), 'token valid() rejects short');
check(!Token::valid('ABCDEFGHIK'), 'token valid() rejects K? (K is allowed) — expect false for I');
$set = [];
for ($i = 0; $i < 200; $i++) { $set[Token::generate()] = 1; }
check(count($set) === 200, '200 tokens all unique');

// --- Status (derived, never stored) ---
check(Status::fromOutcomes([]) === 'In progress', 'empty -> In progress');
check(Status::fromOutcomes(['no-confirmation']) === 'In progress', 'no-confirmation -> In progress');
check(Status::fromOutcomes(['success']) === 'Cancelled', 'success -> Cancelled');
check(Status::fromOutcomes(['success', 'still-charged']) === 'Disputed', 'success then still-charged -> Disputed');
check(Status::fromOutcomes(['still-charged']) === 'Disputed', 'still-charged alone -> Disputed');
check(Status::fromOutcomes(['still-charged', 'success']) === 'Disputed', 'still-charged ever -> Disputed');

// --- Validator: case ---
$ok = Validator::case(['merchant' => ' GymHub ', 'amount' => '14.99', 'cycle' => 'monthly',
    'started' => '2026-01-05', 'plan' => 'Premium', 'ref' => 'A-123', 'tried' => 'x']);
check($ok['merchant'] === 'GymHub', 'case: merchant trimmed');
check($ok['amount'] === 14.99, 'case: amount parsed');
check($ok['cycle'] === 'monthly', 'case: cycle passes');
try { Validator::case(['merchant' => '']); check(false, 'case: empty merchant rejected'); $fail++; }
catch (InvalidArgumentException $e) { check(true, 'case: empty merchant rejected'); }
try { Validator::case(['merchant' => str_repeat('x', 121)]); check(false, 'case: long merchant rejected'); $fail++; }
catch (InvalidArgumentException $e) { check(true, 'case: long merchant rejected'); }
try { Validator::case(['merchant' => 'M', 'cycle' => 'weekly']); check(false, 'case: bad cycle rejected'); $fail++; }
catch (InvalidArgumentException $e) { check(true, 'case: bad cycle rejected'); }
try { Validator::case(['merchant' => 'M', 'started' => '05/01/2026']); check(false, 'case: bad date rejected'); $fail++; }
catch (InvalidArgumentException $e) { check(true, 'case: bad date rejected'); }
try { Validator::case(['merchant' => 'M', 'amount' => -5]); check(false, 'case: negative amount rejected'); $fail++; }
catch (InvalidArgumentException $e) { check(true, 'case: negative amount rejected'); }
$min = Validator::case(['merchant' => 'M']);
check($min['plan'] === null && $min['amount'] === null && $min['started'] === null, 'case: minimal case ok');

// --- Validator: attempt ---
$a = Validator::attempt(['date' => date('Y-m-d'), 'channel' => 'phone',
    'outcome' => 'no-confirmation', 'note' => ' hold music ', 'screenshot_url' => '']);
check($a['note'] === 'hold music', 'attempt: note trimmed');
check($a['screenshot_url'] === null, 'attempt: empty url -> null');
check($a['date'] === date('Y-m-d'), 'attempt: today allowed');
try { Validator::attempt(['date' => date('Y-m-d', strtotime('+2 days')), 'channel' => 'phone', 'outcome' => 'other']); check(false, 'attempt: future date rejected'); $fail++; }
catch (InvalidArgumentException $e) { check(true, 'attempt: future date rejected'); }
try { Validator::attempt(['date' => date('Y-m-d'), 'channel' => 'fax', 'outcome' => 'other']); check(false, 'attempt: bad channel rejected'); $fail++; }
catch (InvalidArgumentException $e) { check(true, 'attempt: bad channel rejected'); }
try { Validator::attempt(['date' => date('Y-m-d'), 'channel' => 'phone', 'outcome' => 'scammed']); check(false, 'attempt: bad outcome rejected'); $fail++; }
catch (InvalidArgumentException $e) { check(true, 'attempt: bad outcome rejected'); }
try { Validator::attempt(['date' => date('Y-m-d'), 'channel' => 'phone', 'outcome' => 'other', 'screenshot_url' => 'not a url']); check(false, 'attempt: bad screenshot url rejected'); $fail++; }
catch (InvalidArgumentException $e) { check(true, 'attempt: bad screenshot url rejected'); }
$a2 = Validator::attempt(['date' => '2026-01-01', 'channel' => 'email',
    'outcome' => 'success', 'confirmation' => 'CN-99', 'screenshot_url' => 'https://imgur.com/x']);
check($a2['confirmation'] === 'CN-99' && $a2['screenshot_url'] === 'https://imgur.com/x', 'attempt: full fields pass');

// --- Validator: hostile array/object input (BUG t_b0bb43a1) — must throw, never cast ---
try { Validator::case(['merchant' => ['array']]); check(false, 'case: array merchant rejected'); $fail++; }
catch (InvalidArgumentException $e) { check(true, 'case: array merchant rejected'); }
try { Validator::case(['merchant' => 'M', 'amount' => ['x']]); check(false, 'case: array amount rejected'); $fail++; }
catch (InvalidArgumentException $e) { check(true, 'case: array amount rejected'); }
try { Validator::case(['merchant' => 'M', 'cycle' => ['x']]); check(false, 'case: array cycle rejected'); $fail++; }
catch (InvalidArgumentException $e) { check(true, 'case: array cycle rejected'); }
try { Validator::case(['merchant' => 'M', 'plan' => ['x']]); check(false, 'case: array plan rejected'); $fail++; }
catch (InvalidArgumentException $e) { check(true, 'case: array plan rejected'); }
try { Validator::attempt(['date' => date('Y-m-d'), 'channel' => 'phone', 'outcome' => ['x']]); check(false, 'attempt: array outcome rejected'); $fail++; }
catch (InvalidArgumentException $e) { check(true, 'attempt: array outcome rejected'); }
try { Validator::attempt(['date' => date('Y-m-d'), 'channel' => 'phone', 'outcome' => 'other', 'note' => ['x']]); check(false, 'attempt: array note rejected'); $fail++; }
catch (InvalidArgumentException $e) { check(true, 'attempt: array note rejected'); }
try { Validator::attempt(['date' => ['y'], 'channel' => 'phone', 'outcome' => 'other']); check(false, 'attempt: array date rejected'); $fail++; }
catch (InvalidArgumentException $e) { check(true, 'attempt: array date rejected'); }
// null fields are absent-friendly (coalesce), not hostile
$nullable = Validator::case(['merchant' => 'M', 'plan' => null]);
check($nullable['plan'] === null, 'case: null plan treated as absent');

echo "\n" . ($fail ? "$fail FAILED, " : '') . "$pass passed\n";
exit($fail ? 1 : 0);
