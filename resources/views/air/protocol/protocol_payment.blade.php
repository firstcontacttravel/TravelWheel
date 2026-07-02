@component('layouts.app', ['title' => 'Protocol Payment - TravelWheel'])
<style>
    .input-control {
        display: block; width: 100%; padding: .175rem .25rem; font-size: 1rem;
        font-weight: 400; line-height: 1.5; color: #212529; background-color: #fff;
        border-top-style: hidden; border-right-style: hidden; border-left-style: hidden;
        border-bottom: 1px solid #ced4da; -webkit-appearance: none; -moz-appearance: none;
        appearance: none; transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out;
    }
    .no-outline:focus { outline: none; }
</style>

<section class="shadow-sm py-4">
    <div class="container">
        <div class="row pt-3 pb-2">
            <div class="col-sm-6 p-3">
                <h3><img src="{{ asset('assets/img/pp.png') }}" class="me-2" style="width:30px;" alt="protocol"> Airport Protocol Service</h3>
            </div>
        </div>

        <div class="row shadow p-4 mb-5">
            <div class="col-sm-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0" style="color:rgba(13,156,83,1);">Payment Details</h5>
                    </div>
                    @php
                        $package = $protocols->package == '2' ? 'Regular' : 'VIP';
                        $formattedValue = number_format($protocols->amount);
                        $trans_id = $protocols->trans_id;
                    @endphp
                    <div class="card-body text-center p-4">
                        <img src="{{ asset('assets/img/77suc.gif') }}" class="w-auto mb-3" alt="Success" style="max-height:100px;">
                        <h5>Payment Successful</h5>
                        <small class="d-block mb-1">The sum of ₦{{ $formattedValue }} has been made for Protocol Service ({{ $package }}).</small>
                        <small class="d-block mb-3"><b>Please continue to generate your service pass.</b></small>
                        <a href="{{ route('air.protocol_generate', ['trans_id' => $trans_id]) }}" class="btn btn-success" id="generate-pass-link">
                            Generate Pass
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0" style="color:rgba(13,156,83,1);">Protocol Service Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mt-2">
                            <div class="col-sm-4 mb-2">
                                <label class="form-label">Location</label>
                                <input type="text" disabled class="input-control no-outline" value="{{ $protocols->state }}">
                            </div>
                            <div class="col-sm-4 mb-2">
                                <label class="form-label">Airport</label>
                                <input type="text" disabled class="input-control no-outline" value="{{ $protocols->airport }}">
                            </div>
                            <div class="col-sm-4 mb-2">
                                <label class="form-label">Travel Date</label>
                                <input type="text" disabled class="input-control no-outline" value="{{ $protocols->travel_date?->format('d M Y') }}">
                            </div>
                            <div class="col-sm-4 mb-2">
                                <label class="form-label">{{ $protocols->service_type }} Time</label>
                                <input type="text" disabled class="input-control no-outline" value="{{ $protocols->d_time }}">
                            </div>
                            <div class="col-sm-4 mb-2">
                                <label class="form-label">Airline</label>
                                <input type="text" disabled class="input-control no-outline" value="{{ $protocols->airline }}">
                            </div>
                            <div class="col-sm-4 mb-2">
                                <label class="form-label">No. of Passengers</label>
                                <input type="text" disabled class="input-control no-outline" value="{{ $protocols->passenger }}">
                            </div>
                            <div class="col-sm-4 mb-2">
                                <label class="form-label">Phone Number</label>
                                <input type="text" disabled class="input-control no-outline" value="{{ $protocols->phone }}">
                            </div>
                            <div class="col-sm-4 mb-2">
                                <label class="form-label">Email</label>
                                <input type="text" disabled class="input-control no-outline" value="{{ $protocols->email }}">
                            </div>
                            <div class="col-sm-4 mb-2">
                                <label class="form-label">Optional Request</label>
                                <input type="text" disabled class="input-control no-outline" value="{{ $protocols->optional_request }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    document.getElementById('generate-pass-link').addEventListener('click', function () {
        this.textContent = 'Generating...';
        this.classList.add('disabled');
    });
</script>
@endcomponent
