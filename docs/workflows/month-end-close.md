# Workflow: Month-End Close

> The accountant's monthly checklist, in order. Each step must pass before the next; the period is closed at the end and no further posting into it is possible.

```
Day 1–2 of new month, closing the previous period:

① Cut-off checks
   □ All POS shifts Z-read and cash banked (no open tills for the period)
   □ All completed job cards invoiced (report: completed-not-invoiced)
   □ All dispatched sales orders invoiced
   □ All GRNs for goods physically received are posted
      │
      ▼
② Sub-ledger reconciliations
   □ AR ageing total   = Debtors Control GL balance   [⚠ must equal]
   □ AP ageing total   = Creditors Control GL balance [⚠ must equal]
   □ Stock value report = Inventory Asset GL balance  [⚠ must equal]
   → any difference: drill into the exceptions report (unposted docs,
     manual journals to control accounts — which are blocked, so
     differences indicate a bug or a backdated document)
      │
      ▼
③ Bank reconciliation
   □ Import final bank statement for the month
   □ Match all lines; investigate unmatched > 3 days old
   □ Outstanding items report reviewed (stale cheques, uncleared EFTs)
      │
      ▼
④ Supplier statements
   □ Reconcile major supplier statements vs our AP ledger
   □ Chase missing credit notes (returns shipped, no credit — see
     returns-and-credits.md chase list)
      │
      ▼
⑤ Standing journals
   □ Depreciation
   □ Accruals / prepayments
   □ Payroll journal (from payroll system)
   □ FX revaluation of foreign-currency AP balances [GL → FX Gain/Loss]
      │
      ▼
⑥ VAT
   □ VAT summary report: output vs input for the period
   □ VAT return prepared (status: draft → submitted when filed)
   □ [GL] transfer Output + Input to VAT Control; liability agreed
      │
      ▼
⑦ Review
   □ Trial balance reviewed line by line vs prior month (variance %)
   □ Draft P&L and gross margin by category reviewed with owner
   □ Aged debt review meeting → hold/release decisions on customers
      │
      ▼
⑧ Close
   □ gl_periods.status → 'closed' (posting blocked) [⚠]
   □ Statements auto-emailed to all trade customers
   □ Scheduled month-end reports fire (P&L, ageing, top customers)
   □ Next period confirmed open
```

## Year-end additions (after period 12 closes)

```
□ Full stock take posted (see stock-take-process.md)
□ Bad debt provision reviewed and adjusted
□ Dead stock write-down approved by owner
□ Year-end adjustments journal (auditor/accountant)
□ gl_years.status → 'locked'
□ Retained earnings roll-forward; opening balances into new year
```

## Rules

1. A closed period can be **re-opened only by a super admin**, with reason, fully audited — and never after the VAT return for it is submitted.
2. Documents dated into a closed period are rejected at posting with: *"Period Jan 2026 is closed — use the current period date or contact your administrator."*
3. Steps ② and ⑧ are enforced by the system (blocking); the rest are checklist guidance surfaced on a Month-End Close screen with live status per item.
