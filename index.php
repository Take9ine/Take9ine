<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta
        name="description"
        content="Welcome to T@ke9ine. Explore our rooms, discover our services, and book your comfortable stay with us."
    >

    <title>T@ke9ine | Welcome</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap"
        rel="stylesheet"
    >

    <!-- Global Styles -->
    <link rel="stylesheet" href="css/global.css">

    <!-- Homepage Styles -->
    <link rel="stylesheet" href="css/home.css">

    <!-- Font Awesome Icons -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
    >

    <!-- Homepage JavaScript -->
    <script src="js/home.js" defer></script>
    <script src="js/global.js" defer></script>
</head>

<body>

    <!-- ================= HEADER ================= -->

    <header class="site-header">

        <div class="container navbar">

            <!-- Hotel Logo -->
            <a href="index.php" class="brand" aria-label="T@ke9ine homepage">

                <span class="brand-icon">
                    <i class="fa-solid fa-hotel" aria-hidden="true"></i>
                </span>

                <span class="brand-text">
                    <strong>T@ke9ine</strong>
                    <small>HOTEL & SUITES</small>
                </span>

            </a>

            <!-- Mobile Navigation Toggle -->
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

                <a href="index.php" class="nav-link active" aria-current="page">
                    Home
                </a>
                <a href="Login.html" class="nav-link">
                    LogIn
                </a>

                <a href="#about" class="nav-link">
                    About
                </a>

                <a href="#rooms" class="nav-link">
                    Our Rooms
                </a>

                <a href="#services" class="nav-link">
                    Services
                </a>

                <a href="booking.php" class="nav-link nav-booking">
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

        <!-- ================= HERO SECTION ================= -->
        <section class="hero-section" aria-labelledby="hero-title">
            <!-- Background image is controlled by home.css -->
            <div class="hero-overlay" aria-hidden="true"></div>

            <div class="container hero-content">
                <div class="hero-layout">

                    <!-- LEFT: Main hero content -->
                    <div class="hero-main">
                        <span class="hero-eyebrow">
                            <i class="fa-solid fa-star" aria-hidden="true"></i>
                            WELCOME TO T@ke9ine
                        </span>

                        <h1 id="hero-title">
                            Comfort Meets
                            <span>Elegance.</span>
                        </h1>

                        <p class="hero-description">
                            Experience exceptional hospitality, beautifully designed
                            rooms and memorable moments. Your perfect stay begins here.
                        </p>

                        <div class="hero-actions">
                            <a href="booking.php" class="btn btn-primary">
                                Book Your Stay
                                <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                            </a>

                            <a href="#rooms" class="btn btn-outline">
                                Explore Our Rooms
                            </a>
                        </div>
                    </div>

                    <!-- RIGHT: Hotel amenities card -->
                    <aside class="hero-amenities-card"
                        aria-label="T@ke9ine amenities">

                        <div class="amenities-card-heading">
                            <span class="amenities-card-label">
                                <i class="fa-solid fa-sparkles" aria-hidden="true"></i>
                                THE T@ke9ine EXPERIENCE
                            </span>

                            <h2>Everything you need for a perfect stay.</h2>

                            <p>Comfort, convenience and warm hospitality.</p>
                        </div>

                        <div class="amenity-item">
                            <span class="amenity-icon">
                                <i class="fa-solid fa-bed" aria-hidden="true"></i>
                            </span>

                            <div class="amenity-text">
                                <h3>Comfortable Rooms</h3>
                                <p>Relax in style</p>
                            </div>
                        </div>

                        <div class="amenity-item">
                            <span class="amenity-icon">
                                <i class="fa-solid fa-wifi" aria-hidden="true"></i>
                            </span>

                            <div class="amenity-text">
                                <h3>Free Wi-Fi</h3>
                                <p>Stay connected</p>
                            </div>
                        </div>

                        <div class="amenity-item">
                            <span class="amenity-icon">
                                <i class="fa-solid fa-utensils" aria-hidden="true"></i>
                            </span>

                            <div class="amenity-text">
                                <h3>Restaurant &amp; Bar</h3>
                                <p>Enjoy delicious meals</p>
                            </div>
                        </div>

                        <div class="amenity-item">
                            <span class="amenity-icon">
                                <i class="fa-solid fa-headset" aria-hidden="true"></i>
                            </span>

                            <div class="amenity-text">
                                <h3>24/7 Support</h3>
                                <p>We're always here to help</p>
                            </div>
                        </div>

                        <a href="booking.php" class="amenities-card-link">
                            Make yourself at home
                            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </a>
                    </aside>

                </div>
            </div>

            <!-- Scroll Indicator -->
            <a href="#about"
            class="scroll-indicator"
            aria-label="Scroll to learn about us">
                <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
            </a>
        </section>

        <!-- ================= ABOUT SECTION ================= -->

        <section class="about-section section-padding" id="about">

            <div class="container about-grid">

                <div class="about-image-wrapper">

                    <img
                        src="https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=1000&q=85"
                        alt="Elegant hotel exterior with a swimming pool and landscaped surroundings"
                        class="about-image"
                        loading="lazy"
                    >

                    <div class="experience-badge">
                        <i class="fa-solid fa-heart" aria-hidden="true"></i>
                        <span>Hospitality</span>
                        <strong>With Care</strong>
                    </div>

                </div>

                <div class="about-content">

                    <span class="section-eyebrow">
                        ABOUT T@ke9ine
                    </span>

                    <h2 class="section-title">
                        A Place to Relax,
                        <span>A Reason to Return.</span>
                    </h2>

                    <p>
                        At T@ke9ine, we believe every guest deserves
                        comfort, convenience, and a warm welcome.
                    </p>

                    <p>
                        Whether you are travelling for business, enjoying a
                        holiday, or visiting family, our goal is to make your
                        stay enjoyable from check-in to check-out.
                    </p>

                    <a href="booking.php" class="text-link">
                        Plan Your Stay
                        <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                    </a>

                </div>

            </div>

        </section>


        <!-- ================= ROOMS SECTION ================= -->

        <section class="rooms-section section-padding" id="rooms">

            <div class="container">

                <div class="section-heading">

                    <span class="section-eyebrow">
                        ACCOMMODATION
                    </span>

                    <h2 class="section-title">
                        Find Your Perfect Room
                    </h2>

                    <p>
                        Choose the room that suits your needs and budget.
                    </p>

                </div>

                <div class="rooms-grid">

                    <!-- Single Room -->
                    <article class="room-card">

                        <div class="room-image-wrapper">

                            <img
                                src="https://images.unsplash.com/photo-1611892440504-42a792e24d32?auto=format&fit=crop&w=800&q=85"
                                alt="Comfortable hotel room with a neatly arranged bed"
                                class="room-image"
                                loading="lazy"
                            >

                            <span class="room-tag">
                                Ideal for Solo Travellers
                            </span>

                        </div>

                        <div class="room-details">

                            <div class="room-heading">
                                <h3>Single Room</h3>
                                <span class="room-capacity">
                                    <i class="fa-solid fa-user" aria-hidden="true"></i>
                                    1 Guest
                                </span>
                            </div>

                            <p>
                                A comfortable and practical space for
                                individuals travelling alone.
                            </p>

                            <div class="room-footer">

                                <div class="room-price">
                                    <span>From</span>
                                    <strong>KSh 3,500</strong>
                                    <small>/ night</small>
                                </div>

                                <a
                                    href="booking.php?room=single"
                                    class="room-book-btn"
                                    aria-label="Book a single room"
                                >
                                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                </a>

                            </div>

                        </div>

                    </article>


                    <!-- Double Room -->
                    <article class="room-card">

                        <div class="room-image-wrapper">

                            <img
                                src="https://images.unsplash.com/photo-1590490360182-c33d57733427?auto=format&fit=crop&w=800&q=85"
                                alt="Spacious double hotel room prepared for guests"
                                class="room-image"
                                loading="lazy"
                            >

                            <span class="room-tag">
                                Great for Couples
                            </span>

                        </div>

                        <div class="room-details">

                            <div class="room-heading">
                                <h3>Double Room</h3>
                                <span class="room-capacity">
                                    <i class="fa-solid fa-user-group" aria-hidden="true"></i>
                                    2 Guests
                                </span>
                            </div>

                            <p>
                                Enjoy additional space and comfort for
                                couples, friends, or shared stays.
                            </p>

                            <div class="room-footer">

                                <div class="room-price">
                                    <span>From</span>
                                    <strong>KSh 5,000</strong>
                                    <small>/ night</small>
                                </div>

                                <a
                                    href="booking.php?room=double"
                                    class="room-book-btn"
                                    aria-label="Book a double room"
                                >
                                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                </a>

                            </div>

                        </div>

                    </article>


                    <!-- Executive Room -->
                    <article class="room-card">

                        <div class="room-image-wrapper">

                            <img
                                src="https://images.unsplash.com/photo-1616486338812-3dadae4b4ace?auto=format&fit=crop&w=800&q=85"
                                alt="Elegant executive accommodation with refined furnishings"
                                class="room-image"
                                loading="lazy"
                            >

                            <span class="room-tag">
                                Extra Comfort
                            </span>

                        </div>

                        <div class="room-details">

                            <div class="room-heading">
                                <h3>Executive Room</h3>
                                <span class="room-capacity">
                                    <i class="fa-solid fa-user-group" aria-hidden="true"></i>
                                    2 Guests
                                </span>
                            </div>

                            <p>
                                A refined accommodation option for guests
                                who appreciate additional comfort.
                            </p>

                            <div class="room-footer">

                                <div class="room-price">
                                    <span>From</span>
                                    <strong>KSh 7,500</strong>
                                    <small>/ night</small>
                                </div>

                                <a
                                    href="booking.php?room=executive"
                                    class="room-book-btn"
                                    aria-label="Book an executive room"
                                >
                                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                </a>

                            </div>

                        </div>

                    </article>

                </div>

                <div class="rooms-cta">

                    <p>
                        Ready to experience T@ke9ine?
                    </p>

                    <a href="booking.php" class="btn btn-primary">
                        Make a Reservation
                        <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                    </a>

                </div>

            </div>

        </section>


        <!-- ================= SERVICES SECTION ================= -->

        <section class="services-section section-padding" id="services">

            <div class="container">

                <div class="section-heading">

                    <span class="section-eyebrow">
                        OUR SERVICES
                    </span>

                    <h2 class="section-title">
                        More Than Just a Stay
                    </h2>

                    <p>
                        Enjoy convenient services designed around your needs.
                    </p>

                </div>

                <div class="services-grid">

                    <article class="service-card">

                        <div class="service-icon">
                            <i class="fa-solid fa-mug-hot" aria-hidden="true"></i>
                        </div>

                        <h3>Breakfast</h3>

                        <p>
                            Add a breakfast option to make your mornings
                            more enjoyable.
                        </p>

                    </article>

                    <article class="service-card">

                        <div class="service-icon">
                            <i class="fa-solid fa-plane-arrival" aria-hidden="true"></i>
                        </div>

                        <h3>Airport Transfer</h3>

                        <p>
                            Request airport transfer services when making
                            your reservation.
                        </p>

                    </article>

                    <article class="service-card">

                        <div class="service-icon">
                            <i class="fa-solid fa-shirt" aria-hidden="true"></i>
                        </div>

                        <h3>Laundry Service</h3>

                        <p>
                            Keep your clothes fresh during your stay with
                            our optional laundry service.
                        </p>

                    </article>

                </div>

            </div>

        </section>


        <!-- ================= CALL TO ACTION ================= -->

        <section class="booking-cta-section">

            <div class="container booking-cta-content">

                <div>

                    <span class="section-eyebrow">
                        YOUR NEXT STAY STARTS HERE
                    </span>

                    <h2>-
                        Make Your Stay
                        <span>Memorable.</span>
                    </h2>

                    <p>
                        Select your room, choose your services, and get
                        an estimated bill before submitting your booking.
                    </p>

                </div>

                <a href="booking.php" class="btn btn-primary">
                    Book Your Room
                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </a>

            </div>

        </section>

    </main>


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
