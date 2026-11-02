<?php
declare(strict_types=1);

/** PATCH (or POST) /api/attempt/{id} — edit an attempt. Full replacement of fields. */

$id = (int)($seg[2] ?? 0);
if ($id <= 0) {
    json_out(['error' => 'Attempt not found.'], 404);
}

$pdo = cs_db();
$st = $pdo->prepare('SELECT case_id FROM attempts WHERE id = ?');
$st->execute([$id]);
$att = $st->fetch();
if (!$att) {
    json_out(['error' => 'Attempt not found.'], 404);
}

$v = CancelStack\Validator::attempt(json_in());
$st = $pdo->prepare(
    'UPDATE attempts
     SET attempt_date = ?, channel = ?, outcome = ?, confirmation = ?, note = ?, screenshot_url = ?
     WHERE id = ?'
);
$st->execute([
    $v['date'], $v['channel'], $v['outcome'],
    $v['confirmation'], $v['note'], $v['screenshot_url'], $id,
]);

json_out(['ok' => true, 'id' => $id]);
