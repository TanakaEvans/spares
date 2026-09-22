# System Workflows

> End-to-end business processes that cut across modules. Each workflow shows the full chain: who does what, in which module, and what the system does at each step (stock, GL, documents).

| Workflow | File | Modules involved |
|----------|------|------------------|
| Order to Cash (counter sale) | [order-to-cash.md](order-to-cash.md) | Sales, Inventory, Customers, Finance |
| Procure to Pay | [procure-to-pay.md](procure-to-pay.md) | Purchasing, Inventory, Suppliers, Finance |
| Workshop Job Lifecycle | [workshop-job-lifecycle.md](workshop-job-lifecycle.md) | Workshop, Inventory, Sales, Finance |
| Stock Take Process | [stock-take-process.md](stock-take-process.md) | Inventory, Finance |
| Returns & Credits (both directions) | [returns-and-credits.md](returns-and-credits.md) | Sales, Purchasing, Inventory, Finance |
| Month-End Close | [month-end-close.md](month-end-close.md) | Finance, all modules |
| New Part Onboarding | [new-part-onboarding.md](new-part-onboarding.md) | Inventory, Suppliers, Vehicle Reference |

## Reading the diagrams

- Boxes = documents/records; arrows = state transitions
- `[STOCK]` = a stock ledger posting happens here
- `[GL]` = a general ledger journal posts here
- `[⚠]` = an approval gate or validation checkpoint
