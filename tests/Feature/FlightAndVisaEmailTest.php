<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The flight and visa emails now share one layout (resources/views/components/
 * mail/). These guard the things that were actually wrong before it existed —
 * each assertion here corresponds to a defect found in the templates, not a
 * style preference.
 */
class FlightAndVisaEmailTest extends TestCase
{
    /** Every flight/visa template converted onto the shared layout. */
    private const TEMPLATES = [
        'resources/views/mail/eticket.blade.php',
        'resources/views/emails/booking-pending.blade.php',
        'resources/views/emails/payment-receipt.blade.php',
        'resources/views/emails/unticketed-confirmation-alert.blade.php',
        'resources/views/emails/visa-application-update.blade.php',
        'resources/views/emails/visa-application-vendor.blade.php',
        'resources/views/emails/visa-portal-access-code.blade.php',
        'resources/views/emails/support-visa-confirmation-success.blade.php',
        'resources/views/emails/support-visa-confirmation-notification.blade.php',
        'resources/views/emails/travelflex-status.blade.php',
        'resources/views/emails/travelflex-repayment-reminder.blade.php',
        'resources/views/emails/travelflex-ticket-booked.blade.php',
        'resources/views/emails/travelflex-provider-review.blade.php',
    ];

    public function test_every_flight_and_visa_email_uses_the_shared_layout(): void
    {
        foreach (self::TEMPLATES as $path) {
            $source = $this->source($path);

            $this->assertStringContainsString('<x-mail.layout', $source, "{$path} does not use the shared layout.");
            $this->assertStringNotContainsString('<!DOCTYPE', $source, "{$path} still builds its own HTML document.");
        }
    }

    /**
     * Seven templates shipped <img src="https://your-travelwheel-logo-url.png">
     * — a placeholder nobody ever replaced, rendering as a broken image at the
     * top of live customer email.
     */
    public function test_no_email_references_a_placeholder_asset(): void
    {
        // Scoped to the flight and visa set. The same placeholder is still in
        // six support templates (yellow card, extra luggage, flight assist)
        // that were deliberately left out of this redesign — widening this
        // test would fail on a defect nobody has agreed to fix yet.
        foreach (self::TEMPLATES as $path) {
            $source = $this->source($path);
            $this->assertStringNotContainsString('your-travelwheel-logo-url', $source, "{$path} points at a placeholder logo.");
            $this->assertDoesNotMatchRegularExpression('/https?:\/\/(example\.com|placeholder|your-)/i', $source, "{$path} points at a placeholder URL.");
        }
    }

    /**
     * Gradients are dropped entirely by Outlook on Windows, which left the
     * brand-coloured header white — and the white header text invisible on it.
     * Eleven templates had one.
     */
    public function test_no_email_header_relies_on_a_css_gradient(): void
    {
        foreach (self::TEMPLATES as $path) {
            $this->assertStringNotContainsString('linear-gradient', $this->markup($path), "{$path} uses a gradient Outlook will drop.");
        }

        $this->assertStringNotContainsString(
            'linear-gradient',
            $this->markup('resources/views/components/mail/layout.blade.php'),
        );
    }

    /**
     * Several templates were written with markdown bold inside plain HTML, so
     * customers were reading literal "**Yellow Card**" asterisks.
     */
    public function test_no_email_leaks_literal_markdown(): void
    {
        foreach (self::TEMPLATES as $path) {
            $this->assertDoesNotMatchRegularExpression(
                '/\*\*[A-Za-z{]/',
                $this->source($path),
                "{$path} contains markdown bold, which renders literally in an HTML email.",
            );
        }
    }

    /**
     * The brand blue existed as #0D1883 in 30 places and #303191 in 14, and the
     * green as #009933 rather than the site's #00a859. Colours now come from
     * config/brand.php so they cannot drift apart again.
     */
    public function test_emails_do_not_hardcode_brand_colours(): void
    {
        foreach (self::TEMPLATES as $path) {
            $this->assertDoesNotMatchRegularExpression(
                '/#(0[Dd]1883|303191|009933)/',
                $this->source($path),
                "{$path} hardcodes a brand colour instead of reading config('brand.colors').",
            );
        }
    }

    /**
     * Without a preheader the client scrapes the first visible words for the
     * inbox preview line, which on these templates was the header eyebrow —
     * every e-ticket previewed as "Electronic ticket".
     */
    public function test_customer_facing_emails_set_an_inbox_preheader(): void
    {
        foreach (self::TEMPLATES as $path) {
            $this->assertMatchesRegularExpression(
                '/:?preheader=/',
                $this->source($path),
                "{$path} sets no preheader, so the inbox preview falls back to the header text.",
            );
        }
    }

    private function source(string $path): string
    {
        return (string) file_get_contents(base_path($path));
    }

    /** Source with Blade comments removed, so a comment explaining a defect is
     *  not mistaken for the defect itself. */
    private function markup(string $path): string
    {
        return (string) preg_replace('/\{\{--.*?--\}\}/s', '', $this->source($path));
    }
}
