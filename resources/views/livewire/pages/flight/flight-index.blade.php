<style>
    /* Container */
    .sr-promo {
        background: #fff;
        padding: 30px 0;
    }

    /* Horizontal row */
    .sr-promo-row {
        display: flex;
        gap: 16px;
        overflow-x: auto;
        padding: 0 16px;
        scroll-behavior: smooth;

        /* smooth scrolling on mobile */
        -webkit-overflow-scrolling: touch;
    }

    /* Hide scrollbar (optional) */
    .sr-promo-row::-webkit-scrollbar {
        display: none;
    }

    /* Card */
    .sr-promo-card {
        min-width: 240px;
        max-width: 260px;
        flex: 0 0 auto;

        background: #f9fafb;
        border-radius: 12px;
        padding: 14px;
        border: 1px solid #e5e7eb;

        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    /* Hover effect */
    .sr-promo-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.08);
    }

    /* Text styles */
    .sr-promo-label {
        font-size: 12px;
        font-weight: 600;
        color: #6b7280;
        margin-bottom: 6px;
    }

    .sr-promo-title {
        font-size: 14px;
        font-weight: 700;
        color: #111827;
    }

    .sr-promo-body {
        font-size: 12px;
        color: #6b7280;
        margin: 6px 0 10px;
    }

    .sr-promo-btn {
        font-size: 12px;
        font-weight: 600;
        color: #2563eb;
        text-decoration: none;
    }
</style>  
<div>
        {{-- Widget --}}
        @include('livewire.pages.flight.flight-search')
        
    {{-- ── Quick info strip below the widget ── --}}
    <div style=" background: #fff; border-bottom: 1px solid #e8ecf0; padding: 18px 24px;">
        <div style="
            max-width: 1100px; margin: 0 auto;
            display: flex; align-items: center; gap: 32px;
            flex-wrap: wrap; justify-content: center;
        ">
            <div style="display:flex; align-items:center; gap:10px; font-size:18px; color:#374151;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#00a651" stroke-width="2" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
                Best price guarantee
            </div>
            <div style="display:flex; align-items:center; gap:10px; font-size:18px; color:#374151;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#00a651" stroke-width="2" stroke-linecap="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                Secure booking
            </div>
            <div style="display:flex; align-items:center; gap:10px; font-size:18px; color:#374151;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#00a651" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                Instant confirmation
            </div>
            <div style="display:flex; align-items:center; gap:10px; font-size:18px; color:#374151;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#00a651" stroke-width="2" stroke-linecap="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 1.27h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.91a16 16 0 0 0 6 6l.91-.91a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21.73 17z"/></svg>
                24/7 support
            </div>
        </div>
    </div>
    <div class="sr-promo" x-data="promoSlider()">

        <div class="sr-promo-row">
            <template x-for="(item, index) in items" :key="index">
                <div class="sr-promo-card">
                    
                    <div class="sr-promo-label">
                        💡 <span x-text="item.label"></span>
                    </div>

                    <div class="sr-promo-title" x-text="item.title"></div>
                    <div class="sr-promo-body" x-text="item.body"></div>

                    <a class="sr-promo-btn" :href="item.link" x-text="item.cta"></a>

                </div>
            </template>
        </div>

    </div>
</div>

<script>
    function promoSlider() {
        return {
            current: 0,
            interval: null,

            items: [
                {
                    label: 'Hotel Booking',
                    title: 'Find the best hotel deals',
                    body: 'Book top-rated hotels at better prices.',
                    cta: 'Explore Hotels',
                    link: '#'
                },
                {
                    label: 'Airport Protocol',
                    title: 'VIP airport assistance',
                    body: 'Skip queues and enjoy fast-track services at the airport.',
                    cta: 'Book Protocol',
                    link: '#'
                },
                {
                    label: 'Airport Lounge',
                    title: 'Relax before your flight',
                    body: 'Access premium airport lounges worldwide.',
                    cta: 'View Lounges',
                    link: '#'
                },
                {
                    label: 'Travel Insurance',
                    title: 'Travel with peace of mind',
                    body: 'Get coverage for delays, medical emergencies, and more.',
                    cta: 'Get Covered',
                    link: '#'
                },
                {
                    label: 'Visa Assistance',
                    title: 'Hassle-free visa processing',
                    body: 'Let us handle your visa application from start to finish.',
                    cta: 'Apply Now',
                    link: '#'
                },
                {
                    label: 'Air Cargo',
                    title: 'Fast and reliable cargo delivery',
                    body: 'Ship goods locally and internationally with ease.',
                    cta: 'Send Cargo',
                    link: '#'
                }
            ],

            next() {
                this.current = (this.current + 1) % this.items.length;
            },

            prev() {
                this.current = (this.current - 1 + this.items.length) % this.items.length;
            },

            

            init() {
                setInterval(() => {
                    const container = document.querySelector('.sr-promo-row');
                    if (container) {
                        container.scrollBy({ left: 260, behavior: 'smooth' });
                    }
                }, 4000);
            }
        }
    }

    
</script>