# Dashboard smoke test (S9.4)

`smoke.spec.ts` covers the lawyer's critical path and every admin page:

- sign in with two-factor, claim a case, edit the draft, save a version, approve;
- open every admin page with no console errors;
- send unauthenticated visitors to the login page.

The plan names Cypress for this test. Playwright is used instead for the same flow, because its browser is already part
of the toolchain and it runs the same way in CI and against staging.

## In CI (every change)

The `Web` workflow generates the dashboard with the fake API and runs the test against that build:

```bash
pnpm build
```

```bash
pnpm e2e
```

## Against staging or a local API

1. Seed the accounts and a case waiting for review. The seeder refuses to run in production. Run it again before each
   test run, because the test claims and approves the case.

   ```bash
   SMOKE_LAWYER_EMAIL=… SMOKE_LAWYER_PASSWORD=… SMOKE_LAWYER_TOTP_SECRET=… \
   SMOKE_ADMIN_EMAIL=… SMOKE_ADMIN_PASSWORD=… SMOKE_ADMIN_TOTP_SECRET=… \
   php artisan db:seed --class=SmokeTestSeeder
   ```

   The TOTP secrets are base32 strings (for example 32 characters from `A–Z2–7`). Keep the values in the password
   manager, not in the repository.

2. Run the test with the same values:

   ```bash
   E2E_BASE_URL=https://portal.<domain> \
   E2E_LAWYER_EMAIL=… E2E_LAWYER_PASSWORD=… E2E_LAWYER_TOTP_SECRET=… \
   E2E_ADMIN_EMAIL=… E2E_ADMIN_PASSWORD=… E2E_ADMIN_TOTP_SECRET=… \
   pnpm e2e
   ```

The two sign-ins use separate accounts, so neither waits for a new TOTP window. A failed run keeps a screenshot and a
trace in `test-results/`.

Checked locally on 25 Nov 2026 against Laravel (`php artisan serve`) and a non-mock build: all three tests passed, and
the database showed the claim, the saved version 2, the locked approved version and the audit entries.
