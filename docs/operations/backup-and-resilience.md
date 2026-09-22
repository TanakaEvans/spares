# Backup, Power & Resilience

> Load-shedding, generator switchovers, and patchy internet are operating conditions here, not incidents. The system is designed so that **a power cut never loses a posted transaction and never corrupts the books.**

---

## Hosting decision

| Option | Fits when | Notes |
|--------|-----------|-------|
| **Local server on premises (recommended default)** | Single site or branches with weak internet | LAN-only operation — POS never depends on internet. Server = any solid PC/mini-server with SSD, on UPS |
| Cloud VPS | Reliable internet at all branches | Cheaper ops, but every till dies with the internet — only choose with redundant links |
| Hybrid | Multi-branch | Local server per major branch is complex; prefer one local HQ server + VPN for small branches — accept the small-branch internet dependency, or run them as periodic-sync satellites (future) |

---

## Backups (uses/extends the existing Backup Management sub-module 10.7)

**Schedule (automated via Laravel scheduler + `spatie/laravel-backup`):**

| What | Frequency | Retention |
|------|-----------|-----------|
| Full DB dump | Nightly 22:00 | 30 daily, 12 monthly |
| Incremental DB dump | Every 2 hours during trading | 48 hours |
| Files (part images, uploaded docs) | Nightly | 14 daily, 6 monthly |

**The 3-2-1 rule:** 3 copies, 2 media, 1 off-site —
1. On-server backup directory
2. Second local device (NAS / external SSD that a manager rotates)
3. Off-site: cloud object storage when internet allows (queued upload, retries automatically) — **or** the rotated external drive taken home; both is best

**Rules:**
- Backup failure = red banner on the admin dashboard + email/SMS to the administrator. Silence is the enemy.
- **Quarterly restore drill is mandatory**: restore last night's backup to a scratch database, run the integrity checks below, record the result in the backup log. An untested backup is a hope, not a backup.
- Backups are encrypted at rest (they contain customer data and finances).

---

## Power resilience

- **UPS on: server, network switch/router, at least one till per branch.** Target ≥ 15 min runtime — enough to finish the sale in progress and shut down cleanly, or ride until the generator catches.
- Server: enable auto-shutdown on UPS low-battery signal; MySQL with `innodb_flush_log_at_trx_commit=1` (default) so committed transactions survive hard cuts.
- The app's own guarantees (already in the architecture):
  - Every posting is a single `DB::transaction()` — a cut mid-post leaves a clean *nothing*, never half an invoice ([architecture.md](../architecture.md)).
  - Operational screens keep **drafts** (suspended sales, draft GRNs, line-by-line stock-take saves) — power back on, work resumes ([ui-rules.md](../design/ui-rules.md) §4, §9).
- After any outage, a one-click **Integrity Check** screen (admin) runs: stock ledger sums = stock levels; AR/AP sub-ledgers = control accounts; journal debits = credits; gapless invoice sequence check. Green ticks or named exceptions.

## Internet resilience

- Core trading (POS, GRN, stock, job cards) is **LAN-only** — zero internet paths in those flows.
- Internet-dependent features degrade gracefully and queue: emails/SMS (queued, auto-retry), off-site backup upload (queued), VIN decode (optional field stays manual). A visible "offline — 4 emails queued" indicator, never an error.

## Anti-ransomware / security basics

- The rotated off-site drive is the ransomware insurance (offline copy).
- Server OS auto-updates on; database not exposed beyond LAN; app behind HTTPS even on LAN (self-signed/internal CA); admin accounts with strong passwords per the existing password policy; activity logs (10.6) reviewed monthly.
