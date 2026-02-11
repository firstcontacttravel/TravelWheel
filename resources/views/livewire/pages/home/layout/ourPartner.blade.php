<style>
    .sliderLOGO {
        height: 100px;
        position: relative;
        width: 100%;
        display: grid;
        place-items: center;
        overflow: hidden;
    }
    
    .sliderLOGO::before,
    .sliderLOGO::after {
        position: absolute;
        background-image: linear-gradient(to right, rgba(255,255,255,1) 0%, rgba(255,255,255,0) 100%);
        content: '';
        height: 100%;
        width: 25%;
        z-index: 2;
        pointer-events: none;
    }
    
    .sliderLOGO::before {
        left: 0;
        top: 0;
    }
    
    .sliderLOGO::after {
        right: 0;
        top: 0;
        transform: rotateZ(180deg);
    }

    .slide-track {
        width: calc(150px * 20);
        display: flex;
        animation: scroll 20s linear infinite;
        justify-content: space-between;
    }

    .slideLOGO {
        width: 200px;
        height: 60px;
        display: grid;
        place-items: center;
        transition: var(--transition-base);
        cursor: pointer;
    }
    
    .slideLOGO:hover {
        transform: scale(0.8);
    }

    @keyframes scroll {
        0% {
            transform: translateX(0px);
        }
        100% {
            transform: translateX(calc(-150px * 10));
        }
    }
</style>

<section class="shadow-sm">
    <div class="container-fluid">
        <div class="row p-4 pt-5 partners">
            <h4 class="text-center sub-main-color">Strategic Partners</h4>
        </div>

        <div class="row p-4">
            <div class="container h-100">
                <div class="row align-items-center h-100">
                    <div class="container rounded">
                        <div class="sliderLOGO">
                            <div class="slide-track">
                                <div class="slideLOGO"><img src="assets/img/First-con.png" alt="" class="ms-4 me-4"></div>
                                <div class="slideLOGO"><img src="assets/img/TL.png" alt="" class="ms-4 me-4"></div>
                                <div class="slideLOGO"><img src="assets/img/HOB.png" alt="" class="ms-4 me-4"></div>
                                <div class="slideLOGO"><img src="assets/img/natureborn.png" alt="" class="ms-4 me-4"></div>
                                <div class="slideLOGO"><img src="assets/img/upstarts.png" alt="" class="ms-4 me-4"></div>
                                <div class="slideLOGO"><img src="assets/img/Rebate.jpg" alt="" class="ms-4 me-4"></div>
                                <div class="slideLOGO"><img src="assets/img/airspace.png" alt="" class="ms-4 me-4"></div>
                                <div class="slideLOGO"><img src="assets/img/allianz.png" alt="" class="ms-4 me-4"></div>
                                <div class="slideLOGO"><img src="assets/img/oasis.png" alt="" class="ms-4 me-4"></div>
                                <div class="slideLOGO"><img src="assets/img/loungeone.png" alt="" class="ms-4 me-4"></div>
                                <div class="slideLOGO"><img src="assets/img/soula.png" alt="" class="ms-4 me-4"></div>
                                <div class="slideLOGO"><img src="assets/img/Spiffy.png" alt="" class="ms-4 me-4"></div>
                                <div class="slideLOGO"><img src="assets/img/First-con.png" alt="" class="ms-4 me-4"></div>
                                <div class="slideLOGO"><img src="assets/img/TL.png" alt="" class="ms-4 me-4"></div>
                                <div class="slideLOGO"><img src="assets/img/HOB.png" alt="" class="ms-4 me-4"></div>
                                <div class="slideLOGO"><img src="assets/img/Rebate.jpg" alt="" class="ms-4 me-4"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
 
