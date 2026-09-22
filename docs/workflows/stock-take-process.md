# Workflow: Stock Take Process

> Physical counting reconciled to system stock. Full counts (annual), cycle counts (weekly rotation), spot checks (ad hoc).

```
① Plan
   - type: full / cycle (bin range e.g. Aisle A) / spot (named parts)
   - decide freeze_movements (full takes: yes; cycle: usually no)
      │
      ▼
② Create & freeze snapshot
   - system_qty captured per line AT THIS MOMENT
   - if freeze: sales + GRN posting blocked for branch [⚠]
      │
      ▼
③ Count sheets
   - printed per bin (or counted on tablet, scanning bin barcode
     loads that bin's lines)
   - counters work in pairs; sheet shows part + bin, NEVER system qty
     (blind counting — prevents "confirming" the system number)
      │
      ▼
④ Enter counts → variance computed per line
   variance_qty = counted − system   ·   variance_value = variance × AVCO
      │
      ▼
⑤ Variance review [⚠]
   - variance above threshold (qty or value) → mandatory RECOUNT
     by a DIFFERENT counter
   - recount stands as final_qty; supervisor may accept/override with reason
      │
      ▼
⑥ Approve & post
   [STOCK] ADJUSTMENT_IN / ADJUSTMENT_OUT per variance line
   [GL]    shortage: DR Stock Write-off Expense  CR Inventory Asset
           surplus:  DR Inventory Asset          CR Stock Write-off (contra)
   - movements unfrozen
      │
      ▼
⑦ Variance report auto-emailed to management
   - by category, by bin, by counter; total shrinkage value & %
```

## Cycle count rotation (recommended)

| Class | Count frequency | Why |
|-------|----------------|-----|
| A parts (top 20% by value) | Monthly | Highest shrinkage risk/cost |
| B parts | Quarterly | |
| C parts | Twice a year | |
| Quarantine + returns bins | Monthly | Error-prone areas |

## Rules & edge cases

1. Movements during an unfrozen cycle count: lines that moved between snapshot and count entry are flagged `stale` and auto-queued for recount — never auto-posted.
2. A part found in the wrong bin is a bin correction (`part_bin_assignments`), not a quantity variance.
3. Serial-tracked parts: the count records *which* serials are present; missing serials are named on the variance report.
4. Stock take cannot be posted into a closed financial period.
5. Two takes cannot be open for the same branch + bin range simultaneously.
