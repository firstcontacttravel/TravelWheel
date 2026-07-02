@component('layouts.app', ['title' => 'Leadway Insurance - TravelWheel'])

<section class="shadow-sm">
    <div class="container">
        <div class="row p-2 pt-3">
            <div class="col-sm-12 p-3">
                <div class="row">
                    <div class="col-xs-3 col-3 col-sm-2 col-lg-1">
                        <img src="{{ asset('assets/image/leadway.png') }}" class="image-fluid w-100" alt="Leadway">
                    </div>
                    <div class="col-xs-12 col-12 col-sm-10 col-lg-7">
                        <h3>Leadway Insurance</h3>
                        <span class="text-muted">Choose a travel insurance plan that suits your needs.</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row airport-form shadow p-4 mb-5">
            @forelse($data as $product)
            <div class="col-sm-4 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title" style="color:rgba(13,156,83,1);">{{ $product['productName'] ?? $product }}</h5>
                        <p class="card-text text-muted flex-grow-1">
                            Product Code: <strong>{{ $product['productCode'] ?? '' }}</strong>
                        </p>
                        <button
                            class="btn btn-pry mt-auto"
                            onclick="confirmSelect('{{ addslashes(($product['productCode'] ?? '') . ' - ' . ($product['productName'] ?? $product)) }}')"
                        >
                            Select Plan
                        </button>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-12">
                <div class="alert alert-info">No products available at this time. Please try again later.</div>
            </div>
            @endforelse
        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function confirmSelect(prodCode) {
        Swal.fire({
            title: 'Confirm Plan',
            text: 'Proceed with: ' + prodCode + '?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: 'rgba(13,156,83,1)',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, proceed'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '{{ route('air.insuranceLeadwayP') }}?prodCode=' + encodeURIComponent(prodCode);
            }
        });
    }
</script>

@endcomponent
