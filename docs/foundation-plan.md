# ProcureTrack foundation plan

Status: planning. This document describes intended behaviour, not completed features.

Before building procurement features, I want to get the basic application structure right: the design, accounts, team management, access rules, and a consistent way to build new modules.

## 1. Design direction

- Main layout reference: **Tabler**.
- Forms, tables, buttons, and other component references: **Flowbite**.
- Frontend implementation: **Vue 3 and Tailwind CSS**.

Tabler guides the sidebar, header, navigation, and content layout. Flowbite guides the components inside those pages. The application will use one consistent set of colours, typography, spacing, and interaction rules across both references.

References:
- [Tabler preview](https://preview.tabler.io/)
- [Flowbite components](https://flowbite.com/docs/getting-started/introduction/)

These are design references. We have not decided to install either component library. Any reused code or assets must follow the relevant license.

## 2. What comes first

The first stage covers:

- Shared application layout and components.
- Login, activation, password setup, and password reset.
- Teams, team PICs, and members.
- Invitations and administrator review.
- Actual company mailbox provisioning.
- Roles, permissions, and account deactivation.
- Account and invitation history.
- A small reference module, followed by developer module scaffolding.

Purchase requests, quotations, deliveries, claims, and other procurement workflows come after the foundation works.

## 3. How people join

There is no public employee signup in the initial version.

An administrator registers the team's PIC. The PIC then invites their own team members. Accepting an invitation does not immediately grant application access: an administrator reviews the person first.

Initial flow:

1. The administrator creates a team and registers its PIC.
2. The PIC enters a member's name, invitation email address, and worker ID.
3. The PIC sends an invitation to that email address.
4. The person opens the invitation link and accepts.
5. The person enters the administrator review queue.
6. The administrator checks the details and approves or rejects the application.
7. After approval, the system requests creation of an actual company mailbox.
8. Once the mailbox is confirmed ready, the system sends activation instructions and the assigned company email address to the invitation email.
9. The person opens the activation link and confirms their worker ID against the approved record.
10. The person sets their own application password before accessing protected features.
11. The person signs in using their Worker ID and application password.

The invitation email is the person's original contact address. The company email is their assigned work mailbox. Worker ID is the application login identifier. Current recovery email initially matches the invitation email; verified replacements preserve the original and restricted old/new email history.

## 4. Company mailbox requirement

The assigned company email must be a real mailbox that can send and receive email. It is not just an email-shaped username stored in the application.

For example, after approval, someone might receive an address such as `name@company.example`. This is an illustrative address, not a configured domain.

Mailbox creation requires a selected email provider, a domain controlled by the organisation, and an authorized provisioning process. Creating a Laravel user record alone does not create a mailbox.

### Intended behaviour

- Create or assign the mailbox only after administrator approval.
- Confirm whether an approved person already has a company mailbox before creating another one.
- Prevent duplicate mailbox creation when a provisioning job is retried.
- Keep mailbox provisioning status separate from application account status.
- Do not report that the account is ready while mailbox provisioning is still pending or has failed.
- Show provisioning failures to an authorized administrator, with a way to retry or resolve them.
- Send initial activation instructions to the existing invitation email, since the person may not yet be able to open the new mailbox.
- Provide instructions for accessing the actual mailbox through the selected provider.
- Never email plaintext application passwords or store mailbox passwords in application logs.

### Decisions still needed

- Which email provider and company domain will we use?
- What are the provider's licensing, cost, and provisioning permissions?
- Will provisioning be automatic through the provider's API or initially completed manually by an administrator?
- What is the mailbox naming convention, including duplicate names?
- How will a person receive their first mailbox access securely?
- Will application authentication remain separate from mailbox authentication, or eventually use the provider's single sign-on?
- How will existing company mailboxes be verified and assigned?
- What should happen to mailbox access when someone leaves the team or company?

For the portfolio project, a mock provisioning adapter can demonstrate the workflow before a real provider is connected. Mock mode must be clearly labelled and must not claim to create real mailboxes. Live mailbox provisioning remains a separate completion milestone.

## 5. Password setup and verification

The product requirement is that a new member chooses a private password before entering the application.

The proposed approach is a single-use activation link followed by password setup, rather than sending a shared or temporary application password. This fulfils the first-access password requirement without an extra forced password-change step.

- Invitation and activation links expire and can only be used once for their intended action.
- Accepting an invitation confirms access to the invitation email; it is not administrator approval.
- Worker ID confirmation is an extra matching step, not a secret or the only proof of identity.
- Worker IDs are a requirement for this project's fictional organisation; they are not assumed to exist at every employer.
- Define worker ID uniqueness within the organisation, not globally across every possible company.
- Repeated verification attempts are limited.
- Protected features remain unavailable until review, mailbox readiness, and application activation requirements are satisfied.
- Resetting an application password does not automatically change the mailbox password.

The exact mailbox authentication and recovery experience will be decided with the provider integration.

## 6. Initial roles

| Role | Responsibilities | Limits |
|---|---|---|
| Administrator | Create teams, register PICs, review applicants, manage roles, monitor provisioning, deactivate accounts | Sensitive actions are recorded |
| Team PIC | Invite members, track invitations, view permitted members of their own team | Cannot approve their own invitations or grant administrator access |
| Team member | Maintain their profile and use assigned features | Cannot manage other users unless explicitly permitted |

Future roles may include approver, procurement officer, supplier, and finance officer. Their permissions will be defined alongside their workflows.

Backend authorization must enforce access rules. Hiding a sidebar item or button is not sufficient.

The administrator who reviews members can later be replaced by a dedicated reviewing department. Keep that responsibility explicit without building the future department workflow yet.

## 7. Account bootstrap

- The first administrator is created through a controlled initial setup process, not public signup.
- The administrator initiates PIC onboarding.
- A PIC must have an active, approved account before inviting team members.
- PIC onboarding must define who approves the PIC and whether an existing company mailbox is reused.
- Do not use permanent shared default passwords for administrator or PIC accounts.

## 8. Track separate lifecycles

### Invitation and review

| Status | Meaning |
|---|---|
| Invited | Invitation issued; waiting for acceptance |
| Awaiting review | Person accepted; administrator review is pending |
| Approved | Administrator approved the person |
| Rejected | Administrator rejected the application with a reason |
| Expired | Invitation expired before acceptance |
| Revoked | An authorized person cancelled the invitation |

### Mailbox provisioning

| Status | Meaning |
|---|---|
| Not started | Provisioning has not been requested |
| Pending | Mailbox creation or existing-mailbox verification is underway |
| Ready | Mailbox existence and assignment are confirmed |
| Failed | Provisioning needs retry or administrator attention |

### Application account

| Status | Meaning |
|---|---|
| Pending activation | Person has not finished the required account setup |
| Active | Person can sign in and access permitted features |
| Deactivated | Application access is disabled |

Approval, mailbox readiness, and application activation are different events. An approved person can still be waiting for mailbox provisioning.

## 9. Foundation page map

### Account access

- Login with Worker ID (or the administrator username).
- Invitation acceptance.
- Invitation expired, revoked, or already used.
- Waiting for review.
- Rejection outcome with appropriate guidance.
- Account setup waiting for mailbox readiness.
- Activation and worker ID confirmation.
- Initial password setup.
- Forgot password and reset password.
- Session expired.

Any page available before login must reveal only appropriate information and require its intended verification where necessary.

### My account

- Profile and team information.
- Application password change.
- Mailbox access guidance.
- Logout.

### Team PIC workspace

- Team member list and permitted member details.
- Invite member form.
- Invitation tracking with resend/revoke actions where allowed.
- Review and activation status, without exposing confidential reviewer notes.

### Administrator workspace

- Teams: list, create, edit, assign PIC.
- Users: list, details, role assignment, deactivate/reactivate.
- Review queue: list, details, approve/reject.
- Mailbox provisioning: status, failure details, retry/manual resolution.
- Roles and permissions.
- Company settings.
- Account activity history.

### Shared application pages

- Role-appropriate home/dashboard.
- Forbidden access.
- Page not found.
- Service unavailable or unexpected error.

### Developer foundation

- Component showcase.
- Reference module: Departments.
- Module scaffolding command, after the reference module proves the conventions.

## 10. Shared design rules

Agree on these before building lots of screens:

| Area | Shared elements |
|---|---|
| Layout | Sidebar, header, content width, page title, breadcrumbs |
| Typography | Headings, labels, body text, helper text |
| Actions | Primary, secondary, destructive, disabled, loading buttons |
| Forms | Inputs, selects, checkboxes, required indicators, field errors |
| Data | Tables, filters, sorting, pagination, status badges |
| Feedback | Alerts, loading indicators, empty states, confirmation dialogs |
| Accessibility | Keyboard access, visible focus, labels, readable contrast |
| Responsive behaviour | Narrow-screen navigation, forms, tables, and actions |

Use a component showcase to inspect the shared elements together. Generated modules should import maintained shared components rather than duplicate their markup and styling.

## 11. Rules and failure cases to discuss

- Inviting an existing active employee or someone already awaiting review.
- Multiple invitations for the same worker ID or email address.
- A person entering a worker ID that does not match the approved record.
- Expired links, resends, and which earlier links become invalid.
- A PIC attempting to invite someone into another team.
- Repeated acceptance or approval clicks.
- A reviewer rejecting someone after acceptance.
- Email delivery failure versus mailbox provisioning failure.
- An email provider successfully creating a mailbox while the application loses the response.
- Incorrect invitation details and who may correct them before activation.
- Team transfers and changes of PIC.
- Deactivation while a member has an active session.
- Whether reactivation reuses the existing account and mailbox.
- Deactivating the last active administrator.
- Application deactivation versus disabling mailbox access: these require an explicit offboarding policy.
- Application recovery uses Worker ID and current recovery email. Lost-address requests require PIC/administrator identity review, company-mailbox reset delivery, employee consent, and replacement-address verification. Provider mailbox recovery remains separate.

Each agreed rule should later become an acceptance check or a business-rule test.

## 12. History we need

Record who performed each important action and when:

- Team and PIC assignment changes.
- Invitation creation, resend, revocation, and acceptance.
- Approval and rejection, including a reason where required.
- Mailbox provisioning requests, outcomes, retries, and assignment.
- Account activation and deactivation.
- Role and permission changes.

Do not include passwords, invitation tokens, activation tokens, or provider credentials in the history.

## 13. Developer module scaffolding

The goal is to generate a new module's starting structure without copying an unrelated module and cleaning it up.

For example, a future Supplier Hub should start with the same layout, shared components, validation presentation, navigation conventions, and access-control structure as the rest of the application.

Potential generated files and registrations:

- List, create/edit, and details pages.
- Frontend routes and API client structure.
- Backend routes and controller skeletons.
- Validation and authorization skeletons.
- Module registration and permission identifiers.
- Meaningful test starting points.

The generator does not invent business rules or automatically make a module secure. Developers implement and review the module-specific behaviour, permissions, and tests.

Start with a developer command. An administrator-facing module builder is outside the initial scope. Running the generator again must not overwrite existing implementation work.

## 14. Proposed implementation order

1. Resolve the account and mailbox decisions in this document.
2. Draw account flows and agree the role/permission matrix.
3. Select specific Tabler and Flowbite screen references.
4. Sketch login, invitation, waiting-for-review, administrator review, and team member screens.
5. Build the shared layout and component showcase.
6. Establish authentication and backend access rules.
7. Implement team/PIC onboarding and member invitations.
8. Implement review, activation, and mailbox provisioning status, using a clearly labelled mock adapter if a real provider is not ready.
9. Connect and verify real mailbox provisioning as a separate milestone.
10. Implement account administration, recovery, and audit history.
11. Build and review the Departments reference module.
12. Extract the repeated conventions into the module generator.
13. Begin procurement modules using the agreed foundation.

Maintain automated tests for access rules and account transitions as those features are built. We will use small tickets and documentation-led learning rather than generating the entire implementation at once.

## 15. Next discussion

Before coding the account flow, decide:

1. Which email provider/domain will host the real company mailboxes?
2. Should the first version provision mailboxes automatically or track administrator provisioning?
3. How will the first PIC be approved and activated?
4. How will secure first mailbox access and provider-specific mailbox recovery work? The application currently uses its own private password.
5. When should provider single sign-on be reconsidered? Current application recovery follows the agreed verified-email/PIC flow.

Once these are settled, turn the foundation into small user-problem tickets and start the page sketches.

## 16. Accepted login and permission decisions — 16 November 2026

Worker ID login, local-only admin/admin credentials, private administrator setup, verified recovery-email changes, PIC recovery review, and bounded group/individual module grants are implemented separately from the remaining onboarding/provider work. See [authentication-access.md](authentication-access.md) for the current rules, schema additions, setup commands, and tests.


## 17. Remaining foundation checklist — 16 November 2026

Agreed follow-up work before the main procurement implementation. These items are pending, not completed features. Reuse the implemented database, transactional audit, authentication, recovery, and module-access foundations described in authentication-access.md.

- [ ] **Teams and user administration:** create/edit teams, assign/change PICs, transfer members, deactivate/reactivate accounts, and protect the last active administrator. Verify that account/team changes affect existing access on the next request.
- [ ] **Complete onboarding:** invitation → acceptance → administrator review → mailbox readiness → activation → private password setup. Cover expired links, resends, duplicates, rejection, single-use tokens, and repeated actions. A new user must not depend on already-approved seed records.
- [ ] **Mailbox process:** choose manual verification or automatic provisioning; define existing-mailbox verification and employee offboarding. Clearly label local mock mode. Real provisioning remains a separate milestone if using a mock initially.
- [ ] **Shared working components:** maintained forms, validation errors, tables, filters, pagination, confirmations, loading/empty/error states, and mobile navigation. Verify keyboard and narrow-screen behaviour on functional pages.
- [ ] **Departments reference module:** implement backend permissions, scoped queries, validation, search/pagination, and transactional audit history. Prove the conventions before extracting a module generator.
- [ ] **Basic CI:** automatically run meaningful existing backend tests against an isolated testing database and the frontend production build. A failing test/build must fail the check.

Suggested implementation sequence: teams/account administration → onboarding/mailbox process → shared components and Departments → verify CI before procurement work. Establish CI early where possible. PT-001 workflow design may proceed alongside this work.

Deferred reminder: revisit the documented Vue/Tailwind direction versus the current live Blade/prototype CSS implementation when choosing the first procurement screen implementation. The owner has explicitly set this discussion aside for now; do not treat it as the next foundation task.

## 18. Proposed document-editing foundation — ONLYOFFICE

Status: owner-requested addition for discussion and later implementation; not installed. Integrate ONLYOFFICE Docs into ProcureTrack so authorized people can create and edit documents within the website. Modules can have default document templates.

### Agreed opening rule

Resolve a document for a specific module record and document purpose/type:

1. If an existing saved document belongs to that record/purpose, open it.
2. Otherwise, if the module has an applicable default template, create a new document by copying that template and open the new document.
3. Otherwise, create and open a valid blank document of the requested type.

Opening an existing document must never reset its contents from a template. Editing a record document must never change the shared template. Later template changes apply to newly created documents, not existing documents.

### Proposed implementation checks

- [ ] Select an ONLYOFFICE Docs edition/version and review its current licensing, branding, capacity, and hosting requirements before installation. Community Edition is a free self-hosted candidate, not a decided deployment configuration.
- [ ] Run Document Server as a separate service alongside Laravel; configure browser/server connectivity and JWT authentication without exposing secrets.
- [ ] Store documents and templates privately, with metadata linking each document to its company, module, record, purpose/type, and template version where applicable.
- [ ] Authorize opening, editing, downloading, and template administration in Laravel. Read-only access and submitted/approved record restrictions must also apply to editor configuration.
- [ ] Implement authenticated document retrieval and validated save callbacks. Persist the edited file durably in application-controlled storage; opening the editor alone does not save a document into ProcureTrack.
- [ ] Make first creation atomic/idempotent so two simultaneous opens do not create competing documents. Use shared editing-session keys correctly and advance versions after completed saves without breaking active co-editing sessions.
- [ ] Handle repeated/delayed callbacks and failed saves without silently overwriting newer content; expose pending/failed save outcomes and a recovery path.
- [ ] Record creation and saved versions in appropriate history without recording secrets or document contents in general logs. Include files in the backup/restore plan.
- [ ] Demonstrate reopening saved content, copying a template without modifying it, blank fallback, denied access, concurrent first opens, and save failure/retry.

### Scope to discuss next

- Start with word-processing documents (.docx), or also spreadsheets/presentations?
- Which first module/document should demonstrate the integration?
- Who can upload/change default templates, and is the default selected per module or per document purpose within a module?
- Should templates initially be copied unchanged, or should a later phase populate placeholders from business data?
- When a business record is submitted/approved, should its document become read-only or require an explicit revision?

Suggested first slice: one document purpose on a reference record, one optional default template, existing/template/blank resolution, private storage, permissions, and a verified save/reopen cycle. This is a reusable supporting capability; it does not replace structured procurement data or approval rules.

Official integration references:

- [Opening a file](https://api.onlyoffice.com/docs/docs-api/get-started/how-it-works/opening-file/)
- [Saving a file](https://api.onlyoffice.com/docs/docs-api/get-started/how-it-works/saving-file/)
- [Callback handler](https://api.onlyoffice.com/docs/docs-api/usage-api/callback-handler/)
- [Community Edition licensing FAQ](https://helpcenter.onlyoffice.com/docs/installation/community/faq)

## 19. Accepted implementation decisions — 16 November 2026

- Start with administrator-created, manually verified real company mailboxes. No provider API integration is required for the first foundation release. Application deactivation does not itself disable provider access; administrators must complete provider offboarding separately.
- Assign a PIC from existing active, approved, activated members of that same active team. Onboard a new person as a member before assigning PIC responsibility.
- Implement the remaining foundation in small reviewable steps, beginning with Teams and account administration. Preserve existing login, recovery, permission, and transactional audit behavior.


## 20. Foundation implementation tracking

The six follow-ups now have local implementations. See [foundation-implementation.md](foundation-implementation.md) for the page map, business rules, setup, learning map, and remaining verification. The checklist in section 17 is the acceptance checklist; implementation alone does not complete manual review or an observed hosted CI run.

- Teams/account administration: functional administrator pages and transaction rules implemented.
- Onboarding: functional invitation, review, activation, resend/revoke, and failure handling implemented.
- Mailboxes: manual verification and activation delivery implemented; actual provider verification remains the administrator's responsibility.
- Shared components: functional components and administrator showcase implemented; desktop/mobile and keyboard review pending.
- Departments: reference module implemented with scoped read/edit access and audit history.
- CI: GitHub Actions workflow added; local test/build verification is recorded in foundation-implementation.md. Hosted execution and branch protection are not configured by this local change.
