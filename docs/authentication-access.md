# Login, recovery, and module access

Implemented on 16 November 2026. This supersedes the earlier company-email login and invitation-email-only recovery proposals. Application login uses Worker ID; an assigned company email remains a real-mailbox requirement, not the login identifier.

## Open the application

- Sign in: `http://localhost:8080/login`
- Module hub: `/workspace`
- Groups, individual grants, and PIC authority: `/access`
- Recovery review queue: `/recovery-requests`
- Administrator history: `/activity`
- Original fictional design gallery: `/design/pages`
- Original Vue/API status demo: `/status-demo` (local/testing only)

The root URL redirects to sign-in or the protected workspace. The application uses the existing dark Blade design and real server-rendered forms with CSRF protection. The Vue files are preserved. The separate `/design` gallery remains fictional and does not perform live actions.

## Setup

```bash
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan db:seed
```

The migration and local seeders have been run successfully as part of this implementation. Do not use `migrate:fresh` against application data you want to keep. The new migration adds 10 access/recovery tables, extends `MSUser`, and changes the standard `sessions.user_id` field to a string so it can store `USNo`.

For local setup, the seeded administrator uses Worker ID **PT-1000**. Set a private password through the console before the first sign-in. Seed reruns preserve the chosen password.

To choose a private password through controlled console access:

```bash
./vendor/bin/sail artisan foundation:admin-password PT-1000
```

The command prompts privately, disables the demo flag, and invalidates existing sessions. A private password alone does not override account status or mailbox readiness.

For a clean installation without fictional seeds, set `FOUNDATION_COMPANY_CODE` to the organisation's code, migrate, and run the controlled first-administrator command:

```bash
./vendor/bin/sail artisan foundation:bootstrap-admin ADMIN-001 \
  --name="Your Administrator" \
  --email="admin@your-company.example" \
  --recovery-email="your-existing-contact@example.com" \
  --company-name="Your Company" \
  --mailbox-verified
```

The addresses above are placeholders. An authorized operator must first verify ownership/access to both actual addresses outside the application. The command records an existing mailbox as manually verified; it does not provision a mailbox. It prompts for a private password of at least 12 characters (bounded to the bcrypt-safe 72-byte limit) and refuses to create another initial administrator when one already exists. No production default password is installed. Real provider integration remains a separate milestone.

## Login and sessions

- Employees sign in with Worker ID and application password. The administrator can sign in with their Worker ID.
- The initial application is scoped to the company identified by `FOUNDATION_COMPANY_CODE` (default `DEMO` locally). No public company selection or signup was added.
- Laravel's web auth provider now uses `App\Models\Foundation\User` / `MSUser`. Existing legacy `users` records are preserved, not silently imported. Old numeric-user sessions cannot authenticate as a prefixed foundation user.
- Access requires `active` account status, recorded approval, completed activation, a password, and a ready mailbox. Mock mailboxes count as ready only in local/testing environments.
- Login failures use one generic message. Attempts are limited by IP and IP/identifier combination; passwords are never flashed back to forms.
- A login regenerates the session identifier. Logout invalidates the session and CSRF token.
- Every protected request checks the current database account and its authentication version. Password resets/changes and verified recovery-email changes increment this version, invalidating existing sessions on their next request. Deactivated accounts and failed mailbox readiness are denied immediately on the next request.
- `/api/v1/me` and Sanctum's stateful `/api/user` expose only the signed-in user's basic identity. Existing API status remains public. No external API-token issuing feature was added.

## Password recovery

### Normal recovery

1. Submit Worker ID and current recovery email. Initially this is the invitation/contact email.
2. The public response does not reveal whether the account or address matched.
3. An eligible matching account gets a reset link at its current recovery address.
4. The employee chooses a private password. A reset changes the application password only.

Reset tokens are random, stored only as SHA-256 hashes, expire after 30 minutes, and can be consumed once. A newly issued link revokes earlier links. Links are built from the configured trusted `APP_URL`, not an incoming Host header. Account/authentication version checks also invalidate stale links.

### Lost invitation/recovery email

1. Submit Worker ID and optionally propose a replacement recovery address.
2. The current active team PIC receives a review notification. PIC requests, missing/inactive PIC cases, and higher-privilege accounts go to an eligible administrator. A reviewer cannot approve their own request.
3. The reviewer confirms identity through an established company contact or in person, confirms any proposed replacement address, records the method and restricted notes, then approves/rejects.
4. Approval sends a reset link to the employee's existing **company mailbox**, never directly to an unverified replacement personal address.
5. On that reset page, the employee sees any proposed replacement email and explicitly chooses whether to send verification there. Declining leaves recovery email unchanged.
6. After password reset, a verification link is sent to the confirmed replacement address. Only its successful single-use verification makes that address current.
7. The previous address stops receiving reset links. Original `USContactEmail` and historical `TRInvitation.IVEmail` values are preserved.

Pending assistance requests expire after two days. A duplicate current-version request does not create another review item. A password/recovery change makes older requests stale. Reviewers may resend approved requests; previous reset links are revoked. Mail delivery failure revokes the undelivered reset token and leaves the approved request available for retry.

If both the personal and company mailboxes are inaccessible, an administrator must verify identity and restore access to the existing company mailbox through the provider's authorized recovery process before issuing an assisted reset. The application does not change mailbox-provider passwords. If the last administrator cannot use email recovery, controlled console password setup is the fallback.

Local mail is SMTP through Mailpit: `http://localhost:8025`. No actual company mailboxes are created by these flows. MailPit captures development mail. Recovery notifications refuse log/array mail transports outside tests, including unsafe failover configurations, so reset links cannot be written to the application log as email content.

## Read and Read & Edit

| Level | Meaning |
|---|---|
| Read | Open the assigned submodule and view authorized records |
| Read & Edit | Read plus creating/updating where that submodule implements those actions |

Approval, deletion, sensitive export, role elevation, and access administration are separate responsibilities. Read & Edit does not grant them. Company name editing is a live example of a backend edit check; Finance is an access-controlled workspace only, with financial workflows deliberately deferred.

Middleware enforces authentication, active-account requirements, and broad module checks. Laravel policies enforce module view/edit, record ownership/team scope, and recovery-review boundaries. Queries filter records to authorized scope. Hiding a menu is only presentation.

### Administrator and PIC responsibilities

- Administrators define immutable permission bundles and assign them or individual grants to one or more workers in their company. To change a bundle's contents, create a replacement bundle; disable the obsolete one when appropriate.
- Administrators authorize a current active PIC for a delegable master module and a maximum level.
- That PIC can use the module within that ceiling and assign its permitted submodules to ordinary members of their own team. They cannot grant to themselves, other PICs/administrators, or another team; exceed their ceiling; create bundles; or grant administrator status.
- Every item in a group must fit the PIC's authority. A batch assignment is atomic: an invalid worker or item rejects the entire batch.
- PICs keep their baseline own-team user/recovery and access-assignment pages. Business modules such as Finance require explicit delegation/grants. These administrative responsibilities cannot be assigned to an ordinary member merely by selecting Read & Edit.
- Each grant records its grantor, optional group membership, and originating delegation. Independent valid sources are combined; there is no implicit deny/override system.
- Revoking PIC module authority disables all grants derived from that delegation, including group-derived grants. Independent administrator or other valid grants remain.
- Lowering a PIC ceiling caps existing grants. Raising it again does not silently restore previous edit rights. Re-enabling revoked authority does not reactivate old grants; assign them explicitly again.
- Removing a group assignment disables its derived grants. Disabling a group disables its memberships and grants. Independent grants remain.
- Permissions are evaluated against current source/account/team status on each request; no long-lived permission cache delays revocation.

There is no approval/payment/delete implementation hidden behind the Finance placeholder. Future business controllers must use the same middleware and policies and introduce separate workflow permissions when those actions are built.

## Table and model additions

Every added table follows `ID`, string `No` primary key, `CB`, `CD`, `MB`, `MD`, and the same prefixed foreign-key convention as the foundation schema.

| Table | Prefix | Model | Purpose |
|---|---|---|---|
| `MSModule` | MO | Module | Developer-registered master modules and submodules |
| `MSAccessGroup` | AG | AccessGroup | Company permission bundles |
| `TRGroupItem` | GI | GroupItem | Bundle module/level entries |
| `TRDelegation` | DL | Delegation | PIC master-module authority and ceiling |
| `TRGroupMembership` | GM | GroupMembership | Worker/group/grantor assignment |
| `TRAccessGrant` | AX | AccessGrant | Effective-source records for individual/group access |
| `TRRecoveryRequest` | RQ | RecoveryRequest | Identity review and proposed replacement email |
| `TRPasswordReset` | PS | PasswordReset | Hashed, expiring reset tokens |
| `TREmailVerification` | EV | EmailVerification | Hashed replacement-address verification tokens |
| `TREmailHistory` | EH | EmailHistory | Encrypted old/new recovery-email history and approver |

`MSUser` adds `USLoginName`, `USRecoveryEmail`, `USApprovedAt`, `USAuthVersion`, `USRecoveryVersion`, and `USLocalDemo`. All models explicitly define their relationships. Baseline schema/model explanations remain in [foundation-database.md](foundation-database.md).

Successful changes and safe audit entries use the existing `FoundationWriter` transaction. Failed writes use `TRFoundationError` after rollback. Tokens, passwords, review notes, and personal address values are omitted from general audit snapshots. Replacement addresses, review notes, and old/new email history are encrypted at rest using Laravel's application key. Preserve that key with backups. The administrator-only history page decrypts address history; PICs cannot open it. Mail transport errors are distinct from database-write errors and use safe transport diagnostics without message bodies or tokens.

## Edit module names centrally

Live module names and hierarchy are declared in `config/modules.php`. Apply registry changes without fictional seed data:

```bash
./vendor/bin/sail artisan foundation:sync-modules
```

This updates catalog metadata, disables removed registrations, and does not expand employee grants. New modules still require developer-written pages/actions. Permission-group names and assignments are maintained through `/access`. `config/design.php` controls the separate fictional design gallery.

## Verification and a small walkthrough

```bash
./vendor/bin/sail artisan test
```

MySQL integration tests refuse any database other than `testing`. They cover login/logout, readiness/deactivation, demo restrictions, stale sessions, normal and assisted recovery, token reuse/expiry, replacement-address consent/verification, self/cross-team review denial, group and individual grants, ceiling reduction/revocation, independent-source preservation, scope forgery, read-only write denial, Sanctum identity responses, and safe mail transport handling. Existing atomic audit/counter and migration rollback checks remain in the suite.

Verification completed: the full suite and subsequent focused checks passed, covering 32 distinct tests across those runs. The live HTTP walkthrough also verified HTTP 419 for missing CSRF, persisted database sessions, protected workspace/access/history pages, both authenticated identity endpoints, HTTP 401 after logout, and actual SMTP reset delivery into local Mailpit. All 27 foundation tables were checked for their prefixed `No` primary key and required metadata columns. Browser screenshots and visual automation were not available; the reused layouts still need a human desktop/mobile visual review.

Local walkthrough:

1. Sign in as the local demo administrator and open Settings → Groups & module access.
2. Give Farid Hassan Finance / Read authority.
3. Use Forgot password for Worker ID `PT-1011` and `farid@personal.example`; read the reset email in Mailpit and choose a private password.
4. Sign in as Farid. Assign Finance overview / Read to Lina Zain. Try granting Edit and observe backend denial.
5. Recover Lina's password similarly using `PT-1043` and `lina@personal.example`, then verify the Finance workspace is available.
6. Revoke Farid's authority as administrator. Lina's derived access must stop on the next request.

The original invitation creation, application-review/activation workflows, procurement workflows, and live provider provisioning remain separate tickets. The current work uses already seeded/approved accounts and implements application login, recovery, and access management.

## Administrator user switching — 17 November 2026

The top-right **Switch user** button opens a searchable user-selection dialog. Only an eligible administrator can initiate switching. The list and switch endpoint enforce same-company scope and full account readiness; ordinary users cannot access either endpoint directly. Search returns at most 50 matching active accounts and exposes only display name, Worker ID, team name, and account reference.

Switching uses the selected account's actual permissions, not administrator permissions. A persistent banner identifies the account and provides **Return to administrator**. The original administrator can select another user directly without a nested impersonation chain. Changes affect real records. Password changes through My account are blocked while switched.

The original administrator reference and authentication version live only in the server session. Both identities are revalidated on protected requests. Losing the administrator's role/readiness or changing their authentication version invalidates the session. If the selected account becomes unavailable or its authentication version changes, the application restores the still-valid administrator and rejects the interrupted request. Switching and returning rotate the session identifier and CSRF token. Logout ends the entire session.

Start, return, automatic account-change return, and explicit logout events are stored in foundation history with the original administrator and selected user. Successful business-write audit snapshots during impersonation also contain `_impersonator`, while retaining the selected user as the acting account. No password or token is stored in switch history. No schema change is required.

The dialog uses native modal focus handling, Escape dismissal, close/cancel controls, and distinct loading/empty/error states with retry. Manual browser visual and keyboard review is still recommended.

## Theme and language — 17 November 2026

The live header and sign-in screens include light/dark and ENG/BM toggles. Defaults remain English and dark. The choices are saved for one year in encrypted, HttpOnly browser cookies and survive logout and account switching. They are browser preferences rather than changes to an employee's profile. A POST with CSRF protection updates the allowlisted choices and returns to the current local page.

`DisplayPreferences` applies the locale and theme before rendering. Shared navigation, module labels, live foundation forms/help, validation, pagination, and modal feedback use Laravel translations in `lang/ms.json` and `lang/ms/`. English phrases serve as fallback translation keys. User-entered names, team names, document contents, record identifiers, stored statuses, permission keys, and audit payloads are not translated or rewritten. Account emails keep their existing language; this change concerns the website display.

`public/foundation/themes.css` provides the live application's light palette, including forms, tables, status badges, menus, and the switch-user dialog. The theme is rendered on the HTML element before styles load, avoiding a JavaScript-dependent theme flash. The fictional `/design` gallery and separate Vue status demo retain their existing presentation.
