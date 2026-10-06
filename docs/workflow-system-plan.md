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

## Phase 1: Work items and history (Flights and Visas) ✅ built

**Goal:** every flight booking and visa application has an owner, a current step and a history.

### 1.1 Schema ✅ built
- Migration `2026_10_07_000000_create_work_items_tables.php`:
  - `work_items`: the booking it belongs to (one per booking, enforced by the database), `service`, `stage`, `state` (open/waiting/done/cancelled), `owner_id`, `department_id`, `priority`, `due_at`, `claimed_at`, `closed_at`.
  - `work_item_events`: `type` (created, stage_changed, claimed, released, reassigned, moved, note, priority_changed), the person (none = system), from/to, `body`, details. Append-only.
- Models `WorkItem` and `WorkItemEvent`; `workItem()` on `FlightBooking` and `VisaApplication` (`HasWorkItem` trait).

### 1.2 Workflow definitions ✅ built
- `App\Workflow\Workflow`: stages (label, state, default department), `stageFor()` read from the booking's own columns, `dueAt()`, reference and admin link, optional owner column on the booking.
- `WorkflowRegistry` lists the workflows. Phase 4 adds the other services there.
- `WorkItemService`: `sync`, `claim`, `release`, `assign` (to a person, a department queue, or both), `addNote`, `setPriority`. Every change writes a history line.
- `SyncsWorkItems` re-syncs whenever a flight booking, post-ticketing request or visa application is saved, from any source (admin, webhook, reconcile job, checkout). **If work tracking fails, the error is reported and the booking still saves.**

### 1.3 Flights workflow ✅ built
- Stages: Awaiting payment → Confirm bank transfer (Finance) / TravelFlex review (Finance) / Awaiting TravelFlex deposit / Hold expired, rebook → Ready to ticket → Ticketing in progress / Ticketing failed → Ticketed, plus Change with supplier and Cancelled.
- Same rules as the existing queue tabs, so the two never disagree.
- An unowned booking moves to the queue of its new stage; an owned one stays with its owner.
- Due time = the airline's ticketing deadline (`tkt_time_limit`) while work is open.
- Only real changes (void, refund, reissue, cancel) count as "Change with supplier". Quotes don't: unanswered quotes stay "in process" at the supplier indefinitely (found in real data from May), which would have held finished bookings open.

### 1.4 Visas workflow ✅ built
- Stage = the application's status (draft, awaiting payment, submitted, under review, waiting on applicant, processing, approved, issued, rejected, cancelled, expired).
- The assigned officer and the work owner are the same person, kept in sync both ways. A claim through the Work menu goes through `VisaOperationsService::assign`, so the visa's own audit trail records it too.
- *Differs from plan:* the visa's existing status history and internal notes are not yet merged into the Work timeline; the timeline shows work events and admin actions.

### 1.5 Backfill ✅ built
- `php artisan workflow:backfill [--service=flights|visas]`, safe to repeat. Local run: 122 flights, 20 visas.

### 1.6 Work panel on booking pages ✅ built
- A **Work** section near the top of the Flight Booking and Visa Application pages: owner, queue, stage, status, priority, due time, and one history (work events plus admin actions, newest first).
- A **Work** menu in the page header: Claim (or Take over), Release, Reassign (person and/or department, with a note), Add note, Set priority. Open to every member of staff.
- Flight Bookings list: **Owner** column. Both lists: **Work** filter (Mine, My department, Unclaimed, Needs action, Overdue).
- *Differs from plan:* no separate Stage column, because the flight list's Queue column and the visa list's Status column already show it.

### 1.7 Tests ✅ built
- `tests/Feature/WorkflowWorkItemsTest.php`, 27 tests: every flight stage, queue moves, owned items staying put, supplier changes reopening and closing, quotes ignored, due times, failure isolation, the full claim/reassign/note/priority/release history, department moves, deactivated staff, visa owner sync both ways, the backfill, and the page actions, panel and filter.
- Full suite: 497 passed. The only failures are the 2 that were already failing before this work.

### 1.8 Ship
- [ ] Production rollout: `php artisan migrate`, then `php artisan workflow:backfill` once.
- [ ] PR → review → merge (after phase 0).

**Phase 1 is done when:** every flight and visa shows who owns it, what step it's at and its full history, and anyone can claim it.

---

## Phase 2: "My Work" page and escalations ✅ built

**Goal:** one place where staff see their work, and escalation in both modes.

### 2.1 Notifications ✅ built
- Laravel's `notifications` table and Filament's notification bell in the top bar, checked every 60 seconds.
- Escalation emails go through the existing outbox, using a new `DurableMailService::store()` that queues without sending on the spot. The person escalating never waits on the mail server, and the scheduled outbox run sends them within a few minutes.

### 2.2 Escalations ✅ built
- `escalations` table: work item, `mode` (help/handoff), `status` (open, accepted, resolved, declined, withdrawn), raised by, target department and/or person, priority, reason, response note, timestamps, and reserved `linear_issue_id`/`linear_issue_url` for phase 3.
- `EscalationService`:
  - **Ask for help** (default): the owner keeps the booking. The other side may accept ("I'm on it"), then **resolves with a note**, which goes back to whoever asked and to the owner.
  - **Hand-off:** whoever accepts becomes the owner, and the booking moves into their department's queue.
  - **Decline** needs a reason and goes back to whoever asked. **Withdraw** is only for whoever raised it.
  - Aimed at a person: only that person (or the CEO) can answer. Aimed at a department: anyone in it can.
  - A named person brings their department along. You can't escalate to yourself or to a deactivated account.
- Every step writes a line in the booking's history and sends a bell notification. Being escalated to, a resolution and a decline also send an email.
- Emails carry only the booking reference, its stage and the staff-written reason: no passenger, passport or payment details.

### 2.3 On the booking page ✅ built
- **Work → Escalate**: choose help or hand-off, department, person (optional), priority, and "What do you need?". Refused on the form if there is no department or person.
- An **Escalation** menu appears only when an escalation is waiting on you (Accept, Resolve, Decline) or was raised by you (Withdraw).
- The Work panel shows **Open escalations** above the history.

### 2.4 My Work page ✅ built
- The first item in the rail, next to the Dashboard (`/admin/my-work`), across every service.
- Tabs with counts: **Mine** (the default), **My department**, **Unclaimed** (needs action, nobody owns it), **Escalated to me**, **Escalated by me**, **Overdue**, **All open**.
- Search by booking reference across services. Filters by service, queue and priority. Claim from the row; click a row to open the booking.

### 2.5 Dashboard ✅ built
- The **Waiting** band now starts with your own work: **Your open work**, **Escalated to you**, **Unclaimed**, each linking to the matching My Work tab.
- "Open post-ticketing" now counts only real supplier changes, not unanswered quotes. It had been counting stale quotes from May.
- *Differs from plan:* the service queues (Awaiting transfer, Ready to ticket…) still use their own queries rather than reading from work items. They give the same answers, and moving them is not worth the risk until phase 4 brings every service in.

### 2.6 Tests ✅ built
- `tests/Feature/WorkflowEscalationsTest.php`, 15 tests:
  - both modes end to end; who may answer; declining and withdrawing;
  - invalid escalations refused; the email content and its delivery through the outbox;
  - the booking-page actions, the My Work tabs and search, and the dashboard counts.
- Full suite: 513 passed. The only failures are the 2 that were already failing before this work.

### 2.7 Ship
- [ ] Production rollout: `php artisan migrate`. The scheduler already runs the outbox every minute.
- [ ] PR → review → merge (after phases 0 and 1).

**Phase 2 is done when:** staff start their day on My Work, and any booking can be escalated to a person or department in either mode.

---

## Phase 3: Linear ✅ built (live webhook pending)

**Goal:** escalations that need tracking show up in Linear and sync back.

### 3.0 Linear access ✅ done
- `LINEAR_API_KEY` is set and checked against the live workspace. Read-only checks only:
  - **Connection:** TravelWheel workspace, as "First Contact" (a Linear admin).
  - **Teams:** IT Department (`IT`) and TravelWheel (`TRA`), both with Done and Canceled states.
  - **Issues:** 7 active; 11 including archived ones.
- `phpunit.xml` blanks the Linear key and secret, so the test suite can never reach the real workspace. Confirmed: the count is unchanged after the full suite.

### 3.1 Linear client ✅ built
- `App\Services\Linear\LinearClient`: create issue, move to Done or Canceled, comment, archive, count active issues.
- Teams, workflow states, labels and users are looked up by name or key and cached for an hour. No Linear IDs are hard-coded in config.
- `config/services.php` → `linear`: `api_key`, `webhook_secret`, team keys (`LINEAR_TEAM_IT=IT`, `LINEAR_TEAM_TRAVELWHEEL=TRA`), and the warning threshold (`LINEAR_ISSUE_WARNING_THRESHOLD=200`).

### 3.2 Mapping ✅ built
- Department → Linear team (IT or TravelWheel) and label, from the Departments screen. A missing label is created as a workspace label the first time it's needed, so Flights, Visas, Finance, Customer Support and Ground & Airport labels appear on first use.
- A named person is matched to their Linear account by email and becomes the assignee, if they have a seat.

### 3.3 Creating issues ✅ built
- **Escalations to IT always become an issue in the IT team.** For other departments, the Escalate dialog has an **"Also track it in Linear"** switch, which creates the issue in TravelWheel with the department's label.
- What the issue contains:
  - title: mode, booking reference, and a short form of the reason;
  - body: the reason, the booking reference and its service, its stage, who asked, the mode, and a link back to the booking in the admin;
  - priority: urgent, high, normal and low map to Linear's 1 to 4.
- **No passenger, passport or contact details.** The dialog warns staff not to type them into the reason.
- Created by a queued job, retried up to 3 times; the outcome is written into the booking's history ("Linear issue IT-12 opened", or a failure line).
- **If Linear is down, the escalation still goes through.** A test covers this, including when the queue runs jobs inline (`QUEUE_CONNECTION=sync`, as it does locally).

### 3.4 Syncing back ✅ built (needs the webhook registered)
- `POST /webhooks/linear` (no CSRF check, rate-limited). It refuses:
  - requests not signed with `LINEAR_WEBHOOK_SECRET`;
  - deliveries older than 60 seconds;
  - everything, with a 404, when no secret is set.
- A repeated delivery is applied once (`linear_webhook_receipts` table).
- **Issue completed in Linear:** the escalation is resolved ("Completed in Linear by …"), matched to staff by email when possible. A completed **hand-off** makes that person the owner, if they are staff here.
- **Issue cancelled in Linear:** the escalation is declined.
- **Comment in Linear:** appears in the booking's history. The admin's own comments carry a signature and are skipped, so nothing echoes back.

### 3.5 Staying under the free-plan limit ✅ built
- Closing an escalation in the admin (resolved, hand-off accepted, declined, withdrawn) moves its issue to Done or Canceled, comments with the outcome, and **archives** it. Closing it from Linear archives it too.
- `php artisan linear:status` checks the connection, teams and active issue count. It runs daily at 08:00 with `--warn`, and notifies the CEO in the admin once active issues reach 200.

### 3.6 Tests ✅ built
- `tests/Feature/WorkflowLinearTest.php`, 16 tests, all with Linear faked:
  - IT always vs other departments when asked; labels; assignee;
  - resolve, decline and archive; Linear down;
  - the form switch;
  - completed, cancelled, hand-off and comments from Linear; our own comment ignored; repeated delivery;
  - forged, unsigned and stale requests refused; no secret means no endpoint;
  - non-escalation issues ignored; the CEO warning.
- Full suite: 529 passed. The only failures are the 2 that were already failing before this work.

### 3.7 Ship
- [ ] Production: `php artisan migrate`; set `LINEAR_API_KEY` in production's `.env`.
- [ ] **Register the webhook** in Linear (Settings → API → Webhooks → New webhook):
  - URL: `https://<production domain>/webhooks/linear`
  - Events: **Issues** and **Comments**; teams: IT Department and TravelWheel
  - Copy the signing secret into production's `.env` as `LINEAR_WEBHOOK_SECRET`.
- [ ] Optional live check: raise one real escalation to IT and confirm the issue appears.
- [ ] Check production's `QUEUE_CONNECTION`. With `database` (as in `.env.example`), Linear calls run in the background through the scheduled queue worker, which is better. With `sync` they run during the request, but are still safe.

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
