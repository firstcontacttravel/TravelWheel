<?php

namespace App\Http\Controllers;

use App\Models\FlightBooking;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Throwable;

/**
 * The design-system specimen and its responsive harness.
 *
 * These are internal design tooling, not product surface: they exist so the
 * console's visual language can be reviewed and its breakpoints verified
 * before any of it reaches the admin panel. Both routes are gated to panel
 * users (see routes/web.php) because they render real booking data.
 */
class DesignSystemController extends Controller
{
    /** Widths the harness renders. Real devices, not round numbers. */
    private const VIEWPORTS = [
        'Phone' => [390, 720],
        'Phone landscape' => [667, 420],
        'Tablet' => [768, 800],
        'Laptop' => [1280, 800],
        'Desktop' => [1600, 820],
    ];

    public function index(): View
    {
        $this->authorisePanelUser();

        $bookings = $this->bookings();

        return view('design-system.index', [
            'bookings' => $bookings['rows'],
            'isLive' => $bookings['live'],
        ]);
    }

    public function responsive(Request $request): View
    {
        $this->authorisePanelUser();

        $requested = trim((string) $request->query('path', ''));

        // A blank field is "not supplied", not an attack — fall back quietly
        // rather than showing a warning for an empty input.
        if ($requested === '') {
            $requested = '/design-system';
        }

        // Same-origin relative paths only. A dev tool that will frame any URL
        // you hand it is a dev tool that will frame someone else's login page.
        $safe = $this->isSafePath($requested);

        return view('design-system.responsive', [
            'path' => $safe ? $requested : '/design-system',
            'rejected' => ! $safe,
            'viewports' => self::VIEWPORTS,
        ]);
    }

    /**
     * These pages print live booking references and amounts, so `auth` alone
     * is not enough — that would admit any signed-in customer. The gate is the
     * same one the admin panel itself uses.
     */
    private function authorisePanelUser(): void
    {
        $user = auth()->user();

        // This application has no route named `login` — only Filament's
        // `filament.admin.auth.login` — so Laravel's `auth` middleware throws
        // while building its redirect and a guest gets a 500 instead of a sign
        // -in page. Handled here rather than by changing redirectGuestsTo()
        // globally, which would alter every other `auth` route in the app.
        if (! $user instanceof User) {
            throw new HttpResponseException(
                redirect()->guest(route('filament.admin.auth.login')),
            );
        }

        abort_unless($user->canAccessPanel(Filament::getPanel('admin')), 403);
    }

    private function isSafePath(string $path): bool
    {
        // Must be a single-slash-rooted path: rejects "//evil.test" (which a
        // browser resolves as protocol-relative), schemes, and backslashes.
        return (bool) preg_match('#^/(?![/\\\\])[A-Za-z0-9\-._~!$&\'()*+,;=:@%/?\#\[\]]*$#', $path);
    }

    /**
     * Real bookings where the database has them, representative rows where it
     * does not. The specimen exists to answer "does 13px on a 40px row read as
     * professional with OUR records in it", so fabricated data would be
     * answering a different question — but the page still has to render on a
     * machine with an empty or unreachable database.
     *
     * @return array{rows: Collection<int, array<string, string>>, live: bool}
     */
    private function bookings(): array
    {
        try {
            $rows = FlightBooking::query()
                ->latest('created_at')
                ->limit(9)
                ->get()
                ->map(fn (FlightBooking $booking): array => $this->row($booking));

            if ($rows->isNotEmpty()) {
                return ['rows' => $rows, 'live' => true];
            }
        } catch (Throwable) {
            // No database, or a schema that predates these columns. Fall through.
        }

        return ['rows' => $this->fallbackRows(), 'live' => false];
    }

    /** @return array<string, mixed> */
    private function row(FlightBooking $booking): array
    {
        [$queue, $tone, $shape] = $this->queue($booking);

        return [
            'ref' => (string) ($booking->booking_ref ?: '---'),
            // The stored route column, not flight_snapshot.segments: a
            // multi-city booking keeps its legs in .multiLegs and leaves
            // .segments empty, so reading segments showed those trips with no
            // route at all.
            'legs' => $booking->routeLegs() ?: ['---'],
            'airline' => (string) ($booking->airline ?: 'Unknown airline'),
            'amount' => number_format((float) $booking->total_price, 2),
            'queue' => $queue,
            'tone' => $tone,
            'shape' => $shape,
        ];
    }

    /**
     * The five states the dot language has to express, mapped from the same
     * columns the admin queue reads.
     *
     * @return array{0: string, 1: string, 2: string}
     */
    private function queue(FlightBooking $booking): array
    {
        if ($booking->payment_status === 'awaiting_bank_transfer') {
            return ['Awaiting transfer', 'warning', 'tc-status-progress'];
        }

        if ($booking->payment_status === 'paid' && in_array($booking->booking_status, ['failed', 'ticketing_failed'], true)) {
            return ['Ticketing failed', 'critical', ''];
        }

        if ($booking->booking_status === 'ticketed' || $booking->ticket_ordered) {
            return ['Ticketed', 'positive', ''];
        }

        if ($booking->payment_status === 'paid') {
            return ['Ready to ticket', 'info', 'tc-status-progress'];
        }

        return ['Pending payment', 'idle', 'tc-status-pending'];
    }

    /** @return Collection<int, array<string, mixed>> */
    private function fallbackRows(): Collection
    {
        return collect([
            ['TW-A35H22EI', ['LOS', 'DXB'], 'Egyptair', '925,023.49', 'Ticketed', 'positive', ''],
            ['TW-1B747VAP', ['LOS', 'DXB'], 'Egyptair', '925,023.49', 'Pending payment', 'idle', 'tc-status-pending'],
            ['TW-KLZOZ1Y', ['LOS', 'DXB'], 'Turkish Airlines', '872,688.13', 'Ready to ticket', 'info', 'tc-status-progress'],
            ['TW-BHM26MVW', ['LOS', 'ABV', 'LOS'], 'Transaero Airlines', '255,566.89', 'Ticketed', 'positive', ''],
            ['TW-2N0Z0DHO', ['SHJ', 'DOH'], 'Air Arabia', '284,194.33', 'Awaiting transfer', 'warning', 'tc-status-progress'],
            ['TW-CIR3U56T', ['LOS', 'DXB'], 'Saudi Arabian Airlines', '874,845.91', 'Ticketed', 'positive', ''],
            ['TW-CLSUVL7A', ['LOS', 'DOH'], 'Egyptair', '863,205.79', 'Ready to ticket', 'info', 'tc-status-progress'],
            ['TW-AP6P911G', ['LOS', 'DOH'], 'Turkish Airlines', '872,688.13', 'Ticketing failed', 'critical', ''],
            ['TW-10LYE2M5', ['LOS', 'ABV'], 'Transaero Airlines', '254,821.48', 'Awaiting transfer', 'warning', 'tc-status-progress'],
        ])->map(fn (array $r): array => [
            'ref' => $r[0], 'legs' => $r[1], 'airline' => $r[2],
            'amount' => $r[3], 'queue' => $r[4], 'tone' => $r[5], 'shape' => $r[6],
        ]);
    }
}
