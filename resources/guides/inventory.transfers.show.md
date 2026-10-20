# About this page

One inter-branch transfer from draft to received: where it goes, who dispatched and received it, and the lines moved at the source branch's average cost.

## How it works
- **Dispatch** posts `TRANSFER_OUT` at the source branch — stock leaves immediately at its AVCO, which becomes each line's unit cost.
- **Receive** posts `TRANSFER_IN` at the destination at that same cost, so stock value simply moves between branches; no GL journal is needed.
- The timeline (created / dispatched / received) is read from the stock ledger entries this transfer wrote, so it is always the true audit trail.

## Watch out
- Only a draft can be dispatched and only a dispatched transfer can be received; the button changes with status.
- Goods in transit are off both branches' on-hand figures until received — reconcile dispatched-but-unreceived transfers at month end.
