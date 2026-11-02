<?php
/* 404 — used for both unknown routes and invalid tokens. */
$caseNotFound = $notFoundTokenCase ?? false;
$repl1 = $caseNotFound ? 'Case not found. Check the token for typos.' : 'Page not found.';
$repl2 = $caseNotFound ? 'Tokens are 10 characters, letters and digits.' : '';
$body = <<<HTML
<section class="card form-card" style="text-align:center">
  <h1 class="h1">404</h1>
  <p class="lede">{$repl1}</p>
  <p class="muted">{$repl2}</p>
  <p><a class="btn btn-primary" href="/">Back to CancelStack</a></p>
</section>
HTML;
page('Not found', $body);
