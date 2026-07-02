@component('layouts.app', ['title' => 'Leadway Insurance Quote - TravelWheel'])

<section class="shadow-sm">
    <div class="container">
        <div class="row p-2 pt-3">
            <div class="col-sm-12 p-3">
                <div class="row">
                    <div class="col-xs-3 col-3 col-sm-2 col-lg-1">
                        <img src="{{ asset('assets/image/leadway.png') }}" class="image-fluid w-100" alt="Leadway">
                    </div>
                    <div class="col-xs-12 col-12 col-sm-10 col-lg-7">
                        <h3>Leadway Insurance Quote</h3>
                        <span class="text-muted">Review your quote and proceed to payment.</span>
                    </div>
                </div>
            </div>
        </div>

        @if(!empty($errorMsg))
        <div class="alert alert-danger">{{ $errorMsg }}</div>
        @endif

        <div class="row airport-form shadow p-4 mb-5">
            <div class="col-sm-5 mb-3">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0" style="color:rgba(13,156,83,1);">Pricing</h5>
                        <small class="text-muted">{{ session('prodName') }}</small>
                    </div>
                    <div class="card-body">
                        <h6><b>PRICING</b></h6>
                        <p>The Quote Price Is: <b style="font-size:20px;">₦{{ number_format($data['totalPremium'] ?? 0, 0) }}</b></p>

                        <div class="row mt-3">
                            <div class="col-6">
                                <a href="javascript:history.back()" class="btn btn-sm btn-secondary">Back to Edit</a>
                            </div>
                            <div class="col-6 text-end">
                                <form action="{{ route('air.makePurchase') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="amount" value="{{ $data['totalPremium'] ?? 0 }}"/>
                                    <input type="hidden" name="quoteNo" value="{{ $data['quoteNo'] ?? '' }}"/>
                                    <input type="hidden" name="prodCode" value="{{ $dataform['prodCode'] ?? '' }}"/>
                                    <button type="submit" class="btn btn-sm btn-success">Purchase</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-7">
                @php
                    $prodName = session('prodName');
                    $group1 = ['Travel TX (UMRAH)', 'Travel TX (HAJJ)'];
                    $group2 = ['Travel TX (PILGRIMAGE EXTRA)', 'Travel TX (PILGRIMAGE PLUS)', 'Travel TX (PILGRIMAGE BASIC)'];
                    $group3 = ['Travel TX (STUDENT WORLDWIDE INCLUSIVE)', 'Travel TX (STUDENT WORLDWIDE EXCLUSIVE)', 'Travel TX (ECONOMY WORLDWIDE EXCLUSIVE)', 'Travel TX (ECONOMY WORLDWIDE INCLUSIVE)'];
                    $group4 = ['Travel TX (AFRICA)', 'Travel TX (SCHENGEN)'];
                    $group5 = ['Travel TX (WORLDWIDE INCLUSIVE)', 'Travel TX (WORLDWIDE EXCLUSIVE)', 'Travel TX (GOLD WORLDWIDE INCLUSIVE)', 'Travel TX (GOLD WORLDWIDE EXCLUSIVE)'];
                @endphp

                @if(in_array($prodName, $group1))
                    <img src="{{ asset('assets/image/LeadwayH&U.jpg') }}" class="img-fluid w-100" alt="Hajj & Umrah">
                @elseif(in_array($prodName, $group2))
                    <img src="{{ asset('assets/image/LeadwayPI.jpg') }}" class="img-fluid w-100" alt="Pilgrimage">
                @elseif(in_array($prodName, $group3))
                    <img src="{{ asset('assets/image/LeadwayE&S.jpg') }}" class="img-fluid w-100" alt="Economy & Student">
                @elseif(in_array($prodName, $group4))
                    <img src="{{ asset('assets/image/LeadwayA&S.jpg') }}" class="img-fluid w-100" alt="Africa & Schengen">
                @elseif(in_array($prodName, $group5))
                    <img src="{{ asset('assets/image/LeadwayP&G.jpg') }}" class="img-fluid w-100" alt="Premium & Gold">
                @endif
            </div>
        </div>
    </div>
</section>

@endcomponent
