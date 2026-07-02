@component('layouts.app', ['title' => 'Air Cargo - TravelWheel'])

<section class="shadow-sm">
    <div class="container">
        <div class="row p-2 pt-3">
            <div class="col-sm-12 p-3">
                <div class="row">
                    <div class="col-xs-3 col-3 col-sm-2 col-lg-1">
                        <img src="{{ asset('assets/img/cargo.jpg') }}" class="image-fluid w-100" alt="cargo">
                    </div>
                    <div class="col-xs-12 col-12 col-sm-10 col-lg-7">
                        <h3>Air Cargo</h3>
                        <span class="text-muted">Fast, reliable international air freight services.</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row airport-form shadow p-4 mb-5">
            <div class="col-md-6 mb-3">
                <div class="card p-4">
                    <h4><b>Create Shipment</b></h4>
                    <div class="card p-3 shadow">
                        <div class="mb-3">
                            <label class="form-label">New Shipment</label>
                            <select class="form-select" id="stateselect">
                                <option value="">-- Select Shipment Type --</option>
                                <option value="{{ route('air.cargo.international') }}">International Shipment</option>
                                <option value="{{ route('air.cargo.international') }}">Local Shipment</option>
                            </select>
                        </div>
                        <div class="text-center">
                            <button type="button" id="purchaseButton" class="btn btn-pry">Continue</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="card p-4">
                    <h4><b>Track Shipment</b></h4>
                    <div class="card p-3 shadow">
                        <div class="mb-3">
                            <label class="form-label">Shipment ID</label>
                            <input type="text" class="form-control" id="tracking_id" placeholder="Enter Shipment ID">
                        </div>
                        <div class="text-center">
                            <button id="trackButton" class="btn btn-pry">Track Shipment</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    document.getElementById('purchaseButton').addEventListener('click', function () {
        const val = document.getElementById('stateselect').value;
        if (val) {
            window.location.href = val;
        } else {
            alert('Please select a shipment type');
        }
    });

    document.getElementById('trackButton').addEventListener('click', function () {
        const id = document.getElementById('tracking_id').value.trim();
        if (id) {
            window.location.href = 'https://www.dhl.com/ng-en/home/tracking.html?tracking-id=' + id;
        } else {
            alert('Please enter a valid Shipment ID.');
        }
    });
</script>

@endcomponent
