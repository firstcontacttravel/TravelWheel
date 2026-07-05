document.getElementById('myForm').addEventListener('keypress', function(event) {
  if (event.key === 'Enter') {
      event.preventDefault(); // Prevent form submission on Enter
  }
});
// Initialize Google Maps Autocomplete for the first address input
function initAutocomplete1() {
  const input = document.getElementById('autocomplete1');
  if (input.dataset.autocompleteInit) return;
  input.dataset.autocompleteInit = '1';

  // Initialize Google Maps Autocomplete
  const autocomplete = new google.maps.places.Autocomplete(input);

  // Add a listener to capture details when a place is selected
  autocomplete.addListener('place_changed', function () {
    const place = autocomplete.getPlace();

    if (!place.geometry) {
      console.error("No details available for input: autocomplete1");
      return;
    }

    // Save the full address into the display field
    document.getElementById('autocomplete1_full').value = input.value;
    document.getElementById('pAddress').innerText = input.value;
    document.getElementById('pAddress1').innerText = input.value;

    document.getElementById('sAddress').innerText = input.value;
    document.getElementById('shipFrom').innerText = input.value;
    // Extract address components
    const addressComponents = place.address_components;
    let countryCode = null; // ISO 3166-1 alpha-2 code
    let countryName = null;
    let postalCode = null;

    addressComponents.forEach(component => {
      if (component.types.includes('postal_code')) {
        postalCode = component.long_name;
      }
    });

    document.getElementById('postalCodeD1').innerText = postalCode;
    document.getElementById('postalCodeP1').innerText = postalCode;

    // Populate the postal code input field
    const postalCodeInput = document.getElementById('postalcode1');
    postalCodeInput.value = postalCode || 'Not found'; // Fallback if postal code is unavailable

    // Extract country name and country code
    addressComponents.forEach(component => {
      if (component.types.includes('country')) {
        countryCode = component.short_name; // e.g., "US"
        countryName = component.long_name; // e.g., "United States"
      }
    });

    if (countryCode) {
      // Update the countrySelect dropdown
      const countrySelect1 = document.getElementById('countrySelect1');
      const countryOptions1 = countrySelect1.options;

      for (let i = 0; i < countryOptions1.length; i++) {
        if (countryOptions1[i].text.includes(countryName)) {
          countrySelect1.selectedIndex = i;
          break;
        }
      }

      // Update the country_code dropdown, if present
      const countryCodeDropdown = document.getElementById('country_code');
      if (countryCodeDropdown) {
        const phoneCodeOptions = countryCodeDropdown.options;

        for (let i = 0; i < phoneCodeOptions.length; i++) {
          if (phoneCodeOptions[i].text.includes(countryName)) {
            countryCodeDropdown.selectedIndex = i;
            break;
          }
        }
      }
    }

    // Optional: Log details for debugging
    console.log("Detected Country Code:", countryCode);
    console.log("Detected Country Name:", countryName);
    console.log("Selected Country:", document.getElementById('countrySelect1').value);
  });
}



// Initialize Google Maps Autocomplete for the second address input
function initAutocomplete2() {
  const input = document.getElementById('autocomplete2');
  if (input.dataset.autocompleteInit) return;
  input.dataset.autocompleteInit = '1';

  // Initialize Google Maps Autocomplete
  const autocomplete = new google.maps.places.Autocomplete(input);

  // Add a listener to capture details when a place is selected
  autocomplete.addListener('place_changed', function () {
    const place = autocomplete.getPlace();

    if (!place.geometry) {
      console.error("No details available for input: autocomplete2");
      return;
    }

    //document.getElementById('shipTo').innerText = input.value;
    document.getElementById('shipmentTo').innerText = input.value;
    document.getElementById('rAddress').innerText = input.value;

    // Extract postal code from address components
    const addressComponents = place.address_components;
    let countryCode = null; // ISO 3166-1 alpha-2 code
    let countryName = null;
    let postalCode = null;

    addressComponents.forEach(component => {
      if (component.types.includes('postal_code')) {
        postalCode = component.long_name;
      }
    });

    // Populate the postal code input field
    const postalCodeInput = document.getElementById('postalcode2');
    postalCodeInput.value = postalCode || 'Not found'; // Fallback if postal code is unavailable
    document.getElementById('postalCodeD2').innerText = postalCode;
    document.getElementById('postalCodeP2').innerText = postalCode;

    addressComponents.forEach(component => {
      if (component.types.includes('country')) {
        countryCode = component.short_name; // e.g., "US"
        countryName = component.long_name; // e.g., "United States"
      }
    });

    if (countryCode) {
      // Update the countrySelect dropdown
      const countrySelect = document.getElementById('countrySelect');
      const countryOptions = countrySelect.options;

      for (let i = 0; i < countryOptions.length; i++) {
        if (countryOptions[i].text.includes(countryName)) {
          countrySelect.selectedIndex = i;
          break;
        }
      }

      // Update the country_code dropdown, if present
      const countryCodeDropdown1 = document.getElementById('country_code2');
      if (countryCodeDropdown1) {
        const phoneCodeOptions1 = countryCodeDropdown1.options;

        for (let i = 0; i < phoneCodeOptions1.length; i++) {
          if (phoneCodeOptions1[i].text.includes(countryName)) {
            countryCodeDropdown1.selectedIndex = i;
            break;
          }
        }
      }
    }

    // Optional: Log details for debugging
    console.log("Selected Address (autocomplete2):", input.value);
    console.log("Postal Code (autocomplete2):", postalCode);

    // Optional: Log details for debugging
    console.log("Detected Country Code:", countryCode);
    console.log("Detected Country Name:", countryName);
    console.log("Selected Country:", document.getElementById('countrySelect').value);
  });
}
/*
document.getElementById('doc_weight').addEventListener('change', function() {
    const countrySelect = document.getElementById('countrySelect');
    const selectedOption = countrySelect.options[countrySelect.selectedIndex];
    const category = selectedOption.getAttribute('data-category');
    const weightSelect = this;
    var selectedWeight = parseFloat(weightSelect.value);
    var priceInput = document.getElementById('priceInput');

    var prices = {
        // Same as the previous pricing structure
    };

    // Get the price based on the selected weight and country category
    if (category && prices[category] && prices[category][selectedWeight]) {
        var selectedPrice = prices[category][selectedWeight];
        priceInput.value = selectedPrice.toLocaleString(); // format price with commas
    } else {
        priceInput.value = ''; // reset if no valid price is found
    }
});
*/
/*document.getElementById('countrySelect').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const selectedCountry = selectedOption.text;
    const category = selectedOption.getAttribute('data-category');
      // Set the price based on the category
      var priceInput = document.getElementById('priceInput');
      // Define the prices for each category
      var prices = {
          'zone_1': 68000,
          'zone_2': 75000,
          'zone_3': 82000,
          'zone_4': 94000,
          'zone_5': 97000,
          'zone_6': 103000,
          'zone_7': 113000,
          'zone_8': 118000,
      };
    document.getElementById('selectedCountry').value = selectedCountry;
    document.getElementById('countryCategory').value = category;

      if (category && prices[category]) {
          priceInput.value = prices[category].toLocaleString(); // format price with commas
      } else {
          priceInput.value = ''; // reset if no valid country is selected
      }
  
});*/


function updateParagraph() {
  // Get the values from the input fields
  var address1 = document.getElementById('autocomplete1').value;
  var postalcode1 = document.getElementById('postalcode1').value;
  var address2 = document.getElementById('autocomplete2').value;
  var postalcode2 = document.getElementById('postalcode2').value;
  var text2 = address2;

  var packageSelect = document.getElementById('packageSelect').options[document.getElementById('packageSelect').selectedIndex].value;
  var typeOfDoc = document.getElementById('typeOfDoc').value;
  var doc_weight = document.getElementById('document_weight').value;
  var priceInput = document.getElementById('priceInput').value;
  var pickUpDate = document.getElementById('pickUpDate').value;

  var pDescription = document.getElementById('pDescription').value;
  var package_weight = document.getElementById('package_weight').value;
  var priceInput = document.getElementById('priceInput').value;
  var plenght = document.getElementById('length').value;
  var pwidth = document.getElementById('width').value;
  var pheight = document.getElementById('height').value;
  var pVolume = "Lenght: " + plenght + " | Width: " + pwidth + " | Height: " + pheight;
  var dropOffDate = document.getElementById('dropoff_date').value;
  var priceInput2 = document.getElementById('priceInput2').value;

  var pickUpPrice = document.getElementById('pick-Upshipment').value;
  if (priceInput  && !pickUpPrice) {

    var priceValue = parseInt(priceInput.replace(/,/g, '')); // Convert to a float 
    //var sevenPercent = priceValue * 0.07; // Calculate 7% (note: changed from 70 to 0.07)
    //var roundedValue = parseInt(sevenPercent.toFixed(2)); // Convert back to number after rounding
    var totalAmount = priceValue;
  } 
  else {
    var priceValue = parseInt(priceInput.replace(/,/g, '')); // Convert to a float 
    //var sevenPercent = priceValue * 0.07; // Calculate 7% (note: changed from 70 to 0.07)
    //var roundedValue = parseInt(sevenPercent.toFixed(2)); // Convert back to number after rounding
    var pickUpPrice2 = parseInt(pickUpPrice.replace(/,/g, ''));
    var totalAmount = priceValue + pickUpPrice2;
  }

  if (priceInput2  && !pickUpPrice) {
    var priceValue2 = parseInt(priceInput2.replace(/,/g, '')); // Convert to a float 
    //var sevenPercent2 = priceValue2 * 0.07; // Calculate 7% (note: changed from 70 to 0.07)
    //var roundedValue2 = parseInt(sevenPercent2.toFixed(2)); // Convert back to number after rounding
    var totalAmount2 = priceValue2;
  } 
  else {
    var priceValue2 = parseInt(priceInput2.replace(/,/g, '')); // Convert to a float 
    //var sevenPercent2 = priceValue2 * 0.07; // Calculate 7% (note: changed from 70 to 0.07)
    //var roundedValue2 = parseInt(sevenPercent2.toFixed(2)); // Convert back to number after rounding
    var pickUpPrice2 = parseInt(pickUpPrice.replace(/,/g, ''));
    var totalAmount2 = priceValue2 + pickUpPrice2;
  }

  document.getElementById('shipFrom').innerText = address1;
  document.getElementById('sAddress').innerText = address1;
  document.getElementById('postalCodeD1').innerText = postalcode1;
  document.getElementById('postalCodeP1').innerText = postalcode1;

  document.getElementById('shipmentTo').innerText = address2;
  document.getElementById('rAddress').innerText = address2;
  document.getElementById('postalCodeD2').innerText = postalcode2;
  document.getElementById('postalCodeP2').innerText = postalcode2;

  var fullname1 = document.getElementById('fullname').value;
  document.getElementById('senderName').innerText = fullname1;

  var fullname2 = document.getElementById('fullname2').value;
  document.getElementById('recieverName').innerText = fullname2;
  
  var phoneNo1 = document.getElementById('phone_no').value;
  document.getElementById('senderPhone').innerText = phoneNo1;

  var phoneNo2 = document.getElementById('contact2').value;
  document.getElementById('recieverPhone').innerText = phoneNo2;

  var email1 = document.getElementById('email').value;
  document.getElementById('senderEmail').innerText = email1;


  var email2 = document.getElementById('email2').value;
  document.getElementById('recieverEmail').innerText = email2;

  const countrySelect1 = document.getElementById('countrySelect1').options[document.getElementById('countrySelect1').selectedIndex];
  const scountrySelect = countrySelect1 ? countrySelect1.getAttribute('data-name') : null;
  document.getElementById('senderCountry').innerText = scountrySelect;
  document.getElementById('sCountry1').value = scountrySelect;


  const countrySelect = document.getElementById('countrySelect').options[document.getElementById('countrySelect').selectedIndex];
  const rcountrySelect = countrySelect ? countrySelect.getAttribute('data-name') : null;
  document.getElementById('recieverCountry').innerText = rcountrySelect;
  document.getElementById('rCountry1').value = rcountrySelect;

  document.getElementById('pType').innerText = packageSelect;
  document.getElementById('dType').innerText = typeOfDoc;
  document.getElementById('dQty').innerText = doc_weight;

  document.getElementById('pType1').innerText = packageSelect;
  document.getElementById('dType1').innerText = typeOfDoc;
  document.getElementById('dQty1').innerText = doc_weight;

  document.getElementById('pPType').innerText = packageSelect;
  document.getElementById('pDescrip').innerText = pDescription;
  document.getElementById('pWeight').innerText = package_weight;
  document.getElementById('pVolume').innerText = pVolume;
  document.getElementById('dDate').innerText = dropOffDate;

  document.getElementById('pPType1').innerText = packageSelect;
  document.getElementById('pDescrip1').innerText = pDescription;
  document.getElementById('pWeight1').innerText = package_weight;
  document.getElementById('pVolume1').innerText = pVolume;
  document.getElementById('dDate1').innerText = dropOffDate;

  document.getElementById('ePrice').innerText = priceInput;
  document.getElementById('ePPrice').innerText = priceInput2;

  document.getElementById('sName').innerText = fullname1;
  document.getElementById('sPhone').innerText = phoneNo1;
  document.getElementById('sEmail').innerText = email1;
  document.getElementById('sCountry').innerText = scountrySelect;

  document.getElementById('rName').innerText = fullname2;
  document.getElementById('rPhone').innerText = phoneNo2;
  document.getElementById('rEmail').innerText = email2;
  document.getElementById('rCountry').innerText = rcountrySelect;

  document.getElementById('tPrice').innerText = priceInput;
  //document.getElementById('vat').innerText = (roundedValue).toLocaleString();
  //document.getElementById('priceVat').value = roundedValue;
  document.getElementById('tAmount').innerText = (totalAmount).toLocaleString();
  document.getElementById('totalPrice').value = totalAmount;

  document.getElementById('tPrice2').innerText = priceInput2;
  //document.getElementById('vat2').innerText = (roundedValue2).toLocaleString();
  //document.getElementById('priceVat2').value = roundedValue2;
  document.getElementById('tAmount2').innerText = (totalAmount2).toLocaleString();
  document.getElementById('totalPrice2').value = totalAmount2;
 
  document.getElementById('pDate').innerText = pickUpDate;
  document.getElementById('pDate1').innerText = pickUpDate;
  
}
