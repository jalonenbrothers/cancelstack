<?php
$body = <<<HTML
<section class="hero">
  <h1 class="h1">They make cancelling hard.<br>Make the paper trail easy.</h1>
  <p class="lede">CancelStack logs every cancellation attempt and turns it into a
  timestamped evidence bundle — ready for a chargeback dispute or an FTC complaint.</p>

  <ul class="value-strip">
    <li>Log each attempt in under a minute</li>
    <li>A clean timeline builds itself</li>
    <li>One-click dispute-ready bundle</li>
  </ul>

  <p class="fear">If you wait until you're disputing the charge, it's too late to remember what happened.</p>

  <p class="cta-row">
    <a class="btn btn-primary" href="/case/new">Start a cancellation case</a>
  </p>
  <p class="microcopy">No account. No bank login. We store nothing but what you type.</p>
</section>

<section class="resume-card card">
  <h2 class="h2">Resume a case</h2>
  <p class="muted">Enter your case token.</p>
  <form id="resume-form" class="resume-form" novalidate>
    <input id="resume-token" name="token" type="text" inputmode="latin"
           pattern="[0-9A-Za-z]{10}" maxlength="10" autocomplete="off"
           aria-label="Case token" placeholder="10-character token">
    <button class="btn btn-ghost" type="submit">Open case</button>
  </form>
  <p id="resume-error" class="form-error" hidden>We couldn't find that case. Check for a typo.</p>
  <p id="resume-last" hidden><a id="resume-last-link" class="chip-last" href="#"></a></p>
</section>
HTML;
page('They make cancelling hard', $body);
