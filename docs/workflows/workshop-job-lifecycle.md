# Workflow: Workshop Job Lifecycle

> From a vehicle arriving at the workshop to a paid, closed job card — including parts issue, extra-work authorisation, and warranty claim-back.

---

## Main Flow

```
① Vehicle arrives (walk-in or booking)
      │
      ▼
② Intake (Receptionist / Service Advisor)
   - customer looked up or created
   - vehicle looked up by registration; VIN decode (NHTSA) if new
   - reported fault captured in customer's words
   - pre-inspection: odometer_in, fuel level, existing damage photos/notes
   - promised_at time agreed with customer
   → Job Card opened: JC-YYYY-XXXXX  (status: open)
      │
      ▼
③ Allocation (Workshop Foreman)
   - technician assigned by skill + availability (technician board)
   → status: allocated
      │
      ▼
④ Diagnosis & work (Technician)
   - clock-on (technician_time_logs.started_at)
   - diagnosis recorded
   - labour lines added (labour codes, flat-rate hours)
   → status: in_progress
      │
      ▼
⑤ Parts needed?
   ├─ in stock → parts requisition → counter issues against job
   │            [STOCK] reservation → JOB_CARD_OUT on issue
   ├─ not in stock → auto purchase requisition (urgent, linked to job)
   │            → status: awaiting_parts until GRN
   └─ none needed → continue
      │
      ▼
⑥ Scope grows? (found more wrong than reported)
   [⚠] additional cost > threshold → status: awaiting_customer
   - customer contacted; approval name + timestamp recorded
   - declined → extra lines removed, proceed with original scope
      │
      ▼
⑦ Work complete → clock-off → status: quality_check
   - foreman inspects, road test if needed
   - fail → back to in_progress with notes
      │
      ▼
⑧ status: completed
   - odometer_out captured
   - customer notified (SMS/email): "Your vehicle is ready"
      │
      ▼
⑨ Invoice (via Sales module)
   - labour lines → invoice line_type 'labour'
   - parts lines → line_type 'part'
   - warranty lines EXCLUDED from customer invoice
   [STOCK] any un-posted part issues finalised
   [GL] standard sale postings (revenue, VAT, COGS on parts)
   → status: invoiced
      │
      ▼
⑩ Payment at counter (or on account for fleet customers)
   → status: closed
   - vehicle_service_history record written (permanent)
```

---

## Warranty Sub-flow (branches from ④/⑤)

```
Failed part still under warranty (sold by us / supplier warranty)
      │
      ▼
Labour + replacement part lines flagged is_warranty
   - customer is NOT charged for these lines
      │
      ▼
Warranty claim raised against the part's supplier
   - failure description, mileage, dates
   → status: submitted → acknowledged → approved/rejected
      │ approved
      ▼
Supplier credit note received
   [GL] DR Creditors Control   CR Warranty Recovery (contra expense)
   → claim status: credited
```

---

## Job Costing (visible on every job card)

```
Cost side                          Billed side
─────────                          ───────────
labour: actual_hrs × tech          labour: billable_hrs (flat rate)
        cost rate                          × customer labour rate
parts:  AVCO unit_cost × qty       parts:  selling price × qty
                     └──── margin = billed − cost ────┘
```

Comeback rule: if the same vehicle returns within 30 days for the same fault, the new job is flagged `comeback` against the original technician — no labour billed to the customer without manager override.

---

## Status Reference

`open → allocated → in_progress ⇄ awaiting_parts ⇄ awaiting_customer → quality_check → completed → invoiced → closed`
(`on_hold` and `cancelled` reachable from any pre-invoice state; cancel after parts issued requires supervisor + parts returned to stock as RETURN_IN.)
