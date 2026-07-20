  <style>
    .main-color {
      color: rgba(13, 24, 131, 1);
    }
    .price-text {
      font-weight: bolder;
      color: rgba(13, 24, 131, 1);
      font-size: 2.5rem;
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
  </style>

  <div class="container col-md-9 pt-5 mt-3">
    <div class="card shadow m-4">
      <div class="card-header bg-main text-white text-center py-3">
        <h4 class="text-white">VISA Confirmation Form</h4>
      </div>

      <div class="card-body p-4 p-md-5">

        {{-- ✅ Success Message --}}
        @if (session('success'))
          <div class="alert alert-success text-center" id="alert-message">{{ session('success') }}</div>
        @endif

        {{-- ✅ Error Messages --}}
        @if ($errors->any())
          <div class="alert alert-danger" id="alert-message">
            <ul class="mb-0">
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger text-center">{{ session('error') }}</div>
        @endif




        {{-- ✅ Visa Confirmation Form --}}
        <form action="{{ route('air.support.visa-confirmation') }}" method="POST" enctype="multipart/form-data">
          @csrf

          <div class="row g-4">
            <!-- Left Column -->
            <div class="col-md-6">
              <!-- Full Name -->
              <div class="mb-3">
                <label for="fullName" class="form-label fw-semibold">Full Name</label>
                <input type="text" class="form-control" id="fullName" name="full_name" placeholder="Enter your full name" required>
              </div>

              <!-- Email -->
              <div class="mb-3">
                <label for="email" class="form-label fw-semibold">Email</label>
                <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email address" required>
              </div>

              <!-- Upload Visa -->
              <div class="mb-3">
                <label for="visaUpload" class="form-label fw-semibold">Upload Visa Document</label>
                <input type="file" class="form-control" id="visaUpload" name="visa_file" accept=".pdf,.jpg,.png" required>
                <div class="form-text">Accepted formats: PDF, JPG, PNG</div>
              </div>
            </div>

            <!-- Right Column -->
            <div class="col-md-6">
              <!-- Phone Number -->
              <div class="mb-3">
                <label for="phoneNumber" class="form-label fw-semibold">Phone Number</label>
                <input type="tel" class="form-control" id="phoneNumber" name="phone_number" placeholder="Enter your phone number" required>
              </div>

              <!-- Payment Method -->
              <div class="mb-3">
              <label for="paymentOption" class="form-label fw-bold main-color">Select Payment Option</label>
              <select class="form-select" id="paymentOption" name="payment_option" required>
                <option value="" selected disabled>-- Choose Payment Option --</option>
                <option value="budpay">BudPay</option>
                <option value="seerbit">SeerBit</option>
              </select>
              </div>

              <div class="mb-3">
                <label for="additionalInfo" class="form-label fw-semibold">Additional Information</label>
                <textarea class="form-control" id="additionalInfo" name="additional_info" rows="2" placeholder="Add any necessary details here..."></textarea>
              </div>

              <div class="price-display" id="price_display">
              <input type="hidden" name="amount" id="hiddenAmountInput" value="50000"> 
              </div>
              
            </div>
          </div>

          <!-- Price Text -->
          <div class="text-center mt-2">
            <p class="price-text">₦50,000</p>
          </div>

          <!-- Proceed Button -->
          <div class="text-center mt-3">
            <button type="submit" class="btn btn-main btn-lg px-4 rounded-3">Proceed to Payment</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  @push('scripts')
  <script>
  // Wait until the page fully loads
    document.addEventListener('DOMContentLoaded', function() {
      const alertBox = document.getElementById('alert-message');
      if (alertBox) {
        setTimeout(() => {
          alertBox.style.transition = 'opacity 0.5s ease';
          alertBox.style.opacity = '0';
          setTimeout(() => alertBox.remove(), 500); // remove from DOM after fade-out
        }, 10000); // 10 seconds = 10000ms
      }
    });
  </script>
  @endpush
