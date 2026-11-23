# CR-01 — Activation keys and the Master Key panel

| | |
|---|---|
| **Status** | **Proposed — waiting for the client's written approval.** Nothing in this document is built until then. |
| **Requested by** | Abdelrahman, 23 Nov 2026 (from `master_key.md`, the client's brief for billing) |
| **Affects** | Project 1 scope, effort and timeline (contract items 3, 4, 7) |
| **Estimate** | **≈ 48 h** (backend 30 h, Master Key panel 14 h, app screen 4 h) |
| **Timeline / price impact** | Does not fit the 13 h buffer of the agreed 9 weeks (PROJECT1_PLAN.md §11.1). Needs **+1.5 weeks** or an agreed extra payment, or another feature moved to Project 2. The client chooses. |

## 1. What it is

A licensing system that controls which dentists may use DentLegal's paid features, managed centrally by the DentLegal owner:

- **Master Admin (the DentLegal owner, the client)** issues an activation key per dentist, edits the licence behind it (type, dates, device limit, features), suspends, reactivates, revokes, replaces keys and transfers licences, with a full history and audit log.
- **Beneficiary (a dentist)** enters the key once in the app, sees the licence status, type, expiry and devices, and can ask for renewal or help. The dentist cannot change anything about the licence.
- The **server is the only source of truth**. The app re-checks the licence on every start and on a schedule; changes made by the admin show up at the next check, without reinstalling.

How it fits the existing design:
- It sits **on top of** the current sign-in (invitation + OTP). The key does not replace login; it unlocks features for the signed-in dentist.
- It becomes the **billing gate** for DentLegal's customers. Payments themselves (card, invoice) are outside this CR; the admin records the licence after the client is paid.
- The panel is a new `/admin/licensing` area in the existing Vue dashboard (lawyer and admin already use it, with MFA), not a separate app.

## 2. Assumptions (to confirm with the client)

| # | Assumption |
|---|---|
| A1 | One licence per dentist. Clinic licences (one key, several dentists) are not in this CR. |
| A2 | Licence types: `trial`, `monthly`, `yearly`, `custom`. Names and prices are business settings, not code. |
| A3 | Features that a licence can switch on: `consultant`, `case_analysis`, `lawyer_review`. A dentist without an active licence can sign in and see past results but cannot start new consultations, analyses or reviews. |
| A4 | Default device limit 2 (phone + tablet). A "device" is an app installation, identified by a random installation ID created on first launch, stored in secure storage. No hardware fingerprinting. |
| A5 | Replacing a key **revokes the old one immediately**; there is no grace period. |
| A6 | A key is shown in full **once**, at creation or replacement. Afterwards only the last 4 characters are visible; a lost key is replaced, never revealed. |
| A7 | The client's admin panel users are the only Master Admins (role `admin` + a new permission `licensing.manage`). |
| A8 | "Instant update" is done with a silent push (FCM) that makes the app re-check; without it the app picks changes up within 15 minutes or on the next start. |

## 3. Data model

Licence identity (`licenses.id`) is fixed; the activation key is a separate, replaceable row, so replacing a key never loses the licence's history.

| Table | Columns | Constraints and indexes |
|---|---|---|
| `licenses` | id (uuid v7), user_id → users, license_type, starts_at, expires_at, max_devices, features (jsonb array), status `pending/active/expired/suspended/revoked`, notes, created_at, updated_at | One non-revoked licence per user: partial unique (user_id) where status ≠ revoked. Check `expires_at > starts_at`, `max_devices between 1 and 10`. Index (status, expires_at). |
| `activation_keys` | id (uuid v7), license_id → licenses, key_hash (HMAC-SHA-256 with a server secret), key_last4, key_version, status `pending/active/replaced/revoked`, issued_by, issued_at, activated_at, revoked_at, replaced_by → activation_keys | Unique (key_hash). **At most one usable key per licence:** partial unique (license_id) where status in (pending, active). |
| `activated_devices` | id, license_id, user_id, installation_id, platform, device_name, app_version, first_activated_at, last_seen_at, status `active/removed` | Unique (license_id, installation_id). Count of `active` rows ≤ `max_devices`, enforced in the activation transaction. |
| `license_history` | id (bigserial), license_id, admin_id, action, old_value (jsonb), new_value (jsonb), reason, created_at | Append-only (same trigger as `audit_logs`). Never holds key values, only `key_last4` and key ids. |
| Audit | the existing `audit_logs` table | Actions `license.created/updated/extended/suspended/reactivated/revoked/transferred`, `key.issued/replaced/revoked`, `device.removed`, `activation.succeeded/failed` |

`users` already exists (dentist, lawyer, admin), so the brief's `Users` table maps onto it.

**Licence statuses and transitions**

| From | To | Who / when |
|---|---|---|
| pending | active | First successful activation |
| active | suspended / revoked | Admin |
| suspended | active / revoked | Admin |
| active / suspended | expired | Nightly job at `expires_at` (or at the next check) |
| expired | active | Admin extends the expiry |
| any key | replaced | Admin replaces the key; licence status is unchanged |

Revoked is final. Every transition runs in a database transaction with a row lock on the licence (`SELECT … FOR UPDATE`), so concurrent admin actions or activations can't produce two usable keys or exceed the device limit.

## 4. Keys

- Generated with `random_bytes` (CSPRNG): 20 characters from Crockford Base32 (no I, L, O, U), shown as `DL-XXXX-XXXX-XXXX-XXXX-XXXX` (≈ 100 bits of entropy). Never sequential.
- Stored only as `HMAC-SHA-256(key, LICENSE_KEY_PEPPER)`. The pepper lives in the server's secrets, not in the database or the app. Lookups are by hash, compared in constant time.
- Uniqueness is guaranteed by the unique index on `key_hash`; a collision retries generation.
- Shown in full once in the admin panel (copy button + "send to dentist by SMS" through the existing SMS gateway). Logs and history never contain it.

## 5. API

**Dentist app** (`auth:sanctum`, role dentist)

| Method | Path | Purpose |
|---|---|---|
| POST | `/api/v1/license/activate` | `{ key, installation_id, platform, device_name, app_version }` → licence summary. Errors: `key_invalid`, `key_revoked`, `key_replaced`, `license_expired`, `license_suspended`, `device_limit_reached`. Rate limit 5 / 15 min per user and 20 / hour per IP. |
| GET | `/api/v1/license` | Current licence: status, type, expiry, features, devices used / allowed. Called on start and every 15 min. Also sends `X-Installation-Id` so revoked devices are refused. |
| POST | `/api/v1/license/renewal-request` | Sends a renewal or help request to the admin (feedback inbox) |

Feature gates: middleware `license.feature:consultant` (etc.) on the consultant, analysis and submit-review routes, returning 402 `license_required` with the reason.

**Master Key panel** (`/api/v1/admin/licensing/*`, role admin, permission `licensing.manage`, MFA confirmed)

| Method | Path | Purpose |
|---|---|---|
| GET | `/overview` | Totals: keys by status, licences by status, beneficiaries |
| GET | `/licenses` | Table with search (name, phone, email, user id, licence id) and filters (status, type, expiry range); cursor pagination |
| POST | `/licenses` | Create licence for a dentist + issue the first key → key shown once |
| GET | `/licenses/{id}` | Licence, current key (last 4), devices, history |
| PATCH | `/licenses/{id}` | Edit type, dates, max devices, features (keeps the same key); `reason` required |
| POST | `/licenses/{id}/extend` | New expiry or "+N days"; `reason` required |
| POST | `/licenses/{id}/suspend`, `/reactivate`, `/revoke` | Status changes; confirmation + `reason` required |
| POST | `/licenses/{id}/replace-key` | Revoke the current key, issue a new one → shown once; `reason` required |
| POST | `/licenses/{id}/transfer` | Move the licence to another dentist (devices are cleared, a new key is issued) |
| DELETE | `/licenses/{id}/devices/{deviceId}` | Remove a device so another can be activated |
| POST | `/licenses/{id}/send-key` | Send the just-issued key by SMS (only within 10 minutes of issuing) |
| GET | `/history`, `/audit` | Licence history and audit log with filters |

Every action is a single Laravel action class (`IssueLicense`, `ReplaceActivationKey`, …) used by the controllers and the tests, writing `license_history` and `audit_logs` in the same transaction.

## 6. Screens

**Master Key panel** (Vue dashboard, PrimeVue, same look as the approved prototype):
1. Overview cards: total keys, active, expired, suspended, revoked, beneficiaries.
2. Licences table with the brief's columns (user id, name, phone/email, licence id, key ••••last4, type, issued, activated, expires, devices used / allowed, status, last change, actions), search and filters.
3. Licence detail drawer: edit form, extend, suspend / reactivate, revoke, replace key, transfer, devices list with "remove", history timeline.
4. Issue / replace dialog: reason, confirmation, then the one-time key view with copy and "send by SMS".
5. Audit log page.

**Dentist app**: «تفعيل الاشتراك» screen (key field, «تفعيل» button, result) and a «اشتراكي» card in «حسابي» showing status, type, expiry, devices used / allowed, «طلب التجديد». Clear Arabic messages for expired, suspended and revoked licences.

## 7. Tests (acceptance criteria from the brief)

Pest feature tests against PostgreSQL, one per brief criterion:
1. Admin issues a unique key bound to a dentist.
2. A dentist activates with a valid key.
3. Invalid, revoked and expired keys are refused.
4. Extending a licence keeps the same key.
5. Changing features and device limit takes effect at the next check.
6. Replacing a key revokes the old one; 7. the old key no longer activates.
8. Changes appear at the next `GET /license`.
9. A dentist gets 403 on every Master Key endpoint.
10. Every admin action writes `audit_logs` and `license_history`.
11. Concurrent activations and replacements (parallel requests) never produce two usable keys or exceed the device limit.
12. Panel pages render at phone and desktop widths (Playwright screenshot check).

## 8. Estimate

| Part | h |
|---|---|
| Schema, models, state machine, key generation and hashing | 6 |
| Admin actions (issue, edit, extend, suspend, reactivate, revoke, replace, transfer, devices) with history + audit | 9 |
| Activation and check API, feature-gate middleware, rate limits, expiry job, silent push | 7 |
| Tests (12 criteria incl. concurrency) | 8 |
| Master Key panel (5 screens) | 14 |
| App activation screen + «اشتراكي» card + periodic check | 4 |
| **Total** | **48** |

## 9. Note for the client (Arabic, ready to send)

> **طلب تغيير رقم 1: نظام مفاتيح التفعيل ولوحة Master Key**
>
> بناءً على طلبكم، نقترح إضافة نظام لإدارة اشتراكات الأطباء عبر مفاتيح تفعيل، مع لوحة تحكم رئيسية لكم تتيح إصدار المفاتيح وتعديل التراخيص وتمديدها وإيقافها وإلغائها واستبدالها، وإدارة الأجهزة، مع سجل كامل لكل العمليات. يُدخل الطبيب المفتاح مرة واحدة في التطبيق، وتنعكس أي تعديلات منكم عليه تلقائيًا دون إعادة تثبيت.
>
> هذا النظام غير مشمول في نطاق المشروع الأول المتفق عليه، ويتطلب نحو **48 ساعة عمل** إضافية، ولا يتسع له الهامش المتبقي في جدول الأسابيع التسعة. نقترح أحد الخيارات التالية:
> 1. تمديد المدة **أسبوعًا ونصفًا** تقريبًا؛
> 2. أو تكلفة إضافية يُتفق عليها؛
> 3. أو تأجيل ميزة أخرى إلى المشروع الثاني لإتاحة الوقت.
>
> نرجو تأكيد الخيار المناسب كتابيًا، وسنبدأ التنفيذ فور الموافقة. التفاصيل التقنية الكاملة متاحة عند الطلب.
