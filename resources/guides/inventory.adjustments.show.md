# About this page

The permanent record of one stock adjustment: which parts moved in or out, at what cost, the GL journal it posted, and who posted it.

## How it works
- Each line is a ledger movement (`ADJUSTMENT_IN` / `ADJUSTMENT_OUT`). OUT lines leave at the part's average cost; IN lines use the cost entered.
- **Net stock value** is the signed total — negative for write-offs, positive for found stock.
- The **GL journal** link opens the matching entry (DR/CR inventory vs the write-off account). An adjustment created by a stock take shows that take as its **Source**.

## Watch out
- Posted adjustments are immutable. To correct one, post a new adjustment in the opposite direction — never edit the ledger.
