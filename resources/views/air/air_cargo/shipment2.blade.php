<!DOCTYPE html>
<html>
<head>
    <title>Shipment Details</title>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Open Sans', Arial, sans-serif;
            line-height: 1;
            margin: 0;
            padding: 5px;
        }
        .container {
            width: 100%;
        }
        .header {
            text-align: center;
            margin-bottom: 10px;
        }
        .row {
            clear: both;
            margin-bottom: 20px;
        }
        .col-6 {
            width: 48%;
            float: left;
            margin-right: 2%;
        }
        .col-12 {
            width: 100%;
            clear: both;
        }
        .section {
            border: 1px solid #ddd;
            padding: 10px;
            margin-bottom: 20px;
            background-color: #fff;
            border-radius: 5px;
        }
        .section-title {
            font-weight: bold;
            font-size: 16px;
            background-color: #f8f9fa;
            padding: 8px;
            margin: -15px -15px 15px -15px;
            border-bottom: 1px solid #ddd;
            border-radius: 5px 5px 0 0;
        }
        .details {
            margin-left: 10px;
        }
        .details p {
            margin: 5px 0;
        }
        .total-section {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .total-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }
        .total-row {
            display: block;
            margin: 5px 0;
        }
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
        
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        .divider {
            border-top: 1px solid #ddd;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 10px;
            font-size: 14px;
            color: #666;
        }
        .company-header {
            text-align: right;
            margin-bottom: 30px;
            position: relative;
        }
        .logo-container {
            margin-bottom: 15px;
        }
        .logo {
            height: 40px; /* Adjust based on your logo size */
            width: auto;
            max-width: 400px; /* Adjust based on your needs */
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="company-header">
            <div class="logo-container">
                <img class="logo" src="{{ 'data:image/png;base64,' . base64_encode(file_get_contents('https://travelwheel.ng/travelwheel.png')) }}" alt="TravelWheel">
            </div>
            
        </div>
        <div style="text-align: center;"><h2>SHIPPING DETAILS</h2></div>
        <!-- Total Amount Section -->
        <div class="row">
            <div class="col-12">
                <div class="total-section">
                    <div class="total-title">Total Amount (NGN)</div>
                    <div class="details">
                        <div class="total-row">
                            <strong>Shipping Price:</strong> NGN {{ number_format($shipping_price, 2) }}
                        </div>
                        <div class="total-row">
                        {{--  <strong>VAT:</strong> NGN {{ number_format($vat, 2) }}--}}
                        </div>
                        <div class="total-row">
                        @if(!empty($pickup_price))
                            <strong>Pick-UP Price:</strong> NGN {{ number_format($pickup_price, 2) }}
                        @endif
                        </div>
                        <div class="total-row" style="font-size: 18px; margin-top: 10px; border-top: 1px solid #ddd; padding-top: 10px;">
                            <strong>Total Price:</strong> NGN {{ number_format($total_price, 2) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Shipping From/To Section - Side by Side -->
        <div class="row clearfix">
            <div class="col-6">
                <div class="section">
                    <div class="section-title"><small>Shipping From</small></div>
                    <div class="details">
                        <small><strong>Name:</strong> {{ $sender_name }}</small><br>
                        <small><strong>Address:</strong> {{ $sender_address }}</small><br>
                        <small><strong>Email:</strong> {{ $sender_email }}</small><br>
                        <small><strong>Phone:</strong> {{ $sender_phone }}</small><br>
                        <small><strong>Country:</strong> {{ $sender_country }}</small><br>
                    </div>
                </div>
            </div>
            
            <div class="col-6">
                <div class="section">
                    <div class="section-title"><small>Shipping To</small></div>
                    <div class="details">
                        <small><strong>Name:</strong> {{ $receiver_name }}</small><br>
                        <small><strong>Address:</strong> {{ $receiver_address }}</small><br>
                        <small><strong>Email:</strong> {{ $receiver_email }}</small><br>
                        <small><strong>Phone:</strong> {{ $receiver_phone }}</small><br>
                        <small><strong>Country:</strong> {{ $receiver_country }}</small><br>
                    </div>
                </div>
            </div>
        </div>

        <div class="divider"></div>

        <!-- Shipment Details Section -->
        <div class="row clearfix">
            <div class="col-12">
                <div class="section">
                    <div class="section-title"><small>Shipment Details</small></div>
                    <div class="details">
                        <div class="row clearfix">
                            <div class="col-6">
                                <small><b>Shipment Type:</b> {{ $shipment_type }}</small><br>
                                @if($shipment_type == 'Document')
                                    <small><strong>Document Type:</strong> {{ $document_type ?? '' }}</small><br>
                                    <small><strong>Weight:</strong> {{ $document_weight ?? '' }} kg</small><br>
                                @else
                                    <small><strong>Package Description:</strong> {{ $package_description ?? '' }}</small><br>
                                    <small><strong>Weight:</strong> {{ $package_weight ?? '' }}</small><br>
                                    <small><strong>Volumetric Weight:</strong> {{ $package_volumetric ?? '' }}</small><br>
                                @endif 
                            </div>
                            @if(!empty($pickup_date) && empty($dropoff_date))
                                <div class="col-6">
                                    <small><strong>Pick-up Address:</strong> {{ $pickup_address }}</small><br>
                                    <small><strong>Nearest Bus-stop:</strong> {{ $pickup_busstop }}</small><br>
                                    <small><strong>Pick-Up Date:</strong> {{ $pickup_date }}</small><br>
                                    <small><strong>Pick-up Window:</strong> 9:00AM - 4:00PM</small><br>
                                </div>
                            @elseif(empty($pickup_date) && !empty($dropoff_date))
                                <div class="col-6">
                                    <small><strong>Drop-Off Address:</strong> 74, Ayanguran Road, Ikorodu Garage, Ikorodu, Lagos</small><br>
                                    <small><strong>Drop-Off Date:</strong> {{ $dropoff_date }}</small><br>
                                    <small><strong>Drop-Off Window: </strong> 9:00AM - 5:00PM</small><br>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

       

        <div class="footer">
            <small>Thank you for choosing our services</small><br>
            <small>For any queries, please contact our customer support @ support@travelwheel.ng</small>
        </div>
    </div>
</body>
</html>