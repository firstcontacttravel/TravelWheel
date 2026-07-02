@php
    $statusLabels = ['draft'=>'Draft','awaiting_payment'=>'Awaiting payment','submitted'=>'Submitted','under_review'=>'In review','action_required'=>'Action required','processing'=>'Processing','approved'=>'Approved','issued'=>'Visa issued','rejected'=>'Not approved','cancelled'=>'Cancelled','expired'=>'Expired'];
    $statusLabel = $statusLabels[$application->status] ?? str($application->status)->replace('_',' ')->title();
    $openRequests = $application->additionalDocumentRequests->whereIn('status', ['open', 'replacement_requested']);
    $issuedDocuments = $application->issuedDocuments->whereNull('superseded_at');
@endphp
@component('layouts.app', ['title' => 'Visa Application '.$application->reference])
<link rel="stylesheet" href="{{ asset('css/visa-portal.css') }}?v={{ filemtime(public_path('css/visa-portal.css')) }}">
<div class="vp-page">
    <header class="vp-hero">
        <div><p class="vp-eyebrow">Visa application</p><h1>{{ $application->product->name }}</h1><p class="vp-reference">Reference <strong>{{ $application->reference }}</strong></p></div>
        <span class="vp-status vp-status--{{ str($application->status)->slug() }}">{{ $statusLabel }}</span>
    </header>
    @if(session('status'))<div class="vp-alert vp-alert--success">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="vp-alert vp-alert--error">{{ $errors->first() }}</div>@endif

    <section class="vp-summary-grid">
        <div><span>Destination</span><strong>{{ data_get($application->search_snapshot, 'destination_name', 'Visa destination') }}</strong></div>
        <div><span>Travel date</span><strong>{{ $application->arrival_date->format('d M Y') }}</strong></div>
        <div><span>Travellers</span><strong>{{ $application->travelers->count() }}</strong></div>
        <div><span>Processing option</span><strong>{{ $application->processingOption?->name ?? 'Standard' }}</strong></div>
    </section>

    <div class="vp-layout"><main class="vp-main">
        <section class="vp-card">
            <div class="vp-card-head"><div><p class="vp-eyebrow">Next steps</p><h2>Outstanding actions</h2></div><span class="vp-count">{{ $openRequests->count() }}</span></div>
            @forelse($application->additionalDocumentRequests as $request)
                <article class="vp-request {{ $request->status !== 'open' ? 'vp-request--complete' : '' }}">
                    <div class="vp-request-copy"><span class="vp-request-state">{{ $request->status === 'open' ? 'Required' : 'Received' }}</span><h3>{{ $request->title }}</h3>
                        @if($request->traveler)<p>For {{ $request->traveler->first_name }} {{ $request->traveler->last_name }}</p>@endif
                        @if($request->instructions)<p>{{ $request->instructions }}</p>@endif
                        @if($request->due_at)<small>Due {{ $request->due_at->format('d M Y, H:i') }}</small>@endif
                    </div>
                    @if(in_array($request->status, ['open', 'replacement_requested'], true))
                    <form method="POST" enctype="multipart/form-data" action="{{ route('visa.portal.requests.upload', [$application, $request]) }}" class="vp-upload">@csrf
                        <label><x-ui.icon name="upload" :size="22" /><span><strong>Choose document</strong><small>PDF, JPG or PNG · max {{ number_format(($request->requirement?->maximum_file_size_kb ?: 5120)/1024, 0) }} MB</small></span><input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png" required></label>
                        <button class="vp-button vp-button--small" type="submit">Upload securely</button>
                    </form>
                    @else <div class="vp-received"><x-ui.icon name="check-circle" :size="22" /> {{ $request->original_name }}</div>@endif
                </article>
            @empty
                <div class="vp-empty"><x-ui.icon name="check-circle" :size="34" /><h3>You’re all caught up</h3><p>There are no outstanding actions for this application.</p></div>
            @endforelse
        </section>

        <section class="vp-card"><div class="vp-card-head"><div><p class="vp-eyebrow">Progress</p><h2>Application timeline</h2></div></div>
            <div class="vp-timeline">@foreach($application->statusHistory->sortByDesc('created_at') as $history)<div class="vp-timeline-item"><i></i><div><strong>{{ $statusLabels[$history->to_status] ?? str($history->to_status)->replace('_',' ')->title() }}</strong><p>{{ $history->reason ?: 'Application status updated' }}</p><time>{{ $history->created_at->format('d M Y, H:i') }}</time></div></div>@endforeach</div>
        </section>
    </main><aside class="vp-side">
        <section class="vp-card"><p class="vp-eyebrow">Payments</p><h2>Payment & receipts</h2>
            @forelse($application->payments as $payment)<div class="vp-payment"><div><strong>{{ $payment->expected_currency }} {{ number_format($payment->verified_amount ?? $payment->expected_amount, 2) }}</strong><small>{{ $payment->verified_at?->format('d M Y') ?? $payment->created_at->format('d M Y') }}</small></div><span class="vp-pill">{{ ucfirst($payment->status) }}</span></div>
                @if($payment->status === 'paid')<a class="vp-outline" href="{{ route('visa.portal.receipts.show', [$application, $payment]) }}">View receipt</a>@endif
            @empty <p class="vp-muted">No payment record yet.</p>@endforelse
        </section>
        <section class="vp-card"><p class="vp-eyebrow">Documents</p><h2>Issued documents</h2>
            @forelse($issuedDocuments as $document)<a class="vp-document" href="{{ route('visa.portal.issued-documents.download', [$application, $document]) }}"><x-ui.icon name="shield" :size="20" /><span><strong>Issued visa · version {{ $document->version }}</strong><small>{{ $document->original_name }}</small></span></a>
            @empty <p class="vp-muted">Issued documents will appear here when ready.</p>@endforelse
        </section>
        <section class="vp-card"><p class="vp-eyebrow">Communication</p><h2>Recent updates</h2>
            @forelse($application->notificationEvents->take(4) as $notification)<div class="vp-notice"><strong>{{ $notification->subject }}</strong><small>{{ $notification->created_at->diffForHumans() }}</small><form method="POST" action="{{ route('visa.portal.notifications.resend', [$application, $notification]) }}">@csrf<button type="submit">Resend email</button></form></div>
            @empty <p class="vp-muted">No email updates have been sent yet.</p>@endforelse
        </section>
    </aside></div>
</div>
@endcomponent
