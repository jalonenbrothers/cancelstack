<?php
$body = <<<HTML
<section class="card about-card">
  <h1 class="h1">About CancelStack</h1>

  <h2 class="h2">Why evidence matters</h2>
  <p>Cancelling a subscription is often deliberately hard. The FTC has brought
  enforcement actions against companies that buried cancellation flows behind
  retention gauntlets, and its Click-to-Cancel rule was vacated in 2025 — which
  means for now, you're on your own.</p>
  <p>What still works is the paper trail. A bank's dispute desk reads evidence.
  A regulator reads evidence. Your memory of a phone call three months ago,
  read after the fact, is not evidence.</p>
  <p>The right time to start the record is before you're angry — the day you
  decide to cancel, not the day the charge comes back.</p>

  <h2 class="h2">The limits</h2>
  <p>CancelStack has no access to your bank, your email, or the merchant. It
  does not verify your claims. It does not send anything to anyone. It is a
  record-keeper: you type what happened, it keeps the timestamps straight and
  formats the record for the two desks that read evidence.</p>
  <p>CancelStack is not a law firm and does not provide legal advice.</p>

  <div class="disclaimer">
    <p><strong>Disclaimer.</strong> CancelStack organizes evidence. It is not a law
    firm and does not provide legal advice.</p>
  </div>

  <p><a class="btn btn-primary" href="/case/new">Start a cancellation case</a></p>
</section>
HTML;
page('About & legal', $body);
