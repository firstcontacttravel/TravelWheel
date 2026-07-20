  <style>
    body {
      background-color: #f8f9fa;
    }
    .card {
      border-radius: 15px;
      overflow: hidden;
    }
    .main-color {
      color: rgba(13, 24, 131, 1);
    }
    .bg-main {
      background-color: rgba(13, 24, 131, 1);
    }
    .btn-main {
      background-color: rgba(13, 24, 131, 1);
      color: white;
      border: none;
      transition: 0.3s ease;
    }
    .btn-main:hover {
      background-color: rgba(9, 18, 100, 1);
      color: #fff;
    }
    .form-section {
      display: none;
      transition: all 0.4s ease;
    }
    .price-display {
      font-size: 2.5rem;
      font-weight: bolder;
      text-align: center;
      color: rgba(13, 24, 131, 1);
      margin-top: 1.5rem;
    }
    .card-body > .card {
      margin-bottom: 1rem;
    }
  </style>

  <div class="container col-md-9 pt-5 mt-3">
    <div class="shadow m-4 card">
      <div class="card-header bg-main text-white text-center py-3">
        <h4 class="text-white">FlightFlex Assist</h4>
      </div>

      <div class="card-body">

        {{--  Success Message --}}
        @if(session('success'))
          <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        {{-- Error Message --}}
        @if(session('error'))
          <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('air.support.flight-assist') }}">
          @csrf

          {{-- Row: Request type + Booking source --}}
          <div class="row mb-4">
            <div class="col-md-6">
              <label class="form-label">Select Request Type</label>
              <select name="request_type" id="request_type" class="form-select" required>
                <option value="">-- Choose Option --</option>
                <option value="date_change">Date Change</option>
                <option value="rerouting">Rerouting</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">Confirm where you booked from</label>
              <select name="booking_source" id="booking_source" class="form-select" required>
                <option value="">-- Select Source --</option>
                <option value="airline">From Airline Website</option>
                <option value="agent">From Travelling Agent</option>
              </select>
            </div>
          </div>

          {{-- Agent Message --}}
          <div id="agent_message" class="alert alert-warning" style="display:none;">
            Please contact your travel agent for any changes or rerouting requests.
          </div>

          {{-- Airline Form --}}
          <div id="airline_form" class="form-section">
            <div class="row g-3">
              {{-- Name & Reference --}}
              <div class="col-md-6">
                <label class="form-label">Name on Ticket</label>
                <input type="text" name="name_on_ticket" class="form-control" required>
              </div>

              <div class="col-md-6">
                <label class="form-label">Airline Reference</label>
                <input type="text" name="airline_reference" class="form-control" required>
              </div>

              {{-- Airline Category & Selection --}}
              <div class="col-md-6">
                <label class="form-label">Select Airline Category</label>
                <select id="airline_category" name="airline_category" class="form-select" required>
                  <option value="">-- Select Category --</option>
                  <option value="local">Local Airline</option>
                  <option value="international">International Airline</option>
                </select>
              </div>

              <div class="col-md-6">
                <label class="form-label">Select Airline</label>
                <select id="airline" name="airline" class="form-select" required>
                  <option value="">-- Select Airline --</option>
                </select>
              </div>

              {{-- Trip Type --}}
              <div class="col-md-6">
                <label class="form-label">Trip Type</label>
                <select name="trip_type" id="trip_type" class="form-select" required>
                  <option value="">-- Select Trip Type --</option>
                  <option value="one_way">One Way</option>
                  <option value="round_trip">Round Trip</option>
                </select>
              </div>

              {{-- One Way Date --}}
              <div class="col-md-6" id="one_way_date" style="display:none;">
                <label class="form-label">Travel Date</label>
                <input type="date" name="travel_date_oneway" class="form-control">
              </div>

              {{-- Round Trip Dates --}}
              <div class="col-md-6" id="round_trip_dates" style="display:none;">
                <div class="row">
                  <div class="col-md-6">
                    <label class="form-label">Departure Date</label>
                    <input type="date" name="departure_date" class="form-control">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Return Date</label>
                    <input type="date" name="return_date" class="form-control">
                  </div>
                </div>
              </div>
            </div> 

            {{-- Route row --}}
            <div class="row g-3 mt-2">
              <div class="col-md-6">
                <label class="form-label">Route From</label>
                <input type="text" name="route_from" id="route_from" class="form-control" placeholder="Search airport...">
                <div id="route_from_results" class="list-group"></div>
              </div>

              <div class="col-md-6">
                <label class="form-label">Route To</label>
                <input type="text" name="route_to" id="route_to" class="form-control" placeholder="Search airport...">
                <div id="route_to_results" class="list-group"></div>
              </div>
            </div> 

            {{-- Preferred Time, Phone, Email, Payment, Additional Info --}}
            <div class="row g-3 mt-2">
              <div class="col-md-6">
                <label class="form-label">Preferred Time</label>
                <input type="text" name="preferred_time" class="form-control" placeholder="Morning / Evening">
              </div>

              <div class="col-md-6">
                <label class="form-label">Phone Number</label>
                <input type="text" name="phone" class="form-control" required>
              </div>

              <div class="col-md-6">
                <label class="form-label">Email (as on ticket)</label>
                <input type="email" name="email" class="form-control" required>
              </div>

              <div class="col-md-6">
                <label for="paymentOption" class="form-label fw-bold main-color">Select Payment Option</label>
                <select class="form-select" id="paymentOption" name="payment_option" required>
                  <option value="" selected disabled>-- Choose Payment Option --</option>
                  <option value="budpay">BudPay</option>
                  <option value="seerbit">SeerBit</option>
                </select>
              </div>

              <div class="col-md-6">
                <label class="form-label">Additional Information</label>
                <textarea name="additional_info" class="form-control" rows="1" placeholder="Provide more details if needed..."></textarea>
              </div>

              {{-- First price-display --}}
              <div class="price-display">
                <input type="hidden" name="amount" id="hiddenAmountInput" value="25000">
              </div>

            </div>

            <div class="price-display mt-2" id="price_display">
              ₦25,000
              <input type="hidden" name="amount" value="25000">
            </div>

            {{-- Submit button --}}
            <div class="text-center mt-3">
              <button type="submit" class="btn btn-main px-5 py-2 rounded-pill">
                Proceed to Payment
              </button>
            </div>

          </div>

        </form>

      </div> 
    </div>
  </div> 

  @push('scripts')
  <script>
    $(document).ready(function() {

      $('#airline_category').on('change', function() {
        const category = $(this).val();
        const airlineSelect = $('#airline');
        airlineSelect.empty().append('<option value="">-- Select Airline --</option>');

        if (category === 'local') {
          airlineSelect.append(`
            <option value="Air Peace">Air Peace</option>
            <option value="Dana Air">Dana Air</option>
            <option value="Ibom Air">Ibom Air</option>
            <option value="Arik Air">Arik Air</option>
            <option value="Max Air">Max Air</option>
            <option value="Azman Air">Azman Air</option>
            <option value="Aero Contractors">Aero Contractors</option>
            <option value="Overland Airways">Overland Airways</option>
            <option value="Med-View Airlines">Med-View Airlines</option>
            <option value="Dornier Aviation Nigeria">Dornier Aviation Nigeria</option>
            <option value="Green Africa Airways">Green Africa Airways</option>
            <option value="ValueJet Nigeria ">ValueJet Nigeria </option>
            <option value="Kabo Air ">Kabo Air </option>
            <option value="First Nation Airways">First Nation Airways</option>
            <option value="Hak Air">Hak Air</option>
            <option value="Associated Airlines">Associated Airlines</option>
            <option value="Rano Air">Rano Air</option>
            <option value="Umza Air">Umza Air</option>
            <option value="XEjet">XEjet</option>
            <option value="United Nigeria">United Nigeria</option>
          `);
        } else if (category === 'international') {
          airlineSelect.append(`
            <option value="Qatar Airways">Qatar Airways</option>
            <option value="Emirates">Emirates</option>
            <option value="British Airways">British Airways</option>
            <option value="Ethiopian Airlines">Ethiopian Airlines</option>
            <option value="Lufthansa">Lufthansa</option>
            <option value="KLM">KLM (Royal Dutch Airlines)</option>
            <option value="Turkish Airlines">Turkish Airlines</option>
            <option value="Air France">Air France</option>
            <option value="EgyptAir">EgyptAir</option>
            <option value="South African Airways">South African Airways</option>
            <option value="Delta Airlines">Delta Airlines</option>
            <option value="Virgin Atlantic">Virgin Atlantic</option>
            <option value="RwandAir">RwandAir</option>
            <option value="Royal Air Maroc">Royal Air Maroc</option>
            <option value="ASKY Airlines">ASKY Airlines</option>
            <option value="FGB">FGB</option>
            <option value="Kenya Airways">Kenya Airways</option>
            <option value="Air Maroc">Air Maroc</option>
            <option value="Airpeace">Airpeace</option>
            <option value="Taag Angola">Taag Angola</option>
            <option value="Uganda Airlines">Uganda Airlines</option>
            <option value="United Airlines">United Airlines</option>
          `);
        }
      });

      $('#trip_type').on('change', function() {
        const tripType = $(this).val();
        if (tripType === 'one_way') {
          $('#one_way_date').show();
          $('#round_trip_dates').hide();
        } else if (tripType === 'round_trip') {
          $('#one_way_date').hide();
          $('#round_trip_dates').show();
        } else {
          $('#one_way_date, #round_trip_dates').hide();
        }
      });

      function toggleSections() {
        const requestType = $('#request_type').val();
        const bookingSource = $('#booking_source').val();

        if (bookingSource === 'agent') {
          $('#agent_message').fadeIn();
          $('#airline_form').slideUp();
        } else if ((requestType === 'date_change' || requestType === 'rerouting') && bookingSource === 'airline') {
          $('#agent_message').hide();
          $('#airline_form').slideDown();
        } else {
          $('#agent_message').hide();
          $('#airline_form').slideUp();
        }
      }

      $('#request_type, #booking_source').on('change', toggleSections);

    });
  </script>

  <script>
    $(document).ready(function () {

      let airports = [];

      $.getJSON("{{ asset('assets/data/airports.json') }}", function (data) {
        airports = data;
      });

      function setupAutocomplete(inputId, resultId) {
        $("#" + inputId).on("input", function () {
          const query = $(this).val().toLowerCase();
          const resultBox = $("#" + resultId);

          resultBox.empty();

          if (query.length < 2) {
            return;
          }

          const matches = airports.filter(ap =>
            (ap.name && ap.name.toLowerCase().includes(query)) ||
            (ap.city && ap.city.toLowerCase().includes(query)) ||
            (ap.country && ap.country.toLowerCase().includes(query)) ||
            (ap.iata && ap.iata.toLowerCase().includes(query))
          ).slice(0, 10); // show max 10

          matches.forEach(ap => {
            const item = `
              <a href="#" class="list-group-item list-group-item-action airport-item"
                 data-value="${ap.name} (${ap.iata}) - ${ap.iata}, ${ap.iso}">
                <strong>${ap.iata}</strong> — ${ap.name} (${ap.iata}, ${ap.iso})
              </a>
            `;
            resultBox.append(item);
          });
        });

        $(document).on("click", "#" + resultId + " .airport-item", function (e) {
          e.preventDefault();
          const value = $(this).data("value");
          $("#" + inputId).val(value);
          $("#" + resultId).empty();
        });
      }
      setupAutocomplete("route_from", "route_from_results");
      setupAutocomplete("route_to", "route_to_results");

    });
  </script>
  @endpush

