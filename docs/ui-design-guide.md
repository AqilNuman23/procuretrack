# ProcureTrack UI design guide

Use this guide when adding or changing a ProcureTrack page. It records the conventions agreed during the workspace design review. The [original shell sketches](application-shell-design.md) are useful background. When the two differ, follow this guide.

## Overall style

- Keep the first view focused on the title, the primary action, and the content the user came for.
- Use the existing dark and light themes and shared Blade styles. The site-wide body text baseline is medium weight (`500`). Keep headings, selected options, and primary actions visibly stronger.
- Keep explanatory copy out of the main content flow where it can be read on demand. Put page purpose and optional guidance in a small **ⓘ** beside the page title. Make it usable by click, tap, and keyboard. Keep field labels, validation errors, and action-critical instructions where the user needs them.
- Use existing controls, icons, spacing, and translated labels. Check both English and Malay for wrapping and cramped controls.

## Navigation and module pages

- The **ProcureTrack** logo returns to the module hub. Leave the duplicate **All modules** button out of the sidebar.
- Module overview tiles contain a meaningful icon and the module name anchored at the bottom. Make the whole tile clickable. Omit **Open →** copy and permission labels on the tiles. The backend still controls which modules a user can see and use.
- Use tabs when a page contains several distinct but related datasets or workflows. In **Activity & error history**, the tabs are **Committed changes**, **Failed writes**, and **Recovery email history**. Committed changes opens by default.
- Give each data tab a linkable URL, a visible active state, and its own search, filters, and pagination state. Query and render the active tab only. Use regular links when switching tabs changes the server-rendered view.

## Tables and lists

Use a shared table for lists of records, including administrative work such as mailbox readiness. Keep fields and long instructions out of table rows. Show key details and compact actions per record.

| Area | Convention |
|---|---|
| Top left | A **Filters** button opens a dialog with relevant dropdowns and an **Apply filters** action. |
| Top right | A separate table **Search** control searches the full authorized dataset. Submitting search preserves active filters. Clicking the input’s **×**, or emptying an applied search, immediately refreshes the results. |
| Filter dialog | Keep it comfortably wide on desktop and responsive on mobile. Let it grow when opening a dropdown so the dialog itself does not show a scrollbar under normal viewport conditions. Long option lists can scroll within their dropdown. |
| Clearing filters | **Clear filters** resets the dropdown criteria and keeps the search term and selected page size. Applying search, filters, or a page-size change starts that dataset at page one. |
| Pagination | Default to **10 rows**. Offer **25, 50, and 100** as alternatives. Keep the current search and filters in page links. |
| Data loading | Apply permissions, search, and filters before paginating. Fetch only the requested page and eager-load the related data the rows display. Use simple pagination when an exact total is unnecessary. |
| Status | Use the shared labeled status badge. **Active** is green, **Awaiting review** amber, **Failed** red, and **Pending activation** blue. Inactive states use a neutral tone. Status must be readable in text as well as color. |
| Row actions | Keep buttons compact and separated by a consistent gap. Open a dialog for actions that need input. Show actions only when the record and permissions allow them. |

## Dropdowns, forms, and feedback

- Use the shared **Select2-style searchable dropdown** for select fields across the workspace and preview, including filters, rows per page, and multi-selects. Open with a search field, show an empty-results message when needed, and support mouse, touch, and keyboard. Preserve native form values, disabled and required behavior, multi-select submissions, and options added dynamically.
- Put primary create actions near the page title. **Create team**, **Invite a member**, and **Create department** open dialogs. Users should not need to scroll past a list to start creating a record.
- Keep row-specific input in its own dialog. **Verify mailbox** opens the selected employee’s verification form. **Approve** needs no review reason. **Reject** opens a form that requires a reason.
- Use a styled, accessible confirmation dialog for actions with meaningful consequences, such as resending or revoking an invitation. State the consequence, keep Cancel easy to reach, and submit the action the user chose.
- Show failed form submissions in a popup on the current page. Preserve the form entries and include actionable validation details when available. Keep page-level error screens for failures to load an entire page. Use distinct loading, empty, and error states.
- Keep dialogs usable with keyboard navigation, Escape, visible focus, and focus return to the opener. Check that dropdowns do not clip or cover critical dialog actions.

## Reuse in the codebase

| Pattern | Starting point |
|---|---|
| Workspace title, sidebar, page actions, page information, and confirmation dialog | [Foundation layout](../resources/views/foundation/layout.blade.php) |
| Module names, icons, and permissions metadata | [Module registry](../config/modules.php) |
| Table, toolbar, and pagination | [Foundation Blade components](../resources/views/components/foundation/) |
| Searchable selects and form error popups | [Shared browser scripts](../public/design-assets/) |
| Page sizes and server query conventions | [TableQuery](../app/Support/Foundation/TableQuery.php) |
| Create and row-action dialogs | [Create dialog component](../resources/views/components/foundation/create-dialog.blade.php) |
| Status badges | [Status badge component](../resources/views/components/shared/status-badge.blade.php) |
| Examples of these patterns together | [Invitations](../resources/views/foundation/invitations.blade.php), [Mailbox readiness](../resources/views/foundation/mailboxes.blade.php), [Activity history](../resources/views/foundation/activity.blade.php) |

## Review before handing off a page

- Open the real page on desktop and a narrow viewport. Check what the user sees first and whether the primary action is easy to find.
- Search, filter, clear search, change page size, and switch tabs where applicable. Verify the right records and query state are preserved.
- Open each dropdown and dialog with a mouse and with the keyboard. Check Escape, focus return, scrolling, and button spacing.
- Try an invalid submission and a confirmation cancellation. Check the current page, entered values, selected action, and error message.
- Check both themes and test with a user who lacks the relevant permission.

To brief another coding assistant: “Read `docs/ui-design-guide.md` first. Reuse the shared ProcureTrack patterns. Apply the conventions to the whole feature, including its narrow layout, table interactions, and failure states.”

See the [ticket and module map](ticket-module-map.md) for starter pages and demo personas. This guide covers presentation and interaction. Each ticket defines its own workflow, record access, and business rules.
