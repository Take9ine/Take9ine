<?php require_once __DIR__ . '/auth_guard.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta
        name="description"
        content="Reserve your stay at T@ke9ine. Select your room, choose additional services, and calculate your estimated accommodation bill."
    >

    <title>Book a Room | T@ke9ine</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap"
        rel="stylesheet"
    >

    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
    >

    <!-- Global Styles -->
    <link rel="stylesheet" href="css/global.css">

    <!-- Booking Page Styles -->
    <link rel="stylesheet" href="css/booking.css">

    <!-- Booking Page JavaScript -->
    <script src="js/bookings.js" defer></script>
    <script src="js/global.js" defer></script>
</head>

<body>

    <!-- ================= HEADER ================= -->

    <header class="site-header">

        <div class="container navbar">

            <!-- Brand -->
            <a
                href="index.php"
                class="brand"
                aria-label="T@ke9ine homepage"
            >

                <span class="brand-icon">
                    <i class="fa-solid fa-hotel" aria-hidden="true"></i>
                </span>

                <span class="brand-text">
                    <strong>T@ke9ine</strong>
                    <small>HOTEL & SUITES</small>
                </span>

            </a>


            <!-- Mobile Menu Button -->
            <button
                class="menu-toggle"
                id="menu-toggle"
                type="button"
                aria-label="Open navigation menu"
                aria-expanded="false"
                aria-controls="primary-navigation"
            >

                <i class="fa-solid fa-bars" aria-hidden="true"></i>

            </button>


            <!-- Navigation -->
            <nav
                class="primary-navigation"
                id="primary-navigation"
                aria-label="Main navigation"
            >

                <a href="index.php" class="nav-link">
                    Home
                </a>

                <a href="Login.html" class="nav-link">
                    LogIn
                </a>

                <a href="index.php#about" class="nav-link">
                    About
                </a>

                <a href="index.php#rooms" class="nav-link">
                    Our Rooms
                </a>

                <a href="index.php#services" class="nav-link">
                    Services
                </a>

                <a
                    href="booking.php"
                    class="nav-link nav-booking active"
                    aria-current="page"
                >
                    Book a Room
                </a>


                <!-- Theme Toggle -->
                <button
                    type="button"
                    class="theme-toggle"
                    id="theme-toggle"
                    aria-label="Switch to dark mode"
                    title="Toggle light and dark mode"
                >

                    <i class="fa-solid fa-moon" aria-hidden="true"></i>

                </button>

            </nav>

        </div>

    </header>


    <!-- ================= MAIN CONTENT ================= -->

    <main id="main-content">

        <!-- ================= PAGE HERO ================= -->

        <section class="booking-hero">

            <div class="booking-hero-overlay"></div>

            <div class="container booking-hero-content">

                <span class="booking-hero-label">
                    <i class="fa-solid fa-calendar-check" aria-hidden="true"></i>
                    RESERVATIONS
                </span>

                <h1>
                    Book Your <span>Perfect Stay</span>
                </h1>

                <p>
                    A few simple steps are all it takes to plan your stay
                    at T@ke9ine.
                </p>

                <div class="booking-breadcrumb">

                    <a href="index.php">Home</a>

                    <a href="Login.html">LogIn</a>

                    <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>

                    <span aria-current="page">Book a Room</span>

                </div>

            </div>

        </section>


        <!-- ================= BOOKING SECTION ================= -->

        <section class="booking-section section-padding">

            <div class="container">

                <div class="booking-intro">

                    <div>

                        <span class="section-eyebrow">
                            YOUR RESERVATION
                        </span>

                        <h2 class="section-title">
                            Let's Plan Your Stay
                        </h2>

                        <p>
                            Enter your details, customize your stay,
                            and review the estimated cost before submitting.
                        </p>

                    </div>

                    <div class="booking-security-note">

                        <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>

                        <span>
                            Your information is used to process your reservation.
                        </span>

                    </div>

                </div>


                <!-- Booking Form and Summary -->
                <div class="booking-layout">


                    <!-- ================= BOOKING FORM ================= -->

                    <div class="booking-form-card">

                        <div class="form-card-header">

                            <div class="form-step-icon">
                                <i class="fa-solid fa-clipboard-list" aria-hidden="true"></i>
                            </div>

                            <div>

                                <h3>Reservation Details</h3>

                                <p>
                                    Fields marked with
                                    <span class="required-indicator">*</span>
                                    are required.
                                </p>

                            </div>

                        </div>


                        <!--
                            The form submits to process_booking.php.
                            JavaScript also calculates a preliminary estimate.
                        -->

                        <form
                            action="process_booking.php"
                            method="POST"
                            id="booking-form"
                            autocomplete="on"
                        >

                            <!-- ================= CUSTOMER DETAILS ================= -->

                            <fieldset class="form-section">

                                <legend>
                                    <span class="fieldset-number">01</span>
                                    Guest Information
                                </legend>


                                <div class="form-grid">

                                    <!-- Full Name -->
                                    <div class="form-group">

                                        <label for="full-name">
                                            Full Name
                                            <span class="required-indicator">*</span>
                                        </label>

                                        <div class="input-wrapper">

                                            <i class="fa-regular fa-user" aria-hidden="true"></i>

                                            <input
                                                type="text"
                                                id="full-name"
                                                name="full_name"
                                                placeholder="Enter your full name"
                                                minlength="2"
                                                maxlength="100"
                                                autocomplete="name"
                                                required
                                            >

                                        </div>

                                        <small class="field-error" id="full-name-error"></small>

                                    </div>


                                    <!-- Email -->
                                    <div class="form-group">

                                        <label for="email">
                                            Email Address
                                            <span class="required-indicator">*</span>
                                        </label>

                                        <div class="input-wrapper">

                                            <i class="fa-regular fa-envelope" aria-hidden="true"></i>

                                            <input
                                                type="email"
                                                id="email"
                                                name="email"
                                                placeholder="you@example.com"
                                                maxlength="254"
                                                autocomplete="email"
                                                required
                                            >

                                        </div>

                                        <small class="field-error" id="email-error"></small>

                                    </div>


                                    <!-- Phone -->
                                    <div class="form-group">

                                        <label for="phone">
                                            Phone Number
                                            <span class="required-indicator">*</span>
                                        </label>

                                        <div class="input-wrapper">

                                            <i class="fa-solid fa-phone" aria-hidden="true"></i>

                                            <input
                                                type="tel"
                                                id="phone"
                                                name="phone"
                                                placeholder="e.g. 0712345678"
                                                minlength="9"
                                                maxlength="16"
                                                autocomplete="tel"
                                                required
                                            >

                                        </div>

                                        <small class="field-hint">
                                            Include your country code if applicable.
                                        </small>

                                        <small class="field-error" id="phone-error"></small>

                                    </div>


                                    <!-- Guest Count -->
                                    <div class="form-group">

                                        <label for="guests">
                                            Number of Guests
                                            <span class="required-indicator">*</span>
                                        </label>

                                        <div class="input-wrapper">

                                            <i class="fa-solid fa-user-group" aria-hidden="true"></i>

                                            <select
                                                id="guests"
                                                name="guests"
                                                required
                                            >

                                                <option value="1" selected>1 Guest</option>
                                                <option value="2">2 Guests</option>
                                                <option value="3">3 Guests</option>
                                                <option value="4">4 Guests</option>

                                            </select>

                                        </div>

                                    </div>

                                </div>

                            </fieldset>


                            <!-- ================= STAY DETAILS ================= -->

                            <fieldset class="form-section">

                                <legend>
                                    <span class="fieldset-number">02</span>
                                    Stay Details
                                </legend>


                                <div class="form-grid">

                                    <!-- Check-in -->
                                    <div class="form-group">

                                        <label for="check-in">
                                            Check-in Date
                                            <span class="required-indicator">*</span>
                                        </label>

                                        <div class="input-wrapper">

                                            <i class="fa-regular fa-calendar" aria-hidden="true"></i>

                                            <input
                                                type="date"
                                                id="check-in"
                                                name="check_in"
                                                required
                                            >

                                        </div>

                                        <small class="field-error" id="check-in-error"></small>

                                    </div>


                                    <!-- Check-out -->
                                    <div class="form-group">

                                        <label for="check-out">
                                            Check-out Date
                                            <span class="required-indicator">*</span>
                                        </label>

                                        <div class="input-wrapper">

                                            <i class="fa-regular fa-calendar-check" aria-hidden="true"></i>

                                            <input
                                                type="date"
                                                id="check-out"
                                                name="check_out"
                                                required
                                            >

                                        </div>

                                        <small class="field-error" id="check-out-error"></small>

                                    </div>


                                    <!-- Room Type -->
                                    <div class="form-group form-group-full">

                                        <label for="room-type">
                                            Room Type
                                            <span class="required-indicator">*</span>
                                        </label>

                                        <div class="input-wrapper">

                                            <i class="fa-solid fa-bed" aria-hidden="true"></i>

                                            <select
                                                id="room-type"
                                                name="room_type"
                                                required
                                            >

                                                <option value="">
                                                    Select your preferred room
                                                </option>

                                                <option value="single" data-price="3000">
                                                    Single Room - KSh 3,500 per night
                                                </option>

                                                <option value="double" data-price="5000">
                                                    Double Room - KSh 5,000 per night
                                                </option>

                                                <option value="executive" data-price="7500">
                                                    Executive Room - KSh 7,500 per night
                                                </option>

                                                <option value="extra" data-price="1500">
                                                    Extra Bed - KSh 1,500 per night
                                                </option>

                                            </select>

                                        </div>

                                        <small class="field-hint">
                                            Prices are illustrative and subject to confirmation.
                                        </small>

                                        <small class="field-error" id="room-type-error"></small>

                                    </div>


                                    <!-- Special Requests -->
                                    <div class="form-group form-group-full">

                                        <label for="special-requests">
                                            Special Requests
                                            <span class="optional-label">(Optional)</span>
                                        </label>

                                        <textarea
                                            id="special-requests"
                                            name="special_requests"
                                            rows="4"
                                            maxlength="1000"
                                            placeholder="Tell us about any special requirements for your stay..."
                                        ></textarea>

                                        <div class="textarea-footer">

                                            <small>
                                                We will do our best to accommodate your requests.
                                            </small>

                                            <small>
                                                <span id="request-count">0</span>/1000
                                            </small>

                                        </div>

                                    </div>

                                </div>

                            </fieldset>


                            <!-- ================= ADDITIONAL SERVICES ================= -->

                            <fieldset class="form-section">

                                <legend>
                                    <span class="fieldset-number">03</span>
                                    Additional Services
                                </legend>

                                <p class="fieldset-description">
                                    Customize your experience with optional services.
                                </p>


                                <div class="service-options">


                                    <!-- Breakfast -->
                                    <label class="service-option" for="breakfast">

                                        <span class="service-option-icon">
                                            <i class="fa-solid fa-mug-hot" aria-hidden="true"></i>
                                        </span>

                                        <span class="service-option-content">

                                            <strong>Breakfast</strong>

                                            <small>
                                                Include breakfast daily during your stay.
                                            </small>

                                            <span class="service-option-price">
                                                KSh 700 per guest/day
                                            </span>

                                        </span>

                                        <input
                                            type="checkbox"
                                            id="breakfast"
                                            name="breakfast"
                                            value="1"
                                            data-price="700"
                                        >

                                    </label>


                                    <!-- Airport Transfer -->
                                    <label class="service-option" for="airport-transfer">

                                        <span class="service-option-icon">
                                            <i class="fa-solid fa-car" aria-hidden="true"></i>
                                        </span>

                                        <span class="service-option-content">

                                            <strong>Airport Transfer</strong>

                                            <small>
                                                Request airport pickup or drop-off each day.
                                            </small>

                                            <span class="service-option-price">
                                                KSh 2,000 per day
                                            </span>

                                        </span>

                                        <input
                                            type="checkbox"
                                            id="airport-transfer"
                                            name="airport_transfer"
                                            value="1"
                                            data-price="2000"
                                        >

                                    </label>


                                    <!-- Laundry -->
                                    <label class="service-option" for="laundry">

                                        <span class="service-option-icon">
                                            <i class="fa-solid fa-shirt" aria-hidden="true"></i>
                                        </span>

                                        <span class="service-option-content">

                                            <strong>Laundry Service</strong>

                                            <small>
                                                Request daily laundry service during your stay.
                                            </small>

                                            <span class="service-option-price">
                                                KSh 1,000 per day
                                            </span>

                                        </span>

                                        <input
                                            type="checkbox"
                                            id="laundry"
                                            name="laundry"
                                            value="1"
                                            data-price="1000"
                                        >

                                    </label>

                                </div>

                            </fieldset>


                            <!-- ================= TERMS AND SUBMISSION ================= -->

                            <div class="form-terms">

                                <label class="checkbox-label" for="terms">

                                    <input
                                        type="checkbox"
                                        id="terms"
                                        name="terms"
                                        value="accepted"
                                        required
                                    >

                                    <span>
                                        I confirm that the information provided is accurate
                                        and agree to have my reservation details processed.
                                        <span class="required-indicator">*</span>
                                    </span>

                                </label>

                                <small class="field-error" id="terms-error"></small>

                            </div>

                            <div class="human-verification">
                                <label for="human-verification">
                                    <input
                                        type="checkbox"
                                        id="human-verification"
                                        name="human_verification"
                                        value="1"
                                        required
                                    >
                                    <span>Check this box to confirm you are human.</span>
                                </label>
                            </div>

                            <!-- General Form Message -->
                            <div
                                class="form-message"
                                id="form-message"
                                role="status"
                                aria-live="polite"
                                hidden
                            ></div>


                            <!-- Submit Button -->
                            <div class="form-actions">

                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                                    Back to Home
                                </a>

                                <button
                                    type="submit"
                                    class="btn btn-primary submit-booking-btn"
                                    id="submit-booking"
                                >

                                    Submit Booking

                                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>

                                </button>

                            </div>

                            <p class="form-disclaimer">
                                Your booking is subject to confirmation.
                                Submitting this form does not guarantee room availability.
                            </p>

                        </form>

                    </div>


                    <!-- ================= BILL SUMMARY ================= -->

                    <aside class="booking-summary" aria-labelledby="summary-title">

                        <div class="summary-header">

                            <span class="summary-icon">
                                <i class="fa-solid fa-receipt" aria-hidden="true"></i>
                            </span>

                            <div>

                                <h3 id="summary-title">
                                    Booking Summary
                                </h3>

                                <p>
                                    Your estimated charges
                                </p>

                            </div>

                        </div>

                        <!-- Selected Room -->
                        <div class="summary-room">

                            <div class="summary-room-image">
                                <img
                                    id="summary-room-image"
                                    src="assets/images/hero_1.jpg"
                                    alt="Selected hotel room"
                                    loading="eager"
                                >

                                <span class="summary-room-image-fallback" aria-hidden="true">
                                    <i class="fa-solid fa-bed"></i>
                                </span>
                            </div>

                            <div class="summary-room-details">
                                <span>Selected Accommodation</span>

                                <strong id="summary-room-name">
                                    No room selected
                                </strong>

                                <small id="summary-room-rate">
                                    Select a room to view its price.
                                </small>
                            </div>

                        </div>

                        <!-- Stay Information -->
                        <div class="summary-stay">

                            <div class="summary-row">

                                <span>
                                    <i class="fa-regular fa-calendar" aria-hidden="true"></i>
                                    Check-in
                                </span>

                                <strong id="summary-check-in">
                                    Not selected
                                </strong>

                            </div>

                            <div class="summary-row">

                                <span>
                                    <i class="fa-regular fa-calendar-check" aria-hidden="true"></i>
                                    Check-out
                                </span>

                                <strong id="summary-check-out">
                                    Not selected
                                </strong>

                            </div>

                            <div class="summary-row">

                                <span>
                                    <i class="fa-solid fa-moon" aria-hidden="true"></i>
                                    Number of Nights
                                </span>

                                <strong id="summary-nights">
                                    0
                                </strong>

                            </div>

                            <div class="summary-row">

                                <span>
                                    <i class="fa-solid fa-user-group" aria-hidden="true"></i>
                                    Guests
                                </span>

                                <strong id="summary-guests">
                                    1
                                </strong>

                            </div>

                        </div>


                        <!-- Price Breakdown -->
                        <div class="summary-breakdown">

                            <h4>Cost Breakdown</h4>

                            <div class="summary-row">

                                <span>Room Charges</span>

                                <strong id="summary-room-total">
                                    KSh 0
                                </strong>

                            </div>

                            <div class="summary-row">

                                <span>Breakfast</span>

                                <strong id="summary-breakfast-total">
                                    KSh 0
                                </strong>

                            </div>

                            <div class="summary-row">

                                <span>Airport Transfer</span>

                                <strong id="summary-airport-total">
                                    KSh 0
                                </strong>

                            </div>

                            <div class="summary-row">

                                <span>Laundry</span>

                                <strong id="summary-laundry-total">
                                    KSh 0
                                </strong>

                            </div>

                        </div>


                        <!-- Grand Total -->
                        <div class="summary-total">

                            <div>

                                <span>Estimated Total</span>

                                <small>
                                    Payable amount subject to confirmation
                                </small>

                            </div>

                            <strong id="summary-grand-total">
                                KSh 0
                            </strong>

                        </div>


                        <!-- Summary Information -->
                        <div class="summary-note">

                            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>

                            <p>
                                This is an estimate based on your selections.
                                The hotel must confirm availability and final charges.
                            </p>

                        </div>


                        <!-- Assistance -->
                        <div class="summary-help">

                            <span class="summary-help-icon">
                                <i class="fa-solid fa-headset" aria-hidden="true"></i>
                            </span>

                            <div>

                                <strong>Need assistance?</strong>

                                <p>
                                    Review your information before submitting
                                    your reservation.
                                </p>

                            </div>

                        </div>

                    </aside>

                </div>

            </div>

        </section>


        <!-- ================= TRUST SECTION ================= -->

        <section class="booking-trust-section">

            <div class="container trust-grid">

                <div class="trust-item">

                    <i class="fa-solid fa-calendar-check" aria-hidden="true"></i>

                    <div>

                        <strong>Simple Booking</strong>

                        <p>
                            Enter your details in one convenient form.
                        </p>

                    </div>

                </div>

                <div class="trust-item">

                    <i class="fa-solid fa-calculator" aria-hidden="true"></i>

                    <div>

                        <strong>Transparent Estimate</strong>

                        <p>
                            Review estimated charges before submitting.
                        </p>

                    </div>

                </div>

                <div class="trust-item">

                    <i class="fa-solid fa-user-shield" aria-hidden="true"></i>

                    <div>

                        <strong>Guest-Focused Design</strong>

                        <p>
                            Clear fields, helpful guidance, and validation.
                        </p>

                    </div>

                </div>

            </div>

        </section>

    </main>


    <div id="booking-error-modal" class="booking-error-modal" aria-hidden="true">
        <section
            class="booking-error-card"
            role="dialog"
            aria-modal="true"
            aria-labelledby="booking-error-title"
            aria-describedby="booking-error-message"
        >
            <div class="booking-error-brand" aria-label="T@ke9ine">
                <svg viewBox="0 0 64 64" aria-hidden="true" focusable="false">
                    <path d="M12 54V24h40v30M8 54h48M20 24V13h24v11M24 32h5m6 0h5M24 41h5m6 0h5M28 54V45h8v9" />
                </svg>
                <span>T@ke9ine</span>
            </div>
            <h2 id="booking-error-title">Please Check Your Booking</h2>
            <p id="booking-error-message"></p>
            <ul id="booking-error-list"></ul>
            <button type="button" id="booking-error-close" class="booking-error-close">
                Back to Booking
            </button>
        </section>
    </div>


    <!-- ================= T@ke9ine FOOTER ================= -->
    <footer class="site-footer" id="site-footer">
        <div class="container footer-grid">

            <!-- Hotel brand -->
            <div class="footer-brand">
                <a href="index.php" class="brand footer-brand-link"
                aria-label="T@ke9ine home">
                    <span class="brand-icon">
                        <i class="fa-solid fa-hotel" aria-hidden="true"></i>
                    </span>

                    <span class="brand-text">
                        <strong>T@ke9ine</strong>
                        <small>HOTEL &amp; SUITES</small>
                    </span>
                </a>

                <p class="footer-description">
                    Experience comfortable accommodation, thoughtful
                    hospitality and a warm welcome at T@ke9ine.
                    Your comfort is our priority.
                </p>

                <a href="booking.php" class="footer-cta">
                    Book your stay
                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </a>
            </div>

            <!-- Quick navigation -->
            <div class="footer-column">
                <h3 class="footer-heading">Explore</h3>

                <nav class="footer-links" aria-label="Explore T@ke9ine">
                    <a href="index.php">
                        <i class="fa-solid fa-angle-right" aria-hidden="true"></i>
                        Home
                    </a>

                    <a href="index.php#about">
                        <i class="fa-solid fa-angle-right" aria-hidden="true"></i>
                        About Us
                    </a>

                    <a href="index.php#rooms">
                        <i class="fa-solid fa-angle-right" aria-hidden="true"></i>
                        Our Rooms
                    </a>

                    <a href="index.php#services">
                        <i class="fa-solid fa-angle-right" aria-hidden="true"></i>
                        Our Services
                    </a>
                </nav>
            </div>

            <!-- Reservations -->
            <div class="footer-column">
                <h3 class="footer-heading">Reservations</h3>

                <nav class="footer-links" aria-label="Room reservations">
                    <a href="booking.php">
                        <i class="fa-solid fa-angle-right" aria-hidden="true"></i>
                        Book a Room
                    </a>

                    <a href="booking.php?room=single">
                        <i class="fa-solid fa-angle-right" aria-hidden="true"></i>
                        Single Room
                    </a>

                    <a href="booking.php?room=double">
                        <i class="fa-solid fa-angle-right" aria-hidden="true"></i>
                        Double Room
                    </a>

                    <a href="booking.php?room=executive">
                        <i class="fa-solid fa-angle-right" aria-hidden="true"></i>
                        Executive Room
                    </a>
                </nav>
            </div>

            <!-- Contact information -->
            <div class="footer-column footer-contact">
                <h3 class="footer-heading">Get in Touch</h3>

                <div class="footer-contact-item">
                    <span class="footer-contact-icon">
                        <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                    </span>

                    <div>
                        <strong>Our Location</strong>
                        <p>T@ke9ine &amp; Suites</p>
                    </div>
                </div>

                <div class="footer-contact-item">
                    <span class="footer-contact-icon">
                        <i class="fa-solid fa-envelope" aria-hidden="true"></i>
                    </span>

                    <div>
                        <strong>Enquiries</strong>
                        <p>Contact our reception for assistance.</p>
                    </div>
                </div>

                <div class="footer-contact-item">
                    <span class="footer-contact-icon">
                        <i class="fa-solid fa-clock" aria-hidden="true"></i>
                    </span>

                    <div>
                        <strong>Guest Services</strong>
                        <p>Here to assist with your stay.</p>
                    </div>
                </div>
            </div>

        </div>

        <!-- Footer copyright -->
        <div class="footer-bottom">
            <div class="container footer-bottom-content">
                <p>
                    &copy; <span id="current-year">2026</span>
                    T@ke9ine &amp; Suites. All rights reserved.
                </p>

                <a href="index.php" class="footer-back-home">
                    Back to Home
                    <i class="fa-solid fa-arrow-up" aria-hidden="true"></i>
                </a>

                <p class="footer-tagline">
                    Designed for comfort. Built for convenience.
                </p>
            </div>
        </div>
    </footer>

</body>

</html>
