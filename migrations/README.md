# CancelStack — Migrations

Plain SQL. Run in order, in Hostinger phpMyAdmin (hPanel → Databases → phpMyAdmin,
select the app database, SQL tab, paste) or via mysql CLI. Every file is safe to
re-run (idempotent via `IF NOT EXISTS`).

## 001_cancelstack.sql
Creates the two tables:
- `cases` — one row per subscription cancellation case (token, merchant, plan,
  amount, billing cycle, subscription start, merchant reference).
- `attempts` — one row per logged cancellation attempt (date, channel, outcome,
  confirmation number, note, optional screenshot URL).

phpMyAdmin: open the file, copy everything, paste into the SQL tab, Run.
CLI: `mysql -u <user> -p <dbname> < 001_cancelstack.sql`
