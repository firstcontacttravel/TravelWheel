# TravelFlex Repayments: Implementation Plan

Status as of 2026-11-04: **Phases 1, 2 and 3 pushed to `main`. Staging and production rollout not confirmed.** Phases 2 and 3 were committed together, so Finance needs to record repayments already received before they deploy, or as soon after as possible. Each one is finished, tested and checked on staging before the next starts.

## Why

Customers get a "Due soon" email every day, forever, once an instalment is overdue. TravelWheel also has no record of whether an instalment has been paid, so paying doesn't stop the emails.

| Problem | Where |
|---|---|
| Any past date counts as `overdue`, with no limit | `TravelFlexApplicationService.php:147` |
| The duplicate check includes today's date, so every day is a new email | `TravelFlexApplicationService.php:155-161` |
| `diffInDays` returns a float, so the `=== 3`, `=== 1` and `=== 0` checks never match and advance reminders never send | `TravelFlexApplicationService.php:142-146` |
| "Due soon" wording is used even when overdue | `travelflex-repayment-reminder.blade.php` |
| `reviewed` applications get reminders, and booking status is never checked | `TravelFlexApplicationService.php:112-113` |
| No instalment payment record exists | - |
| The admin plan table reads `amount`, but the schedule stores `total`, so Amount shows `-` | `TravelFlexPresentation.php:151` |

## Decisions

| Topic | Decision | Source |
|---|---|---|
| Overdue emails to the customer | One the day after the due date, a final one at 7 days, then stop | Agreed 2026-11-04 |
| Overdue alert | Goes to both TravelWheel and Fast Credit | Agreed 2026-11-04 |
| Mark instalment paid | Admin action | Agreed 2026-11-04 |
| Who can mark paid or reverse | `canHandleMoney()`: Finance and the CEO | Existing money-action rule |
| When reminders apply | Application and financing approved, deposit paid, booking still live | Proposed |
| Which bookings count as not live | `cancelled`, `failed`, `hold_expired_review`, `ticketing_failed` | Proposed |
| Partial payments | Full payments only. An instalment is paid or not, and a part-payment goes in as a note on the application | Agreed 2026-11-04 |
| Payment confirmation email | Sent to the customer automatically when an instalment is marked paid | Agreed 2026-11-04 |
| Fast Credit alert address | `MAIL_TRAVELFLEX_PROVIDER`, the same address that receives application packages | Agreed 2026-11-04 |

## Before Phase 3 ships

1. **Check `MAIL_TRAVELFLEX_PROVIDER` on production.** Its default in `config/mail.php` is `loans@travelwheel.com`, which isn't a Fast Credit address.
2. **Check it on staging.** It must not be Fast Credit's real address when Phase 3 reaches staging, or Fast Credit will get alerts about test bookings.

---

## Phase 1: Stop the repeat emails

Small and urgent. Needs no admin screens and no new tables.

1. **Fix the day comparison.** Cast `diffInDays` to an int so the 3-day, 1-day and same-day reminders start sending.
2. **Replace the open-ended overdue rule with fixed stages:**

   | Days relative to due date | Stage | Sends |
   |---|---|---|
   | 3 before | `due_in_3_days` | Once |
   | 1 before | `due_tomorrow` | Once |
   | Due date | `due_today` | Once |
   | 1-6 late | `overdue_first` | Once, on the first run in this range |
   | 7-13 late | `overdue_final` | Once, on the first run in this range |
   | 14+ late | none | Nothing |

   The overdue stages use ranges rather than exact days so a scheduler outage can't lose them. The staging cron has been broken before. The 14-day limit stops long-overdue instalments from suddenly emailing customers on deploy.
3. **Change the duplicate key** to `travelflex-reminder:{application}:{index}:{due date}:{stage}`, removing today's date.
4. **Tighten who gets reminders:** `application_status` and `financing_status` both `approved`, `deposit_status` and `payment_status` both `paid`, and a linked booking that is still live. `payment_status` only reaches `paid` once the fees are in as well.
5. **Write correct wording per stage.** Advance reminders keep "coming up". Overdue emails get their own heading, badge and intro, and drop "before the due date". The final email says this is the last automatic reminder.
6. **Cancel queued old reminders.** A data migration sets unsent `travelflex_repayment` rows in `notification_outboxes` to `cancelled`, which `processPending` ignores. Without this, old emails could still go out after the deploy. The health panel and triage widget don't count cancelled rows as stuck mail, and the outbox page hides "Retry now" on them.
7. **Add `--dry-run`** to `travelflex:send-repayment-reminders`. It lists what would send and what is skipped as 14+ days overdue, and sends nothing.

**Tests** (new `tests/Feature/TravelFlexRepaymentRemindersTest.php`, with frozen dates):
- Each stage sends on the right day, and only once across repeat runs.
- A 30-day-overdue instalment sends nothing.
- An instalment first seen at 8 days late gets only the final email.
- Rejected, reviewed-only, deposit-unpaid and cancelled-booking applications send nothing.
- Each stage renders the right wording, and `--dry-run` sends nothing.

**Ship checklist:**
1. Run `--dry-run` on staging and review the list.
2. Preview the wording with the existing `PreviewMail` command.
3. Confirm TW-5EQVL4CI's daily email stops after the next 08:00 run.
4. Deploy to production, running `--dry-run` there first.

**Known limit until Phase 2:** customers who have paid can still get a reminder, so the "ignore this if you've already paid" line stays.

---

## Phase 2: Mark instalment paid

1. **New table `travel_flex_instalment_payments`:** `travel_flex_application_id`, `instalment_index`, `due_date`, `label`, `amount`, `paid_on`, `payment_reference`, `note`, `recorded_by`, `reversed_at`, `reversed_by`, `reversal_reason`, timestamps.

   This is a separate table because `repayment_plan` is encrypted JSON that gets regenerated at the deposit step (`FlightBookingController.php:3243-3250`). A table can be queried, and a payment can't be overwritten. The action is only offered once the deposit is paid, so the schedule is settled by then.
2. **New `TravelFlexRepaymentService`** with `schedule()`, `markPaid()` and `reverse()`. `schedule()` returns each instalment with its status: paid, due, overdue or upcoming. Writes run in a transaction with a row lock, so two people can't record the same instalment twice.
3. **"Mark instalment paid" action** on the application page and in the table's Actions menu. The form picks an unpaid instalment, with amount shown from the schedule, and takes date received (required), payment reference (required) and note. It needs `canHandleMoney()`, and is exempt from the `WorkAccess` booking-ownership rule because repayments arrive long after ticketing, when the booking usually belongs to someone in Operations. `ActivityRecorder` logs it automatically.
4. **"Reverse payment" action** with a required reason, for mistakes. It keeps the row and sets `reversed_at`, so the money history stays intact. Reminders resume for that instalment if it's still within a stage.
5. **Update the Repayment Plan table:** add Status, Paid on and Reference columns, show "Repaid in full" when every instalment is paid, and fix the Amount bug.
6. **Connect it to reminders.** Paid instalments are skipped. Marking one paid also cancels its queued reminders, so a retry can't email the customer afterwards. The "no action is needed if you have already paid" line stays, because Finance may not have recorded a payment yet when a reminder goes out.
7. **Customer confirmation email:** one durable email per payment, sent automatically, with instalment, amount, date and what's left to pay.

**Tests:**
- Finance and the CEO can mark paid; other staff can't.
- The same instalment can't be recorded twice.
- A paid instalment gets no reminders, and its queued ones are cancelled.
- A reversal restores the reminders and keeps the record.
- Regenerating a plan before the deposit is paid leaves no orphaned payments.
- The plan table shows the right amounts and statuses.

**Ship checklist:**
1. Run the migration on staging.
2. Mark and reverse a test instalment, then confirm reminders stop and resume.
3. Deploy, then have Finance record repayments already received. Phase 3 depends on this.

---

## Phase 3: Overdue alert to TravelWheel and Fast Credit

This comes after Phase 2 so that Fast Credit isn't told a customer is overdue when they've already paid.

1. **New `TRAVELFLEX_OVERDUE_ALERT` email** in `DurableMailService`. It's addressed to `mail.travelflex_provider` with `mail.travelwheel_address` copied, the same pattern as the provider package.
2. **Send it once per instalment, 7-13 days late,** alongside `overdue_final`. Duplicate key: `travelflex-overdue-alert:{application}:{index}:{due date}`. If the instalment is marked paid first, no alert goes out.
3. **Keep the content short:** booking reference, customer name, email and phone, instalment label, amount, due date, days overdue, instalments paid so far, and a link to the admin page. No BVN, bank details or documents.
4. **Show the alert on the application page,** so staff can see Fast Credit has been told.
5. **Hand over the backlog.** Instalments already 14+ days overdue get no automatic alert, so `--dry-run` lists them. Once Finance has marked what's paid, the team sends Fast Credit what's left.

**Tests:**
- The alert goes to both recipients, once, on day 7.
- A scheduler outage is caught up by day 13.
- An instalment paid before day 7 sends no alert.
- 14+ days late sends no alert.
- The email contains no BVN or bank details.

**Ship checklist:**
1. Confirm staging's `MAIL_TRAVELFLEX_PROVIDER` is not Fast Credit's real address, then test there.
2. Confirm the production address and deploy.
3. Tell Fast Credit to expect these alerts.

---

## Not in this plan

- Customers paying instalments online or viewing them in their account.
- Automatic payment matching from the gateway or bank.
- An "all overdue repayments" admin queue. The plan is encrypted, so this would need one database row per instalment. Add it if the alerts prove too little.
- Partial payments. They can be added later without losing any records.
