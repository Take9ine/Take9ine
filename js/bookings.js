    "use strict";

/*
|--------------------------------------------------------------------------
| T@ke9ine ROOM BOOKING AND BILLING SYSTEM
| File: js/bookings.js
|--------------------------------------------------------------------------
| Features:
| 1. Automatic room and service cost calculations
| 2. Check-in and check-out date validation
| 3. Number of nights calculation
| 4. Live booking summary
| 5. Room selection through URL parameters
| 6. Special-request character counter
| 7. Accessible form validation messages
| 8. Booking form submission protection
|--------------------------------------------------------------------------
*/

document.addEventListener("DOMContentLoaded", function () {
    const bookingForm = document.getElementById("booking-form");

    // Avoid running booking functionality on pages without the form.
    if (!bookingForm) {
        return;
    }

    // ---------------------------------------------------------
    // 1. GET FORM ELEMENTS
    // ---------------------------------------------------------

    const fullNameInput = document.getElementById("full-name");
    const emailInput = document.getElementById("email");
    const phoneInput = document.getElementById("phone");

    const guestsInput = document.getElementById("guests");
    const checkInInput = document.getElementById("check-in");
    const checkOutInput = document.getElementById("check-out");
    const roomTypeInput = document.getElementById("room-type");

    const specialRequestsInput =
        document.getElementById("special-requests");

    const breakfastInput = document.getElementById("breakfast");
    const airportTransferInput =
        document.getElementById("airport-transfer");
    const laundryInput = document.getElementById("laundry");

    const termsInput = document.getElementById("terms");
    const humanVerificationInput =
        document.getElementById("human-verification");
    const submitButton = document.getElementById("submit-booking");
    const formMessage = document.getElementById("form-message");
    const errorModal = document.getElementById("booking-error-modal");
    const errorTitle = document.getElementById("booking-error-title");
    const errorMessage = document.getElementById("booking-error-message");
    const errorList = document.getElementById("booking-error-list");
    const errorClose = document.getElementById("booking-error-close");

    const requestCount = document.getElementById("request-count");

    // ---------------------------------------------------------
    // 2. ROOM PRICES AND DETAILS
    // ---------------------------------------------------------

    /*
     * These prices must agree with the room prices displayed
     * on index.php and booking.php.
     *
     * They are for frontend estimates only.
     * PHP must recalculate the bill independently.
     */
    
    const ROOM_DETAILS = {
        single: {
            name: "Single Room",
            rate: 3500,
            capacity: 1,
            image: "assets/images/hero_1.jpg"
        },

        double: {
            name: "Double Room",
            rate: 5000,
            capacity: 4,
            image: "assets/images/hero_2.jpg"
        },

        executive: {
            name: "Executive Room",
            rate: 7500,
            capacity: 4,
            image: "assets/images/hero_3.jpg"
        },

        extra: {
            name: "Extra Bed",
            rate: 1500,
            capacity: 1,
            image: "assets/images/hero_4.jpg"
        }
    };

    const SERVICE_PRICES = {
        breakfastPerGuestPerDay: 700,
        airportTransferPerDay: 2000,
        laundryPerDay: 1000
    };

    // ---------------------------------------------------------
    // 3. SUMMARY ELEMENTS
    // ---------------------------------------------------------

    const summaryElements = {
        roomName: document.getElementById("summary-room-name"),
        roomRate: document.getElementById("summary-room-rate"),

        checkIn: document.getElementById("summary-check-in"),
        checkOut: document.getElementById("summary-check-out"),
        nights: document.getElementById("summary-nights"),
        guests: document.getElementById("summary-guests"),

        roomTotal: document.getElementById("summary-room-total"),
        breakfastTotal:
            document.getElementById("summary-breakfast-total"),
        airportTotal:
            document.getElementById("summary-airport-total"),
        laundryTotal:
            document.getElementById("summary-laundry-total"),

        grandTotal: document.getElementById("summary-grand-total")
    };

    const summaryRoomImage =
        document.querySelector("#summary-room-image");

    // ---------------------------------------------------------
    // 4. CURRENCY FORMATTING
    // ---------------------------------------------------------

    function formatCurrency(amount) {
        return new Intl.NumberFormat("en-KE", {
            style: "currency",
            currency: "KES",
            maximumFractionDigits: 0
        }).format(amount);
    }

    // ---------------------------------------------------------
    // 5. DATE HELPERS
    // ---------------------------------------------------------

    /*
     * Format dates as YYYY-MM-DD using local calendar values.
     * Avoid toISOString() for local date comparisons because
     * it converts dates to UTC.
     */

    function getLocalDateString(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, "0");
        const day = String(date.getDate()).padStart(2, "0");

        return `${year}-${month}-${day}`;
    }

    function parseDate(dateString) {
        if (!dateString) {
            return null;
        }

        const parts = dateString.split("-");

        if (parts.length !== 3) {
            return null;
        }

        const year = Number(parts[0]);
        const month = Number(parts[1]);
        const day = Number(parts[2]);

        const date = new Date(year, month - 1, day);

        // Reject invalid dates, such as 2026-02-31.
        if (
            date.getFullYear() !== year ||
            date.getMonth() !== month - 1 ||
            date.getDate() !== day
        ) {
            return null;
        }

        return date;
    }

    function calculateNights(checkInDate, checkOutDate) {
        if (!checkInDate || !checkOutDate) {
            return 0;
        }

        const millisecondsPerDay = 24 * 60 * 60 * 1000;

        /*
         * Use calendar dates at midnight to avoid daylight-saving
         * time changing the number of nights in other time zones.
         */

        const start = Date.UTC(
            checkInDate.getFullYear(),
            checkInDate.getMonth(),
            checkInDate.getDate()
        );

        const end = Date.UTC(
            checkOutDate.getFullYear(),
            checkOutDate.getMonth(),
            checkOutDate.getDate()
        );

        return Math.round((end - start) / millisecondsPerDay);
    }

    function formatDate(dateString) {
        const date = parseDate(dateString);

        if (!date) {
            return "Not selected";
        }

        return new Intl.DateTimeFormat("en-KE", {
            day: "numeric",
            month: "short",
            year: "numeric"
        }).format(date);
    }

    // ---------------------------------------------------------
    // 6. INITIALISE DATE RESTRICTIONS
    // ---------------------------------------------------------

    function initialiseDateFields() {
        const today = getLocalDateString(new Date());

        checkInInput.min = today;

        if (checkInInput.value && checkInInput.value < today) {
            checkInInput.value = "";
        }

        updateCheckOutMinimum();
    }

    function updateCheckOutMinimum() {
        const checkInDate = parseDate(checkInInput.value);
        const today = getLocalDateString(new Date());

        /*
         * Checkout must be at least one calendar day after check-in.
         * When no check-in date is selected, tomorrow is the minimum.
         */

        const minimumDate = checkInDate
            ? new Date(
                checkInDate.getFullYear(),
                checkInDate.getMonth(),
                checkInDate.getDate() + 1
            )
            : new Date(
                new Date().getFullYear(),
                new Date().getMonth(),
                new Date().getDate() + 1
            );

        checkOutInput.min = getLocalDateString(minimumDate);

        if (
            checkOutInput.value &&
            checkOutInput.value <= checkInInput.value
        ) {
            checkOutInput.value = "";
        }

        /*
         * If check-in has been cleared, do not leave an invalid
         * checkout date selected.
         */

        if (!checkInInput.value && checkOutInput.value < today) {
            checkOutInput.value = "";
        }
    }

    // ---------------------------------------------------------
    // 7. ROOM SELECTION FROM HOMEPAGE
    // ---------------------------------------------------------

    function selectRoomFromURL() {
        const parameters = new URLSearchParams(window.location.search);
        const requestedRoom = parameters.get("room");

        if (
            requestedRoom &&
            Object.prototype.hasOwnProperty.call(
                ROOM_DETAILS,
                requestedRoom
            )
        ) {
            roomTypeInput.value = requestedRoom;
        }
    }

    // ---------------------------------------------------------
    // 8. DISPLAY FIELD ERRORS
    // ---------------------------------------------------------

    function setFieldError(input, message) {
        if (!input) {
            return;
        }

        const formGroup = input.closest(".form-group");

        if (!formGroup) {
            return;
        }

        const errorElement = formGroup.querySelector(".field-error");

        formGroup.classList.toggle("has-error", Boolean(message));

        input.setAttribute("aria-invalid", message ? "true" : "false");

        if (errorElement) {
            errorElement.textContent = message || "";
        }
    }

    function clearFieldError(input) {
        setFieldError(input, "");
    }

    function clearAllErrors() {
        const formGroups = bookingForm.querySelectorAll(".form-group");

        formGroups.forEach(function (group) {
            group.classList.remove("has-error");

            const errorElement = group.querySelector(".field-error");

            if (errorElement) {
                errorElement.textContent = "";
            }

            const input = group.querySelector(
                "input, select, textarea"
            );

            if (input) {
                input.setAttribute("aria-invalid", "false");
            }
        });

        if (formMessage) {
            formMessage.textContent = "";
            formMessage.classList.remove(
                "success",
                "error",
                "is-success",
                "is-error"
            );
            formMessage.hidden = true;
        }
    }

    // ---------------------------------------------------------
    // 9. VALIDATE THE BOOKING FORM
    // ---------------------------------------------------------

    function validateBookingForm() {
        clearAllErrors();

        let isValid = true;
        let firstInvalidField = null;

        function reportError(input, message) {
            setFieldError(input, message);

            if (isValid) {
                firstInvalidField = input;
            }

            isValid = false;
        }

        // Customer name.
        if (!fullNameInput.value.trim()) {
            reportError(
                fullNameInput,
                "Please enter your full name."
            );
        } else if (fullNameInput.value.trim().length < 2) {
            reportError(
                fullNameInput,
                "Your name must contain at least 2 characters."
            );
        } else if (fullNameInput.value.trim().length > 100) {
            reportError(
                fullNameInput,
                "Your name must not exceed 100 characters."
            );
        }

        // Email.
        const email = emailInput.value.trim();

        if (!email) {
            reportError(emailInput, "Please enter your email address.");
        } else if (
            !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)
        ) {
            reportError(
                emailInput,
                "Please enter a valid email address."
            );
        }

        // Phone number.
        const phone = phoneInput.value.trim();

        if (!phone) {
            reportError(phoneInput, "Please enter your phone number.");
        } else if (!/^[+\d][\d\s()+-]{7,19}$/.test(phone)) {
            reportError(
                phoneInput,
                "Please enter a valid phone number."
            );
        }

        // Guests.
        const guests = Number(guestsInput.value);

        if (
            !Number.isInteger(guests) ||
            guests < 1 ||
            guests > 4
        ) {
            reportError(
                guestsInput,
                "Please select between 1 and 4 guests."
            );
        }

        // Room selection.
        const selectedRoom = roomTypeInput.value;

        if (
            !Object.prototype.hasOwnProperty.call(
                ROOM_DETAILS,
                selectedRoom
            )
        ) {
            reportError(
                roomTypeInput,
                "Please select a valid room type."
            );
        } else if (
            Number.isInteger(guests) &&
            guests > ROOM_DETAILS[selectedRoom].capacity
        ) {
            reportError(
                guestsInput,
                `This room accommodates up to ${
                    ROOM_DETAILS[selectedRoom].capacity
                } guest(s). Please select a suitable room or reduce the guest count.`
            );
        }

        // Check-in.
        const today = getLocalDateString(new Date());
        const checkInDate = parseDate(checkInInput.value);

        if (!checkInInput.value) {
            reportError(
                checkInInput,
                "Please select your check-in date."
            );
        } else if (!checkInDate) {
            reportError(
                checkInInput,
                "Please enter a valid check-in date."
            );
        } else if (checkInInput.value < today) {
            reportError(
                checkInInput,
                "Check-in cannot be in the past."
            );
        }

        // Check-out.
        const checkOutDate = parseDate(checkOutInput.value);

        if (!checkOutInput.value) {
            reportError(
                checkOutInput,
                "Please select your check-out date."
            );
        } else if (!checkOutDate) {
            reportError(
                checkOutInput,
                "Please enter a valid check-out date."
            );
        } else if (
            checkInDate &&
            checkOutDate &&
            calculateNights(checkInDate, checkOutDate) < 1
        ) {
            reportError(
                checkOutInput,
                "Check-out must be at least one day after check-in."
            );
        }

        // Special requests.
        if (specialRequestsInput.value.length > 1000) {
            reportError(
                specialRequestsInput,
                "Special requests must not exceed 1,000 characters."
            );
        }

        // Terms.
        if (!termsInput.checked) {
            if (formMessage) {
                formMessage.textContent =
                    "Please accept the terms and conditions before continuing.";

                formMessage.classList.add("error");
                formMessage.hidden = false;
            }

            if (isValid) {
                firstInvalidField = termsInput;
            }

            isValid = false;
        }

        if (!humanVerificationInput.checked) {
            if (formMessage) {
                formMessage.textContent =
                    "Please check the box to confirm you are human.";

                formMessage.classList.add("error");
                formMessage.hidden = false;
            }

            if (isValid) {
                firstInvalidField = humanVerificationInput;
            }

            isValid = false;
        }

        if (!isValid && firstInvalidField) {
            firstInvalidField.focus();
        }

        return isValid;
    }

    // ---------------------------------------------------------
    // 10. UPDATE THE BOOKING SUMMARY
    // ---------------------------------------------------------

    function updateBookingSummary() {
        const selectedRoom = ROOM_DETAILS[roomTypeInput.value];

        const checkInDate = parseDate(checkInInput.value);
        const checkOutDate = parseDate(checkOutInput.value);

        const nights = Math.max(
            0,
            calculateNights(checkInDate, checkOutDate)
        );

        const guests = Number(guestsInput.value) || 0;

        /*
         * Do not calculate a stay when the selected dates are invalid.
         * The displayed estimate is reset until the dates are valid.
         */

        const validStay =
            checkInDate &&
            checkOutDate &&
            checkInInput.value >= getLocalDateString(new Date()) &&
            nights > 0;

        const billableNights = validStay ? nights : 0;

        const roomTotal = selectedRoom
            ? selectedRoom.rate * billableNights
            : 0;

        const breakfastTotal = breakfastInput.checked
            ? SERVICE_PRICES.breakfastPerGuestPerDay *
              guests *
              billableNights
            : 0;

        const airportTotal = airportTransferInput.checked
            ? SERVICE_PRICES.airportTransferPerDay *
              billableNights
            : 0;

        const laundryTotal = laundryInput.checked
            ? SERVICE_PRICES.laundryPerDay *
              billableNights
            : 0;

        const grandTotal =
            roomTotal +
            breakfastTotal +
            airportTotal +
            laundryTotal;

        /* Selected room information. */

        if (summaryElements.roomName) {
            summaryElements.roomName.textContent = selectedRoom
                ? selectedRoom.name
                : "Please select a room";
        }

        if (summaryElements.roomRate) {
            summaryElements.roomRate.textContent = selectedRoom
                ? `${formatCurrency(selectedRoom.rate)} / night`
                : "Select a room to view its price.";
        }

        if (summaryRoomImage) {
            if (selectedRoom && selectedRoom.image) {
                summaryRoomImage.onerror = function () {
                    this.style.display = "none";

                    const fallback = this
                        .closest(".summary-room-image")
                        ?.querySelector(".summary-room-image-fallback");

                    if (fallback) {
                        fallback.style.display = "flex";
                    }
                };

                summaryRoomImage.onload = function () {
                    this.style.display = "block";

                    const fallback = this
                        .closest(".summary-room-image")
                        ?.querySelector(".summary-room-image-fallback");

                    if (fallback) {
                        fallback.style.display = "none";
                    }
                };

                summaryRoomImage.alt = `${selectedRoom.name} at T@ke9ine`;
                summaryRoomImage.src = selectedRoom.image;
            } else {
                summaryRoomImage.removeAttribute("src");
                summaryRoomImage.style.display = "none";

                const fallback = summaryRoomImage
                    .closest(".summary-room-image")
                    ?.querySelector(".summary-room-image-fallback");

                if (fallback) {
                    fallback.style.display = "flex";
                }
            }
        }


        // Stay details.
        if (summaryElements.checkIn) {
            summaryElements.checkIn.textContent =
                formatDate(checkInInput.value);
        }

        if (summaryElements.checkOut) {
            summaryElements.checkOut.textContent =
                formatDate(checkOutInput.value);
        }

        if (summaryElements.nights) {
            summaryElements.nights.textContent =
                `${billableNights} ${
                    billableNights === 1 ? "night" : "nights"
                }`;
        }

        if (summaryElements.guests) {
            summaryElements.guests.textContent =
                `${guests} ${guests === 1 ? "guest" : "guests"}`;
        }

        // Cost breakdown.
        if (summaryElements.roomTotal) {
            summaryElements.roomTotal.textContent =
                formatCurrency(roomTotal);
        }

        if (summaryElements.breakfastTotal) {
            summaryElements.breakfastTotal.textContent =
                formatCurrency(breakfastTotal);
        }

        if (summaryElements.airportTotal) {
            summaryElements.airportTotal.textContent =
                formatCurrency(airportTotal);
        }

        if (summaryElements.laundryTotal) {
            summaryElements.laundryTotal.textContent =
                formatCurrency(laundryTotal);
        }

        if (summaryElements.grandTotal) {
            summaryElements.grandTotal.textContent =
                formatCurrency(grandTotal);
        }
    }

    // ---------------------------------------------------------
    // 11. UPDATE THE SPECIAL-REQUEST CHARACTER COUNTER
    // ---------------------------------------------------------

    function updateRequestCounter() {
        if (!specialRequestsInput || !requestCount) {
            return;
        }

        const currentLength = specialRequestsInput.value.length;

        requestCount.textContent = `${currentLength}/1000`;

        requestCount.setAttribute(
            "aria-label",
            `${currentLength} of 1000 characters used`
        );
    }

    // ---------------------------------------------------------
    // 12. SHOW A FORM MESSAGE
    // ---------------------------------------------------------

    function showFormMessage(message, type) {
        if (!formMessage) {
            return;
        }

        formMessage.textContent = message;
        formMessage.hidden = false;

        formMessage.classList.remove(
            "success",
            "error",
            "is-success",
            "is-error"
        );

        formMessage.classList.add(type);
    }

    function hideBookingErrorModal() {
        if (!errorModal) {
            return;
        }

        errorModal.classList.remove("is-visible");
        errorModal.setAttribute("aria-hidden", "true");
    }

    function showBookingErrorModal(html) {
        if (!errorModal || !errorTitle || !errorMessage || !errorList) {
            return;
        }

        const parsed = new DOMParser().parseFromString(html, "text/html");
        const title =
            parsed.querySelector("#booking-notice-title") ||
            parsed.querySelector("#error-title") ||
            parsed.querySelector("h1");
        const message =
            parsed.querySelector("#booking-notice-message") ||
            parsed.querySelector(".result-card > p") ||
            parsed.querySelector("p");
        const errors = parsed.querySelectorAll("li");

        errorTitle.textContent =
            title?.textContent.trim() || "Please Check Your Booking";
        errorMessage.textContent =
            message?.textContent.trim() ||
            "We could not process your booking. Please review the form and try again.";
        errorList.textContent = "";

        errors.forEach(function (error) {
            const item = document.createElement("li");
            item.textContent = error.textContent.trim();
            if (item.textContent) {
                errorList.appendChild(item);
            }
        });

        errorModal.classList.add("is-visible");
        errorModal.setAttribute("aria-hidden", "false");
        if (errorClose) {
            errorClose.focus();
        }
    }

    if (errorClose) {
        errorClose.addEventListener("click", hideBookingErrorModal);
    }

    if (errorModal) {
        errorModal.addEventListener("click", function (event) {
            if (event.target === errorModal) {
                hideBookingErrorModal();
            }
        });

        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape") {
                hideBookingErrorModal();
            }
        });
    }

    // ---------------------------------------------------------
    // 13. HANDLE INPUT CHANGES
    // ---------------------------------------------------------

    const summaryInputs = [
        guestsInput,
        checkInInput,
        checkOutInput,
        roomTypeInput,
        breakfastInput,
        airportTransferInput,
        laundryInput
    ];

    summaryInputs.forEach(function (input) {
        if (!input) {
            return;
        }

        input.addEventListener("change", function () {
            clearFieldError(input);
            updateBookingSummary();
        });
    });

    checkInInput.addEventListener("change", function () {
        updateCheckOutMinimum();
        clearFieldError(checkOutInput);
        updateBookingSummary();
    });

    /*
     * Clear individual validation errors as the customer corrects
     * the associated field.
     */

    [
        fullNameInput,
        emailInput,
        phoneInput,
        guestsInput,
        checkInInput,
        checkOutInput,
        roomTypeInput,
        specialRequestsInput
    ].forEach(function (input) {
        if (!input) {
            return;
        }

        input.addEventListener("input", function () {
            clearFieldError(input);
        });
    });

    specialRequestsInput.addEventListener(
        "input",
        updateRequestCounter
    );

    termsInput.addEventListener("change", function () {
        if (termsInput.checked && formMessage) {
            formMessage.hidden = true;
            formMessage.textContent = "";
            formMessage.classList.remove("error", "is-error");
        }
    });

    humanVerificationInput.addEventListener("change", function () {
        if (humanVerificationInput.checked && formMessage) {
            formMessage.hidden = true;
            formMessage.textContent = "";
            formMessage.classList.remove("error", "is-error");
        }
    });

    // ---------------------------------------------------------
    // 14. HANDLE FORM SUBMISSION
    // ---------------------------------------------------------

    bookingForm.addEventListener("submit", function (event) {
        /*
         * Validate before allowing the browser to send the form
         * to process_booking.php.
         */

        if (!validateBookingForm()) {
            event.preventDefault();

            if (
                !termsInput.checked &&
                formMessage &&
                formMessage.hidden
            ) {
                showFormMessage(
                    "Please accept the terms and conditions before continuing.",
                    "error"
                );
            }

            return;
        }

        /*
         * The PHP script must validate all fields and recalculate
         * the total independently. This JavaScript is not a security
         * boundary and does not confirm room availability.
         */

        updateBookingSummary();

        const buttonText = submitButton
            ? submitButton.querySelector(".button-text")
            : null;
        const originalButtonText = buttonText
            ? buttonText.textContent
            : submitButton?.textContent;

        if (submitButton) {
            submitButton.disabled = true;
            submitButton.setAttribute("aria-busy", "true");

            if (buttonText) {
                buttonText.textContent = "Processing booking...";
            } else {
                submitButton.textContent = "Processing booking...";
            }
        }

        event.preventDefault();

        fetch(bookingForm.action, {
            method: "POST",
            body: new FormData(bookingForm),
            headers: { "Accept": "text/html" },
            credentials: "same-origin"
        })
            .then(async function (response) {
                const html = await response.text();

                if (!response.ok) {
                    showBookingErrorModal(html);
                    return;
                }

                document.open();
                document.write(html);
                document.close();
            })
            .catch(function () {
                showBookingErrorModal(
                    "<h1>Booking Temporarily Unavailable</h1>" +
                    "<p>We could not submit your booking right now. Please try again later.</p>"
                );
            })
            .finally(function () {
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.removeAttribute("aria-busy");

                    if (buttonText) {
                        buttonText.textContent =
                            originalButtonText || "Submit Booking";
                    } else if (originalButtonText) {
                        submitButton.textContent = originalButtonText;
                    }
                }
            });
    });

    // ---------------------------------------------------------
    // 15. INITIALISE THE BOOKING PAGE
    // ---------------------------------------------------------

    selectRoomFromURL();
    initialiseDateFields();
    updateRequestCounter();
    updateBookingSummary();
});
