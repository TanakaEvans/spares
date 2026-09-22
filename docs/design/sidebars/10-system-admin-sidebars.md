# Sub-module Sidebars: System Administration

Sub-module sidebar layouts for [Module 10: System Administration](../../modules/10-system-administration.md), following the universal template and hard rules in [README.md](README.md). Existing sub-modules (users/roles, company, branches, departments, settings, activity logs, backup, password policy) reflect their current screens; the new sub-modules (10.9–10.13) reflect their planned screens.

## 10.1 Users & Roles

> Context: entered via System Admin › Users & Roles. [Spec](../../modules/10-system-administration/10.1-users-roles.md)

```
↰ System Admin
▍ USERS & ROLES  (shield)
──────────────────────────────
WORK
  Users List .............. auth.users.index                (users)
  New User ................ auth.users.create               (user-plus)
  Roles List .............. auth.roles.index                (shield)
QUICK LINKS ⇄
  Departments & Sections .. admin.departments.index         (network)
  Branch Management ....... admin.branches.index            (map-pin)
  Password Policy ......... admin.settings.index            (key-round)
  Activity Logs ........... admin.logs.index                (scroll-text)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- Role detail / Bulk Assign (`auth.roles.*`) opens from a Roles List row — the route-grant checkbox tree grouped by `SystemModule` is a drill-in, not a standing sidebar item.
- Full sidebar is Super Admin only; Branch Managers get a view-only Users List filtered to their branch, everyone else reaches only their own profile (outside this sidebar).
- Reset-password and deactivate are row actions on the Users List; the last active Super Admin can never be deactivated or de-roled.

## 10.2 Company Setup

> Context: entered via System Admin › Company Setup. [Spec](../../modules/10-system-administration/10.2-company-setup.md)

```
↰ System Admin
▍ COMPANY SETUP  (building-2)
──────────────────────────────
WORK
  Company Settings ........ admin.company.index             (building-2)
QUICK LINKS ⇄
  Number Sequences ........ admin.sequences.index           (hash)
  Print Templates ......... admin.templates.index           (printer)
  Currencies .............. admin.currencies.index          (coins)
  Branch Management ....... admin.branches.index            (map-pin)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- Single-record, single-screen sub-module — Identity / Tax & Finance / Documents are tabs within the one form, not sidebar items.
- Super Admin and Owner edit everything; Accountant edits the tax/financial fields only. VAT-number and banking-detail changes are activity-logged with before/after values.

## 10.3 Branch Management

> Context: entered via System Admin › Branch Management. [Spec](../../modules/10-system-administration/10.3-branch-management.md)

```
↰ System Admin
▍ BRANCHES  (map-pin)
──────────────────────────────
WORK
  Branches List ........... admin.branches.index            (list)
  New Branch .............. admin.branches.create           (plus-circle)
QUICK LINKS ⇄
  Users & Roles ........... auth.users.index                (users)
  Number Sequences ........ admin.sequences.index           (hash)
  System Settings ......... admin.settings.index            (settings)
  Company Setup ........... admin.company.index             (building-2)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- Full CRUD is Super Admin / Owner; Accountant can edit only the `gl_profit_centre_id` mapping; a Branch Manager sees their own branch record read-only.
- Deactivating a branch runs a blocker pre-check (stock on hand, open documents, active users) from the list row — no separate screen.

## 10.4 Departments & Sections

> Context: entered via System Admin › Departments & Sections. [Spec](../../modules/10-system-administration/10.4-departments-sections.md)

```
↰ System Admin
▍ DEPARTMENTS & SECTIONS  (network)
──────────────────────────────
WORK
  Departments List ........ admin.departments.index         (network)
  Sections List ........... admin.sections.index            (git-fork)
QUICK LINKS ⇄
  Users & Roles ........... auth.users.index                (users)
  Branch Management ....... admin.branches.index            (map-pin)
  Activity Logs ........... admin.logs.index                (scroll-text)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- "Add Department" and per-row "Add Section" (`admin.departments.*` / `admin.sections.*`) launch from the Departments List, which shows the whole two-level hierarchy inline.
- CRUD is Super Admin / Owner; Branch Managers can assign own-branch users to existing departments/sections but not change the structure.

## 10.5 System Settings

> Context: entered via System Admin › System Settings. [Spec](../../modules/10-system-administration/10.5-system-settings.md)

```
↰ System Admin
▍ SYSTEM SETTINGS  (settings)
──────────────────────────────
WORK
  Settings Editor ......... admin.settings.index            (settings)
QUICK LINKS ⇄
  Company Setup ........... admin.company.index             (building-2)
  Branch Management ....... admin.branches.index            (map-pin)
  Email Configuration ..... admin.email.index               (mail)
  Notifications Centre .... admin.notifications.routing     (bell)
  Activity Logs ........... admin.logs.index                (scroll-text)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- One screen; the six category tabs (Inventory / Sales / Purchasing / Workshop / Finance / Notifications) are an in-page tab rail, not sidebar items.
- Category-scoped editing: Accountant edits Finance keys, Sales Manager edits Sales keys (minus credit); high-impact keys demand typed confirmation and every change lands in Activity Logs.

## 10.6 Activity Logs

> Context: entered via System Admin › Activity Logs. [Spec](../../modules/10-system-administration/10.6-activity-logs.md)

```
↰ System Admin
▍ ACTIVITY LOGS  (scroll-text)
──────────────────────────────
WORK
  Log Viewer .............. admin.logs.index                (scroll-text)
QUICK LINKS ⇄
  Users & Roles ........... auth.users.index                (users)
  System Settings ......... admin.settings.index            (settings)
  Backup Management ....... admin.backup.index              (database-backup)
  Password Policy ......... admin.settings.index            (key-round)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- Read-only by design — no create/edit routes exist; entry detail (before/after diff) is a row expand, and export is a toolbar action on the viewer (itself logged).
- Scope-filtered: Super Admin/Owner see everything, Accountant sees finance-related entries, Branch Manager sees own-branch activity; other roles never see this sidebar.

## 10.7 Backup Management

> Context: entered via System Admin › Backup Management. [Spec](../../modules/10-system-administration/10.7-backup-management.md)

```
↰ System Admin
▍ BACKUPS  (database-backup)
──────────────────────────────
WORK
  Backup Dashboard ........ admin.backup.index              (database-backup)
QUICK LINKS ⇄
  Activity Logs ........... admin.logs.index                (scroll-text)
  System Settings ......... admin.settings.index            (settings)
  Email Configuration ..... admin.email.index               (mail)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- One-screen sub-module: run history, schedule/retention config, "Backup now", download, and the multi-confirmation restore flow all live on the dashboard.
- Super Admin gets the full set; Owner can view status, run a manual backup, and download — restore and retention config stay Super Admin only, and every restore/download is activity-logged.

## 10.8 Password Policy

> Context: entered via System Admin › Password Policy. [Spec](../../modules/10-system-administration/10.8-password-policy.md)

```
↰ System Admin
▍ PASSWORD POLICY  (key-round)
──────────────────────────────
WORK
  Policy Settings ......... admin.settings.index            (key-round)
  Locked Accounts ......... auth.users.index?filter=locked  (lock)  [badge: locked]
QUICK LINKS ⇄
  Users & Roles ........... auth.users.index                (users)
  Activity Logs ........... admin.logs.index                (scroll-text)
  System Settings ......... admin.settings.index            (settings)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- Badge source: the "accounts locked now" stat from the policy screen — a live count of currently locked users.
- Policy Settings lives in the security section of the settings screen per the spec (a dedicated screen is an allowed alternative); Locked Accounts is the users list pre-filtered to locked, where admin unlock is a row action (logged).
- Sidebar is Super Admin (edit + unlock) and Owner (view + unlock) only; self-service password change lives on the user profile, outside this sidebar.

## 10.9 Number Sequences

> Context: entered via System Admin › Number Sequences. [Spec](../../modules/10-system-administration/10.9-number-sequences.md)

```
↰ System Admin
▍ NUMBER SEQUENCES  (hash)
──────────────────────────────
WORK
  Sequences List .......... admin.sequences.index           (list)
  New Sequence ............ admin.sequences.create          (plus-circle)
QUICK LINKS ⇄
  Company Setup ........... admin.company.index             (building-2)
  Branch Management ....... admin.branches.index            (map-pin)
  Print Templates ......... admin.templates.index           (printer)
  Activity Logs ........... admin.logs.index                (scroll-text)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- The list renders a live format preview per sequence (e.g. `INV-20260922-00483`); edit (`admin.sequences.*`) opens from a row with a keystroke-live preview.
- CRUD and counter adjustments are Super Admin / Owner; Accountant can view and propose counter adjustments for Super Admin approval. Counter edits beyond +1 require confirmation plus a logged reason.

## 10.10 Print Templates

> Context: entered via System Admin › Print Templates. [Spec](../../modules/10-system-administration/10.10-print-templates.md)

```
↰ System Admin
▍ PRINT TEMPLATES  (printer)
──────────────────────────────
WORK
  Templates List .......... admin.templates.index           (printer)
QUICK LINKS ⇄
  Company Setup ........... admin.company.index             (building-2)
  Number Sequences ........ admin.sequences.index           (hash)
  Email Configuration ..... admin.email.index               (mail)
  Currencies .............. admin.currencies.index          (coins)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- One row per document type on the list; sample-data preview and the options panel (paper size, footer override, toggles) open side by side from the row — no separate routes.
- Super Admin manages options; Owner edits footer/header text and previews; Accountant previews financial documents. Template *code* changes remain developer work in `resources/views/print/`.

## 10.11 Email Configuration

> Context: entered via System Admin › Email Configuration. [Spec](../../modules/10-system-administration/10.11-email-configuration.md)

```
↰ System Admin
▍ EMAIL CONFIGURATION  (mail)
──────────────────────────────
WORK
  Transport Settings ...... admin.email.index               (server-cog)
  Email Templates ......... admin.email.index?tab=templates (mail)
QUICK LINKS ⇄
  Print Templates ......... admin.templates.index           (printer)
  System Settings ......... admin.settings.index            (settings)
  Notifications Centre .... admin.notifications.routing     (bell)
  Reports: Scheduled ...... reports.scheduled.index         (calendar-clock)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- Template edit (subject/body with variable palette, live preview, "Send test to me") opens from the templates tab; the SMTP password is write-only and masked everywhere.
- Super Admin holds SMTP settings and all templates; Owner edits templates with SMTP masked; Accountant edits financial templates (invoice, statement) and Sales Manager the quote template only.

## 10.12 Currencies

> Context: entered via System Admin › Currencies. [Spec](../../modules/10-system-administration/10.12-currencies.md)

```
↰ System Admin
▍ CURRENCIES  (coins)
──────────────────────────────
WORK
  Currencies List ......... admin.currencies.index          (coins)
  Exchange Rates .......... admin.exchange-rates.index      (arrow-right-left)
QUICK LINKS ⇄
  Company Setup ........... admin.company.index             (building-2)
  Print Templates ......... admin.templates.index           (printer)
  Reports: Financial ...... reports.finance.index           (banknote)
  System Settings ......... admin.settings.index            (settings)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- "Add rate" is an action on the Exchange Rates screen; a staleness banner appears there when an active pair's newest rate exceeds the configured age — an in-page state, not a sidebar badge.
- Super Admin / Owner hold full CRUD; Accountant maintains rates and activates/deactivates currencies; Accounts Clerk and Purchasing Officer see rates read-only.
- The base currency is set at Company Setup and locks once transactions exist; it can never be deactivated here.

## 10.13 Notifications Centre

> Context: entered via System Admin › Notifications Centre. [Spec](../../modules/10-system-administration/10.13-notifications-centre.md)

```
↰ System Admin
▍ NOTIFICATIONS CENTRE  (bell)
──────────────────────────────
WORK
  My Notifications ........ notifications.index             (bell)  [badge: unread]
INSIGHTS
  Delivery Log ............ admin.notifications.log         (mail-check)
SETUP
  Routing Matrix .......... admin.notifications.routing     (route)
QUICK LINKS ⇄
  Email Configuration ..... admin.email.index               (mail)
  System Settings ......... admin.settings.index            (settings)
  Activity Logs ........... admin.logs.index                (scroll-text)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- Badge source: the user's unread notification count — the same figure the top-bar bell shows.
- Permission split: every user gets My Notifications (and their own channel preferences); Routing Matrix is Super Admin (full matrix, SMS gateway) with Branch Managers configuring own-branch routing; Delivery Log (sent/failed email & SMS with one-click retry) is Super Admin only.
- Events flagged `mandatory` (backup failed, credit hold) cannot be muted from the personal preferences.
