@component('layouts.app', ['title' => 'Create Shipment - TravelWheel'])

@push('head')
<link rel="stylesheet" href="{{ asset('assets/ship/assets/css/bootstrap-datetimepicker.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/ship/style.css') }}">
<link rel="stylesheet" href="{{ asset('assets/ship/assets/css/responsive.css') }}">
<style>
  #area-autocomplete-container { position: relative; max-width: 100%; }
  #area-input { width: 100%; padding: 10px; font-size: 14px; border: 1px solid #ccc; border-radius: 4px; }
  #area-suggestions { position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #ccc; border-top: none; border-radius: 0 0 4px 4px; max-height: 200px; overflow-y: auto; display: none; z-index: 1000; }
  .area-suggestion-item { padding: 8px 10px; cursor: pointer; }
  .area-suggestion-item:hover { background-color: #f0f0f0; }
</style>
@endpush

<div>
<div class="main-wrapper">
  <div class="wshipping-content-block shipping-block">
    <div class="container">
      <h2 class="heading2-border mt0">Create New Shipping</h2>
      <div class="row">
        <div class="shipping-form-block">
          <form class="steps" id="myForm" action="{{ route('air.cargo.post') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <!-- progressbar -->
            <ul id="progressbar">
              <li class="active">From</li>
              <li>To</li>
              <li>Details</li>
              <li>How</li>
              <li>Review</li>
              <li>Payment</li>
              <li>Complete</li>
            </ul>

            <!-- Step 1: From -->
            <fieldset>
              <h2 class="fs-title">Welcome, where are you shipping from?</h2>
              <h3 class="fs-subtitle">* Indicates required field</h3>
              <div class="shipping-form">
                <div class="row">
                  <div class="col-12 col-lg-9">
                    <div class="form-group">
                      <label>Address<sup>*</sup></label>
                      <input type="text" id="autocomplete1" class="form-control" name="address1" required autocomplete="off" placeholder="Your Address" oninput="updateParagraph()"/>
                    </div>
                  </div>
                  <div class="col-12 col-lg-3">
                    <div class="form-group">
                      <label>Postal Code<sup>*</sup></label>
                      <input type="text" id="postalcode1" class="form-control" required name="postalcode1"/>
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-12 col-lg-3">
                    <div class="form-group">
                      <label>Country<sup>*</sup></label>
                      <select name="country" class="form-control" id="countrySelect1" onchange="updateParagraph()">
                        <option value="NG" data-category="zone_2" data-name="Nigeria" selected>Nigeria</option>
                        <option value="GB" data-category="zone_1" data-name="United Kingdom">United Kingdom</option>
                        <option value="IE" data-category="zone_1" data-name="Ireland">Ireland</option>
                        <option value="JE" data-category="zone_1" data-name="Jersey">Jersey</option>
                        <option value="GG" data-category="zone_1" data-name="Guernsey">Guernsey</option>
                        <option value="US" data-category="zone_3" data-name="United States">United States</option>
                        <option value="CA" data-category="zone_3" data-name="Canada">Canada</option>
                        <option value="AL" data-category="zone_4" data-name="Albania">Albania</option>
                        <option value="AD" data-category="zone_4" data-name="Andorra">Andorra</option>
                        <option value="AT" data-category="zone_4" data-name="Austria">Austria</option>
                        <option value="BY" data-category="zone_4" data-name="Belarus">Belarus</option>
                        <option value="BE" data-category="zone_4" data-name="Belgium">Belgium</option>
                        <option value="BA" data-category="zone_4" data-name="Bosnia & Herzegovina">Bosnia & Herzegovina</option>
                        <option value="BG" data-category="zone_4" data-name="Bulgaria">Bulgaria</option>
                        <option value="HR" data-category="zone_4" data-name="Croatia">Croatia</option>
                        <option value="CY" data-category="zone_4" data-name="Cyprus">Cyprus</option>
                        <option value="CZ" data-category="zone_4" data-name="Czech Republic">Czech Republic</option>
                        <option value="DK" data-category="zone_4" data-name="Denmark">Denmark</option>
                        <option value="EE" data-category="zone_4" data-name="Estonia">Estonia</option>
                        <option value="FO" data-category="zone_4" data-name="Faroe Islands">Faroe Islands</option>
                        <option value="FI" data-category="zone_4" data-name="Finland">Finland</option>
                        <option value="FR" data-category="zone_4" data-name="France">France</option>
                        <option value="DE" data-category="zone_4" data-name="Germany">Germany</option>
                        <option value="GI" data-category="zone_4" data-name="Gibraltar">Gibraltar</option>
                        <option value="GR" data-category="zone_4" data-name="Greece">Greece</option>
                        <option value="GL" data-category="zone_4" data-name="Greenland">Greenland</option>
                        <option value="HU" data-category="zone_4" data-name="Hungary">Hungary</option>
                        <option value="IS" data-category="zone_4" data-name="Iceland">Iceland</option>
                        <option value="IT" data-category="zone_4" data-name="Italy">Italy</option>
                        <option value="LV" data-category="zone_4" data-name="Latvia">Latvia</option>
                        <option value="LI" data-category="zone_4" data-name="Liechtenstein">Liechtenstein</option>
                        <option value="LT" data-category="zone_4" data-name="Lithuania">Lithuania</option>
                        <option value="LU" data-category="zone_4" data-name="Luxembourg">Luxembourg</option>
                        <option value="MK" data-category="zone_4" data-name="North Macedonia">North Macedonia</option>
                        <option value="MT" data-category="zone_4" data-name="Malta">Malta</option>
                        <option value="MD" data-category="zone_4" data-name="Moldova">Moldova</option>
                        <option value="MC" data-category="zone_4" data-name="Monaco">Monaco</option>
                        <option value="ME" data-category="zone_4" data-name="Montenegro">Montenegro</option>
                        <option value="NL" data-category="zone_4" data-name="Netherlands">Netherlands</option>
                        <option value="NO" data-category="zone_4" data-name="Norway">Norway</option>
                        <option value="PL" data-category="zone_4" data-name="Poland">Poland</option>
                        <option value="PT" data-category="zone_4" data-name="Portugal">Portugal</option>
                        <option value="RO" data-category="zone_4" data-name="Romania">Romania</option>
                        <option value="MF" data-category="zone_4" data-name="Saint Martin">Saint Martin</option>
                        <option value="SM" data-category="zone_4" data-name="San Marino">San Marino</option>
                        <option value="RS" data-category="zone_4" data-name="Serbia">Serbia</option>
                        <option value="SK" data-category="zone_4" data-name="Slovakia">Slovakia</option>
                        <option value="SI" data-category="zone_4" data-name="Slovenia">Slovenia</option>
                        <option value="ES" data-category="zone_4" data-name="Spain">Spain</option>
                        <option value="SE" data-category="zone_4" data-name="Sweden">Sweden</option>
                        <option value="CH" data-category="zone_4" data-name="Switzerland">Switzerland</option>
                        <option value="VA" data-category="zone_4" data-name="Vatican City">Vatican City</option>
                        <option value="DZ" data-category="zone_5" data-name="Algeria">Algeria</option>
                        <option value="AO" data-category="zone_5" data-name="Angola">Angola</option>
                        <option value="BW" data-category="zone_5" data-name="Botswana">Botswana</option>
                        <option value="BI" data-category="zone_5" data-name="Burundi">Burundi</option>
                        <option value="KM" data-category="zone_5" data-name="Comoros">Comoros</option>
                        <option value="DJ" data-category="zone_5" data-name="Djibouti">Djibouti</option>
                        <option value="EG" data-category="zone_5" data-name="Egypt">Egypt</option>
                        <option value="ER" data-category="zone_5" data-name="Eritrea">Eritrea</option>
                        <option value="SZ" data-category="zone_5" data-name="Eswatini">Eswatini</option>
                        <option value="ET" data-category="zone_5" data-name="Ethiopia">Ethiopia</option>
                        <option value="KE" data-category="zone_5" data-name="Kenya">Kenya</option>
                        <option value="LS" data-category="zone_5" data-name="Lesotho">Lesotho</option>
                        <option value="LY" data-category="zone_5" data-name="Libya">Libya</option>
                        <option value="MG" data-category="zone_5" data-name="Madagascar">Madagascar</option>
                        <option value="MW" data-category="zone_5" data-name="Malawi">Malawi</option>
                        <option value="MR" data-category="zone_5" data-name="Mauritania">Mauritania</option>
                        <option value="MU" data-category="zone_5" data-name="Mauritius">Mauritius</option>
                        <option value="MA" data-category="zone_5" data-name="Morocco">Morocco</option>
                        <option value="MZ" data-category="zone_5" data-name="Mozambique">Mozambique</option>
                        <option value="NA" data-category="zone_5" data-name="Namibia">Namibia</option>
                        <option value="PG" data-category="zone_5" data-name="Papua New Guinea">Papua New Guinea</option>
                        <option value="RW" data-category="zone_5" data-name="Rwanda">Rwanda</option>
                        <option value="SC" data-category="zone_5" data-name="Seychelles">Seychelles</option>
                        <option value="SL" data-category="zone_5" data-name="Sierra Leone">Sierra Leone</option>
                        <option value="SB" data-category="zone_5" data-name="Solomon Islands">Solomon Islands</option>
                        <option value="SO" data-category="zone_5" data-name="Somalia">Somalia</option>
                        <option value="ZA" data-category="zone_5" data-name="South Africa">South Africa</option>
                        <option value="SS" data-category="zone_5" data-name="South Sudan">South Sudan</option>
                        <option value="SD" data-category="zone_5" data-name="Sudan">Sudan</option>
                        <option value="SR" data-category="zone_5" data-name="Suriname">Suriname</option>
                        <option value="TZ" data-category="zone_5" data-name="Tanzania">Tanzania</option>
                        <option value="TG" data-category="zone_2" data-name="Togo">Togo</option>
                        <option value="TN" data-category="zone_5" data-name="Tunisia">Tunisia</option>
                        <option value="UG" data-category="zone_5" data-name="Uganda">Uganda</option>
                        <option value="ZM" data-category="zone_5" data-name="Zambia">Zambia</option>
                        <option value="ZW" data-category="zone_5" data-name="Zimbabwe">Zimbabwe</option>
                        <option value="AF" data-category="zone_6" data-name="Afghanistan">Afghanistan</option>
                        <option value="BH" data-category="zone_6" data-name="Bahrain">Bahrain</option>
                        <option value="IR" data-category="zone_6" data-name="Iran">Iran</option>
                        <option value="IQ" data-category="zone_6" data-name="Iraq">Iraq</option>
                        <option value="IL" data-category="zone_6" data-name="Israel">Israel</option>
                        <option value="JO" data-category="zone_6" data-name="Jordan">Jordan</option>
                        <option value="KW" data-category="zone_6" data-name="Kuwait">Kuwait</option>
                        <option value="LB" data-category="zone_6" data-name="Lebanon">Lebanon</option>
                        <option value="OM" data-category="zone_6" data-name="Oman">Oman</option>
                        <option value="QA" data-category="zone_6" data-name="Qatar">Qatar</option>
                        <option value="SA" data-category="zone_6" data-name="Saudi Arabia">Saudi Arabia</option>
                        <option value="SY" data-category="zone_6" data-name="Syria">Syria</option>
                        <option value="AE" data-category="zone_6" data-name="United Arab Emirates">United Arab Emirates</option>
                        <option value="YE" data-category="zone_6" data-name="Yemen">Yemen</option>
                        <option value="AU" data-category="zone_7" data-name="Australia">Australia</option>
                        <option value="AZ" data-category="zone_7" data-name="Azerbaijan">Azerbaijan</option>
                        <option value="BD" data-category="zone_7" data-name="Bangladesh">Bangladesh</option>
                        <option value="BN" data-category="zone_7" data-name="Brunei">Brunei</option>
                        <option value="KH" data-category="zone_7" data-name="Cambodia">Cambodia</option>
                        <option value="CN" data-category="zone_7" data-name="China">China</option>
                        <option value="GE" data-category="zone_7" data-name="Georgia">Georgia</option>
                        <option value="HK" data-category="zone_7" data-name="Hong Kong">Hong Kong</option>
                        <option value="IN" data-category="zone_7" data-name="India">India</option>
                        <option value="ID" data-category="zone_7" data-name="Indonesia">Indonesia</option>
                        <option value="JP" data-category="zone_7" data-name="Japan">Japan</option>
                        <option value="KZ" data-category="zone_7" data-name="Kazakhstan">Kazakhstan</option>
                        <option value="KP" data-category="zone_7" data-name="North Korea">North Korea</option>
                        <option value="KR" data-category="zone_7" data-name="South Korea">South Korea</option>
                        <option value="KG" data-category="zone_7" data-name="Kyrgyzstan">Kyrgyzstan</option>
                        <option value="LA" data-category="zone_7" data-name="Laos">Laos</option>
                        <option value="MO" data-category="zone_7" data-name="Macau">Macau</option>
                        <option value="MY" data-category="zone_7" data-name="Malaysia">Malaysia</option>
                        <option value="MV" data-category="zone_7" data-name="Maldives">Maldives</option>
                        <option value="MN" data-category="zone_7" data-name="Mongolia">Mongolia</option>
                        <option value="MM" data-category="zone_7" data-name="Myanmar">Myanmar</option>
                        <option value="NP" data-category="zone_7" data-name="Nepal">Nepal</option>
                        <option value="NZ" data-category="zone_8" data-name="New Zealand">New Zealand</option>
                        <option value="PK" data-category="zone_7" data-name="Pakistan">Pakistan</option>
                        <option value="PH" data-category="zone_7" data-name="Philippines">Philippines</option>
                        <option value="RU" data-category="zone_7" data-name="Russia">Russia</option>
                        <option value="SG" data-category="zone_7" data-name="Singapore">Singapore</option>
                        <option value="LK" data-category="zone_7" data-name="Sri Lanka">Sri Lanka</option>
                        <option value="TW" data-category="zone_7" data-name="Taiwan">Taiwan</option>
                        <option value="TJ" data-category="zone_7" data-name="Tajikistan">Tajikistan</option>
                        <option value="TH" data-category="zone_7" data-name="Thailand">Thailand</option>
                        <option value="TR" data-category="zone_7" data-name="Turkey">Turkey</option>
                        <option value="TM" data-category="zone_7" data-name="Turkmenistan">Turkmenistan</option>
                        <option value="UA" data-category="zone_7" data-name="Ukraine">Ukraine</option>
                        <option value="UZ" data-category="zone_7" data-name="Uzbekistan">Uzbekistan</option>
                        <option value="VN" data-category="zone_7" data-name="Vietnam">Vietnam</option>
                      </select>
                    </div>
                  </div>
                  <div class="col-12 col-lg-3">
                    <div class="form-group">
                      <label>Full Name<sup>*</sup></label>
                      <input type="text" class="form-control" name="fullname" id="fullname" required oninput="updateParagraph()"/>
                    </div>
                  </div>
                  <div class="col-12 col-lg-3">
                    <div class="form-group">
                      <label>Phone Number<sup>*</sup></label>
                      <input type="text" class="form-control" name="phone_no" id="phone_no" required oninput="updateParagraph()"/>
                    </div>
                  </div>
                  <div class="col-12 col-lg-3">
                    <div class="form-group">
                      <label>E-mail<sup>*</sup></label>
                      <input type="email" class="form-control" name="email" id="email" required oninput="updateParagraph()"/>
                    </div>
                  </div>
                </div>
              </div>
              <input type="button" name="next" class="next action-button" value="Continue"/>
              <input type="reset" name="cancel" class="action-button btn-blue" value="Cancel Shipment"/>
            </fieldset>

            <!-- Step 2: To -->
            <fieldset>
              <h2 class="fs-title">Where are you shipping to?</h2>
              <h3 class="fs-subtitle">* Indicates required field</h3>
              <div class="shipping-form">
                <div class="row">
                  <div class="col-12 col-lg-9">
                    <div class="form-group">
                      <label>Address<sup>*</sup></label>
                      <input type="text" id="autocomplete2" class="form-control" name="address2" required placeholder="Street Address" oninput="updateParagraph()"/>
                    </div>
                  </div>
                  <div class="col-12 col-lg-3">
                    <div class="form-group">
                      <label>Zip Code<sup>*</sup></label>
                      <input type="text" id="postalcode2" class="form-control" name="postalcode2" required placeholder="Postal Code"/>
                    </div>
                  </div>
                  <input type="hidden" id="sCountry1" name="sCountry" value=""/>
                  <input type="hidden" id="rCountry1" name="rCountry" value=""/>
                </div>
                <div class="row">
                  <div class="col-12 col-lg-3">
                    <label>Country<sup>*</sup></label>
                    <select id="countrySelect" name="country1" class="form-control" required onchange="calculatePrice(); updateParagraph();">
                      <option value="AF" data-category="zone_6" data-name="Afghanistan">Afghanistan</option>
                      <option value="AL" data-category="zone_4" data-name="Albania">Albania</option>
                      <option value="DZ" data-category="zone_5" data-name="Algeria">Algeria</option>
                      <option value="AO" data-category="zone_5" data-name="Angola">Angola</option>
                      <option value="AR" data-category="zone_8" data-name="Argentina">Argentina</option>
                      <option value="AM" data-category="zone_7" data-name="Armenia">Armenia</option>
                      <option value="AU" data-category="zone_7" data-name="Australia">Australia</option>
                      <option value="AT" data-category="zone_4" data-name="Austria">Austria</option>
                      <option value="AZ" data-category="zone_7" data-name="Azerbaijan">Azerbaijan</option>
                      <option value="BH" data-category="zone_6" data-name="Bahrain">Bahrain</option>
                      <option value="BD" data-category="zone_7" data-name="Bangladesh">Bangladesh</option>
                      <option value="BE" data-category="zone_4" data-name="Belgium">Belgium</option>
                      <option value="BJ" data-category="zone_2" data-name="Benin">Benin</option>
                      <option value="BT" data-category="zone_7" data-name="Bhutan">Bhutan</option>
                      <option value="BO" data-category="zone_8" data-name="Bolivia">Bolivia</option>
                      <option value="BA" data-category="zone_4" data-name="Bosnia & Herzegovina">Bosnia & Herzegovina</option>
                      <option value="BW" data-category="zone_5" data-name="Botswana">Botswana</option>
                      <option value="BR" data-category="zone_8" data-name="Brazil">Brazil</option>
                      <option value="BN" data-category="zone_7" data-name="Brunei">Brunei</option>
                      <option value="BG" data-category="zone_4" data-name="Bulgaria">Bulgaria</option>
                      <option value="BF" data-category="zone_2" data-name="Burkina Faso">Burkina Faso</option>
                      <option value="BI" data-category="zone_5" data-name="Burundi">Burundi</option>
                      <option value="KH" data-category="zone_7" data-name="Cambodia">Cambodia</option>
                      <option value="CM" data-category="zone_2" data-name="Cameroon">Cameroon</option>
                      <option value="CA" data-category="zone_3" data-name="Canada">Canada</option>
                      <option value="CF" data-category="zone_2" data-name="Central African Republic">Central African Republic</option>
                      <option value="TD" data-category="zone_2" data-name="Chad">Chad</option>
                      <option value="CL" data-category="zone_8" data-name="Chile">Chile</option>
                      <option value="CN" data-category="zone_7" data-name="China">China</option>
                      <option value="CO" data-category="zone_8" data-name="Colombia">Colombia</option>
                      <option value="KM" data-category="zone_5" data-name="Comoros">Comoros</option>
                      <option value="CG" data-category="zone_2" data-name="Congo">Congo</option>
                      <option value="CR" data-category="zone_8" data-name="Costa Rica">Costa Rica</option>
                      <option value="CI" data-category="zone_2" data-name="Cote D'Ivoire">Cote D'Ivoire</option>
                      <option value="HR" data-category="zone_4" data-name="Croatia">Croatia</option>
                      <option value="CU" data-category="zone_8" data-name="Cuba">Cuba</option>
                      <option value="CY" data-category="zone_4" data-name="Cyprus">Cyprus</option>
                      <option value="CZ" data-category="zone_4" data-name="Czech Republic">Czech Republic</option>
                      <option value="DK" data-category="zone_4" data-name="Denmark">Denmark</option>
                      <option value="DJ" data-category="zone_5" data-name="Djibouti">Djibouti</option>
                      <option value="DO" data-category="zone_8" data-name="Dominican Republic">Dominican Republic</option>
                      <option value="EC" data-category="zone_8" data-name="Ecuador">Ecuador</option>
                      <option value="EG" data-category="zone_5" data-name="Egypt">Egypt</option>
                      <option value="SV" data-category="zone_8" data-name="El Salvador">El Salvador</option>
                      <option value="GQ" data-category="zone_2" data-name="Equatorial Guinea">Equatorial Guinea</option>
                      <option value="ER" data-category="zone_5" data-name="Eritrea">Eritrea</option>
                      <option value="EE" data-category="zone_4" data-name="Estonia">Estonia</option>
                      <option value="SZ" data-category="zone_5" data-name="Eswatini">Eswatini</option>
                      <option value="ET" data-category="zone_5" data-name="Ethiopia">Ethiopia</option>
                      <option value="FO" data-category="zone_4" data-name="Faroe Islands">Faroe Islands</option>
                      <option value="FI" data-category="zone_4" data-name="Finland">Finland</option>
                      <option value="FR" data-category="zone_4" data-name="France">France</option>
                      <option value="GA" data-category="zone_2" data-name="Gabon">Gabon</option>
                      <option value="GM" data-category="zone_2" data-name="Gambia">Gambia</option>
                      <option value="GE" data-category="zone_7" data-name="Georgia">Georgia</option>
                      <option value="DE" data-category="zone_4" data-name="Germany">Germany</option>
                      <option value="GH" data-category="zone_2" data-name="Ghana">Ghana</option>
                      <option value="GI" data-category="zone_4" data-name="Gibraltar">Gibraltar</option>
                      <option value="GR" data-category="zone_4" data-name="Greece">Greece</option>
                      <option value="GL" data-category="zone_4" data-name="Greenland">Greenland</option>
                      <option value="GG" data-category="zone_1" data-name="Guernsey">Guernsey</option>
                      <option value="GN" data-category="zone_2" data-name="Guinea">Guinea</option>
                      <option value="GW" data-category="zone_2" data-name="Guinea-Bissau">Guinea-Bissau</option>
                      <option value="GY" data-category="zone_8" data-name="Guyana">Guyana</option>
                      <option value="HT" data-category="zone_8" data-name="Haiti">Haiti</option>
                      <option value="HN" data-category="zone_8" data-name="Honduras">Honduras</option>
                      <option value="HK" data-category="zone_7" data-name="Hong Kong">Hong Kong</option>
                      <option value="HU" data-category="zone_4" data-name="Hungary">Hungary</option>
                      <option value="IS" data-category="zone_4" data-name="Iceland">Iceland</option>
                      <option value="IN" data-category="zone_7" data-name="India">India</option>
                      <option value="ID" data-category="zone_7" data-name="Indonesia">Indonesia</option>
                      <option value="IR" data-category="zone_6" data-name="Iran">Iran</option>
                      <option value="IQ" data-category="zone_6" data-name="Iraq">Iraq</option>
                      <option value="IE" data-category="zone_1" data-name="Ireland">Ireland</option>
                      <option value="IL" data-category="zone_6" data-name="Israel">Israel</option>
                      <option value="IT" data-category="zone_4" data-name="Italy">Italy</option>
                      <option value="JM" data-category="zone_8" data-name="Jamaica">Jamaica</option>
                      <option value="JP" data-category="zone_7" data-name="Japan">Japan</option>
                      <option value="JE" data-category="zone_1" data-name="Jersey">Jersey</option>
                      <option value="JO" data-category="zone_6" data-name="Jordan">Jordan</option>
                      <option value="KZ" data-category="zone_7" data-name="Kazakhstan">Kazakhstan</option>
                      <option value="KE" data-category="zone_5" data-name="Kenya">Kenya</option>
                      <option value="KP" data-category="zone_7" data-name="North Korea">North Korea</option>
                      <option value="KR" data-category="zone_7" data-name="South Korea">South Korea</option>
                      <option value="KW" data-category="zone_6" data-name="Kuwait">Kuwait</option>
                      <option value="KG" data-category="zone_7" data-name="Kyrgyzstan">Kyrgyzstan</option>
                      <option value="LA" data-category="zone_7" data-name="Laos">Laos</option>
                      <option value="LV" data-category="zone_4" data-name="Latvia">Latvia</option>
                      <option value="LB" data-category="zone_6" data-name="Lebanon">Lebanon</option>
                      <option value="LS" data-category="zone_5" data-name="Lesotho">Lesotho</option>
                      <option value="LR" data-category="zone_2" data-name="Liberia">Liberia</option>
                      <option value="LY" data-category="zone_5" data-name="Libya">Libya</option>
                      <option value="LI" data-category="zone_4" data-name="Liechtenstein">Liechtenstein</option>
                      <option value="LT" data-category="zone_4" data-name="Lithuania">Lithuania</option>
                      <option value="LU" data-category="zone_4" data-name="Luxembourg">Luxembourg</option>
                      <option value="MO" data-category="zone_7" data-name="Macau">Macau</option>
                      <option value="MK" data-category="zone_4" data-name="North Macedonia">North Macedonia</option>
                      <option value="MG" data-category="zone_5" data-name="Madagascar">Madagascar</option>
                      <option value="MW" data-category="zone_5" data-name="Malawi">Malawi</option>
                      <option value="MY" data-category="zone_7" data-name="Malaysia">Malaysia</option>
                      <option value="MV" data-category="zone_7" data-name="Maldives">Maldives</option>
                      <option value="ML" data-category="zone_2" data-name="Mali">Mali</option>
                      <option value="MT" data-category="zone_4" data-name="Malta">Malta</option>
                      <option value="MR" data-category="zone_5" data-name="Mauritania">Mauritania</option>
                      <option value="MU" data-category="zone_5" data-name="Mauritius">Mauritius</option>
                      <option value="MX" data-category="zone_8" data-name="Mexico">Mexico</option>
                      <option value="MD" data-category="zone_4" data-name="Moldova">Moldova</option>
                      <option value="MC" data-category="zone_4" data-name="Monaco">Monaco</option>
                      <option value="MN" data-category="zone_7" data-name="Mongolia">Mongolia</option>
                      <option value="ME" data-category="zone_4" data-name="Montenegro">Montenegro</option>
                      <option value="MA" data-category="zone_5" data-name="Morocco">Morocco</option>
                      <option value="MZ" data-category="zone_5" data-name="Mozambique">Mozambique</option>
                      <option value="MM" data-category="zone_7" data-name="Myanmar">Myanmar</option>
                      <option value="NA" data-category="zone_5" data-name="Namibia">Namibia</option>
                      <option value="NP" data-category="zone_7" data-name="Nepal">Nepal</option>
                      <option value="NL" data-category="zone_4" data-name="Netherlands">Netherlands</option>
                      <option value="NZ" data-category="zone_8" data-name="New Zealand">New Zealand</option>
                      <option value="NI" data-category="zone_8" data-name="Nicaragua">Nicaragua</option>
                      <option value="NE" data-category="zone_2" data-name="Niger">Niger</option>
                      <option value="NG" data-category="zone_2" data-name="Nigeria">Nigeria</option>
                      <option value="NO" data-category="zone_4" data-name="Norway">Norway</option>
                      <option value="OM" data-category="zone_6" data-name="Oman">Oman</option>
                      <option value="PK" data-category="zone_7" data-name="Pakistan">Pakistan</option>
                      <option value="PA" data-category="zone_8" data-name="Panama">Panama</option>
                      <option value="PG" data-category="zone_5" data-name="Papua New Guinea">Papua New Guinea</option>
                      <option value="PY" data-category="zone_8" data-name="Paraguay">Paraguay</option>
                      <option value="PE" data-category="zone_8" data-name="Peru">Peru</option>
                      <option value="PH" data-category="zone_7" data-name="Philippines">Philippines</option>
                      <option value="PL" data-category="zone_4" data-name="Poland">Poland</option>
                      <option value="PT" data-category="zone_4" data-name="Portugal">Portugal</option>
                      <option value="PR" data-category="zone_8" data-name="Puerto Rico">Puerto Rico</option>
                      <option value="QA" data-category="zone_6" data-name="Qatar">Qatar</option>
                      <option value="RO" data-category="zone_4" data-name="Romania">Romania</option>
                      <option value="RU" data-category="zone_7" data-name="Russia">Russia</option>
                      <option value="RW" data-category="zone_5" data-name="Rwanda">Rwanda</option>
                      <option value="SA" data-category="zone_6" data-name="Saudi Arabia">Saudi Arabia</option>
                      <option value="SN" data-category="zone_2" data-name="Senegal">Senegal</option>
                      <option value="RS" data-category="zone_4" data-name="Serbia">Serbia</option>
                      <option value="SC" data-category="zone_5" data-name="Seychelles">Seychelles</option>
                      <option value="SL" data-category="zone_5" data-name="Sierra Leone">Sierra Leone</option>
                      <option value="SG" data-category="zone_7" data-name="Singapore">Singapore</option>
                      <option value="SK" data-category="zone_4" data-name="Slovakia">Slovakia</option>
                      <option value="SI" data-category="zone_4" data-name="Slovenia">Slovenia</option>
                      <option value="SB" data-category="zone_5" data-name="Solomon Islands">Solomon Islands</option>
                      <option value="SO" data-category="zone_5" data-name="Somalia">Somalia</option>
                      <option value="ZA" data-category="zone_5" data-name="South Africa">South Africa</option>
                      <option value="SS" data-category="zone_5" data-name="South Sudan">South Sudan</option>
                      <option value="ES" data-category="zone_4" data-name="Spain">Spain</option>
                      <option value="LK" data-category="zone_7" data-name="Sri Lanka">Sri Lanka</option>
                      <option value="SD" data-category="zone_5" data-name="Sudan">Sudan</option>
                      <option value="SR" data-category="zone_5" data-name="Suriname">Suriname</option>
                      <option value="SE" data-category="zone_4" data-name="Sweden">Sweden</option>
                      <option value="CH" data-category="zone_4" data-name="Switzerland">Switzerland</option>
                      <option value="SY" data-category="zone_6" data-name="Syria">Syria</option>
                      <option value="TW" data-category="zone_7" data-name="Taiwan">Taiwan</option>
                      <option value="TJ" data-category="zone_7" data-name="Tajikistan">Tajikistan</option>
                      <option value="TZ" data-category="zone_5" data-name="Tanzania">Tanzania</option>
                      <option value="TH" data-category="zone_7" data-name="Thailand">Thailand</option>
                      <option value="TG" data-category="zone_2" data-name="Togo">Togo</option>
                      <option value="TT" data-category="zone_8" data-name="Trinidad and Tobago">Trinidad and Tobago</option>
                      <option value="TN" data-category="zone_5" data-name="Tunisia">Tunisia</option>
                      <option value="TR" data-category="zone_7" data-name="Turkey">Turkey</option>
                      <option value="TM" data-category="zone_7" data-name="Turkmenistan">Turkmenistan</option>
                      <option value="UG" data-category="zone_5" data-name="Uganda">Uganda</option>
                      <option value="UA" data-category="zone_7" data-name="Ukraine">Ukraine</option>
                      <option value="AE" data-category="zone_6" data-name="United Arab Emirates">United Arab Emirates</option>
                      <option value="GB" data-category="zone_1" data-name="United Kingdom">United Kingdom</option>
                      <option value="US" data-category="zone_3" data-name="United States">United States</option>
                      <option value="UY" data-category="zone_8" data-name="Uruguay">Uruguay</option>
                      <option value="UZ" data-category="zone_7" data-name="Uzbekistan">Uzbekistan</option>
                      <option value="VA" data-category="zone_4" data-name="Vatican City">Vatican City</option>
                      <option value="VE" data-category="zone_8" data-name="Venezuela">Venezuela</option>
                      <option value="VN" data-category="zone_7" data-name="Vietnam">Vietnam</option>
                      <option value="YE" data-category="zone_6" data-name="Yemen">Yemen</option>
                      <option value="ZM" data-category="zone_5" data-name="Zambia">Zambia</option>
                      <option value="ZW" data-category="zone_5" data-name="Zimbabwe">Zimbabwe</option>
                    </select>
                  </div>
                  <div class="col-12 col-lg-3">
                    <div class="form-group">
                      <label>Full Name<sup>*</sup></label>
                      <input type="text" class="form-control" name="fullname2" id="fullname2" required oninput="updateParagraph()"/>
                    </div>
                  </div>
                  <div class="col-12 col-lg-3">
                    <div class="form-group">
                      <label>Contact Number<sup>*</sup></label>
                      <input type="text" class="form-control" name="contact2" id="contact2" required oninput="updateParagraph()"/>
                    </div>
                  </div>
                  <div class="col-12 col-lg-3">
                    <div class="form-group">
                      <label>E-mail<sup>*</sup></label>
                      <input type="email" class="form-control" name="email2" id="email2" required oninput="updateParagraph()"/>
                    </div>
                  </div>
                </div>
              </div>
              <input type="button" name="previous" class="previous action-button" value="Previous"/>
              <input type="button" name="next" class="next action-button" value="Continue"/>
              <input type="reset" name="cancel" class="action-button btn-blue" value="Cancel Shipment"/>
            </fieldset>

            <!-- Step 3: What are you sending -->
            <fieldset>
              <h2 class="fs-title">What are you Sending?</h2>
              <h3 class="fs-subtitle">* Indicates required field</h3>
              <div class="shipping-form">
                <div class="row">
                  <div class="col-12 col-lg-8">
                    <div class="row">
                      <div class="col-12 col-lg-6">
                        <div class="form-group">
                          <label>Shipment Type<sup>*</sup></label>
                          <div class="input-comment">
                            <select name="shipment_type" class="form-control" id="packageSelect" required onchange="updateParagraph()">
                              <option disabled selected value>Client Packaging</option>
                              <option value="Document">Document</option>
                              <option value="Package">Package</option>
                            </select>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div id="additionalFields" class="d-none">
                      <div class="row">
                        <div class="col-12 col-lg-6">
                          <div class="form-group">
                            <label>Weight<sup>*</sup></label>
                            <div class="input-comment">
                              <select name="package_weight" class="form-control" id="package_weight" required onchange="calculatePrice(); updateParagraph()">
                                <option disabled selected value>Select Weight</option>
                                <option value="0.5" data-category="weight_0_5">0.5kg</option>
                                <option value="1" data-category="weight_1_0">1kg</option>
                                <option value="1.5" data-category="weight_1_5">1.5kg</option>
                                <option value="2" data-category="weight_2_0">2kg</option>
                                <option value="2.5" data-category="weight_2_5">2.5kg</option>
                                <option value="3" data-category="weight_3_0">3kg</option>
                                <option value="3.5" data-category="weight_3_5">3.5kg</option>
                                <option value="4" data-category="weight_4_0">4kg</option>
                                <option value="4.5" data-category="weight_4_5">4.5kg</option>
                                <option value="5" data-category="weight_5_0">5kg</option>
                              </select>
                              <span class="field-comment">kg</span>
                            </div>
                          </div>
                        </div>
                        <div class="col-12 col-lg-6">
                          <div class="form-group">
                            <label>Package Description</label>
                            <input type="text" class="form-control" name="pDescription" id="pDescription" onchange="updateParagraph()"/>
                          </div>
                        </div>
                        <div class="col-12 col-lg-6">
                          <div class="form-group">
                            <label>Length</label>
                            <div class="input-comment">
                              <input type="number" class="form-control" name="length" id="length" onchange="updateParagraph()"/>
                              <span class="field-comment">in</span>
                            </div>
                          </div>
                        </div>
                        <div class="col-12 col-lg-6">
                          <div class="form-group">
                            <label>Width</label>
                            <div class="input-comment">
                              <input type="number" class="form-control" name="width" id="width" onchange="updateParagraph()"/>
                              <span class="field-comment">in</span>
                            </div>
                          </div>
                        </div>
                        <div class="col-12 col-lg-6">
                          <div class="form-group">
                            <label>Height</label>
                            <div class="input-comment">
                              <input type="number" class="form-control" name="height" id="height" onchange="updateParagraph()"/>
                              <span class="field-comment">in</span>
                            </div>
                          </div>
                        </div>
                        <div class="col-12 col-lg-6">
                          <div class="form-group">
                            <label>Upload a picture of your Package</label>
                            <input type="file" class="form-control" name="pPreview"/>
                          </div>
                        </div>
                        <div class="col-12 col-lg-6">
                          <div class="form-group">
                            <label>Estimated Price</label>
                            <div class="input-comment">
                              <input type="text" class="form-control" name="price2" id="priceInput2" readonly onchange="updateParagraph()"/>
                              <small class="text-danger"><b>Note: Price is Estimated, Until TravelWheel re-weigh.</b></small>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div id="additionalFields1" class="d-none">
                      <div class="row">
                        <div class="col-12 col-lg-6">
                          <div class="form-group">
                            <label>Describe your document<sup>*</sup></label>
                            <div class="input-comment">
                              <input type="text" class="form-control" name="typeOfDoc" id="typeOfDoc" placeholder="Passport, Company Document, Certificate, etc." onchange="updateParagraph()"/>
                            </div>
                          </div>
                        </div>
                        <div class="col-12 col-lg-6">
                          <div class="form-group">
                            <label>Document Weight</label>
                            <div class="input-comment">
                              <select name="doc_weight" class="form-control" id="document_weight" required onchange="calculatePrice(); updateParagraph();">
                                <option disabled selected value>Select Weight</option>
                                <option value="0.5" data-category="weight_0_5">0.5kg</option>
                                <option value="1" data-category="weight_1_0">1kg</option>
                                <option value="1.5" data-category="weight_1_5">1.5kg</option>
                                <option value="2" data-category="weight_2_0">2kg</option>
                                <option value="2.5" data-category="weight_2_5">2.5kg</option>
                                <option value="3" data-category="weight_3_0">3kg</option>
                                <option value="3.5" data-category="weight_3_5">3.5kg</option>
                                <option value="4" data-category="weight_4_0">4kg</option>
                                <option value="4.5" data-category="weight_4_5">4.5kg</option>
                                <option value="5" data-category="weight_5_0">5kg</option>
                              </select>
                            </div>
                          </div>
                        </div>
                        <div class="col-12 col-lg-6">
                          <div class="form-group">
                            <label>Upload a picture of your Document</label>
                            <input type="file" class="form-control" name="preview"/>
                          </div>
                        </div>
                        <div class="col-12 col-lg-6">
                          <div class="form-group">
                            <label>Estimated Price</label>
                            <div class="input-comment">
                              <input type="text" class="form-control" name="price" id="priceInput" readonly onchange="updateParagraph()"/>
                              <small class="text-danger"><b>Note: Price is Estimated, Until TravelWheel re-weigh.</b></small>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="col-12 col-lg-4">
                    <div class="what-package d-none" id="whatpackage"><img src="{{ asset('assets/ship/assets/images/package.jpg') }}" alt="Package"/></div>
                    <div class="what-package d-none" id="whatpackage1"><img src="{{ asset('assets/ship/assets/images/document.jpg') }}" alt="Document"/></div>
                  </div>
                  <script>
                    document.getElementById('packageSelect').addEventListener('change', function () {
                      const pkg  = document.getElementById('additionalFields');
                      const doc  = document.getElementById('additionalFields1');
                      const imgP = document.getElementById('whatpackage');
                      const imgD = document.getElementById('whatpackage1');
                      if (this.value === 'Package') {
                        pkg.classList.remove('d-none'); doc.classList.add('d-none');
                        imgP.classList.remove('d-none'); imgD.classList.add('d-none');
                      } else if (this.value === 'Document') {
                        pkg.classList.add('d-none'); doc.classList.remove('d-none');
                        imgP.classList.add('d-none'); imgD.classList.remove('d-none');
                      }
                    });
                  </script>
                </div>
              </div>
              <input type="button" name="previous" class="previous action-button" value="Previous"/>
              <input type="button" name="next" class="next action-button" value="Continue"/>
              <input type="reset" name="cancel" class="action-button btn-blue" value="Cancel Shipment"/>
            </fieldset>

            <!-- Step 4: Pickup / Drop-off -->
            <fieldset>
              <h2 class="fs-title">Would you like us to pick up your shipment?</h2>
              <h3 class="fs-subtitle">* Indicates required field</h3>
              <div class="shipping-form dropoff-block">
                <ul class="nav nav-tabs" role="tablist">
                  <li class="nav-item"><a class="nav-link active" href="#no-drop" aria-controls="no-drop" role="tab" data-toggle="tab">No, I'll drop it off</a></li>
                  <li class="or-text">--or--</li>
                  <li class="nav-item"><a class="nav-link" href="#yes-drop" aria-controls="yes-drop" role="tab" data-toggle="tab">Yes, pick up my shipment</a></li>
                </ul>
                <div class="tab-content">
                  <div role="tabpanel" class="tab-pane active" id="no-drop">
                    <div class="row">
                      <div class="col-12 col-lg-4">
                        <div class="form-group">
                          <label>Dropoff date:</label>
                          <input type="date" class="form-control" name="dropoff_date" id="dropoff_date" required placeholder="Date" onchange="updateParagraph()"/>
                        </div>
                      </div>
                      <div class="col-12 col-lg-5">
                        <h6>Drop-Off Information</h6>
                        <small class="d-block"><b>Address: <span>74, Ayanguran Road, Ikorodu Garage, Ikorodu, Lagos.</span></b></small>
                        <small class="d-block"><b>Support No.: +2342018891759</b></small>
                        <small class="d-block"><b>Drop-Off Window: 9:00AM - 5:00PM</b></small>
                      </div>
                    </div>
                  </div>
                  <div role="tabpanel" class="tab-pane" id="yes-drop">
                    <div class="row">
                      <div class="col-lg-5">
                        <div class="col-12">
                          <div class="form-group">
                            <label>Pick-up Address</label>
                            <input type="text" id="autocomplete1_full" name="pick_upAddress" class="form-control"/>
                          </div>
                        </div>
                        <div class="col-12">
                          <label>Nearest Bus-Stop</label>
                          <div id="area-autocomplete-container">
                            <input type="text" id="area-input" name="nBus_stop" placeholder="Enter an Area" onfocus="updateDPrice()"/>
                            <div id="area-suggestions"></div>
                          </div>
                        </div>
                        <div class="col-12">
                          <div class="form-group">
                            <label>Pickup date:</label>
                            <input type="date" class="form-control" name="pick_upDate" id="pickUpDate" placeholder="Date" onchange="updateParagraph()" autocomplete="off"/>
                          </div>
                        </div>
                      </div>
                      <div class="col-lg-6">
                        <h6 id="deliveryPrice"></h6>
                        <small class="d-block">Pickup window: 9:00AM - 4:00PM</small>
                        <small class="d-block">Pickup location: Front Door</small>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <input type="button" name="previous" class="previous action-button" value="Previous"/>
              <input type="button" name="next" class="next action-button" value="Continue"/>
              <input type="reset" name="cancel" class="action-button btn-blue" value="Cancel Shipment"/>
            </fieldset>

            <!-- Step 5: Review -->
            <fieldset>
              <h2 class="fs-title">Please re-confirm your details.</h2>
              <div class="shipping-form">
                <div class="review">
                  <h3><b>Ship from</b></h3>
                  <ul>
                    <li><span id="senderName"></span></li>
                    <li><span id="shipFrom"></span></li>
                    <li><span id="senderPhone"></span></li>
                    <li><span id="senderEmail"></span></li>
                    <li><span id="senderCountry"></span></li>
                    <li><span id="postalCodeD1"></span></li>
                  </ul>
                </div>
                <div class="review">
                  <h3>Ship to</h3>
                  <ul>
                    <li><b><span id="recieverName"></span></b></li>
                    <li><b><span id="shipmentTo"></span></b></li>
                    <li><b><span id="recieverPhone"></span></b></li>
                    <li><b><span id="recieverEmail"></span></b></li>
                    <li><b><span id="recieverCountry"></span></b></li>
                    <li><b><span id="postalCodeD2"></span></b></li>
                  </ul>
                </div>
                <div class="review">
                  <h3>Shipment information</h3>
                  <ul class="d-none" id="document">
                    <li><b>Shipment Type: <span id="pType"></span></b></li>
                    <li><b>Type of Document: <span id="dType"></span></b></li>
                    <li><b>Document Weight: <span id="dQty"></span>kg</b></li>
                  </ul>
                  <ul class="d-none" id="package">
                    <li><b>Shipment Type: <span id="pPType"></span></b></li>
                    <li><b>Package Description: <span id="pDescrip"></span></b></li>
                    <li><b>Package Weight: <span id="pWeight"></span>kg</b></li>
                    <li><b>Package Volumetric: <span id="pVolume"></span></b></li>
                  </ul>
                </div>
                <div class="review">
                  <div class="d-none" id="pick-up">
                    <h3>Pick-up Information</h3>
                    <ul>
                      <li><b>Pick-up Address: <span id="pAddress"></span></b></li>
                      <li><b>Nearest Bus-stop: <span id="pBustop"></span></b></li>
                      <li><b>Pick-Up Date: <span id="pDate"></span></b></li>
                      <li><b>Pick-Up Window: 9:00AM - 4:00PM</b></li>
                      <li><b>Pick-Up Price: <span id="pPrice"></span></b></li>
                    </ul>
                  </div>
                  <div class="d-none" id="drop-off">
                    <h3>Drop-Off Information</h3>
                    <ul>
                      <li><b>Drop-Off Address: 74, Ayanguran Road, Ikorodu Garage, Ikorodu, Lagos</b></li>
                      <li><b>Drop-Off Date: <span id="dDate"></span></b></li>
                      <li><b>Drop-Off Window: 9:00AM - 5:00PM</b></li>
                    </ul>
                  </div>
                </div>
                <div class="review">
                  <div class="d-none" id="pDocument">
                    <h3>Shipment Price</h3>
                    <h4 class="pt-3"><b>Estimated Price: ₦<span id="ePrice"></span></b></h4>
                    <small class="text-danger"><b>Note: Price is Estimated, Until TravelWheel re-weigh.</b></small>
                  </div>
                  <div class="d-none" id="pPackage">
                    <h3>Shipment Price</h3>
                    <h4 class="pt-3"><b>Estimated Price: ₦<span id="ePPrice"></span></b></h4>
                    <small class="text-danger"><b>Note: Price is Estimated, Until TravelWheel re-weigh.</b></small>
                  </div>
                </div>
              </div>
              <input type="button" name="previous" class="previous action-button" value="Previous"/>
              <input type="button" name="next" class="next action-button" value="Continue"/>
              <input type="reset" name="cancel" class="action-button btn-blue" value="Cancel Shipment"/>
            </fieldset>

            <!-- Step 6: Payment confirmation -->
            <fieldset>
              <h3 class="fs-title pb-3"><b>Please Confirm and Make Payment</b></h3>
              <div class="shipping-form">
                <div class="form-group">
                  <div class="row pe-3">
                    <div class="col-12 text-end">
                      <div class="d-none" id="cmpDocument">
                        <h4>Total Amount (NGN)</h4>
                        <span class="d-block"><b>Shipping Price: <span id="tPrice"></span>.00</b></span>
                        <span class="d-none" id="displayPickUp"><b>Pick-UP Price: <span id="pPrice1"></span>.00</b></span>
                        <span class="d-block"><b>Total Price: <span id="tAmount"></span>.00</b></span>
                        <input type="hidden" id="pick-Upshipment" name="pick-Upshipment">
                        <input type="hidden" id="totalPrice" name="totalPrice">
                      </div>
                      <div class="d-none" id="cmpPackage">
                        <h4>Total Amount (NGN)</h4>
                        <span class="d-block"><b>Shipping Price: <span id="tPrice2"></span>.00</b></span>
                        <span class="d-none" id="displayPickUp2"><b>Pick-UP Price: <span id="pPrice22"></span>.00</b></span>
                        <span class="d-block"><b>Total Price: <span id="tAmount2"></span>.00</b></span>
                        <input type="hidden" id="pick-Upshipment2" name="pick-Upshipment2">
                        <input type="hidden" id="totalPrice2" name="totalPrice2">
                      </div>
                    </div>
                    <hr>
                    <div class="col-12 col-lg-6 p-3">
                      <h6><b>Shipping From</b></h6>
                      <span class="d-block"><span id="sName"></span></span>
                      <span class="d-block" id="sEmail"></span>
                      <span class="d-block" id="sPhone"></span>
                      <span class="d-block" id="sAddress"></span>
                      <span class="d-block" id="sCountry"></span>
                      <span class="d-block" id="postalCodeP1"></span>
                    </div>
                    <div class="col-12 col-lg-6 p-3">
                      <h6><b>Shipping To</b></h6>
                      <span class="d-block"><span id="rName"></span></span>
                      <span class="d-block" id="rEmail"></span>
                      <span class="d-block" id="rPhone"></span>
                      <span class="d-block" id="rAddress"></span>
                      <span class="d-block" id="rCountry"></span>
                      <span class="d-block" id="postalCodeP2"></span>
                    </div>
                    <div class="col-12 col-lg-6 p-3">
                      <h6><b>Shipment Details</b></h6>
                      <div class="d-none" id="document1">
                        <span class="d-block">Shipment Type: <span id="pType1"></span></span>
                        <span class="d-block">Type of Document: <span id="dType1"></span></span>
                        <span class="d-block">Document Weight: <span id="dQty1"></span>kg</span>
                      </div>
                      <div class="d-none" id="package1">
                        <span class="d-block">Shipment Type: <span id="pPType1"></span></span>
                        <span class="d-block">Package Description: <span id="pDescrip1"></span></span>
                        <span class="d-block">Package Weight: <span id="pWeight1"></span>kg</span>
                        <span class="d-block">Package Volumetric: <span id="pVolume1"></span></span>
                      </div>
                    </div>
                    <div class="col-12 col-lg-6 p-3">
                      <div class="d-none" id="dPick-up">
                        <h6><b>Pick-Up Details</b></h6>
                        <span class="d-block">Address: <span id="pAddress1"></span></span>
                        <span class="d-block">Nearest Bus-stop: <span id="pBustop1"></span></span>
                        <span class="d-block">Pick-Up Date: <span id="pDate1"></span></span>
                        <span class="d-block">Pickup window: 9:00AM - 4:00PM</span>
                      </div>
                      <div class="d-none" id="dDrop-off">
                        <h6><b>Drop-Off Details</b></h6>
                        <span class="d-block">Address: 74, Ayanguran Road, Ikorodu Garage, Ikorodu, Lagos</span>
                        <span class="d-block">Drop-Off Date: <span id="dDate1"></span></span>
                        <span class="d-block">Drop-Off Window: 9:00AM - 5:00PM</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <input type="button" name="previous" class="previous action-button" value="Previous"/>
              <input type="submit" name="submit" class="action-button" value="Make Payment"/>
            </fieldset>

          </form>
        </div>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
  function calculatePrice() {
    const countryZone = document.querySelector('#countrySelect').selectedOptions[0]?.dataset.category;
    const serviceType = document.querySelector('#packageSelect')?.value;
    const weightEl = document.getElementById(serviceType?.toLowerCase() === 'document' ? 'document_weight' : 'package_weight');
    const weight = weightEl?.selectedOptions?.[0]?.dataset?.category ?? null;

    if (countryZone && serviceType && weight) {
      $.ajax({
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        url: '{{ route("air.cargo.shipping-price") }}',
        method: 'POST',
        data: { zone: countryZone, service: serviceType, weight: weight },
        success: function (response) {
          const formatted = response.price
            ? new Intl.NumberFormat('en-NG', { style: 'decimal' }).format(response.price)
            : 'Price not available';
          document.querySelector(serviceType === 'Document' ? '#priceInput' : '#priceInput2').value = formatted;
        },
        error: function (xhr) {
          const msg = xhr.responseJSON?.message || 'Error fetching price';
          document.querySelector(serviceType === 'Document' ? '#priceInput' : '#priceInput2').value = msg;
        }
      });
    }
  }

  const zones = {
    'Mainland 1': { price: 4500, areas: ['Abule oja', 'Alagomeji', 'Ebute metta', 'Folagoro', 'Idiaraba', 'Iponri', 'Itire', 'Iwaya', 'Lawanson', 'Ojuelegba', 'Oyingbo', 'Oshodi'] },
    'Mainland 2': { price: 4000, areas: ['Magodo', 'Alapere', 'Bariga', 'Ilupeju', 'Jibowu', 'Ikeja', 'Ogudu', 'Onipanu', 'Obanikoro', 'Somolu', 'Palmgroove', 'Yaba', 'Ajao', 'Maryland', 'Ketu', 'Mile12', 'Anthony', 'Shangisha', 'Ojota'] },
    'Mainland 3': { price: 4500, areas: ['Abule-egba', 'Agege', 'Berger', 'Iju ishaga', 'Fagba', 'Akute', 'Magodo phase2', 'Omole'] },
    'Mainland 4': { price: 6000, areas: ['Egbeda', 'Gowon', 'Ayobo', 'Ipaja', 'Igando', 'Ikotun', 'Iyana Ipaja', 'Okota', 'Ago palace', 'Ijaye', 'Dopemu', 'Meran'] },
    'Mainland 5': { price: 2500, areas: ['Agric Ikorodu', 'Ajegunle ikorodu', 'Asolo', 'Owede oniri', 'Ishawo', 'Ogolonto'] },
    'Mainland 6': { price: 2500, areas: ['Ikorodu garage', 'Sabo', 'Jubilee', 'laspotech', 'Ori okuta', 'ojokoro'] },
    'Mainland 7': { price: 4000, areas: ['Lucky fiber', 'Imota', 'Odogunyan', 'Gberigbe', 'Ogijo', 'Caleb'] },
    'Mainland 8': { price: 6000, areas: ['Festac', 'Apapa', 'Ajegunle', 'Satellite', 'Ijegun'] },
    'Mainland 9': { price: 6000, areas: ['Lasu Gate', 'Iyana iba', 'Iyana Ishashi', 'Okoko', 'Ojo'] },
    'Island 1':   { price: 6000, areas: ['Lagos island', 'Cms', 'Marina', 'Idumota'] },
    'Island 2':   { price: 6000, areas: ['Eko Hotel', 'VI', 'Bonny camp', '1004', 'Oniru'] },
    'Island 3':   { price: 7000, areas: ['Lekki phase 1', 'Igbo efun', 'Ikate', 'Maruwa'] },
    'Island 4':   { price: 7000, areas: ['Ajah', 'Badore', 'Ogombo', 'Abraham adesanya', 'Ado', 'Langbasa'] },
    'Island 5':   { price: 8000, areas: ['Awoyaya', 'Sangotedo', 'Ocean bay'] }
  };

  const allAreas = Object.values(zones).flatMap(z => z.areas);

  function getPriceBasedOnAddress(address) {
    for (const [zone, data] of Object.entries(zones)) {
      for (const area of data.areas) {
        if (address.toLowerCase() === area.toLowerCase()) return { zone, price: data.price };
      }
    }
    return { zone: 'Unknown', price: 0 };
  }

  function updateDPrice() {
    const address = document.getElementById('area-input').value;
    const result  = getPriceBasedOnAddress(address);
    document.getElementById('pBustop').innerHTML   = address;
    document.getElementById('pBustop1').innerHTML  = address;
    document.getElementById('deliveryPrice').innerText =
      result.price ? `Delivery Price: ₦${result.price}` : 'Price not found, please call customer support';
    document.getElementById('pPrice').innerText  =
      result.price ? `₦${result.price}` : 'Price not found';
    document.getElementById('pPrice1').innerText  = result.price.toLocaleString();
    document.getElementById('pPrice22').innerText = result.price.toLocaleString();
    document.getElementById('pick-Upshipment').value  = result.price;
    document.getElementById('pick-Upshipment2').value = result.price;
  }

  const areaInput = document.getElementById('area-input');
  const suggestions = document.getElementById('area-suggestions');
  areaInput.addEventListener('input', function () {
    const val = this.value.toLowerCase();
    updateDPrice();
    if (val.length < 1) { suggestions.style.display = 'none'; return; }
    const matches = allAreas.filter(a => a.toLowerCase().includes(val));
    if (matches.length) {
      suggestions.innerHTML = matches.map(a => `<div class="area-suggestion-item">${a}</div>`).join('');
      suggestions.style.display = 'block';
    } else { suggestions.style.display = 'none'; }
  });
  suggestions.addEventListener('click', function (e) {
    if (e.target.classList.contains('area-suggestion-item')) {
      areaInput.value = e.target.textContent;
      suggestions.style.display = 'none';
      updateDPrice();
    }
  });
  document.addEventListener('click', function (e) {
    if (!areaInput.contains(e.target) && !suggestions.contains(e.target)) suggestions.style.display = 'none';
  });

  // Sync review display state with packageSelect changes
  document.getElementById('packageSelect').addEventListener('change', function () {
    const v = this.value;
    ['document', 'pDocument', 'cmpDocument', 'document1'].forEach(id => {
      document.getElementById(id)?.classList.toggle('d-none', v !== 'Document');
    });
    ['package', 'pPackage', 'cmpPackage', 'package1'].forEach(id => {
      document.getElementById(id)?.classList.toggle('d-none', v !== 'Package');
    });
  });

  // Pickup/dropoff display toggle
  function syncPickupDropoff() {
    const pVal = document.getElementById('pickUpDate')?.value;
    const dVal = document.getElementById('dropoff_date')?.value;
    const showPickup = pVal && !dVal;
    const showDropoff = dVal && !pVal;
    document.getElementById('pick-up')?.classList.toggle('d-none', !showPickup);
    document.getElementById('drop-off')?.classList.toggle('d-none', !showDropoff);
    document.getElementById('dPick-up')?.classList.toggle('d-none', !showPickup);
    document.getElementById('dDrop-off')?.classList.toggle('d-none', !showDropoff);
    document.getElementById('displayPickUp')?.classList.toggle('d-none', !showPickup);
    document.getElementById('displayPickUp2')?.classList.toggle('d-none', !showPickup);
  }
  document.getElementById('pickUpDate')?.addEventListener('change', syncPickupDropoff);
  document.getElementById('dropoff_date')?.addEventListener('change', syncPickupDropoff);

  // Sync pickup address to review panel
  document.getElementById('autocomplete1_full')?.addEventListener('input', function () {
    document.getElementById('pAddress').innerText = this.value;
    document.getElementById('pAddress1').innerText = this.value;
  });
</script>

<script src="{{ asset('assets/ship/assets/js/datetimepicker-moment.min.js') }}"></script>
<script src="{{ asset('assets/ship/assets/js/bootstrap-datetimepicker.min.js') }}"></script>
<script src="{{ asset('assets/ship/assets/js/jquery.validate.js') }}"></script>
<script src="{{ asset('assets/ship/assets/js/form-step.js') }}"></script>
<script src="{{ asset('assets/ship/assets/js/active.js') }}"></script>
<script src="{{ asset('assets/js/cargo.js') }}"></script>
@endpush

@endcomponent
