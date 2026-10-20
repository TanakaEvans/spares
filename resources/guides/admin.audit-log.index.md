# About this page

The **audit trail** is the system's memory of who changed what. Every supervisor override at the till, every permission grant, every setting change and every edit to customers, suppliers, parts, prices, users, roles, branches and the company record lands here with the user, the time and the before/after values. Rows are never edited or deleted.

## Reading it
- **Action** names what happened (`sales.discount_override`, `role.permissions_changed`, `customer.updated`, `setting.changed`…). Red = overrides/deletions/locks, amber = permissions and settings.
- **Record** is the thing that changed (part number, customer, role, setting key).
- **Details** opens the before → after diff for the changed fields only.

## Typical questions it answers
- *Who approved that 40% discount on Friday?* Filter action `sales.discount_override`.
- *When did this customer's credit limit change, and from what?* Search the customer's name, open Details.
- *Who gave the Cashier role access to journals?* Filter action `role.permissions_changed`.

## Related
- [Roles & permissions](route:auth.roles.index) · [Configuration Centre](route:admin.settings.index) · [System Health](route:admin.health)
