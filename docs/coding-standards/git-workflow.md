# Coding Standards: Git Workflow

> Branching strategy, commit messages, and release process for SparesPro.

---

## Branching Model

```
main              ← Production-ready. Tagged releases only.
  └── develop     ← Integration branch. All features merge here first.
        ├── feature/inventory-stock-take
        ├── feature/sales-pos-barcode-scan
        ├── fix/grn-quantity-calculation
        └── release/v1.2.0
```

### Branch Types

| Prefix | Purpose | Merges into | Example |
|--------|---------|-------------|---------|
| `feature/` | New functionality | `develop` | `feature/inventory-reorder-management` |
| `fix/` | Bug fix on develop | `develop` | `fix/vat-calculation-credit-note` |
| `hotfix/` | Critical prod fix | `main` AND `develop` | `hotfix/login-500-error` |
| `release/` | Release prep | `main` AND `develop` | `release/v1.0.0` |
| `chore/` | No-code changes | `develop` | `chore/update-dependencies` |

### Branch Naming Conventions
- Use lowercase with hyphens
- Include the module name: `feature/workshop-technician-board`
- Be specific: `feature/inventory-abc-analysis` not `feature/reports`

---

## Commit Message Format

Follow **Conventional Commits** specification:

```
<type>(<scope>): <short description>

[optional body]

[optional footer]
```

### Types
| Type | When to use |
|------|------------|
| `feat` | New feature |
| `fix` | Bug fix |
| `refactor` | Code change that doesn't add feature or fix bug |
| `test` | Adding or updating tests |
| `docs` | Documentation only |
| `chore` | Build process, dependencies, config |
| `perf` | Performance improvement |
| `style` | Formatting, whitespace (no logic change) |

### Scopes
Use the module name as scope:

```
feat(inventory): add cycle count to stock take module
fix(sales): correct VAT calculation on credit notes with discounts
fix(purchasing): prevent over-receiving beyond 10% tolerance
refactor(workshop): extract job card billing into dedicated service
test(inventory): add unit tests for StockLedgerService
feat(customers): add loyalty points expiry batch job
docs(finance): document COA structure for motor spares
chore: upgrade Laravel to 12.x
```

### Good Commit Examples
```
feat(workshop): add technician efficiency report

Calculates efficiency ratio (flat rate billed / actual hours worked)
and utilisation rate per technician for configurable date ranges.

Includes PDF export via DomPDF.

feat(inventory): implement parts fitment search at POS

When a customer's vehicle registration is entered at the POS screen,
parts are filtered to confirmed and likely fitments for that vehicle's
make/model/year combination. Unconfirmed fitments are shown with a
warning badge.

fix(purchasing): GRN quantities not updating stock levels

Stock levels were not being updated when a GRN had partially received
lines. The StockLedgerService was only called when all lines were
fully received. Fixed to post ledger entries per GRN line.
```

### Bad Commit Examples
```
❌ "fix bug"
❌ "wip"
❌ "changes"
❌ "fixed the thing"
❌ "feat: stuff"
```

---

## Pull Request Process

### Before Creating a PR
1. Branch is up to date with `develop`
2. All tests pass locally: `php artisan test`
3. No PHP linting errors: `./vendor/bin/pint --test`
4. No JS errors: `npm run build`
5. Self-reviewed the diff

### PR Description Template
```markdown
## What does this PR do?
Brief description of the changes.

## Module(s) affected
- Inventory Management
- Reports

## How to test
1. Step-by-step testing instructions
2. Edge cases to verify
3. Database seeder to run (if any): `php artisan db:seed --class=XSeeder`

## Screenshots (for UI changes)
[Before] [After]

## Checklist
- [ ] Tests added/updated
- [ ] Migration included (if schema change)
- [ ] Seeder updated (if reference data changed)
- [ ] Documentation updated in `/docs`
```

### PR Review Checklist
Reviewers check:
- [ ] Business logic is correct
- [ ] No N+1 queries
- [ ] Sensitive data is not exposed in Inertia props
- [ ] Monetary values use `decimal(15,2)` — not floats
- [ ] Stock movements go through `StockLedgerService`
- [ ] Financial postings go through the posting service
- [ ] Form validation is exhaustive
- [ ] Policy authorisation present on every controller action
- [ ] Migration has a `down()` method

---

## Release Process

### Version Numbering
Follows [Semantic Versioning](https://semver.org/):

```
MAJOR.MINOR.PATCH
  │     │     └── Bug fixes, no new features
  │     └── New features, backwards-compatible
  └── Breaking changes (schema changes requiring data migration)
```

### Release Steps
```bash
# 1. Create release branch from develop
git checkout develop
git pull origin develop
git checkout -b release/v1.2.0

# 2. Update version number (if tracked)
# bump version in config/app.php or package.json

# 3. Final testing on release branch

# 4. Merge to main
git checkout main
git merge --no-ff release/v1.2.0
git tag -a v1.2.0 -m "Release v1.2.0: Workshop module, Loyalty programme"

# 5. Merge back to develop
git checkout develop
git merge --no-ff release/v1.2.0

# 6. Delete release branch
git branch -d release/v1.2.0
```

---

## Hotfix Process

For critical bugs found in production:

```bash
# 1. Branch from main (not develop)
git checkout main
git checkout -b hotfix/login-500-on-empty-password

# 2. Fix the bug
# 3. Test the fix

# 4. Merge to main
git checkout main
git merge --no-ff hotfix/login-500-on-empty-password
git tag -a v1.1.1 -m "Hotfix: login 500 error"

# 5. Also merge to develop
git checkout develop
git merge --no-ff hotfix/login-500-on-empty-password

git branch -d hotfix/login-500-on-empty-password
```

---

## .gitignore — Key Entries

Ensure these are in `.gitignore`:

```
.env
.env.*
!.env.example
storage/app/
storage/logs/
bootstrap/cache/
node_modules/
vendor/
*.sqlite
*.sqlite-journal
public/build/
public/hot
```

---

## Environment Files

| File | Purpose | Committed? |
|------|---------|-----------|
| `.env.example` | Template with all keys, no secrets | ✅ Yes |
| `.env` | Local development values | ❌ No |
| `.env.testing` | Test environment values | ❌ No |
| `.env.production` | Production values | ❌ Never — use server env vars |

### Required `.env` Keys for SparesPro
```env
APP_NAME="SparesPro ERP"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=spares_erp
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=database

MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=
MAIL_FROM_NAME="${APP_NAME}"

FILESYSTEM_DISK=local

# Optional: TECDOC API
TECDOC_API_KEY=
TECDOC_API_URL=

# Optional: SMS (for job card customer notifications)
SMS_PROVIDER=
SMS_API_KEY=
```
