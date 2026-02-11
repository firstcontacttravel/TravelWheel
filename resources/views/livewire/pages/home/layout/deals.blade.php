<head>
    <style>
        @media (max-width: 768px) {
            .carousel-inner .carousel-item > div {
                display: none;
            }

            .carousel-inner .carousel-item > div:first-child {
                display: block;
            }
        }

        .btn-pry {
            font-family: var(--font-primary);
            font-weight: var(--font-semibold);
            font-size: var(--text-lg);
            background-color: var(--color-secondary);
            color: white;
        }

        .carousel-inner .carousel-item.active,
        .carousel-inner .carousel-item-start,
        .carousel-inner .carousel-item-next,
        .carousel-inner .carousel-item-prev {
            display: flex;
        }

        @media (min-width: 768px) {
            .carousel-inner .carousel-item-right.active,
            .carousel-inner .carousel-item-next,
            .carousel-item-next:not(.carousel-item-start) {
                transform: translateX(25%) !important;
            }

            .carousel-inner .carousel-item-left.active,
            .carousel-item-prev:not(.carousel-item-end),
            .active.carousel-item-start,
            .carousel-item-prev:not(.carousel-item-end) {
                transform: translateX(-25%) !important;
            }

            .carousel-item-next.carousel-item-start,
            .active.carousel-item-end {
                transform: translateX(0) !important;
            }

            .carousel-inner .carousel-item-prev,
            .carousel-item-prev:not(.carousel-item-end) {
                transform: translateX(-25%) !important;
            }
        }

        .card {
            position: relative;
            overflow: hidden;
        }

        .overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: var(--color-secondary);
            opacity: 0;
            transition: opacity var(--transition-base) ease-in-out;
        }

        .card img {
            width: 100%;
            height: auto;
            display: block;
        }

        .card:hover .overlay {
            opacity: 0.9;
        }

        .vertical-center {
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>

<section class="shadow-sm">
    <div class="container-fluid">
        <div class="row pt-5 partners">
            <h4 class="text-center sub-main-color">Our Deals Offer</h4>
        </div>
        <div class="row">
            <div id="myCarousel" class="carousel slide container" data-bs-ride="carousel">
                <div class="carousel-inner w-100">
                    <div class="carousel-item active">
                        <div class="col-md-3 p-2">
                            <div class="card card-body">
                                <a href="#">
                                    <img class="img-fluid" src="{{asset('assets/image/webad3.jpg')}}">
                                </a>

                            </div>
                        </div>
                    </div>
                    <div class="carousel-item">
                        <div class="col-md-3 p-2">
                            <div class="card card-body image">
                                <a href="#">
                                    <div class="overlay text-center " style="padding-top:80%;"> 
                                        <h3 class=" text-white mb-n4 pb-n5" >Book Now</h3>
                                        <span class=" text-white mt-n4 pt-n5">To get the exclusive deal.</span> 
                                    </div>
                                </a>
                                <img class="img-fluid" src="{{asset('assets/image/webad4.jpg')}}" alt="Your Image">
                            </div>
                        </div>
                    </div>
                    <div class="carousel-item ">
                        <div class="col-md-3 p-2">
                            <div class="card card-body">
                                <img class="img-fluid" src="{{asset('assets/image/webad5.jpg')}}">
                            
                                
                            </div>
                        </div>
                    </div>
                    <div class="carousel-item ">
                        <div class="col-md-3 p-2">
                            <div class="card card-body">
                                <img class="img-fluid" src="{{asset('assets/image/webad1.jpg')}}">
                            
                                
                            </div>
                        </div>
                    </div>
                    <div class="carousel-item ">
                        <div class="col-md-3 p-2">
                            <div class="card card-body">
                                <img class="img-fluid" src="{{asset('assets/image/webad2.jpg')}}">
                            
                                
                            </div>
                        </div>
                    </div>
                    <div class="carousel-item ">
                        <div class="col-md-3 p-2">
                            <div class="card card-body">
                                <img class="img-fluid" src="{{asset('assets/image/webad7.jpg')}}">
                            
                                
                            </div>
                        </div>
                    </div>
                    
                    
                    <div class="carousel-item ">
                        <div class="col-md-3 p-2">
                            <div class="card card-body">
                                <img class="img-fluid" src="{{asset('assets/image/webad6.jpg')}}">
                            
                                
                            </div>
                        </div>
                    </div>
                    
                    
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#myCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#myCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                </button>
            </div>
        </div>
    </div>


    <script src='https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js'></script>
    <script>
        $('.carousel .carousel-item').each(function () {
var minPerSlide = 4;
var next = $(this).next();
if (!next.length) {
next = $(this).siblings(':first');
}
next.children(':first-child').clone().appendTo($(this));

for (var i = 0; i < minPerSlide; i++) { next=next.next(); if (!next.length) { next=$(this).siblings(':first'); } next.children(':first-child').clone().appendTo($(this)); } });
    </script>
</section>
