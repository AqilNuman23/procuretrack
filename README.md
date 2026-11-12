# ProcureTrack
A procurement and project delivery platform built with Laravel and Vue.

I’m building this project to bring together what I’ve worked on as a software developer so far, while learning things I haven’t had much experience with yet—especially Vue, Tailwind CSS, APIs, Docker, and deployment.

Most of my experience is with PHP and Laravel, working on procurement modules, user management, claims, certifications, budgets, and delivery tracking. This project gives me a chance to build my own version from the ground up, explore different approaches, and have something I can walk through during interviews.

# What’s the idea?
The idea is to help a business manage its purchasing process in one place, from requesting something to receiving it and processing the payment.

The main flow will look something like this:

## Purchase Request → Budget Approval → Supplier Quotation → Purchase Order → Delivery Inspection → Payment Claim → Completion

For example, a staff member requests equipment, a manager approves the budget, a procurement officer selects a supplier, and the supplier submits delivery details. Once someone checks and accepts the delivery, the payment claim can move forward.

Each person has their own role, tasks, and permissions.

# What I’m planning to build
## Users and access
- User invitations and account activation.
- Roles for administrators, requesters, approvers, procurement officers, suppliers, and finance staff.
- Permissions to control what each user can view and do.
- Activity history showing who changed something and when.
## Purchase requests and budgets
- Create purchase requests with items, quantities, estimated costs, and attachments.
- Submit requests for approval or return them for changes.
- Set approval limits based on the purchase amount.
- Track allocated, reserved, and remaining budgets.
- Prevent users from approving their own requests.
## Suppliers and quotations
- Manage supplier profiles.
- Invite suppliers to submit quotations.
- Compare prices and supporting documents.
- Record the selected supplier and the reason for the decision.
## Purchase orders and deliveries
- Create purchase orders from approved requests.
- Track full and partial deliveries.
- Let suppliers submit delivery details and documents.
- Use inspection checklists to accept or reject delivered items.
- Keep a record of outstanding items.
## Claims and payment certificates
- Submit payment claims with supporting documents.
- Review claims against accepted deliveries.
- Record approvals, rejections, and comments.
- Generate payment certificates and track claim progress.
This will track the payment workflow; actual bank payments are outside the initial plan.
## Reports, imports, and history
- Dashboards for pending tasks, budgets, deliveries, and claims.
- Searchable tables with filtering, sorting, and pagination.
- CSV imports with a preview of valid and invalid rows.
- Downloadable reports.
- Audit trails for important actions and status changes.

# What I want to learn
| Technology | What I’ll use it for |
|---|---|
| Laravel 12 | Backend logic, validation, permissions, queues, and database operations |
| Vue 3 | Interactive pages, reusable components, forms, and tables |
| Vue Router | Navigation between frontend pages |
| Tailwind CSS | Styling and responsive layouts |
| Laravel JSON API | Communication between the Vue frontend and Laravel backend |
| Laravel Sanctum | Authentication for the frontend and API access |
| MySQL | Application data, relationships, indexes, and transactions |
| Docker Compose / Laravel Sail | Running the local development environment |
| GitHub Actions | Running automated tests and build checks |

The frontend and backend will live in the same repository. Vue will communicate with Laravel through API endpoints under /api/v1.
I’ll use Node.js tooling for the frontend, while keeping the backend in Laravel.

# Some challenges I want to work through
A few situations I want the application to handle properly:
- What happens if two people approve purchases against the same budget at the same time?
- How do I prevent a double-click or repeated request from creating duplicate records?
- Can someone bypass an approval step by calling the API directly?
- What happens when only part of an order has been delivered?
- How should the system handle a failed notification or export?
- Can an import be retried without inserting the same records again?
These are things I want to build, test, and be able to explain.

# How I plan to build it
I’ll build this in stages so there’s a working flow early on.
1. Start with the basics
   Set up Laravel, Vue, Tailwind, Docker, authentication, and the database.
2. Build the first complete flow
   Create a purchase request, submit it, approve it, and track its status.
3. Connect the procurement process
   Add budgets, suppliers, quotations, and purchase orders.
4. Handle deliveries and claims
   Add partial deliveries, inspections, payment claims, and certificates.
5. Improve reliability
   Add background jobs, import validation, audit logs, and tests for important business rules.
6. Prepare the demo
   Deploy the application, add sample accounts and data, document the API, and record a walkthrough.
Support for multiple independent companies can come later, once the main workflow is working well.

# What I want this repository to include
- Clear setup instructions.
- Sample data and demo accounts for different roles.
- A database relationship diagram.
- API documentation with example requests and responses.
- Tests for permissions, approvals, budgets, and duplicate submissions.
- GitHub issues and milestones to track progress.
- Short notes explaining important technical decisions.
- A live demo and walkthrough video.

# A note on Laravel 12
I’m planning to start with Laravel 12. Since its security support ends on 24 February 2027, upgrading to Laravel 13 will be part of the project roadmap if the application stays publicly available. See the Laravel support policy.

# Project status
Currently in the planning stage. Features listed here are planned and will be updated as development progresses.

This is an independent personal project using fictional business data. It’s a place for me to apply what I already know, try things I haven’t done before, and show how I approach building a complete application.
