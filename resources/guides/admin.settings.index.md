# About this page

The Configuration Centre holds **every configurable business value** in the system — VAT rate, discount limits, credit rules, document texts and more. Nothing is hardcoded: change it here, and the whole system follows.

## How it works
- **Global (all branches)** edits the company-wide value.
- Pick a **branch** to override a setting for that branch only. Settings that can't differ per branch are locked in branch view.
- **Inherits global** means the branch has no override; **Overridden** means it does. Use the ↺ button to revert an override.
- Nothing saves until you press **Save** — the button shows how many changes are pending.

## Watch out
- Settings marked with the shield are **sensitive** (e.g. base currency, VAT rate) — changing them affects money. Check with management first.
- Changes take effect immediately for all users.

## Related
- Exchange rates live in [Currencies & Rates](route:admin.currencies.index)
- Document numbering lives in [Number Sequences](route:admin.sequences.index)
