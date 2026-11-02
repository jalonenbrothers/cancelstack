<?php
declare(strict_types=1);

/** GET /api/case/{token} — case + attempts (newest first) + derived status. */

$token = strtoupper((string)($seg[2] ?? ''));
if (!CancelStack\Token::valid($token)) {
    json_out(['error' => 'Case not found.'], 404);
}

$pdo = cs_db();
$st = $pdo->prepare('SELECT * FROM cases WHERE token = ?');
$st->execute([$token]);
$case = $st->fetch();
if (!$case) {
    json_out(['error' => 'Case not found.'], 404);
}

$st = $pdo->prepare(
    'SELECT id, attempt_date, channel, outcome, confirmation, note, screenshot_url, logged_at
     FROM attempts WHERE case_id = ?
     ORDER BY attempt_date DESC, logged_at DESC, id DESC'
);
$st->execute([$case['id']]);
$attempts = $st->fetchAll();

// chronological list for status derivation
$chrono = array_column(
    array_reverse($attempts),
    'outcome'
);

json_out([
    'ok' => true,
    'case' => [
        'token'     => $case['token'],
        'merchant' => $case['merchant'],
        'plan'      => $case['plan'],
        'amount'    => $case['amount'] !== null ? (float)$case['amount'] : null,
        'cycle'     => $case['cycle'],
        'started'   => $case['started'],
        'ref'       => $case['ref'],
        'created_at'=> $case['created_at'],
        'days_open' => (int)floor((time() - strtotime($case['created_at'])) / 86400),
        'status'    => CancelStack\Status::fromOutcomes($chrono),
    ],
    'attempts' => $attempts,
]);
