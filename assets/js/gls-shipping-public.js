/* eslint-disable */
(function ($) {
    "use strict";

    jQuery(document).ready(function ($) {
        const mapElements = document.getElementsByClassName("inchoo-gls-map");

        if (mapElements.length > 0) {
            for (let i = 0; i < mapElements.length; i++) {
                mapElements[i].addEventListener("change", (e) => {
                    const pickupInfo = e.detail;
                    const pickupInfoDiv =
                        document.getElementById("gls-pickup-info");
                    if (pickupInfoDiv) {
                        pickupInfoDiv.innerHTML = `
                        <strong>${gls_croatia.pickup_location}:</strong><br>
                        ${gls_croatia.name}: ${pickupInfo.name}<br>
                        ${gls_croatia.address}: ${pickupInfo.contact.address}, ${pickupInfo.contact.city}, ${pickupInfo.contact.postalCode}<br>
                        ${gls_croatia.country}: ${pickupInfo.contact.countryCode}
                    `;
                        pickupInfoDiv.style.display = "block";
                    }

                    // Create or update the hidden input field
                    let hiddenInput = document.getElementById(
                        "gls-pickup-info-data"
                    );
                    if (!hiddenInput) {
                        hiddenInput = document.createElement("input");
                        hiddenInput.type = "hidden";
                        hiddenInput.id = "gls-pickup-info-data";
                        hiddenInput.name = "gls_pickup_info";
                        document.forms["checkout"].appendChild(hiddenInput);
                    }
                    hiddenInput.value = JSON.stringify(pickupInfo);
                });
            }
        }

        function showMapModal(mapClass) {
            const selectedCountry = $("#billing_country").val();
            const mapElement = $(`.${mapClass}`);
            mapElement.attr("country", selectedCountry.toLowerCase());
            mapElement[0].showModal();
        }

        // Event listener for locker button
        $(document.body).on(
            "click",
            ".dugme-gls_shipping_method_parcel_locker",
            function () {
                showMapModal("gls-map-locker");
            }
        );

        // Event listener for shop button
        $(document.body).on(
            "click",
            ".dugme-gls_shipping_method_parcel_shop",
            function () {
                showMapModal("gls-map-shop");
            }
        );

        $(document.body).on("updated_checkout", function () {
            // Find the selected shipping method radio button
            const selectedShippingMethod = $(
                'input[name="shipping_method[0]"]:checked'
            ).val();

            // Filter map pins
            switch (selectedShippingMethod) {
                case "gls_shipping_method_parcel_locker":
                    $("#gls-map").attr("filter-type", "parcel-locker");
                    break;
                case "gls_shipping_method_parcel_shop":
                    $("#gls-map").attr("filter-type", "parcel-shop");
                    break;
                default:
                    $("#gls-pickup-info").hide();
                    break;
            }
        });
    });
})(jQuery);
