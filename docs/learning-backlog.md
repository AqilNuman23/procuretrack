# ProcureTrack: requests, complaints, and things to figure out

This is my personal project backlog. The users below are fictional. These are planned requests, not features I have already completed.

The goal is to learn by solving a problem, explain why I chose an approach, and have something working to show. I can ask for hints without asking someone to build the whole thing for me.

## How I'll use this

- Work in the order below. Only keep one ticket in progress.
- Read the complaint and acceptance checks first. The research prompts are optional hints, not required implementations.
- Before coding, write what I think the problem is and sketch the relevant screen or flow.
- Read enough documentation to try a small version. Then improve it.
- Save evidence: a screenshot, sample API response, short recording, or test result.
- Create one GitHub issue per ticket when I reach it. Link the commit or pull request when finished.
- If stuck, explain what I expected, what happened, what I tried, and the exact error. Never share tokens, passwords, or .env contents.

Board columns: Backlog | Ready | In progress | Review | Done.

Done means the acceptance checks work, I tried a failure case, and I can explain the important code. Automate checks for permissions, calculations, and workflow rules; visual layout changes can be checked manually.

## Working business assumptions

These are fictional product decisions so I have something concrete to build. They are not government procurement rules.

- Start with one company and one currency: MYR.
- Initial roles: requester, approver, procurement officer, supplier, finance officer, administrator. Add roles only when their workflow is needed.
- Initial approval: one designated approver. A requester cannot approve their own request.
- Later approval limits: requests above RM5,000 need a second, different approver before final approval.
- Drafts can be edited. Submitted requests are read-only until returned for changes.
- Reserve the estimated amount when a request receives final approval. Release it when an approved request is cancelled before an order exists.
- Budget available = allocated amount minus outstanding reservations minus recorded spending. Actual payment records must not count the same amount twice.
- Later tickets will define how reservations become order commitments and recorded spending. Record that decision before implementing those transitions.
- Initial purchase request -> approval -> quotation -> purchase order -> delivery acceptance -> payment claim -> payment record -> completion.
- Payment recording is manual. Moving money through a bank is outside this project's scope.
- Use independently written code and fictional data.

## Ticket sheet

| Order | Ticket | User problem | Depends on | Status |
|---|---|---|---|---|
| 1 | PT-001 | Nobody agrees how a purchase should move through the system | None | Backlog |
| 2 | PT-002 | The screens look unrelated and are hard to navigate | PT-001 | Backlog |
| 3 | PT-003 | I don't understand whether the page is loading or broken | PT-002 | Backlog |
| 4 | PT-004 | I need a private account and a way to sign out | PT-003 | Backlog |
| 5 | PT-005 | People can access things outside their role | PT-004 | Backlog |
| 6 | PT-006 | I lose purchase details while preparing a request | PT-005 | Backlog |
| 7 | PT-007 | I can't find the request I submitted | PT-006 | Backlog |
| 8 | PT-008 | Requests get approved without a clear review process | PT-007 | Backlog |
| 9 | PT-009 | Two approvals might spend the same budget | PT-008 | Backlog |
| 10 | PT-010 | Nobody knows who changed a record | PT-008 | Backlog |
| 11 | PT-011 | I need a safe way to invite and deactivate staff | PT-005, PT-010 | Backlog |
| 12 | PT-012 | Large purchases need extra approval | PT-009 | Backlog |
| 13 | PT-013 | Comparing supplier quotations means opening lots of files | PT-009, PT-010 | Backlog |
| 14 | PT-014 | The supplier needs a clear order to fulfil | PT-013 | Backlog |
| 15 | PT-015 | The order says delivered, but half the items are missing | PT-014 | Backlog |
| 16 | PT-016 | I might pay for goods we never accepted | PT-015 | Backlog |
| 17 | PT-017 | I forget approvals and exports slow everything down | PT-016 | Backlog |
| 18 | PT-018 | Our old records are in spreadsheets | PT-014 | Backlog |
| 19 | PT-019 | A summary total doesn't match the underlying records | PT-016 | Backlog |
| 20 | PT-020 | Another developer can't understand our API | PT-016 | Backlog |
| 21 | PT-021 | Changes keep breaking things that already worked | PT-009, PT-016 | Backlog |
| 22 | PT-022 | It works on your laptop, but I can't run it | PT-021 | Backlog |

## PT-001 — "Can you show me how buying something will actually work?"

Requester: "I ask for equipment in chat, my manager replies somewhere else, and finance sees a different spreadsheet. Can we agree on one process?"

Acceptance checks:
- [ ] Describe one purchase from the requester's first action to completion.
- [ ] List each actor, their decisions, and the information they need.
- [ ] Draw the happy path and a return/rejection path.
- [ ] List states for the purchase request, allowed transitions, and who can trigger each one.
- [ ] Write down open questions and explicit exclusions.

Deliverable: a short workflow note and diagram. No application changes needed.

Research prompts: user stories, acceptance criteria, process flow diagrams, state transitions.

## PT-002 — "Every page feels like a different app."

Requester: "I want to know where I am, find my requests, and see the main action without hunting around. I sometimes use my phone."

Acceptance checks:
- [ ] Sketch request list, request form, and request details screens before implementation.
- [ ] Pick consistent spacing, type sizes, primary colour, input styles, and button styles.
- [ ] Build a reusable page layout with navigation and a clear page title.
- [ ] A narrow screen remains usable, and keyboard focus is visible.
- [ ] Include a blank state and a validation-error sketch.

Research prompts: MDN CSS box model/Flexbox/Grid, Tailwind responsive design, Vue components and props.

Design task: collect three references, explain one useful choice from each, then sketch your own layout. Stop collecting references after that.

## PT-003 — "Is this loading, empty, or broken?"

Requester: "Sometimes I see nothing and I don't know whether to wait or refresh."

Acceptance checks:
- [ ] A small Vue screen reads a Laravel JSON endpoint.
- [ ] Loading, success, empty, and failure states have distinct messages.
- [ ] I can retry a failed request without reloading the page.
- [ ] You can explain the request and response in the browser Network tab.

Research prompts: JavaScript promises/async/await, Fetch API, Vue ref/onMounted, HTTP status codes.

Note: the existing status page is a starting point. Verify its behaviour yourself before marking this done.

## PT-004 — "I need to sign in, and sign out on a shared computer."

Employee: "Only staff with an account should see internal pages. After I sign out, I shouldn't still be able to fetch private data."

Acceptance checks:
- [ ] Seeded fictional users can log in and log out.
- [ ] Wrong credentials produce a helpful message without exposing private account details.
- [ ] Private API requests are rejected when signed out.
- [ ] Refreshing the browser preserves a valid session.
- [ ] An expired session gives a clear route back to login.

Research prompts: Laravel Sanctum SPA authentication, session cookies, CSRF, Vue form bindings. Public registration is outside this ticket.

## PT-005 — "Hiding a button didn't stop someone accessing my request."

Manager: "Requesters should see their own requests. Approvers should only review assigned work. Suppliers must never see another supplier's private records."

Acceptance checks:
- [ ] Write a role/action/access matrix.
- [ ] Enforce ownership and assignment rules in the backend.
- [ ] Changing a record ID or calling an endpoint directly cannot bypass access rules.
- [ ] Include automated checks for an allowed action and denied access to someone else's record.

Research prompts: authentication vs authorization, Laravel policies, object-level authorization, HTTP 401/403/404.

## PT-006 — "Let me save my request before it's ready."

Requester: "I need to add several items and come back later. I also need clear errors when I miss something."

Acceptance checks:
- [ ] Save and reopen an owned draft with purpose, required date, and item rows.
- [ ] Add/remove rows and display quantity x unit cost and a total.
- [ ] Reject invalid quantities and costs on the backend, with errors next to relevant fields.
- [ ] Totals remain correct for decimal prices and after editing rows.
- [ ] Show when saving is in progress and prevent accidental repeated submission.

Research prompts: Vue v-model/v-for/computed, Laravel validation, relational modelling, currency precision, database transactions.

## PT-007 — "I can't find the request I made last week."

Requester: "There are too many records. Let me search, filter by status, and open one to see the details."

Acceptance checks:
- [ ] Search, status filter, sorting, and pagination work together.
- [ ] Filters apply to the full authorized dataset, not just the currently visible rows.
- [ ] Empty results explain how to clear the filters.
- [ ] Shared controls use consistent styling.

Research prompts: Laravel pagination/query scopes, API query parameters, Vue watch, debouncing, database indexes. Compare behaviour with a few hundred seeded records.

## PT-008 — "Who approved this, and why can't I fix my submission?"

Approver: "I need to approve, reject, or return a request with comments. The requester shouldn't edit it while I'm reviewing it."

Acceptance checks:
- [ ] An owned valid draft can be submitted to its designated approver.
- [ ] An approver cannot approve their own request.
- [ ] Rejecting or returning requires a reason.
- [ ] Returned requests can be edited and resubmitted with review history retained.
- [ ] Repeating an action or sending an invalid transition does not corrupt the request.

Research prompts: workflow transitions, Laravel policies, domain rules, transactions, feature tests.

## PT-009 — "We approved two purchases, but only had money for one."

Finance: "The remaining budget looked fine for both reviewers. Now the approvals together exceed our allocation."

Acceptance checks:
- [ ] Display allocated, reserved, spent, and available amounts with clear definitions.
- [ ] Final approval reserves the estimated cost and rejects insufficient funds.
- [ ] Simultaneous approvals cannot reserve more than the available budget.
- [ ] Repeating approval does not reserve the amount twice.
- [ ] Cancelling an approved request before an order exists releases its reservation once.
- [ ] Include evidence of a concurrent-request check and automated business-rule tests.

Research prompts: race conditions, MySQL transactions and locking, idempotency, financial ledger basics. Explain why a disabled button alone cannot solve this.

## PT-010 — "The amount changed, but nobody remembers who changed it."

Administrator: "I need a history of important actions without giving everyone access to sensitive logs."

Acceptance checks:
- [ ] Record actor, time, action, record reference, and relevant changes.
- [ ] Show an understandable timeline to authorized users.
- [ ] Failed operations do not appear as successful actions.
- [ ] Passwords, tokens, and other secrets are excluded.

Research prompts: audit history vs application logs, Laravel events, transactional consistency, access-controlled history.

## PT-011 — "A new colleague needs access, and someone who left still has access."

Administrator: "Let me invite a colleague and deactivate an account without losing their historical approvals."

Acceptance checks:
- [ ] An invite expires and can only be accepted once.
- [ ] Reissuing an invite has defined behaviour.
- [ ] Deactivation blocks access, including an already signed-in session.
- [ ] Existing records still identify their original actor.
- [ ] Only authorized administrators manage accounts and roles.

Research prompts: account lifecycle, signed URLs/tokens, session invalidation, Mailpit, notification testing.

## PT-012 — "A RM20,000 request shouldn't use the same approval as a RM200 one."

Finance: "Requests above RM5,000 need two different approvers. I also need to know who's next."

Acceptance checks:
- [ ] Requests at RM5,000 and above RM5,000 follow the documented boundary rule correctly.
- [ ] Show the current and remaining approval stages.
- [ ] One person cannot fulfil both stages or approve their own request.
- [ ] Reserve the budget only after final approval.
- [ ] Decide and document what happens when rules change during an active review.

Research prompts: approval routing, configuration snapshots, boundary-value testing. Use fixed stages first; a drag-and-drop workflow builder is out of scope.

## PT-013 — "I'm comparing quotations in five different browser tabs."

Procurement officer: "Let me collect comparable supplier offers and explain why we chose one."

Acceptance checks:
- [ ] Invite suppliers to quote against an approved request with a deadline.
- [ ] Suppliers can access only their own invitation and quotation.
- [ ] Compare item prices and totals side by side.
- [ ] Record selection and justification, with unavailable actions clearly explained.
- [ ] Attachments are validated and downloaded only by authorized users.

Research prompts: relationship modelling, upload validation, private file storage, comparison-table design.

## PT-014 — "The supplier and our team have different versions of the order."

Supplier: "I need a clear order number, quantities, agreed prices, and delivery date."

Acceptance checks:
- [ ] Issue an order from the selected quotation, with a unique reference and printable copy.
- [ ] A later supplier-profile change does not rewrite the issued document.
- [ ] An issued order cannot silently change through the API.
- [ ] Define and implement how the approved reservation becomes the order commitment; handle price differences without overspending or double-counting.
- [ ] Record and explain the order states and cancellation limits.

Research prompts: document snapshots, reference generation, printable CSS/PDF generation, budget lifecycle modelling.

## PT-015 — "Five items arrived, but the system says all ten were delivered."

Receiving officer: "We need partial deliveries, inspection notes, and a way to reject damaged items."

Acceptance checks:
- [ ] Supplier submits delivery quantities and supporting documents.
- [ ] An authorized officer accepts or rejects quantities with checklist results.
- [ ] Show ordered, delivered, accepted, rejected, and outstanding quantities clearly.
- [ ] Prevent over-delivery and repeated acceptance of the same quantity.
- [ ] Only eligible orders become delivery-complete.

Research prompts: quantity invariants, partial fulfilment, checklist UI, transactional updates, concurrent submissions.

## PT-016 — "This claim includes items we never accepted."

Finance: "Show me the order, accepted deliveries, previous claims, and the amount still eligible before I approve payment."

Acceptance checks:
- [ ] Claims refer to accepted quantities and cannot exceed the remaining claimable value.
- [ ] Support return-for-correction and approval with history.
- [ ] Generate a certificate containing a stable approved snapshot.
- [ ] Record manual payment reference, date, and amount without duplicate payment records.
- [ ] Reconcile commitments and spending without double-counting.
- [ ] Define when an order is financially complete and show outstanding balances.

Research prompts: reconciliation, cumulative totals, document generation, decimal precision, transaction boundaries.

## PT-017 — "I missed an approval, and exporting makes the page hang."

Approver: "Tell me when something needs my action." Finance: "Let me request a report and come back when it's ready."

Acceptance checks:
- [ ] Notify the correct person after a successful workflow change.
- [ ] A large export runs without holding the browser request open.
- [ ] Show queued, processing, complete, and failed outcomes.
- [ ] Failed work can be retried without duplicate business actions.
- [ ] Only the authorized requester can download the private export.

Research prompts: Laravel queues, Redis, retries, after-commit dispatch, worker processes, Mailpit.

## PT-018 — "Our old purchase orders are all in a spreadsheet."

Procurement officer: "Show me mistakes before importing. Don't create everything twice if I upload the same file again."

Acceptance checks:
- [ ] Publish a sample CSV format and define supported historical order states.
- [ ] Preview row errors before committing accepted rows.
- [ ] Define whether mixed valid/invalid files are rejected or partially accepted.
- [ ] Map legacy references and prevent duplicates on retry.
- [ ] Imported records remain subject to access and workflow rules.
- [ ] Provide a summary with row numbers and readable errors.

Research prompts: CSV parsing, staged imports, idempotency keys, legacy mapping, batch processing. Full historical migration is a later extension.

## PT-019 — "The dashboard total doesn't match the records I open."

Manager: "Show pending work, budget availability, late deliveries, and unpaid claims. Let me inspect the records behind a number."

Acceptance checks:
- [ ] Define each metric and its included statuses/date range.
- [ ] Drill-down results reconcile with summary totals.
- [ ] Role and ownership restrictions apply to aggregates too.
- [ ] Explain empty periods and when figures were last updated.
- [ ] Inspect query behaviour with realistic seeded data and record one measured improvement if a bottleneck exists.

Research prompts: SQL aggregation, Eloquent eager loading, N+1 queries, indexes, cache invalidation, timezone boundaries.

## PT-020 — "How does another application use this API?"

Integration developer: "I need documented endpoints, predictable errors, and a way to read permitted order statuses."

Acceptance checks:
- [ ] Document request/response examples, authentication, pagination, validation errors, and permissions.
- [ ] Demonstrate an authorized external-client request and rejection of unauthorized access.
- [ ] The client has no broader permissions than needed and can have access revoked.
- [ ] Define rate-limit behaviour.
- [ ] Keep the browser's session authentication distinct from external-client token access.

Research prompts: OpenAPI, Sanctum token abilities and policies, API versioning, HTTP semantics, rate limiting. A second Node backend is not required.

## PT-021 — "A small update broke approvals again."

Maintainer: "Tell me before a change breaks permissions, budget calculations, or the frontend build."

Acceptance checks:
- [ ] A pull request runs meaningful backend tests and the frontend production build.
- [ ] Tests cover forbidden access, invalid workflow transitions, money boundaries, and duplicate operations.
- [ ] A deliberately broken rule causes a failing check; restore it afterwards.
- [ ] A fresh checkout uses committed dependency lockfiles and documented runtime versions.
- [ ] No secrets are committed or printed in CI logs.

Research prompts: GitHub Actions services, Laravel testing, database test isolation, npm ci, Composer lockfiles. Start important tests during earlier tickets; this ticket automates and fills gaps.

## PT-022 — "Can I actually try it without you setting up my computer?"

Interviewer: "Give me a demo and explain your design decisions. Another developer should also be able to run it."

Acceptance checks:
- [ ] Verify setup instructions from a clean checkout with sample data.
- [ ] Publish a demo with fictional accounts and a documented reset strategy.
- [ ] Configure production errors, secrets, persistent files/data, HTTPS, workers, and scheduled tasks as applicable.
- [ ] Document a backup/restore check and a deployment rollback approach.
- [ ] Include architecture/database diagrams and a short role-based walkthrough.
- [ ] Review Laravel 12 support and upgrade to a supported release before leaving a long-lived public deployment running beyond its support window.

Research prompts: development vs production Docker images, container volumes/networks, Laravel deployment, health checks, logs, migrations during deployment. Sail is the local development environment, not the production deployment plan.

## Later ideas — only after the main journey works

- Multiple companies with strict data isolation.
- Project milestones, work certifications, and progress claims for services.
- Configurable approval routes and delegated approvers.
- Returns, order amendments, tax rules, and credit adjustments.
- Resumable historical migration and reconciliation reports.
- Incoming webhooks and a small external integration client.
- Real-time task updates, if polling is measurably insufficient.

## Free learning and design starting points

Use documentation for the specific ticket, not as a huge prerequisite course.

- [MDN learning material](https://developer.mozilla.org/en-US/docs/Learn_web_development): JavaScript basics, CSS layout, browser tools, and HTTP.
- [Vue guide](https://vuejs.org/guide/introduction.html): start with the essentials and component basics.
- [Vue Router](https://router.vuejs.org/): use when the application needs multiple frontend routes.
- [Tailwind utility classes](https://tailwindcss.com/docs/styling-with-utility-classes): connect classes to CSS concepts you understand.
- [Laravel 12 documentation](https://laravel.com/docs/12.x): search within the version matching the project.
- [Sanctum](https://laravel.com/docs/12.x/sanctum): read the SPA authentication section for browser login.
- [Docker getting started](https://docs.docker.com/get-started/): containers, images, networks, and volumes.
- [Accessible forms tutorial](https://www.w3.org/WAI/tutorials/forms/): labels, instructions, validation, and feedback.
- [GitHub Projects](https://docs.github.com/en/issues/planning-and-tracking-with-projects/learning-about-projects/about-projects): turn the ticket list into a board or table.
- [Figma Community](https://www.figma.com/community): search for dashboard wireframes, purchase request forms, approval inboxes, and supplier comparison tables. Look for free resources and check each resource's license before reuse; availability and terms vary. Paper sketches work too.

When looking at a design, ask: What is the main action? What information is missing? What happens with no records, bad input, slow loading, and a narrow screen? Can I use it with a keyboard? Do not start by designing every dashboard chart.

## My notes for each ticket

Copy this under an issue or into a notes file:

```text
Ticket:
Status:
User problem in my own words:
Questions / assumptions:
Sketch or workflow:
Documentation read:
Approach I chose and why:
What I tried:
Failure case checked:
Evidence:
Commit / PR:
What I can now explain:
What still confuses me:
```

## How I want mentoring to work

First: ask me a useful question about my attempt.
Then: offer one small hint and the relevant documentation section.
Then: help me debug the smallest failing example.
Give a worked solution only when I ask for it; explain the reasoning and leave a small follow-up task for me to do myself.

Start with PT-001. Bring back the workflow, actors, and three unanswered product questions before starting authentication.
