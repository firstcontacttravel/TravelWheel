# TravelWheel Workflow System: Implementation Plan

Status as of 2026-10-06. Branch: `feature/workflow-phase-0`.

## What we are building

A layer inside the existing Filament admin (`/admin`) that tracks how every customer booking and application is worked:

- who owns it,
- what step it is at,
- when it is due,
- who it was escalated to,
- what everyone did to it.

Escalations can go out to Linear and sync back.

### Decisions already made

| Topic | Decision |
|---|---|
| Scope | Customer bookings and applications only. Internal company processes (leave, expenses, purchasing) are out of scope. |
| Admin | One admin: the CEO. Manages staff, departments and settings, and sees everything. |
| Staff | Each person belongs to one department. A department can have any number of staff, and the CEO adds them. |
| Ownership | Any member of staff can claim any booking. |
| Escalation | Within the department or to another one, aimed at a department or a named person. There are two modes, chosen when escalating:<br>• **Ask for help** (default): the owner keeps the booking.<br>• **Hand-off**: the booking moves to the new owner. |
| Money actions | Only the Finance department (and the CEO). Finance acts without needing approval, but every action is recorded. |
| Changing workflows | Steps and the moves between them are defined in code by developers. Deadlines and default departments are set by the CEO in the admin. |
| Linear | Free tier, two teams: **IT department** and **Travelwheel** (everything else). Only escalations become Linear issues, never every booking. The admin stays the record of truth. No customer personal data is sent to Linear. |
| Departments | Flights, Visas, Finance, Customer Support, Ground & Airport Services, IT. |

### Principles

1. **Existing booking status stays the record of truth.** Work items follow it; the 3,776-line flight actions are not rewritten.
2. **Every action leaves a record:** who, which department at the time, what, on which booking, with what input. The record is append-only and nobody can edit it.
3. **One record per booking.** Status, owner and history are not duplicated across tables or systems.
4. **Each phase ships on its own**, with tests, and leaves the admin working.

---

## Phase 0: Foundation (staff, departments, permissions, action log)

**Goal:** know who everyone is, limit money actions to Finance, and record every action.

### 0.1 Data model ✅ built
- Migration `2026_10_06_000000_create_departments_and_activity_log.php`:
  - `departments` table: name, slug, description, `linear_team` (`it` or `travelwheel`), `linear_label`.
  - New columns on `users`: `department_id` and `deactivated_at`.
  - `activity_logs` table: user, department at the time, action, description, the booking it concerns, details (JSON). Append-only.
  - Fills in the six departments.
  - Moves existing staff over from `visa_role`: officer and administrator → Visas, finance → Finance, support → Customer Support.
- Models: `Department`, `ActivityLog` (with `ActivityLog::record()`, which removes passwords and secrets and shortens long text).

**Deliverable:** after migrating, every existing staff member has a department and nobody loses access.

### 0.2 Permission helpers ✅ built
- On `User`:
  - `isAdmin()`: true for `is_admin` or an email in `ADMIN_EMAILS`.
  - `inDepartment(...)`, `isDeactivated()`.
  - `canHandleMoney()`: true for the CEO or Finance staff.
- `canAccessPanel()`: the account must not be deactivated, and must be the CEO, have a department, or have an old role.
- The visa checks now accept the Visas department. Every member of staff can view the visa queues.
- `ReportingAccess` treats Finance and Customer Support departments the same as the old roles.
- `visa_role` is still honoured as a fallback until every account has a department.

### 0.3 Money actions limited to Finance ✅ built
- Flights: Mark paid, Mark fees paid, Verify SeerBit, Void ticket, Process refund. Getting a void or refund *quote* stays open to everyone.
- TravelFlex: Approve and Reject.
- These actions are blocked on the server too, not just hidden: an action that isn't allowed can't be run.

### 0.4 Automatic action log ✅ built
- `App\Support\Admin\ActivityRecorder` listens for Filament's `ActionCalled` event, so **every action that completes in the admin is logged automatically**. That includes actions written in the future.
- Creating and editing staff or departments is logged explicitly with what changed. Password changes show only as `[changed]`.

### 0.5 Team screens ✅ built
- New **Team** sidebar group, visible only to the CEO:
  - **Staff:** add a staff member (name, email, department, password); edit; deactivate or reactivate. Deleting is not possible, because people's names are on payments and tickets.
  - **Departments:** list with staff counts and where each lands in Linear; create and edit (the slug is fixed after creation). Each department page lists its staff and has an **Add staff** button. A department can only be deleted when it has no staff.
  - **Activity Log:** read-only, filterable by person, department and date, with a details view.

### 0.6 Finish and ship ✅ built (PR pending)
- [x] `tests/Feature/WorkflowStaffAccessTest.php`: 12 tests, all passing.
- [x] Full suite: 470 passed. The only failures are the 2 that were already failing before this work (`LegacyVoaImportTest`, `PricingAdminConfigurationTest`), both unrelated.
- [x] Pricing and settings edits are logged. `App\Filament\Concerns\RecordsPageActivity` is on every Create/Edit page and records the values when something is created, and the old and new values when something is edited. Passwords never appear in the log.
- [x] Browser check (local): the Staff and Departments lists work, and the CEO rows show "All departments". The department edit page, adding staff, and Finance-only actions are covered by automated tests instead, because the browser tab was too slow to drive reliably.
- [x] Built assets (`public/build`) rebuilt for the one new CSS class.
- [ ] Production rollout:
  - Run `php artisan migrate`. The migration moves existing staff into departments using their old visa roles.
  - The CEO then checks **Team → Staff** and gives a department to anyone shown as "None".
  - Remove the `visa_role` fallback in a later phase.
- [ ] PR → review → merge.

**Phase 0 is done when:** the CEO can manage staff and departments, only Finance can move money, and every admin action appears in the Activity Log.

---

## Phase 1: Work items and history (Flights and Visas)

**Goal:** every flight booking and visa application has an owner, a current step and a history.

### 1.1 Schema
- `work_items` table: the booking it belongs to, `service`, `stage`, `owner_id`, `department_id`, `priority` (low/normal/high/urgent), `due_at`, `state` (open/waiting/done/cancelled), `claimed_at`, `closed_at`. One per booking (enforced by the database).
- `work_item_events` table: `type` (stage_changed, claimed, released, reassigned, note, escalated, system), the person (or none for system events), old and new values, `body`, details.
- `activity_logs` stays the raw record of actions; `work_item_events` is the readable history shown to staff.

**Deliverable:** migrations and models, with the relationships on `FlightBooking` and `VisaApplication`.

### 1.2 Workflow definitions
- A `Workflow` contract with: list of stages, how to work out the current stage from the booking, default department, which stages count as closed, and the deadline setting for each stage.
- A `WorkflowRegistry` that maps each kind of booking to its workflow.
- A `WorkItemService` with: sync from the booking, claim, release, reassign, add note, close.

**Deliverable:** the service and contract, unit-tested.

### 1.3 Flights workflow
- Stages: `awaiting_payment`, `awaiting_transfer`, `ready_to_ticket`, `ticketing_failed`, `ticketed`, `post_ticketing`, `closed`. Worked out from `payment_status`, `booking_status`, `ticket_ordered` and any open post-ticketing request, using the same rules as the existing queue tabs.
- A model observer on `FlightBooking`, `PostTicketingRequest` and `TicketingRecord` re-syncs the stage and writes a `stage_changed` event.
- Default department: Flights. The `awaiting_transfer` stage suggests Finance.

### 1.4 Visas workflow
- Stages follow the existing `VisaApplicationTransitionService` statuses (submitted → … → issued).
- `assigned_to` becomes the work item's owner, kept in sync both ways.
- Existing status history and internal notes appear in the timeline.

### 1.5 Backfill
- `php artisan workflow:backfill`: creates work items for existing open flights and visas. Safe to run more than once.

### 1.6 Work panel on booking pages
- On the Flight Booking and Visa Application view pages, show: owner, department, stage, due time, priority.
- Actions: **Claim**, **Release**, **Reassign** (to a person or department), **Add note**, **Set priority**.
- The timeline lists the newest first and combines work item events with the relevant activity log entries.
- Add **Owner** and **Stage** columns and a **Mine / Unclaimed** filter to both tables.

### 1.7 Tests
- Stages are worked out correctly for every status combination.
- Rules for claiming and reassigning; every change writes an event.
- Running the backfill twice changes nothing the second time.

**Phase 1 is done when:** every flight and visa shows who owns it, what step it's at and its full history, and anyone can claim it.

---

## Phase 2: "My Work" page and escalations

**Goal:** one place where staff see their work, and escalation in both modes.

### 2.1 Notifications
- Add Laravel's `notifications` table and turn on Filament database notifications (the bell in the top bar).
- Important notifications also go by email through the existing `NotificationOutbox`.

### 2.2 Escalation service
- `escalations` table: work item, `mode` (help/handoff), who escalated, target department and/or person, `reason`, `priority`, `status` (open → accepted → resolved, or declined), `resolution_note`, timestamps, and the Linear fields (filled in phase 3).
- `EscalationService`:
  - **Hand-off:** ownership moves to the target once they accept. If the target is a department, anyone in it can accept.
  - **Ask for help:** the owner stays; the target resolves and the escalation returns to the owner with a note.
  - Declining needs a reason and goes back to the person who escalated.
- Every step writes a work item event and a notification.

### 2.3 Escalate dialog
- On the booking page: mode (help is the default), department, person (optional, limited to that department), reason (required), priority.
- An **Escalations** section on the booking lists open and past escalations, with Accept, Resolve and Decline.

### 2.4 "My Work" page
- A top-level page and the first link in the sidebar. Tabs:
  - **Mine**
  - **My department**
  - **Unclaimed**
  - **Escalated to me / my department**
  - **Escalated by me**
  - **Overdue**
- Covers every service; each row links to the booking.
- The sidebar link shows a count of open items assigned to the person or their department.

### 2.5 Dashboard
- The "broken" and "waiting" signals on the operations dashboard read from work items instead of queries written by hand for each service.

### 2.6 Tests
- Both escalation modes from start to finish: accept, decline, resolve.
- Notifications go to the right people.
- The My Work tabs show the right items.

**Phase 2 is done when:** staff start their day on My Work, and any booking can be escalated to a person or department in either mode.

---

## Phase 3: Linear

**Goal:** escalations that need tracking show up in Linear and sync back.

### 3.0 Needed from you
- A Linear API key, created by someone with Linear admin rights.
- The webhook signing secret.
- Confirmation of the free-tier limit on active issues (believed to be 250; also check whether archived issues count toward it).

### 3.1 Linear client
- `LinearClient` using Linear's API, configured in `config/services.php` and `.env`.
- A **Test connection** button.

### 3.2 Mapping
- Department → Linear team (IT or Travelwheel) and label. The table already exists from phase 0.
- Staff → Linear user, matched by email, with a manual override.

### 3.3 Creating issues
- **Escalations to IT always create a Linear issue.** For other departments it's an "Also create in Linear" checkbox.
- The issue contains: booking reference, service, stage, escalation reason, priority, and a link back to the booking in the admin. **No passport numbers, payment details or contact details.**
- Created by a queued job. If Linear is down, the escalation still works and the issue is created on retry.

### 3.4 Syncing back
- `POST /webhooks/linear` checks Linear's signature and ignores repeat deliveries (`linear_webhook_receipts` table).
- Issue completed or cancelled → the escalation is resolved in the admin.
- Linear comments → appear in the booking's history.
- Payloads are small, well under the hosting's 1 MiB request limit.

### 3.5 Keeping under the free-tier limit
- When an escalation is resolved in the admin, the Linear issue is closed and archived.
- A daily check counts active issues and warns the CEO before the limit is reached.

### 3.6 Tests
- Linear calls are faked: issue creation, signature checking, completing and commenting, repeat deliveries ignored, Linear outages.

**Phase 3 is done when:** an IT escalation appears in Linear within seconds, and closing it in Linear closes it in the admin.

---

## Phase 4: Workflows for the remaining services

**Goal:** replace the free-for-all "Change status" dropdown on every other service with proper steps.

### 4.1 Separate payment from fulfilment
- Services that use `payment_status` for everything (Car Hire stores `confirmed` and `completed` there) get a separate `fulfilment_status`. Existing values are moved over by migration.

### 4.2 Workflows, one group at a time

| Group | Services | Default department |
|---|---|---|
| Ground | Car Hire, Transfers (including driver assignment) | Ground & Airport Services |
| Airport | Lounge bookings, Protocol bookings | Ground & Airport Services |
| Cargo | Air Cargo | Ground & Airport Services |
| Support requests | Yellow Card, Extra Luggage, Flight Assist, Visa Confirmation | Customer Support |
| Insurance | Insurance purchases and quotes | Customer Support |
| TravelFlex | Applications | Finance |

For each group:
- [ ] A workflow class and stages.
- [ ] Move buttons that ask for the right information (for example, a cancellation reason).
- [ ] Moves to `paid` limited to Finance.
- [ ] The booking work panel.
- [ ] Backfill.
- [ ] Tests.

### 4.3 Remove the old dropdowns
- Delete the `changeStatus` actions once each group's replacement is live.

**Phase 4 is done when:** every service appears on My Work with an owner, steps and history, and nobody can jump a booking to any status at will.

---

## Phase 5: Deadlines and automatic escalation

**Goal:** late work is visible and gets escalated automatically.

### 5.1 Workflow Settings page (CEO only)
- Deadline for each service and step, and the default department for each service. Stored in `AppSetting`, so changes apply without a deploy.

### 5.2 Due times
- `due_at` is set when a booking enters a step, using that step's deadline.
- Flights use the airline's `tkt_time_limit` when it is sooner.

### 5.3 Deadline monitor
- `workflow:check-sla` runs every 5 minutes on the existing scheduler:
  - At 75% of the time allowed: notify the owner.
  - When the deadline passes: mark it as missed, notify the owner and the department.
  - Still unresolved after a further set time: notify the CEO.

### 5.4 Showing deadlines
- Overdue and due-soon markers on My Work, the booking tables and the dashboard.

**Phase 5 is done when:** nothing goes past its deadline without the right people being told.

---

## Phase 6: Workload reporting

**Goal:** the CEO can see how work is flowing.

### 6.1 Reports
- Time spent in each step.
- Deadlines met vs missed.
- Bookings handled per person and per department.
- Escalations by department and how long they took to resolve.

Shown on the existing Reports page, built on the existing reporting platform (`ReportingFact`).

### 6.2 Staff activity summary
- Per person: actions taken, items closed, escalations raised and resolved, over a chosen period.

**Phase 6 is done when:** the CEO can answer "who is overloaded, where do bookings get stuck, and are we meeting deadlines?" from the Reports page.

---

## Applies to every phase

- **Tests:** each step adds feature tests. The full suite must pass, apart from the two known failures, before merging.
- **Branches:** one branch per phase (`feature/workflow-phase-N`), with a PR into `main`.
- **Hosting limits:** request bodies are capped at 1 MiB, so dialogs and pages must stay small. Scheduled jobs use the existing scheduler and its heartbeat check.
- **Customer data:** nothing beyond the booking reference and a link leaves the system, whether to Linear or to emails.
- **Retiring `visa_role`:** once every account has a department, remove the fallback and the column (end of phase 2 at the latest).
