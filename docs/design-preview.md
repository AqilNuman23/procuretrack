# Foundation design preview

This is a local Blade prototype with fictional data, separate from the Vue application. It implements visual layouts and browser-only interactions, not authentication or business workflows.

## Open it

With the existing Sail application running:

- Module hub: `/design`
- All 54 screens: `/design/pages`
- Administrator dashboard: `/design/people`
- Component showcase: `/design/components`
- Account access example: `/design/login`

For the current Sail port mapping, the hub is `http://localhost:8080/design`.

The existing `/` Vue application is preserved. The preview uses its own CSS and JavaScript, so it does not require a Vite development server or frontend build. Preview routes are registered only in Laravel's `local` and `testing` environments. Do not change a deployed application's environment to expose this prototype.

## Edit shared content in one place

`config/design.php` owns the brand, fictional company and administrator, shared UI wording, master module names, module descriptions, sidebar membership, page titles, form field definitions, FAQ, and sample datasets.

- Rename a master module: edit `modules.<key>.name`.
- Rename a sidebar page: edit `pages.<key>.title`. Navigation reads the title from this same definition.
- Reorder navigation: edit `modules.<key>.links`.
- Rename a repeated form field: edit `$fields` at the top of the file.
- Edit the sample users: edit `records`.
- Change shared action labels: edit `ui`.

Keep keys such as `people`, `users`, and `reviews` stable when changing display names. Keys identify preview URLs and navigation targets. Page-specific guidance lives in the Blade partials; it is not a repeated menu label. Some tables intentionally have independent fixtures to demonstrate different lifecycle states, rather than a consistent simulated database.

If Laravel configuration has been cached, run `./vendor/bin/sail artisan config:clear` after editing this file.

## File guide

| File | Responsibility |
|---|---|
| `config/design.php` | Shared content, navigation, and screen definitions |
| `resources/views/design/page.blade.php` | Global shell, sidebar, header, account menu, and preview dialog |
| `resources/views/design/partials/` | Reusable Blade layouts for dashboards, tables, forms, details, help, and states |
| `resources/views/components/design/icon.blade.php` | Shared inline SVG icons |
| `public/design-assets/prototype.css` | Shared dark theme, typography, spacing, and responsive rules |
| `public/design-assets/prototype.js` | Drawer, password visibility, dialogs, forms, and table interactions |
| `routes/web.php` | Read-only local preview route |

The styling is dependency-free prototype CSS. This does not install Tabler or Flowbite or change the existing Vue/Tailwind entry points. The maintained Blade partials establish shared visual patterns for later implementation.

## Review walkthrough

1. Open All modules and enter Users & Teams. Check the sidebar, current page, account menu, and return path.
2. Open Users. Search, filter, sort, and change pages. Use the state selector for loading, empty, and error examples; retry restores fictional records.
3. Open the review detail example. Try approve and reject; rejection asks for a reason. The confirmation explicitly performs no real action.
4. Open mailbox details. Review the mock failure and retry/manual-resolution dialogs.
5. Open a create/edit form. Required fields use browser validation. Password setup checks minimum length and matching values. These are preview rules, not a finalized authentication policy.
6. Browse Settings, Help Centre, My account, and the account-access/error screens from Page index.
7. At a narrow viewport, open/close the navigation drawer. Try Escape, keyboard focus, and the account menu.

All forms are previews. They do not save records, send invitations or resets, change access, or provision mailboxes. Account flow links illustrate transitions without validating real tokens or credentials. Use fictional values only. No provider or unresolved onboarding decision is finalized here.

## Verification

The initial pass compiled all Blade templates, rendered all 54 configured pages successfully through Laravel with in-memory sessions, passed JavaScript syntax checking, and passed the existing two tests. Browser visual and interaction checks are still required; no browser automation is installed in the current environment.

Review this prototype before converting individual pages into functional Vue/API features. Live authentication, authorization, provisioning, lifecycle transitions, and audit records remain separate learning tickets.
