    <style>
        .main-color { color: rgba(13, 24, 131, 1); }
        .price-text { font-size: 2.5rem; font-weight: bolder; color: rgb(9, 9, 9); }
        .card { border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-radius: 1rem; }
        .bg-main { background-color: rgba(13, 24, 131, 1); }
        .btn-main { background-color: rgba(13, 24, 131, 1); color: white; border: none; transition: 0.3s ease; }
        .btn-main:hover { background-color: rgba(9, 18, 100, 1); color: #fff; }
    </style>

    <div class="container col-md-9 pt-5 mt-3">
        @if (session('success'))
          <div class="alert alert-success text-center" id="alert-message">{{ session('success') }}</div>
        @endif
        
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger text-center">{{ session('error') }}</div>
        @endif

        
        <div class="card shadow m-4">
            <div class="card-header bg-main text-white text-center py-3">
                <h4 class="text-white">Yellow Card Assistance</h4>
            </div>

            <div class="card-body">
                <form id="yellowCardForm" method="POST" action="{{ route('air.support.yellow-card') }}" enctype="multipart/form-data">
                    
                    @csrf  

                    <div class="mb-4">
                        <label for="serviceType" class="form-label fw-semibold">Select Service Type</label>
                        <select class="form-select" id="serviceType" name="service_type" required>
                            <option value="" selected disabled>Select an option</option>
                            <option value="standard" {{ old('service_type') == 'standard' ? 'selected' : '' }}>Standard (3 Days)</option>
                            <option value="fasttrack" {{ old('service_type') == 'fasttrack' ? 'selected' : '' }}>Fast Track (24 Hours)</option>
                        </select>
                    </div>

                    <div id="formSection" class="{{ old('service_type') ? '' : 'd-none' }}"> 
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="fullName" class="form-label fw-semibold">Full Name</label>
                                    <input type="text" class="form-control" id="fullName" name="full_name" value="{{ old('full_name') }}" required>
                                </div>

                                <div class="mb-3">
                                    <label for="dataPage" class="form-label fw-semibold">Upload Passport Data Page</label>
                                    {{-- File inputs do not retain their value with old() --}}
                                    <input type="file" class="form-control" id="dataPage" name="data_page" accept=".jpg,.jpeg,.png,.pdf" required> 
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label fw-semibold">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" required>
                                </div>

                                <div class="mb-3">
                                    <label for="paymentOption" class="form-label fw-bold main-color">Select Payment Option</label>
                                    <select class="form-select" id="paymentOption" name="payment_option" required>
                                        <option value="" selected disabled>-- Choose Payment Option --</option>
                                        <option value="budpay">BudPay</option>
                                        <option value="seerbit">SeerBit</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="homeAddress" class="form-label fw-semibold">Home Address</label>
                                    <input type="text" class="form-control" id="homeAddress" name="home_address" value="{{ old('home_address') }}" required>
                                </div>

                                <div class="mb-3">
                                    <label for="phoneNumber" class="form-label fw-semibold">Phone Number</label>
                                    <input type="tel" class="form-control" id="phoneNumber" name="phone_number" value="{{ old('phone_number') }}" required>
                                </div>

                                <div class="mb-3">
                                    <label for="deliveryAddress" class="form-label fw-semibold">Delivery Address</label>
                                    <input type="text" class="form-control" id="deliveryAddress" name="delivery_address" value="{{ old('delivery_address') }}" required>
                                </div>
                            </div>
                        </div>

                        <div class="text-center mb-3 mt-2 {{ old('service_type') ? '' : 'd-none' }}" id="priceContainer">
                            <p class="price-text" id="priceDisplay">
                                {{ old('service_type') == 'fasttrack' ? '₦50,000' : (old('service_type') == 'standard' ? '₦30,000' : '') }}
                            </p>
                        </div>

                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-main px-5 py-2 rounded-pill">Proceed to Payment</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const serviceType = document.getElementById('serviceType');
        const formSection = document.getElementById('formSection');
        const priceContainer = document.getElementById('priceContainer');
        const priceDisplay = document.getElementById('priceDisplay');
        
        function updateDisplay() {
            const selectedValue = serviceType.value;
            
            if (selectedValue) {
                formSection.classList.remove('d-none');
                priceContainer.classList.remove('d-none');
                
                if (selectedValue === 'standard') {
                    priceDisplay.textContent = '₦30,000';
                } else if (selectedValue === 'fasttrack') {
                    priceDisplay.textContent = '₦50,000';
                }
            } else {
                 if (!'{{ old('service_type') }}') { 
                    formSection.classList.add('d-none');
                    priceContainer.classList.add('d-none');
                 }
            }
        }

        serviceType.addEventListener('change', updateDisplay);
    </script>