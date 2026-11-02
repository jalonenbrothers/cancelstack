<?php
$body = <<<HTML
<section class="card form-card">
  <h1 class="h1">Start a cancellation case</h1>
  <p class="muted">One case = one subscription. You can run several cases side by side.</p>

  <form id="new-case-form" novalidate>
    <label class="field">
      <span class="req">Merchant name</span>
      <input name="merchant" type="text" maxlength="120" required autocomplete="off">
    </label>

    <label class="field">
      <span>Plan / product name</span>
      <input name="plan" type="text" maxlength="120" autocomplete="off">
    </label>

    <div class="field-row">
      <label class="field">
        <span>Amount</span>
        <input name="amount" type="number" step="0.01" min="0" inputmode="decimal" placeholder="14.99">
      </label>
      <label class="field">
        <span>Billing cycle</span>
        <select name="cycle">
          <option value="">—</option>
          <option value="monthly">Monthly</option>
          <option value="annual">Annual</option>
          <option value="other">Other</option>
        </select>
      </label>
    </div>

    <label class="field">
      <span>Subscription started</span>
      <input name="started" type="date" max="">
    </label>

    <label class="field">
      <span>Merchant account / reference number</span>
      <input name="ref" type="text" maxlength="120" autocomplete="off">
    </label>

    <label class="field">
      <span>What have you tried already?</span>
      <textarea name="tried" rows="4"
        placeholder="Optional — becomes your first logged attempt, dated today. You can edit it later."></textarea>
    </label>

    <p id="new-case-error" class="form-error" hidden></p>
    <button class="btn btn-primary btn-wide" type="submit">Create case</button>
  </form>
</section>

<section id="token-reveal" class="card token-card" hidden aria-live="polite">
  <h2 class="h2">Save this token — it's the only key to your case.</h2>
  <p><code id="token-text" class="token-text"></code></p>
  <div class="cta-row">
    <button id="copy-link" class="btn btn-ghost" type="button">Copy link</button>
    <a id="open-case" class="btn btn-primary" href="#">Open my case</a>
  </div>
  <p class="microcopy">We can't recover this token. Bookmark the link.</p>
</section>
HTML;
page('New case', $body);
