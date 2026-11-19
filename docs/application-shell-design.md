# ProcureTrack application shell design

For current workspace conventions, use the [UI design guide](ui-design-guide.md). The sketches below record the original design ticket.

Status: first design draft for review. No application features are implemented by this document.

## Agreed direction

- Start with the internal administrator experience.
- After login, show an **All modules** hub.
- Each master module opens its own dashboard and module-specific sidebar.
- Foundation master modules: **Users & Teams**, **Settings**, and **Help Centre**.
- Keep an **All modules** action visible inside each module.
- Tabler guides layout; Flowbite guides components. Neither library is selected for installation.
- Procurement modules will be introduced later, when their workflows are ready.

## First design ticket: FND-DES-001

User problem: "I need to choose an area of the application, then find the pages belonging to that area without seeing every page in the whole system."

Deliverable: desktop and mobile sketches of the module hub and Users & Teams dashboard, with navigation rules. This is a design ticket only.

### Master module cards

| Card | Description | Destination |
|---|---|---|
| Users & Teams | Manage staff, teams, invitations, reviews, and mailbox readiness. | Users & Teams dashboard |
| Settings | Manage company details, roles, permissions, and application preferences. | Settings overview |
| Help Centre | Find the user manual, frequently asked questions, and help guidance. | Help Centre overview |

FAQ is the proposed starting point for Q&A. A user-submitted question and answer workflow remains undecided.

## Desktop: All modules

```text
+---------------------------------------------------------------------+
| ProcureTrack                                      Administrator [v] |
+---------------------------------------------------------------------+
|                                                                     |
| All modules                                                         |
| Choose an area to get started.                                       |
|                                                                     |
| +-------------------+ +-------------------+ +-------------------+     |
| | Users & Teams     | | Settings          | | Help Centre       |     |
| | Staff, teams,     | | Company details,  | | User manual, FAQ,  |     |
| | invitations, and  | | access rules, and | | and help guidance.|     |
| | account setup.    | | preferences.      | |                   |     |
| | Open module  ->   | | Open module  ->   | | Open module  ->   |     |
| +-------------------+ +-------------------+ +-------------------+     |
|                                                                     |
+---------------------------------------------------------------------+
```

The hub has no module-specific sidebar. Each card is one keyboard-focusable link, with its title included in its accessible name. Avoid nested links or buttons inside the card.

## Desktop: Users & Teams

```text
+---------------------+-----------------------------------------------+
| ProcureTrack        |                         Administrator [v]     |
+---------------------+-----------------------------------------------+
| <- All modules      | Users & Teams / Dashboard                     |
|                     |                                               |
| USERS & TEAMS       | Dashboard                                     |
| Dashboard [active]  | Review account setup and team activity.       |
| Teams               |                                               |
| Users               | +-------------------+ +-------------------+   |
| Invitations         | | Awaiting review   | | Mailbox issues    |   |
| Review queue        | | 4 people          | | 2 need attention  |   |
| Mailboxes           | | View review queue | | View mailboxes    |   |
| Activity history    | +-------------------+ +-------------------+   |
|                     |                                               |
|                     | Account setup summary                         |
|                     | Invitations awaiting acceptance     6         |
|                     | Approved, awaiting mailbox          3         |
|                     | Awaiting activation                 5         |
|                     |                                               |
|                     | Recent activity                               |
|                     | Administrator approved a member.              |
|                     | PIC sent a team invitation.                   |
+---------------------+-----------------------------------------------+
```

All counts and activity above are fictional sketch examples. The dashboard content is a proposal for review, not an agreed reporting specification. Each summary link opens the corresponding filtered list. Approval, mailbox readiness, and activation remain separate concepts.

### Sidebar ownership

| Page | Purpose |
|---|---|
| Dashboard | Summarize reviews, mailbox issues, and account setup progress. |
| Teams | Manage teams and PIC assignments. |
| Users | View accounts, roles, and activation/deactivation state. |
| Invitations | Track invitations, with resend/revoke actions where permitted. |
| Review queue | Review accepted invitations and approve or reject applicants. |
| Mailboxes | Track provisioning or existing-mailbox verification and resolve failures. |
| Activity history | View authorized account, invitation, and team history. |

Roles & permissions lives in Settings. A user's detail page may offer role assignment to an authorized administrator; it does not duplicate permission configuration.

## Mobile sketches

```text
ALL MODULES                    USERS & TEAMS
+--------------------------+   +--------------------------+
| ProcureTrack    Account   |   | Menu  ProcureTrack  Acct  |
+--------------------------+   +--------------------------+
| All modules              |   | <- All modules           |
| Choose an area.          |   | Users & Teams            |
|                          |   | Dashboard                |
| +----------------------+ |   |                          |
| | Users & Teams        | |   | +----------------------+ |
| | Staff and accounts.  | |   | | Awaiting review: 4   | |
| | Open module ->       | |   | | View review queue    | |
| +----------------------+ |   | +----------------------+ |
| +----------------------+ |   | +----------------------+ |
| | Settings             | |   | | Mailbox issues: 2    | |
| | Open module ->       | |   | | View mailboxes       | |
| +----------------------+ |   | +----------------------+ |
| +----------------------+ |   | Account setup summary    |
| | Help Centre          | |   | Recent activity          |
| | Open module ->       | |   |                          |
| +----------------------+ |   +--------------------------+
+--------------------------+
```

On mobile, Menu opens a drawer containing the same module navigation as desktop. Include a visible Close control; Escape and selecting a destination close it. Keep focus inside the open drawer and return focus to Menu when dismissed. The page must remain usable without horizontal scrolling.

## Shared shell behaviour

- Keep the ProcureTrack brand, typography, spacing, and account menu consistent across modules.
- Account menu: My account and Logout.
- Show the current module name and highlight the active sidebar page using more than colour alone.
- Use one page heading and a clear place for the primary action on list/form pages.
- Use a light neutral canvas, white content surfaces, and blue primary actions as a first visual proposal.
- Provide visible keyboard focus, labelled controls, and accessible text contrast.
- Display only modules and pages the signed-in person may access; backend authorization must also enforce access.
- Show a clear empty state when a dashboard has no pending work. Never substitute zero counts for failed data loading.
- For loading or failed requests, show a distinct state and a retry action where appropriate.

## Acceptance checks for this design ticket

- [ ] Review the desktop and mobile sketches with the project owner.
- [ ] Confirm the three foundation cards and their descriptions.
- [ ] Confirm the Users & Teams sidebar and proposed dashboard priorities.
- [ ] Explain how to enter a module, identify the current page, and return to All modules.
- [ ] Review an empty dashboard, loading failure, and narrow-screen navigation before implementation.

Next mentoring step: review this sketch, record adjustments, then choose one small shell implementation ticket. Provider decisions remain open in foundation-plan.md; these sketches do not resolve them.
