<?php
declare(strict_types=1);

/** POST /api/case/{token}/attempt — log an attempt. */

$token = strtoupper((string)($seg[2] ?? ''));
if (!CancelStack\Token::valid($token)) {
    json_out(['error' => 'Case not found.'], 404);
}

$pdo = cs_db();
$st = $pdo->prepare('SELECT id FROM cases WHERE token = ?');
$st->execute([$token]);
$case = $st->fetch();
if (!$case) {
    json_out(['error' => 'Case not found.'], 404);
}

$v = CancelStack\Validator::attempt(json_in());
$st = $pdo->prepare(
    'INSERT INTO attempts (case_id, attempt_date, channel, outcome, confirmation, note, screenshot_url)
     VALUES (?, ?, ?, ?, ?, ?, ?)'
);
$st->execute([
    $case['id'], $v['date'], $v['channel'], $v['outcome'],
    $v['confirmation'], $v['note'], $v['screenshot_url'],
]);

json_out(['ok' => true, 'id' => (int)$pdo->lastInsertId()], 201);
