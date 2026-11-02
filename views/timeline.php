<?php
/** GET /c/{token} — case timeline. $seg[1] = token. Data loaded client-side from API. */
$token = strtoupper($seg[1] ?? '');
if (!CancelStack\Token::valid($token)) {
    http_response_code(404);
    $notFoundTokenCase = true;
    require __DIR__ . '/404.php';
    return;
}
$caseUrl = e(app_url() . '/c/' . $token);
$body = <<<HTML
<div id="case-head" class="case-head" data-token="{$token}">
  <div class="case-head-line">
    <h1 class="h1 case-merchant" id="c-merchant">…</h1>
    <span id="c-status" class="chip">…</span>
  </div>
  <p class="muted" id="c-sub">…</p>
  <p class="mono muted" id="c-days">…</p>
  <div class="case-actions">
    <button id="log-attempt" class="btn btn-primary" type="button">Log an attempt</button>
    <a class="btn btn-ghost" href="{$caseUrl}/export">Export bundle</a>
  </div>
</div>

<div id="attempt-modal" class="modal-backdrop" hidden>
  <div class="modal card" role="dialog" aria-modal="true" aria-labelledby="modal-title">
    <h2 class="h2" id="modal-title">Log an attempt</h2>
    <form id="attempt-form" novalidate>
      <input type="hidden" name="attempt_id" id="attempt-id" value="">
      <label class="field">
        <span class="req">Date</span>
        <input name="date" id="f-date" type="date" required>
      </label>
      <div class="field-row">
        <label class="field">
          <span class="req">Channel</span>
          <select name="channel" required>
            <option value="phone">Phone</option>
            <option value="email">Email</option>
            <option value="web portal">Web portal</option>
            <option value="chat">Chat</option>
            <option value="app">App</option>
            <option value="other">Other</option>
          </select>
        </label>
        <label class="field">
          <span class="req">Outcome</span>
          <select name="outcome" required>
            <option value="spoke-to-human">Spoke to a human</option>
            <option value="no-confirmation">No confirmation</option>
            <option value="retention-offer-received">Retention offer received</option>
            <option value="success">Success — cancelled</option>
            <option value="still-charged">Still charged after cancelling</option>
            <option value="other">Other</option>
          </select>
        </label>
      </div>
      <label class="field">
        <span>Confirmation number</span>
        <input name="confirmation" type="text" maxlength="120" autocomplete="off">
      </label>
      <label class="field">
        <span>Note</span>
        <textarea name="note" rows="3"></textarea>
      </label>
      <label class="field">
        <span>Screenshot URL</span>
        <input name="screenshot_url" type="url" placeholder="Link to where your screenshot lives — Drive, iCloud, Imgur">
      </label>
      <p id="attempt-error" class="form-error" hidden></p>
      <div class="cta-row">
        <button type="submit" class="btn btn-primary" id="attempt-submit">Save attempt</button>
        <button type="button" class="btn btn-ghost" data-close-modal>Cancel</button>
      </div>
    </form>
  </div>
</div>

<p id="empty-state" class="empty-state" hidden>No attempts logged yet. Log your first — even a failed one is evidence.</p>

<ol id="timeline" class="timeline" aria-label="Attempt timeline"></ol>
HTML;
page('Case timeline', $body);
