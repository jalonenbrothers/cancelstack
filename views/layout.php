<?php
/** Shared page shell. Call page(title, bodyHtml, extraHead). */
function page(string $title, string $body, string $extraHead = '', string $bodyClass = ''): void
{
    $home = app_url();
    $nav = <<<NAV
<header class="site-head">
  <nav class="nav">
    <a class="brand" href="{$home}/">CancelStack</a>
    <div class="nav-links">
      <a href="{$home}/case/new">New case</a>
      <a href="{$home}/about">About &amp; legal</a>
    </div>
  </nav>
</header>
NAV;
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>{$title} — CancelStack</title>
<link rel="stylesheet" href="{$home}/assets/css/app.css">
{$extraHead}
</head>
<body class="{$bodyClass}">
<a class="skip" href="#main">Skip to content</a>
{$nav}
<main id="main" class="wrap">
{$body}
</main>
<footer class="site-foot">
  <p>CancelStack organizes evidence. It is not a law firm and does not provide legal advice.</p>
</footer>
<script src="{$home}/assets/js/app.js" defer></script>
</body>
</html>
HTML;
}
