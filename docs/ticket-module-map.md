# Ticket and module map

The module codes in this table open at `/modules/{code}`. Each new starter page has a title, a description, its ticket references, and the current user's module access level. The existing workspace supplies navigation and authorization.

| Ticket | Starting page or project area | Work to add |
|---|---|---|
| PT-001 | [Workflow assumptions](learning-backlog.md#working-business-assumptions) | Document actors, states, and the happy and return paths. |
| PT-002 | `procurement.requests`, `settings.components` | Sketch the request list, form, and detail layout. |
| PT-003 | `/status-demo`, `settings.components` | Build and verify the Vue fetch states and retry behavior. |
| PT-004 | `/login`, My account | Verify the sign-in, sign-out, and expired-session flows. |
| PT-005 | `settings.access`, `procurement.requests`, `procurement.approvals`, supplier pages | Add record ownership and assignment policies as the records are introduced. |
| PT-006 | `procurement.requests` | Add owned drafts, item rows, validation, and totals. |
| PT-007 | `procurement.requests` | Add the list, search, and status filter for actual requests. |
| PT-008 | `procurement.requests`, `procurement.approvals` | Add submission, review decisions, return, and resubmission. |
| PT-009 | `finance.budgets`, `finance.overview`, `procurement.approvals` | Add allocations, reservations, and release rules. |
| PT-010 | `people.history`, request and approval pages | Record changes and show the relevant workflow history. |
| PT-011 | `people.users`, `people.invitations`, `people.mailboxes`, `settings.access` | Cross-check account lifecycle scenarios. |
| PT-012 | `procurement.approvals`, `finance.budgets` | Add staged approvals and the amount boundary. |
| PT-013 | `suppliers.directory`, `suppliers.quotations`, `procurement.quotations` | Add invitations, private quotations, comparison, and supplier selection. |
| PT-014 | `procurement.orders`, `suppliers.orders`, `finance.budgets` | Issue orders and convert reservations into commitments. |
| PT-015 | `procurement.deliveries`, `procurement.inspections`, `suppliers.deliveries` | Add partial deliveries, inspection, and quantity rules. |
| PT-016 | `finance.claims`, `finance.certificates`, `finance.payments`, `finance.budgets`, `suppliers.claims` | Add claims, certificates, manual payments, and reconciliation. |
| PT-017 | `reporting.notifications`, `reporting.exports` | Add notifications and queued private exports. |
| PT-018 | `procurement.imports`, `procurement.orders` | Add CSV validation, preview, import, and deduplication. |
| PT-019 | `reporting.dashboard`, `reporting.exports`, `finance.overview` | Add metrics and drill-down that reconcile. |
| PT-020 | `integrations.docs`, `integrations.clients`, `procurement.orders` | Add API contracts, external-client authorization, and limits. |
| PT-021 | `.github/workflows/checks.yml`, `tests/` | Add workflow coverage to CI as tickets land. |
| PT-022 | `README.md`, `compose.yaml`, `docs/` | Verify a clean setup and document deployment, recovery, and the demo. |

## Local demo accounts

Once your fictional DEMO foundation is seeded and Sail is running, set a local-only password in your ignored .env as PROCUREMENT_DEMO_PASSWORD. Then run:

```bash
./vendor/bin/sail artisan db:seed --class=ProcurementDemoSeeder
```

The seeder registers modules and creates these accounts only in the local DEMO company. Use the Worker ID and the password you set locally. The Users & Teams administrator can also use **Switch user**. Rerunning the seeder preserves existing passwords and direct grant decisions. To register the pages on their own, use `./vendor/bin/sail artisan foundation:sync-modules`.

| Worker ID | Persona | Example access |
|---|---|---|
| PT-3101 | Requester | Edit purchase requests. |
| PT-3102 | Approver | Edit request approvals. |
| PT-3103 | Second approver | Edit request approvals using a separate identity. |
| PT-3104 | Procurement officer | Edit quotations, orders, imports, and supplier records. |
| PT-3105, PT-3106 | Suppliers A and B | Read supplier orders and edit supplier quotations, deliveries, and claims. |
| PT-3107 | Receiving officer | Read orders and deliveries and edit inspections. |
| PT-3108 | Finance officer | Edit budgets, claims, certificates, payments, and exports. |
| PT-3109 | Manager | Read the dashboard and key procurement and finance summaries. |
| PT-3110 | Integration developer | Read API documentation. |

The existing administrator can manage grants in **Settings → Groups & module access**. Demo personas use the existing member role with explicit Read or Read & Edit grants. These grants authorize the starter pages. The tickets will define record ownership, supplier isolation, approval authority, and external API credentials when those workflows are built.
