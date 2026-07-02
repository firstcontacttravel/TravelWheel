@component('layouts.app', ['title' => 'Airport Protocol Service - TravelWheel'])
<style>.hidden{display:none;}</style>



<section class="shadow-sm py-4">
    <div class="container">
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="row pt-3">
            <div class="col-sm-12 p-3">
                <div class="row">
                    <div class="col-3 col-sm-2 col-lg-1">
                        <img src="{{ asset('assets/img/pp.png') }}" class="img-fluid w-100" alt="protocol">
                    </div>
                    <div class="col-12 col-sm-10 col-lg-8">
                        <h3>Airport Protocol and Services</h3>
                        <span class="text-muted">
                            As part of our aggregating effort we can also assist you with some of
                            the services you can get within the airport zone. We are committed to
                            making your travel experience remarkable with all support and fast-tracking
                            services within the airport.
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row pb-5">
            <div class="col-sm-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0" style="color: rgba(13,156,83,1);">Select Airport</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('air.protocolplan') }}" method="POST" id="protocolForm">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Location</label>
                                <select class="form-select" id="stateselect" name="state" required>
                                    <option value="">-- Select Location --</option>
                                    <option value="Abuja">FCT - Abuja</option>
                                    <option value="Lagos">Lagos - Ikeja</option>
                                    <option value="Kano">Kano - Kano</option>
                                    <option value="Rivers">Rivers - Port Harcourt</option>
                                    <option value="Enugu">Enugu - Enugu</option>
                                    <option value="Delta Asaba">Delta - Asaba</option>
                                    <option value="Imo">Imo - Owerri</option>
                                    <option value="Oyo">Oyo - Ibadan</option>
                                    <option value="Kwara">Kwara - Ilorin</option>
                                    <option value="Anambra">Anambra - Anambra</option>
                                    <option value="Delta Warri">Delta - Warri</option>
                                    <option value="Edo">Edo - Benin City</option>
                                    <option value="Gombe">Gombe - Gombe</option>
                                    <option value="Borno">Borno - Maiduguri</option>
                                    <option value="Adamawa">Adamawa - Yola</option>
                                    <option value="Sokoto">Sokoto - Sokoto</option>
                                    <option value="Kaduna">Kaduna - Kaduna</option>
                                    <option value="Cross River">Cross River - Calabar</option>
                                </select>
                            </div>

                            <div class="mb-3 hidden" id="airportSection">
                                <input type="hidden" name="location" id="locationInput">
                                <label class="form-label">Airport</label>
                                <select class="form-select" name="airport" id="airportSelect" required></select>

                                <label class="form-label mt-3">I need Protocol Service for my:</label>
                                <select class="form-select" name="service" required>
                                    <option value="">-- Segment --</option>
                                    <option value="Departure">Departure</option>
                                    <option value="Arrival">Arrival</option>
                                </select>
                            </div>

                            <div class="text-center mt-3 hidden" id="submitSection">
                                <button type="submit" class="btn btn-success px-5">Book Protocol</button>
                            </div>
                        </form>

                        <script>
                            const stateselect    = document.getElementById('stateselect');
                            const airportSection = document.getElementById('airportSection');
                            const submitSection  = document.getElementById('submitSection');
                            const airportSelect  = document.getElementById('airportSelect');
                            const locationInput  = document.getElementById('locationInput');

                            const localOnly = ['Delta Asaba','Imo','Oyo','Kwara','Anambra','Delta Warri','Edo','Gombe','Borno','Adamawa','Sokoto','Kaduna','Cross River'];

                            stateselect.addEventListener('change', function () {
                                const selected = stateselect.value;
                                locationInput.value = selected;
                                if (!selected) {
                                    airportSection.classList.add('hidden');
                                    submitSection.classList.add('hidden');
                                    airportSelect.innerHTML = '';
                                    return;
                                }
                                airportSection.classList.remove('hidden');
                                submitSection.classList.remove('hidden');
                                airportSelect.innerHTML = '';
                                if (localOnly.includes(selected)) {
                                    airportSelect.innerHTML = '<option value="Local Airport" selected>Local Airport</option>';
                                } else {
                                    airportSelect.innerHTML = '<option value="">-- Choose Airport --</option><option value="International Airport">International Airport</option><option value="Local Airport">Local Airport</option>';
                                }
                            });
                        </script>
                    </div>
                </div> 
            </div>

            <div class="col-md-6 pb-3">
                <div class="bg-light p-3 shadow-sm">
                    <p style="color:rgba(13,156,83,1);"><b>The benefits of our Protocol Services depends on the package you selected</b></p>
                    <small class="text-muted">List of Our Services</small>
                    <ul class="list-unstyled mt-2">
                        <li><i class="fa fa-check"></i> Meet and Greet.</li>
                        <li><i class="fa fa-check"></i> Exclusive Baggage Handling.</li>
                        <li><i class="fa fa-check"></i> No Queue.</li>
                        <li><i class="fa fa-check"></i> Fast-tracking Check-in Process.</li>
                        <li><i class="fa fa-check"></i> Stress Free Check-in Process.</li>
                        <li><i class="fa fa-check"></i> Escort to the Arrival Lobby.</li>
                        <li><i class="fa fa-check"></i> Cordinate Passenger to their Pre-arranged Transportation.</li>
                        <li><i class="fa fa-check"></i> Other relevant Airport Protocol Service as case may be.</li>
                        <li><i class="fa fa-angle-right" style="font-size:20px;"></i> Pick-up Request (Optional)</li>
                        <li><i class="fa fa-angle-right" style="font-size:20px;"></i> Drop-off Request (Optional)</li>
                        <li><i class="fa fa-angle-right" style="font-size:20px;"></i> Police Escort Request (Optional)</li>
                    </ul>
                    <h6 class="mt-3">NB: <b style="color:red;">A Protocol Boarding Pass will be generated after a Successful transaction. It expires after Departure / Arrival date.</b></h6>
                </div>
            </div>
        </div>
    </div>
</section>
@endcomponent
