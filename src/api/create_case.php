<?php
declare(strict_types=1);

/** POST /api/case — create a case. Body: merchant, plan, amount, cycle, started, ref, tried. */

$in = json_in();
$v = CancelStack\Validator::case($in);

$pdo = cs_db();
$token = CancelStack\Token::generate();
for ($i = 0; $i < 5; $i++) {
    try {
        $st = $pdo->prepare(
            'INSERT INTO cases (token, merchant, plan, amount, cycle, started, ref)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $st->execute([
            $token, $v['merchant'], $v['plan'], $v['amount'],
            $v['cycle'], $v['started'], $v['ref'],
        ]);
        break;
    } catch (PDOException $e) {
        // duplicate token: regenerate and retry
        $token = CancelStack\Token::generate();
    }
}

$caseId = (int)$pdo->lastInsertId();

// "What have you tried already?" becomes attempt #1
if ($v['tried'] !== null) {
    $st = $pdo->prepare(
        'INSERT INTO attempts (case_id, attempt_date, channel, outcome, note)
         VALUES (?, ?, "other", "no-confirmation", ?)'
    );
    $st->execute([$caseId, date('Y-m-d'), $v['tried']]);
}

json_out([
    'ok' => true,
    'token' => $token,
    'case_url' => app_url() . '/c/' . $token,
], 201);
