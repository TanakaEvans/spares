# Buying & Receiving Stock

The full chain from ordering to paying: Purchase Order → Goods Received → Supplier Invoice → Payment. Each step links to the next.

## Raise a purchase order

**Purchasing → Purchase Orders → New** (route:purchasing.orders.index).

1. Pick the **supplier**.
2. Add the **parts** and quantities you're ordering, with the unit cost.
3. Save. The PO is a draft; submit it and print or email it to the supplier.

> Short of stock? The **Reorder Report** (route:inventory.reorder.index) lists everything below its reorder point and drafts the POs for you in one click — grouped by each part's preferred supplier.

## Receive the goods (GRN)

When the delivery arrives:

1. Open the purchase order and click **Receive** (or **Purchasing → Goods Received**).
2. Enter how many of each line actually arrived. Record any **rejected** items separately — rejects never enter your stock.
3. Post the GRN. Stock goes up, the cost is averaged in, and the value is accrued in the books.

## Match the supplier invoice

When the supplier's invoice arrives:

1. **Purchasing → Supplier Invoices → Capture** against the GRN.
2. Enter the invoice number, date and amounts.
3. The system does a **3-way match** (order vs received vs invoiced). If it's within tolerance it posts to the supplier's account; if not, it's flagged **disputed** for you to resolve.

## Pay the supplier

1. **Finance → Supplier Payments** (route:finance.payments.index), or run a **payment run** to pay many at once (route:finance.payment-run.index).
2. Choose the supplier and the invoices to settle, and the payment method.
3. Post it. The supplier's balance comes down and the bank/cash is reduced.

## Returns to a supplier

**Purchasing → Returns** (route:purchasing.returns.index): raise an RMA, ship it back, and capture the supplier's credit when it comes.

## See it all in one place

Open any **supplier** (route:suppliers.index) to see their orders, receipts, invoices, payments, returns, price list and the parts you buy from them — all linked.
