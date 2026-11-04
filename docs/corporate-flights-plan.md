# TravelWheel Corporate Flights: Implementation Plan

Status as of 2026-11-04: **Phases 1-3 pushed to `main`, staging check not confirmed. Phases 4-7 not started.** Each phase is meant to be checked on staging before the next one starts. Phases 1-3 need that check together.

## What we are building

A login-gated corporate section on the main site, using the existing flight search and booking engine. Companies join by invitation or approved request, book at their own rates, and pay from a company wallet or per booking.

| After phase | What a company can do |
|---|---|
| 1 | Join, sign in and invite bookers. No booking yet |
| 2 | Search and book at company rates, paying by card or transfer, and see its bookings |
| 3 | Top up a wallet and see its balance |
| 4 | Pay from the wallet and receive refunds into it |
| 5 | Reuse saved travellers and add cost codes |
| 6 | Download invoices, statements and exports |
| 7 | Get an account manager and priority handling, and have TravelWheel book for it |

Phases 1-7 are the launch scope. A pilot company could start after Phase 4, with invoices issued by hand until Phase 6.

## Decisions

| Topic | Decision | Source |
|---|---|---|
| First customers | Small and mid-size companies | Agreed 2026-11-04 |
| Joining | Single-use, expiring invite link to a named contact, plus a public request form that admin approves | Agreed 2026-11-04 |
| Checks | Company name, CAC number and contact, then admin approval. No document check | Agreed 2026-11-04 |
| Roles | Company Admin and Booker. Bookers see their own bookings, Admins see all | Agreed 2026-11-04 |
| Payment | Wallet, or card or transfer per booking. No credit at launch, but the ledger must allow it later | Agreed 2026-11-04 |
| Top-ups | Bank transfer confirmed by Finance, or card | Agreed 2026-11-04 |
| Low balance | Pay the whole fare per booking, or top up first. No split payments | Agreed 2026-11-04 |
| Wallet charge | At booking, returned automatically if the reservation fails (see "Failed reservations" in Phase 4) | Agreed 2026-11-04, detail proposed |
| Fare rise | Show the new price. The booker accepts or goes back | Agreed 2026-11-04 |
| Refunds | Back to the wallet, with payout through Finance on request | Agreed 2026-11-04 |
| Pricing | Rate per company. Admin is warned if it is above the public rate | Agreed 2026-11-04 |
| Fee display | Separate service fee line on invoices, total in search results | Agreed 2026-11-04 |
| Who controls money | CEO sets markups and credit limits, Finance confirms top-ups, everything logged | Agreed 2026-11-04 |
| Flights | All APIs, no TravelFlex | Agreed 2026-11-04 |
| Portal | Section of the main site (`/business`) with its own login | Agreed 2026-11-04 |
| Login security | Email code for company Admins, password for Bookers, code optional for Bookers | Agreed 2026-11-04 |
| Traveller profiles | Saved encrypted, passport masked after entry, changes logged | Agreed 2026-11-04 |
| Cost codes | Optional free text | Agreed 2026-11-04 |
| Support | Account manager, workflow priority, staff can book for a company, logged | Agreed 2026-11-04 |
| Rate fallback | Company rate, then default corporate rate, then public rate | Proposed |
| Who runs the admin Corporate actions | CEO only: add, approve, reject, invite, revoke, suspend, reactivate. Other staff can view. One method to widen: `CompanyResource::canManageAccounts()` | Proposed, built in Phase 1 |
| Rejected requests | No email is sent. Staff contact the company themselves if needed, and a rejected company may apply again | Proposed, built in Phase 1 |
| Password rule | At least 10 characters, with letters and numbers | Proposed, built in Phase 1 |
| Card top-up fee | A percentage on top, paid by the company and never credited to the wallet. Set by the CEO on the Default Rates page. Card top-ups stay off until it is set | Agreed 2026-11-04 |
| Who can top up | Company Admins only. Bookers don't see the wallet | Proposed, built in Phase 3 |
| Top-up limits | ₦1,000 to ₦100,000,000 each | Proposed, built in Phase 3 |
| Saved travellers | Shared across the company, masked for everyone | Proposed |
| Signed-in corporate user on public pages | Always sees company rates, so one browser never shows two prices | Proposed |

## Principles

1. **One booking engine.** Corporate bookings stay in `flight_bookings` and go through `FlightBookingController`, with no copied flow.
2. **Public customers notice nothing.** Public prices, TravelFlex and checkout stay the same, and existing tests must keep passing.
3. **The company comes from the server.** It is never read from the browser. Without a signed-in company or a saved company on the search or booking, the price is public.
4. **The ledger is the record of money.** Entries are append-only, and a stored balance is only a copy.
5. **Corporate and staff logins never mix.** A company login cannot reach `/admin`.
6. **Every money and account action is logged**, whether by staff or a company user.

## Sort out before building

| # | Item | Who | Blocks |
|---|---|---|---|
| 1 | VAT treatment of the service fee, and when an invoice should be issued | Accountant | Phase 6 |
| 2 | Storing passport details long term, under Nigerian data protection rules | Legal or compliance adviser | Phase 5 |
| 3 | Ticket and change turnaround you can promise on SkyLink, after confirming these are still manual | Operations | Phase 7 |
| 4 | ~~Who pays the gateway fee on card top-ups~~ Decided 2026-11-04: the company pays a percentage on top. The CEO still needs to set the percentage | CEO | Card top-ups |
| 5 | Whether wallet bank transfers should go to the same accounts as TravelFlex (`TRAVELFLEX_BANK_*`), which is what the wallet page shows | Finance | Phase 3 going live |

---

## Phase 1: Companies, invitations and login

**Goal:** a company can be approved, invited and signed in. Bookings are not affected.

**Build:**
- **Tables:**
  - `companies`: name, CAC number, contact, status (`requested`, `invited`, `active`, `suspended`, `rejected`), `account_manager_id`.
  - `company_users`: company, name, email, password, role, `deactivated_at`.
  - `company_invitations`: company, email, role, hashed token, expiry, accepted and revoked times, who sent it.
- **Separate login:** a `corporate` guard, a `company_users` provider and its own password reset. Sign-in lives at `/business/login`, and signed-out visitors to `/business` go there rather than to the admin login.
- **Public request form:** creates a `requested` company and tells staff. It has rate limiting and spam protection, and its response never reveals whether a company already exists.
- **Admin "Corporate" section:** list, approve, reject, invite, resend or revoke an invite, suspend.
- **Accepting an invite:** the link works once and expires. The named contact becomes the company Admin.
- **Team page for company Admins:** invite a Booker, deactivate a user, change a role. A company can't lose its last Admin.
- **Email codes:** required for Admins and optional for Bookers. Codes are hashed, short-lived and limited to a few attempts. They send immediately rather than through the queue, because a stuck scheduler would lock people out.
- **Immediate cut-off:** middleware signs out suspended companies and deactivated users on their next request.
- **Logging:** `activity_logs` only records staff today, so it gains a `company_user_id`.

**Deliverables:**
- [x] Migrations and models for companies, users and invitations
- [x] `/business` login, password reset, email code and an empty dashboard
- [x] Public request form
- [x] Admin Corporate section with its invite emails
- [x] Company team page
- [x] `CorporateAccessTest`: a company user can't open `/admin`, and staff can't sign in at `/business/login`
- [x] `CorporateInvitationTest`: a link can't be reused, expired or revoked links fail, and suspension takes effect immediately

**Ship checklist:**
1. Run `php artisan migrate --force`.
2. Set `CORPORATE_EMAIL` if request alerts shouldn't go to `RESERVATIONS_EMAIL`.
3. Keep the request form link off the public site until launch. The form is live at `/business/request` but nothing links to it.
4. Invite a test company on staging from start to finish, including real delivery of the invitation and sign-in code emails.
5. Pages were checked by eye on desktop and at phone width locally. The migration has run up and down on a temporary MySQL database.

---

## Phase 2: Company rates and booking

**Goal:** a signed-in company sees its own rates and can book by card or transfer. This phase carries the most risk of the wrong price.

**Build:**
- **Rates:** `company_service_charges` uses the same route and cabin table as `FlightServiceCharge`, and a row with no company holds the default corporate rate. The admin screen is limited to the CEO, shows the public rate alongside, warns if a rate is above it, and logs every change.
- **Required price context:** `FlightMarkup::apply()` takes a required argument saying whose rate applies, so a missed call fails instead of quietly charging the public rate. The static charge cache becomes per rate table.

  | Call site | Where the company comes from |
  |---|---|
  | `FlightController` (search) | Signed-in user |
  | `FlightPage` (results page) | Signed-in user |
  | `FlightBookingController::select` | Signed-in user |
  | `FlightSupplierSearchController` | New `flight_searches.company_id`, because this route has no session |
  | `SkylinkFareRefresh` | `flight_bookings.company_id` |
  | `FlightPlatformHold` | `flight_bookings.company_id` |

- **Search separation:** sign-in and sign-out clear stored search results and checkout data from the session, so corporate fares can't carry over into public use, or the reverse.
- **Booking link:** `flight_bookings` gains `company_id` and `company_user_id`, filled in by `_persistBooking`.
- **Suspended companies:** checked at select, book and pay, using the same graceful refusal as a switched-off API.
- **Fare rise:** `SkylinkFareRefresh` and `FlightPlatformHold` currently stop on a higher fare. Corporate bookings get a page showing the old and new prices, with accept or go back. The fare is priced again on accept, because of SkyLink's five-minute pricing window. Public customers keep today's behaviour.
- **No TravelFlex:** hidden on the pages and refused on the server, including the `intent` value and TravelFlex routes.
- **Rate limits:** the search, select and book limits count per user for signed-in companies, because an office shares one IP address.
- **Emails:** the booking contact defaults to the booker, with a copy to the company Admin.
- **Company bookings page:** Bookers see their own and Admins see all, with search, status and e-ticket download. No supplier names appear.
- **Admin:** a company column and filter on Flight Bookings.

**As built:**
- Searching uses the existing public search page. Signed-in companies get company rates and a "Booking for" badge there.
- The session rules live in the `CorporateFlightSession` middleware, which runs on every web request. It skips the SeerBit routes, because a payment already made must still complete.
- A fare can only rise before payment on SkyLink's re-price, so that is where the accept page applies. `FlightPlatformHold` also takes an accepted price because a re-price can fall back to it. Its TravelFlex use is unchanged.
- Admin copies are added in `DurableMailService`, so they cover checkout, admin and ticketing emails.

**Deliverables:**
- [x] Rate table and CEO screen with the above-public warning
- [x] Required price context at every call site
- [x] Portal search and full booking by card, bank transfer and hold. Tested through real routes on SkyLink with faked responses. TravelNext shares the code but has no end-to-end corporate test, so it depends on the sandbox check below
- [x] Accept-new-price page
- [x] Company bookings page
- [x] `CorporatePricingTest`, with both sequential and parallel search covered:
  - The same price at search, select, re-price and payment
  - The company's rate survives the SeerBit return trip
  - A public visitor never gets a corporate rate, including after sign-out in the same browser
  - The fallback order works
- [x] `CorporateBookingTest`: isolation between companies, Booker versus Admin visibility, TravelFlex refused, suspended company refused
- [x] Existing flight tests pass, and public prices match golden values

**Ship checklist:**
1. Run `php artisan migrate --force`.
2. Run a real sandbox booking on each API, comparing the charged amount with the price shown. This is the first check against real suppliers, and the first corporate booking on TravelNext.
3. Compare public and corporate prices for one route in two browsers.
4. Check the "Booking for" badge doesn't cover a button on the phone checkout pages, and look at the accept-new-price page, which hasn't been seen in a browser.

---

## Phase 3: Wallet and top-ups

**Goal:** a company can hold a balance, and every naira can be traced. Bookings can't spend it yet.

**Build:**
- **`company_wallet_entries`:** company, type (`top_up`, `booking_debit`, `booking_return`, `refund`, `payout`, `adjustment`), signed amount, balance after, booking or top-up link, who did it, reason, and a unique key that stops double posting. Rows can't be edited or deleted.
- **`CompanyWallet` service:** the only code that writes entries. It locks the company row inside a transaction, so two bookers can't spend the same money. `companies.wallet_balance` is a stored copy.
- **Ready for credit:** `companies.credit_limit` defaults to 0, and spending is allowed down to minus that limit. Nothing sets it at launch, so a wallet can't go below zero.
- **`company_wallet_top_ups`:** amount, method, reference, status, who confirmed it.
- **Bank transfer:** the company Admin records a transfer and gets a reference. Finance sees a queue under the Finance menu and confirms or rejects, protected by `canHandleMoney()`.
- **Card:** a new SeerBit flow for top-ups. `_prepareSeerbitBooking` assumes a booking, so this needs its own start and callback. The credit is applied after verifying with SeerBit and is safe against repeated callbacks.
- **Company wallet page:** balance, history and top-up.
- **Admin:** per-company ledger, plus Finance adjustments that require a reason.
- **Health check:** `SystemHealth` flags any company whose stored balance doesn't match its ledger.

**As built:**
- SeerBit has one webhook address, so top-ups arrive at `seerbitWebhook` and are recognised by reference.
- Finance can also use "Check with SeerBit" on a card top-up whose return trip and webhook both went missing.
- A card fee is recorded on the top-up. It isn't in the reports yet, so Phase 6 needs to invoice it and decide how it is counted.

**Deliverables:**
- [x] Ledger, wallet service and top-up tables
- [x] Bank transfer and card top-ups
- [x] Finance queue and admin ledger
- [x] Company wallet page
- [x] Balance health check
- [x] `CompanyWalletTest`: spending from stale copies can't overdraw, repeated callbacks credit once, only Finance can confirm, rows can't be changed. Tests run on SQLite, which doesn't exercise row locks, so true simultaneous spending is only covered by the design

**Ship checklist:**
1. Run `php artisan migrate --force`.
2. Set the card fee on the Default Rates page.
3. Top up on staging by both methods, including a real SeerBit webhook. Confirm the webhook reaches `/payments/seerbit/webhook` for a top-up.
4. Confirm top-ups don't appear as revenue in reports.
5. Open a company page in Corporate a few times. It came up blank once in local preview and couldn't be reproduced.

---

## Phase 4: Pay by wallet, and refunds

**Goal:** booking from the wallet is instant, and money comes back correctly when something fails. A pilot can start after this phase.

**Build:**
- **New payment method `wallet`:** only offered to signed-in companies. The server re-checks the company, the balance and the price just before debiting.
- **Pay-first fares (SkyLink, WebFare):** re-price, debit, then reserve using the same steps as after a card payment. There's no gateway redirect.
- **Hold fares (TravelNext public and private):** a wallet option on the payment page, then debit and order the ticket as `_completeHeldTicketPayment` does.
- **Low balance:** the page shows the shortfall and offers card, transfer or top-up. There are no split payments.
- **Failed reservations:**

  | Outcome | What happens |
  |---|---|
  | The supplier clearly refused | Money returns automatically, and the booker sees why |
  | Timeout or unclear answer | Money stays debited, and operations gets the ticketing alert, because the seat may exist |
  | TravelNext hold, ticket order failed | Money stays debited while operations retries. Finance returns it if the hold is abandoned |

  `_reserveSkylink` doesn't separate these outcomes today, so that distinction is part of this phase.
- **Refunds:** a Finance action, "Credit wallet", on company bookings, including those paid by card. It requires an amount and reason, can't exceed what was paid less earlier refunds, and is logged. It's separate from the supplier refund, because SkyLink refunds are handled by hand.
- **Payouts:** Finance records a payment to the company's bank, which debits the wallet.
- **Existing admin actions:** hide Mark paid and Verify SeerBit on wallet bookings, and show "Wallet" wherever the payment method appears, including e-tickets and reports.

**Deliverables:**
- [ ] Wallet payment on both APIs and all fare types
- [ ] Low balance page
- [ ] Automatic return and the "unclear" path
- [ ] Credit wallet and payout actions
- [ ] Admin, e-ticket, email and report labels
- [ ] `CorporateWalletPaymentTest`:
  - A double-click debits once
  - A clear failure returns the money
  - A timeout doesn't
  - Refunds can't exceed what was paid
  - Reports count a wallet booking once, and reflect its refund

**Ship checklist:**
1. Run sandbox wallet bookings on each API, including a forced failure.
2. Confirm the refund and payout steps with Finance.
3. **Start the pilot** with one friendly company, and reconcile its ledger by hand each week.

---

## Phase 5: Saved travellers and cost codes

**Goal:** repeat bookings take less typing.

**Build:**
- **`company_travellers`:** names, date of birth, gender, nationality, contact, frequent flyer number, and encrypted passport details, using the existing `EncryptedJson` cast.
- **Masking:** after saving, only the last digits are ever sent to the browser. They aren't placed in Livewire snapshots.
- **Using a profile:** checkout sends the traveller's ID, and the server adds the full passport details after confirming the traveller belongs to that company. `book()` then validates them as usual.
- **Replacing a passport:** staff and company users can enter a new one but can't view the old one. Changes are logged.
- **Expiry warning:** at checkout, when a passport is expired or close to expiry for the trip.
- **Save for next time:** an option at checkout, plus guest travellers who aren't saved.
- **Removal:** a company Admin can delete a traveller, and profiles are removed when a company closes, following the adviser's retention guidance.
- **E-tickets:** also go to the traveller's email if the profile has one.
- **Cost code:** optional free-text `cost_code` on `flight_bookings`, shown on the booking page and searchable.

**Deliverables:**
- [ ] Traveller table, management page and checkout picker
- [ ] Full passport handled only on the server
- [ ] Expiry warning and retention rule
- [ ] Cost code from checkout through to the booking page
- [ ] `CompanyTravellerTest`: no passport number in any page or snapshot, another company's traveller ID is refused, changes are logged

**Ship checklist:** complete item 2 of "Sort out before building" first, then check the rendered page source on staging for passport numbers.

---

## Phase 6: Invoices, statements and exports

**Goal:** a company's accounts team has what it needs without calling TravelWheel.

**Build:**
- **`company_invoices`:** number sequence and a fixed snapshot, so later rate changes don't alter issued invoices. Lines cover fare, service fee (`markup_amount`), extras and any VAT, following the accountant's answer.
- **Credit notes:** issued for refunds, linked to the original invoice.
- **PDFs:** built with the existing `barryvdh/laravel-dompdf`, following `ETicketPdfService`.
- **Monthly statement:** opening balance, entries and closing balance, from the ledger.
- **CSV exports:** bookings and wallet entries with cost codes. Bookers get their own bookings and invoices, and Admins get everything.
- **Admin:** view any invoice or statement, and a company filter in `ReportingAnalytics`.

**Deliverables:**
- [ ] Invoices and credit notes as PDFs
- [ ] Monthly statements
- [ ] CSV exports
- [ ] Company filter in admin reports
- [ ] `CorporateInvoiceTest`: fare plus fee plus extras equals the amount paid, numbers don't repeat, issued invoices don't change, and statements match the ledger

**Ship checklist:** the accountant reviews a sample invoice, credit note and statement before release.

---

## Phase 7: Support and launch

**Goal:** TravelWheel staff can look after companies, and the portal opens.

**Build:**
- **Account manager:** set per company, shown on the company's dashboard, and told about that company's bookings and problems.
- **Workflow priority:** corporate bookings start with higher `WorkItem` priority and a company badge in the queues. Confirm that wallet-paid bookings skip the workflow's payment-verification step.
- **Book for a company:** staff start from the company's admin page and use the normal portal with a visible "booking for" banner. The company's rate and wallet apply. The staff member is recorded on the booking and in the log, and the company sees "booked by TravelWheel". Passports stay masked.
- **Company dashboard:** balance, upcoming trips, recent bookings and the account manager's contact.
- **SkyLink promise:** the agreed turnaround appears in the booking emails.
- **Launch:** add `/business` and the request form to the public site.

**Deliverables:**
- [ ] Account manager assignment and alerts
- [ ] Priority and badge in the workflow
- [ ] Book-for-company with logging
- [ ] Company dashboard
- [ ] Public links, and a short staff guide
- [ ] `CorporateSupportTest`: only permitted staff can book for a company, the staff member is recorded, and a staff session never gains company login rights

**Ship checklist:**
1. Finish the pilot review: ledger reconciled, invoices accepted, no price mismatches.
2. Run a full production booking on a real company account.
3. Publish the links.

---

## After launch

Not in this plan, and designed for by the ledger and roles above:

1. Credit terms and monthly settlement, with fuller company checks
2. Approval before booking, with a re-approval rule when a fare rises
3. Travel policy: cabin and fare limits that warn or block
4. Spend reports for company Admins
5. Finance role for company accounts teams
6. Managed cost code lists
7. Unused ticket tracking and group bookings
8. Visas, lounges, car hire and other services on the same account
