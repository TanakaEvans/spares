# Selling (POS & Invoices)

Everything for serving a customer, from a quick cash sale to an account invoice, a quote, or a return.

## Point of Sale — make a cash sale

The till. Open it at [**Sales → Point of Sale**](route:sales.pos).

1. **Scan or search** the part in the big search box at the top (barcode, part number or name). Press **Enter** to add the top match to the cart.
2. Repeat for every item. Adjust the **quantity** or add a **discount %** on any line if needed.
3. When the cart is complete, click **Charge**.
4. In the **Take payment** box, the amount is already filled. Choose **Cash**, **Card** or **EFT**. For cash, type what the customer handed over in **Cash handed over** and the system shows the change.
5. Click **Complete sale**. The invoice is posted, stock comes down, and you can print the slip.

> The customer defaults to **Cash Customer** (walk-in). To sell to a named account, change the customer first (next section).

## Sell on account (credit sale)

1. On the till, click **Change customer** and pick the account customer. Their **account balance** and **available credit** show straight away.
2. Add items as usual and click **Charge**.
3. In the payment box, click **Full amount on account**. (The system blocks this if they're over their credit limit or on hold.)
4. **Complete sale.** The amount goes onto their account to pay later.

## Take a part-payment (deposit now, rest on account)

1. With an account customer selected, click **Charge**.
2. Enter the **cash/card** amount they're paying now.
3. Click **Put balance on account** — the rest goes onto their account.
4. **Complete sale.**

## Quote → order → invoice

1. [**Sales → Quotations → New Quote**](route:sales.quotes.index). Pick the customer, add parts, save. Print or email it.
2. When the customer accepts, open the quote and click **Convert to order** — this reserves the stock.
3. When you deliver/collect, open the order and **invoice** it. The quote, order and invoice stay linked — open any one and the "Connected to" card walks the chain.

## Credit note / return

1. Open the original invoice (Sales → [Tax Invoices](route:sales.invoices.index), or click its number anywhere).
2. Click **Raise credit note**.
3. Choose the lines and quantities coming back, tick **restock** if the goods are resaleable, and pick **refund cash** or **credit the account**.
4. Post it. Stock goes back up (if restocked) and the money is reversed.

## Lay-by and delivery notes

- [**Lay-by**](route:sales.laybys.index): reserve goods, take a deposit, record instalments, hand over when paid off.
- [**Delivery notes**](route:sales.delivery-notes.index): a dispatch note against an invoice or order.

## End of day

Cash up and reconcile the till — see **Daily cash-up** in [finance guide](guide:finance).
