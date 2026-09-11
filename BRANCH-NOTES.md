# `demo/skylink-only` — throwaway branch

**Do not merge this branch. Delete it when the SkyLink review is over.**

Purpose: give the SkyLink (247Travels) team a link where they can exercise
our integration against their API end to end, without TravelNext inventory
in the way and without touching the real codebase.

Branched from `feature/ui-redesign` at `00f445fe`, which is where all the
SkyLink work lives. `main` has none of it.

---

## Why the branch was necessary

On the main branch SkyLink is a *supplement*, not a supplier in its own
right. `FlightController::performSearch()` calls TravelNext synchronously
and **redirects away when that leg fails**, before `FlightPage`'s
`wire:init` SkyLink supplement is ever reached. So you cannot get a
SkyLink-only flow just by blanking `TRAVELNEXT_*` — the search dies first.

Everything from `select()` onward was *already* SkyLink-only
(`_selectSkylinkFare` → `book()`'s gateway branch → `skylink_reserve_full`
→ `_completeSkylinkReservation`). Only the search step was contaminated,
and that is what this branch changes.

## What changed

| File | Change |
|---|---|
| `FlightController::performSearch()` | Calls `SkylinkFlightService::search()` instead of TravelNext's `availability`; ~490 lines of TravelNext mapping removed. Writes `flightResultsStore` **and** `skylinkResultsStore` (the latter is where `_selectSkylinkFare()` resolves the chosen fare), plus `searchSessionId = ''`. Assigns each flight an `id`, which SkyLink's mapper does not set and the results page needs for its `x-for` key. |
| `FlightPage::loadSkylinkResults()` | No-op. The `wire:init` binding is removed from `flight-page-result.blade.php`, so a stale cached view cannot fire a second search and double every request in SkyLink's logs. |
| `flight-result.blade.php` | `searchingMore: false` — results are complete at first paint, no second supplier is pending. |
| `FlightBookingController::select()` | Routes every fare to `_selectSkylinkFare()`; the ~450-line TravelNext revalidate + `extra_services` + `fare_rules` branch is deleted. A stale page posting `source=travelnext` cannot reach a TravelNext call. |
| `FlightBookingController::book()` | The gateway redirect is unconditional; TravelNext's WebFare and Public/Private hold paths are deleted. |
| `FlightBookingController::processGatewayPayment()` | Always `skylink_reserve_full`. |
| `FlightBookingController::_callTripDetailsApi()` | Returns `[]` with no outbound call — see below. |
| `AppServiceProvider` | Tripwire: any outbound HTTP request to a TravelNext host throws. |

### The `trip_details` leak (worth fixing on `main` separately)

`confirmation()` and `_sendConfirmedEmail()` both call
`_callTripDetailsApi()` whenever `payment_method === 'gateway'` — which is
always true for SkyLink, because `book()` sends those fares straight to the
gateway. So **on main, every SkyLink booking fires two TravelNext
`trip_details` calls carrying a SkyLink PNR.** TravelNext doesn't recognise
it, the call fails, `[]` comes back and nothing breaks — but it is real
cross-supplier traffic on every confirmation. This branch stubs it; main
still has it.

### What was deliberately left alone

`AdminTicketingService`, `AdminPostTicketingService`,
`AdminReplacementFlightSearchService` and `flights:release-check` are
TravelNext-only and were **not** touched — they are Filament-admin surface
the demo team never sees, and rewriting them risked breaking the panel for
no gain. `_callBookApi()` also survives, with no reachable caller left.
The tripwire is what makes "no TravelNext traffic" provable rather than
merely intended.

---

## Deploying to the subdomain

Use a **separate database and a separate `.env`**. Do not point this at the
production database.

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://<subdomain>
APP_KEY=                      # php artisan key:generate — its own key

# Its own cookie name, so a demo session can never collide with a real one
SESSION_DRIVER=database
SESSION_COOKIE=skylinkdemo_session
SESSION_SECURE_COOKIE=true
SESSION_ENCRYPT=true

DB_DATABASE=travelwheel_skylink_demo   # separate DB

# No TRAVELNEXT_* keys at all. The tripwire throws if anything tries.

SKYLINK_BASE_URL=https://247travels.cloud/api/
SKYLINK_EMAIL=
SKYLINK_PASSWORD=
SKYLINK_ENABLED=true

SEERBIT_PUBLIC_KEY=           # TEST keys only — see the warning below
SEERBIT_SECRET_KEY=
```

Then: `composer install --no-dev -o`, `php artisan migrate --force`, seed a
USD exchange rate (markup returns 1.0 and prices come out wrong without
one), `php artisan config:cache`, and run a queue worker — confirmation
email and the notification outbox are queued.

### Before you hand over the link

- [ ] **Sandbox credentials.** `/reserve` issues a live, billable PNR and
      `SkylinkFlightService` exposes no cancel or void endpoint. Confirm with
      SkyLink whether `247travels.cloud` is their sandbox — `config/services.php`
      defaults to `247travels.com`, and the difference decides whether every
      test booking is real. **Resolve this before anyone clicks Pay.**
- [ ] **SeerBit test keys**, never live ones.
- [ ] **Zero the markup.** `FlightMarkup::apply()` turns the supplier fare
      into NGN retail. Showing the supplier both numbers hands them your exact
      commission per route and cabin. Set every `flight_service_charges` row
      to 0 on the demo database.
- [ ] Access control on the subdomain — basic auth, an IP allowlist for their
      egress IPs, or both.
- [ ] `X-Robots-Tag: noindex` at the vhost, so the demo is never indexed.
- [ ] Confirm no real customer data was copied into the demo database.

## Deleting it afterwards

```bash
# drop the vhost and the demo database first, then:
git push origin --delete demo/skylink-only
git branch -D demo/skylink-only
```

Nothing on `main` or `feature/ui-redesign` depends on this branch.
