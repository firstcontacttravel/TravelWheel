@component('layouts.app', ['title' => 'Airport Protocol Service - TravelWheel'])
    <main id="main" style="padding-top: 60px;">

        <div class="container-fluid">
            <img src="{{ asset('assets/image/Protocol.jpg') }}" class="image-fluid w-100" alt="">
        </div>

        <section class="shadow-sm">
            <div class="container">
                <div class="row p-2 pt-5 ">
                    <div class="col-sm-12 p-3 ">
                        <div class="row">
                            <div class="col-xs-3 col-3 col-sm-2 col-lg-1">
                                <img src="{{ asset('assets/img/pp.png') }}" class="image-fluid w-100" alt="protocol"> 
                            </div>

                            <div class="col-xs-12 col-12 col-sm-10 col-lg-8 protocol">

                                <h3>   Airport Protocol and Services  </h3>

                                <span class="text-muted">
                                    As part of our aggregating effort we can also assist you with some of 
                                    the services you can get within the airport zone. We are committed to 
                                    making your travel experience remarkable with all support and fast-tracking 
                                    services within the airport.
                                </span>

                            </div>

                        </div>
                    </div>
                </div>
                <div class="row pb-90">
                    <div class="col-sm-6">
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0" style="color: rgba(13, 156, 83, 1);">Select Airport</h5>
                            </div>
                            <div class="card-body">
                                @if(session('error'))
                                    <div class="alert alert-danger">
                                        {{ session('error') }}
                                    </div>
                                @endif

                                <form action="{{ route('air.protocolplan')}}" method="POST" id="protocolForm">
                                    @csrf
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <!-- Location -->
                                            <div class="mb-3">
                                                <label class="form-label">Location</label>
                                                <select class="form-select" id="stateselect" name="state" required>
                                                    <option value="">-- Select Location --</option>
                                                    <!-- Both Intl + Local -->
                                                    <option value="Abuja">FCT - Abuja</option>
                                                    <option value="Lagos">Lagos - Ikeja</option>
                                                    <option value="Kano">Kano - Kano</option>
                                                    <option value="Rivers">Rivers - Port Harcourt</option>
                                                    <option value="Enugu">Enugu - Enugu</option>
                                                    <!-- Local Only -->
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

                                            <!-- Airport -->
                                            <div class="mb-3 protocol-hide" id="airportSection">
                                                <input type="hidden" name="location" id="locationInput">

                                                <label class="form-label">Airport</label>
                                                <select class="form-select" name="airport" id="airportSelect" required></select>

                                                <!-- Service Type -->
                                                <label class="form-label mt-3">I need Protocol Service for my:</label>
                                                <select class="form-select" name="service" required>
                                                    <option value="">-- Segment --</option>
                                                    <option value="Departure">Departure</option>
                                                    <option value="Arrival">Arrival</option>
                                                </select>
                                            </div>

                                            <div class="col-sm-12 text-center mt-3 protocol-hide" id="submitSection">
                                                <button type="submit" class="btn btn-pry">Book Protocol</button>
                                            </div>
                                        </div>
                                    </div>
                                </form>

                                <script>
                                    const stateselect = document.getElementById("stateselect");
                                    const airportSection = document.getElementById("airportSection");
                                    const submitSection = document.getElementById("submitSection");
                                    const airportSelect = document.getElementById("airportSelect");
                                    const locationInput = document.getElementById("locationInput");

                                    // Locations that have only Local Airport
                                    const localOnly = [
                                        "Delta Asaba", "Imo", "Oyo", "Kwara",
                                        "Anambra", "Delta Warri", "Edo", "Gombe", "Borno", "Adamawa",
                                        "Sokoto", "Kaduna", "Cross River"
                                    ];

                                    stateselect.addEventListener("change", function() {
                                        const selected = stateselect.value;
                                        locationInput.value = selected;

                                        if (selected === "") {
                                            airportSection.classList.add("protocol-hide");
                                            submitSection.classList.add("protocol-hide");
                                            airportSelect.innerHTML = "";
                                            return;
                                        }

                                        airportSection.classList.remove("protocol-hide");
                                        submitSection.classList.remove("protocol-hide");

                                        // Clear old options
                                        airportSelect.innerHTML = "";

                                        if (localOnly.includes(selected)) {
                                            // Local only location
                                            airportSelect.innerHTML = `
                                                <option value="Local Airport" selected>Local Airport</option>
                                            `;
                                            airportSelect.setAttribute("readonly", true);
                                        } else {
                                            // Both available
                                            airportSelect.innerHTML = `
                                                <option value="">-- Choose Airport --</option>
                                                <option value="International Airport">International Airport</option>
                                                <option value="Local Airport">Local Airport</option>
                                            `;
                                            airportSelect.removeAttribute("readonly");
                                        }
                                    });
                                </script>
                            </div>


                        </div>
                    </div>
                    <div class="col-md-6 pb-3">

                        <div class="bg-light p-3 shadow-sm protocol"  >
                            <p style="color: rgba(13, 156, 83, 1);"> 
                                <b>The benefits of our Protocol Services depends on the package you selected</b> 
                            </p>
                            <small class="text-muted ">
                                List of Our Services
                            </small>

                            <ul class="list-unstyled"  >
                                <li> <i class="fa fa-check"></i> Meet and Greet.</li>
                                <li><i class="fa fa-check"></i> Exclusive Baggage Handling. </li>
                                <li><i class="fa fa-check"></i> No Queue.</li>
                                <li><i class="fa fa-check"></i> Fast-tracking Check-in Process.</li>
                                <li><i class="fa fa-check"></i> Stress Free Check-in Process.</li>
                                <li><i class="fa fa-check"></i> Escort to the Arrival Lobby. </li> 
                                <li><i class="fa fa-check"></i> Cordinate Passenger to their Pre-arranged Transportation. </li> 
                                <li><i class="fa fa-check"></i> Other relevant Airport Protocol Service as case may be.</li>
                                <li><i class="fa fa-angle-right" style="font-size:24px;"></i> Pick-up Request (Optional)</li>
                                <li><i class="fa fa-angle-right" style="font-size:24px;"></i> Drop-off Request (Optional)</li>
                                <li><i class="fa fa-angle-right" style="font-size:24px;"></i> Police Escort Request (Optional)</li>
                            </ul>
                            <h6>NB: <span > <b style="color:red;">A Protocol Boarding Pass will be generated after a Succefful transaction. It expires after Departure / Arrival date.</span>
                            </b></h6>

                        </div>

                    </div>
                </div>
            </div>
        </section>

    





    </main>

@endcomponent
