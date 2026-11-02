# CancelStack

They make cancelling hard. Make the paper trail easy.

CancelStack logs every subscription-cancellation attempt and turns it into a
timestamped evidence bundle — ready for a bank chargeback dispute or an FTC
complaint. No accounts, no uploads, no bank access: one anonymous 10-character
token is the only key to a case.

## Stack

- PHP 8+ (no frameworks, zero dependencies)
- MySQL via PDO (prepared statements everywhere)
- Vanilla HTML5/JS/CSS — no build steps
- Repo root = web root

## How it works

- `/` — landing + resume by token (localStorage remembers your last case)
- `/case/new` — create a case (merchant, plan, amount, cycle, start, "what have
  you tried already?" becomes attempt #1) → token reveal card (shown once)
- `/c/{token}` — timeline: sticky case header with derived status, log/edit
  attempts (date, channel, outcome, confirmation number, note, screenshot URL)
- `/c/{token}/export` — pick bundle type + optional recipient →
- `/c/{token}/bundle?type=bank|regulator&to=...` — print-oriented evidence
  bundle (identity header, facts table, chronological timeline, screenshot
  references, sign-off footer; `window.print()` to save as PDF)
- `/about` — why evidence matters + the limits + disclaimer

Case status is DERIVED from outcomes, never stored: In progress → Cancelled
(any success) → Disputed (any `still-charged`).

## API

- `POST /api/case` — create case → `{ok, token, case_url}`
- `GET /api/case/{token}` — case + attempts (newest first) + derived status
- `POST /api/case/{token}/attempt` — log attempt
- `PATCH /api/attempt/{id}` — edit attempt (POST alias accepted)

Tokens: 10-char base32 (no I/L/O/U), generated server-side with CSPRNG.

## Tests

```
php tests/run.php    # unit tests: Token, Status derivation, Validator
```

## Deploy (Hostinger subdomain)

Full operator run/deploy instructions — local Docker stack with tested
commands, Hostinger migration steps and a post-deploy verification
checklist — in **[docs/DEPLOY.md](docs/DEPLOY.md)**.

1. Clone/upload repo to the subdomain's web root
2. cp lib/config.example.php lib/config.php — fill db creds from hPanel
3. Run migrations/ per migrations/README.md
4. Done — no build steps

No external APIs are used; runtime cost is $0.

## Legal

CancelStack organizes evidence. It is not a law firm and does not provide
legal advice.
