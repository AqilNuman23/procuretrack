# Foundation implementation — 16 November 2026

This implements the six follow-ups in foundation-plan.md using the existing live Blade application. The Vue/Tailwind architecture discussion remains deferred at the owner's request. ONLYOFFICE is planned separately and is not installed by this change.

## Decisions

- Administrators create or verify real mailboxes through their provider, then record verified ownership and send/receive readiness in ProcureTrack. This is manual verification, not automatic provisioning.
- Choose a PIC from existing fully onboarded active members of the same active team. Invite and activate a new person as a member before assigning PIC responsibility.
- App deactivation does not disable provider mailbox access. Complete provider offboarding separately.

## Functional pages

| Page | Access / behavior |
|---|---|
| `/teams` | Administrator: search, create, open/edit teams, assign or replace PIC. |
| `/modules/people.users` | Existing authorized scope, with name/Worker ID search and account-status filter. |
| `/users/{USNo}` | Administrator can change name, transfer team, deactivate/reactivate. Existing user-view policy remains enforced. |
| `/invitations` | Administrator sees their company's invitations; active PIC sees only their team's. Create, filter, resend/revoke; administrator reviews accepted invitations. |
| `/mailboxes` | Administrator records initial mailbox verification for approved users awaiting activation, or retries activation delivery. |
| `/invitation/{token}` | Public token page; POST consumes a valid invitation and enters review. GET does not accept it. |
| `/activation/{token}` | Public token page; POST confirms Worker ID and sets a private password. GET does not consume the token. |
| `/departments` | Module-level read/edit authorization and company-scoped search, filtering, sorting, pagination, create/details/edit. |
| `/components` | Administrator showcase of shared functional form, select, table, pagination, and feedback conventions. |

Module links appear through the existing registry. Apply new registrations without resetting data:

```bash
./vendor/bin/sail artisan foundation:sync-modules
```

No new migration or fictional seed reset is required for these workflows.

## Rules

Teams/account changes use `PeopleAdministration` and the existing `FoundationWriter`. Company-row locking serializes administrator changes. The last eligible active administrator cannot be deactivated. Replace the current PIC before transferring or deactivating them. PIC replacement revokes previous delegations and their derived grants; reassigning that PIC later does not revive them. Transfers/deactivation revoke the affected member's derived grants and increment authentication version. Independent administrator grants remain separate. Reactivation requires existing approval, activation, a password, and mailbox readiness.

`OnboardingService` keeps the invitation, account, and mailbox lifecycles separate. Invitation links expire after two days; activation links after 24 hours. Tokens are random, hashed at rest, and single use. Resend invalidates previous links. Duplicate current invitations or existing account identities are rejected within a company. Expired invitations are shown as expired even if their persisted status has not been changed by a scheduler. Correcting invitation details means revoking an unaccepted invitation and creating a new one.

Approval creates a pending account and pending manual mailbox assignment in one audited transaction. Rejecting requires a reason and creates no user. PICs cannot review applications. Verification of the mailbox sends activation instructions and the assigned company address to the original invitation email. Activation confirms Worker ID and private password; it never sets a provider password. Mail transport failures preserve the business record, invalidate undelivered tokens, and provide explicit resend paths. Messages use the trusted configured APP_URL. Public token pages use no-referrer and private/no-store headers. Activation attempts are rate limited independently from other onboarding actions.

Departments demonstrates the maintained reference-module pattern: allowlisted filters and sorting, full-dataset company scope before pagination, backend authorization, validation, audited transactions, and inactive records instead of deletion. Departments remain independent from Teams. The module generator remains deferred until these conventions have been reviewed.

Shared Blade components live in `resources/views/components/foundation/`. Server-rendered forms use CSRF, field errors, a page error summary, confirmation for sensitive actions, a saving state and repeated-submit protection. Database constraints and transaction rules provide the durable enforcement. Loading/error/empty examples are distinct in the live component showcase; a failed server request is not represented as an empty dataset.

## CI and verification

`.github/workflows/checks.yml` runs on pushes and pull requests. It installs locked Composer/npm dependencies, uses PHP 8.4 and Node 22, runs backend tests against a MySQL 8.4 database named `testing`, and builds frontend assets. Its database credentials are disposable service credentials, not application secrets. No CI run has been triggered on GitHub by this local change. Requiring the check for merging depends on repository branch protection.

Local checks and automated tests cover account/PIC rules, cross-team and cross-company denial, invitation duplicates/resend/expiry/rejection, activation/password setup, read-only Departments access, audit entries, and mail delivery failure. Verification on 16 November 2026:

- Full suite: 37 passed, 410 assertions.
- Subsequent focused run: 4 passed, 38 assertions, including three additional cases and the final PIC-assignment code. Across both runs, 40 distinct tests passed.
- Frontend production build, JavaScript syntax check, Blade compilation, and Pint check on all 13 changed/new PHP files passed.
- New module registrations synchronized locally without expanding employee grants or resetting application data.

Manual desktop/mobile visual and keyboard review remains required. In particular, review the drawer, long tables, inline review forms, validation errors, and confirmation flow. Verify actual mailboxes through the selected provider; automated tests and Mailpit do not prove a real mailbox exists.

## Laravel learning map

- `routes/web.php`: route registration and middleware.
- `PeopleController`, `OnboardingController`: request/response handling and injected services.
- `PeopleAdministration`, `OnboardingService`: business decisions, locked records, transactions, and after-transaction email delivery.
- `ModuleAccess`, policies: authentication versus module permission and record scope.
- `DepartmentController`: one small complete module using these conventions.
- `FoundationWriter`: why related writes and successful audit history commit or roll back together.
- `AccountAccessTest`: failure cases that demonstrate the rules, rather than relying on hidden buttons.
