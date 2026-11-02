<?php
/** GET /c/{token}/export — export options page. */
$token = strtoupper($seg[1] ?? '');
if (!CancelStack\Token::valid($token)) {
    http_response_code(404);
    $notFoundTokenCase = true;
    require __DIR__ . '/404.php';
    return;
}
$caseUrl = e(app_url() . '/c/' . $token);
$body = <<<HTML
<section class="card form-card">
  <h1 class="h1">Export bundle</h1>
  <p class="lede">What you get: a timestamped dossier of every attempt — built for the
  two desks that actually read evidence: your bank's dispute desk and a regulator.</p>

  <form id="export-form" onsubmit="return false;">
    <label class="field">
      <span>Document type</span>
      <select id="export-type" name="type">
        <option value="bank">Bank chargeback dispute pack</option>
        <option value="regulator">Regulator complaint attachment (FTC / state AG)</option>
      </select>
    </label>
    <label class="field">
      <span>Recipient line (optional)</span>
      <input id="export-to" name="to" type="text" maxlength="120"
        placeholder="Bank name or AG office — appears in the document header">
    </label>
    <p>
      <button id="build-bundle" class="btn btn-primary btn-wide" type="button"
        onclick="window.location='{$caseUrl}/bundle?type='+encodeURIComponent(document.getElementById('export-type').value)+'&to='+encodeURIComponent(document.getElementById('export-to').value)">Build my bundle</button>
    </p>
    <p class="microcopy"><a href="{$caseUrl}">Back to the case timeline</a></p>
  </form>
</section>
HTML;
page('Export bundle', $body);
