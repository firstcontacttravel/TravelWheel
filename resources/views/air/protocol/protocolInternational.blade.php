@component('layouts.app', ['title' => 'Protocol Plans (International) - TravelWheel'])
<style>.hidden{display:none;}</style>

<section class="shadow-sm py-4">
    <div class="container">
        <div class="row pt-3">
            <div class="col-sm-12 p-3">
                <div class="row">
                    <div class="col-3 col-sm-2 col-lg-1">
                        <img src="{{ asset('assets/img/pp.png') }}" class="img-fluid w-100" alt="protocol">
                    </div>
                    <div class="col-12 col-sm-10 col-lg-7">
                        <h3>Airport Protocol and Services</h3>
                        <span class="text-muted">
                            As part of our aggregating effort we can also assist you with some of
                            the services you can get within the airport zone. We are committed to
                            making your travel experience remarkable.
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row pb-5">
            <div class="col-md-5 pb-3">
                <div class="bg-light p-3 shadow-sm">
                    <p style="color:rgba(13,156,83,1);"><b>All the benefits of using our Airport Protocol Services In All Nigeria International Airports</b></p>
                    <input type="hidden" value="{{ Session::get('protocol_data')['service'] ?? '' }}" id="trip">
                    <ul class="list-unstyled">
                        <li><i class="fa fa-check"></i> Meet and Greet.</li>
                        <li><i class="fa fa-check"></i> Exclusive Baggage Handling.</li>
                        <li><i class="fa fa-check"></i> No Queue.</li>
                        <li><i class="fa fa-check"></i> Fast-tracking Check-in Process.</li>
                        <li><i class="fa fa-check"></i> Stress free Check-in Process.</li>
                        <li><i class="fa fa-check"></i> Escort through Boarding Gate.</li>
                        <li><i class="fa fa-check"></i> Escort to the Arrival Lobby.</li>
                        <li><i class="fa fa-check"></i> Cordinate Passenger to their Pre-arranged Transportation.</li>
                        <li><i class="fa fa-check"></i> Other relevant Airport Protocol Service as case may be.</li>
                        <li><i class="fa fa-angle-right" style="font-size:20px;"></i> Pick-up Request (Optional)</li>
                        <li><i class="fa fa-angle-right" style="font-size:20px;"></i> Drop-off Request (Optional)</li>
                        <li><i class="fa fa-angle-right" style="font-size:20px;"></i> Police Escort Request (Optional)</li>
                    </ul>
                    <h6>NB: <b style="color:red;">A Protocol Boarding Pass will be generated after a Successful transaction.</b></h6>
                </div>
            </div>

            <div class="col-md-7">
                @php
                    $formattedprice1 = number_format($price1, 0, '', ',');
                    $formattedprice2 = number_format($price2, 0, '', ',');
                    $plan1 = '1';
                    $plan2 = '2';
                @endphp
                <div class="hidden" id="departure">
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="card p-3 mb-3 shadow-sm">
                                <h5 class="text-center">Regular Plan</h5>
                                <hr>
                                <ul class="list-unstyled">
                                    <li class="text-muted"><i class="fa fa-check text-success"></i> <b>Meet and Greet.</b></li>
                                    <li class="text-muted"><i class="fa fa-check text-success"></i> <b>Exclusive Baggage Handling.</b></li>
                                    <li class="text-muted"><i class="fa fa-check text-success"></i> <b>No Queuing.</b></li>
                                    <li class="text-muted"><i class="fa fa-check text-success"></i> <b>Stress free Check-in Process.</b></li>
                                    <li class="text-muted"><i class="fa fa-check text-success"></i> <b>Other relevant Airport Protocol Service.</b></li>
                                </ul>
                                <div class="text-center">
                                    <p><sup>₦</sup>{{ $formattedprice2 }}<span>/Passenger</span></p>
                                    <a href="{{ route('air.protocolForm', ['plan' => $plan2]) }}" class="btn btn-success">Select This Plan</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="card p-3 mb-3 shadow-sm">
                                <h5 class="text-center">VIP Plan</h5>
                                <hr>
                                <ul class="list-unstyled">
                                    <li class="text-muted"><i class="fa fa-check text-success"></i> <b>Meet and Greet.</b></li>
                                    <li class="text-muted"><i class="fa fa-check text-success"></i> <b>Exclusive Baggage Handling.</b></li>
                                    <li class="text-muted"><i class="fa fa-check text-success"></i> <b>Fast-tracking Check-in Process.</b></li>
                                    <li class="text-muted"><i class="fa fa-check text-success"></i> <b>No Queuing.</b></li>
                                    <li class="text-muted"><i class="fa fa-check text-success"></i> <b>Stress free Check-in Process.</b></li>
                                    <li class="text-muted"><i class="fa fa-check text-success"></i> <b>Escort through Boarding Gate.</b></li>
                                    <li class="text-muted"><i class="fa fa-check text-success"></i> <b>Other relevant Airport Protocol Service.</b></li>
                                </ul>
                                <div class="text-center">
                                    <p><sup>₦</sup>{{ $formattedprice1 }}<span>/Passenger</span></p>
                                    <a href="{{ route('air.protocolForm', ['plan' => $plan1]) }}" class="btn btn-success">Select This Plan</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="hidden" id="arrival">
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="card p-3 mb-3 shadow-sm">
                                <h5 class="text-center">VIP Plan</h5>
                                <hr>
                                <ul class="list-unstyled">
                                    <li class="text-muted"><i class="fa fa-check text-success"></i> <b>Meet and Greet.</b></li>
                                    <li class="text-muted"><i class="fa fa-check text-success"></i> <b>Exclusive baggage handling.</b></li>
                                    <li class="text-muted"><i class="fa fa-check text-success"></i> <b>Escort to the Arrival Lobby.</b></li>
                                    <li class="text-muted"><i class="fa fa-check text-success"></i> <b>Cordinate Passenger to their Pre-arranged Transportation.</b></li>
                                    <li class="text-muted"><i class="fa fa-check text-success"></i> <b>Other relevant Airport Protocol Service.</b></li>
                                </ul>
                                <div class="text-center">
                                    <p><sup>₦</sup>{{ $formattedprice1 }}<span>/Passenger</span></p>
                                    <a href="{{ route('air.protocolForm', ['plan' => $plan1]) }}" class="btn btn-success">Select This Plan</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    var trip      = document.getElementById('trip');
    var arrival   = document.getElementById('arrival');
    var departure = document.getElementById('departure');
    if (trip.value === 'Arrival') arrival.style.display = 'block';
    else if (trip.value === 'Departure') departure.style.display = 'block';
</script>
@endcomponent
