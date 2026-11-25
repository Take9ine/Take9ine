<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| T@ke9ine ROOM BOOKING AND BILLING SYSTEM
| File: process_booking.php
|--------------------------------------------------------------------------
| Responsibilities:
| 1. Receive booking form submissions.
| 2. Validate customer information.
| 3. Validate room type, guest count and dates.
| 4. Calculate the bill on the server.
| 5. Display a booking confirmation and itemised bill.
|--------------------------------------------------------------------------
| IMPORTANT:
| This version saves bookings to the configured hotel database.
| It does not verify live room availability.
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/auth_guard.php';
require_once dirname(__DIR__) . '/config/bootstrap.php';

/*
|--------------------------------------------------------------------------
| 1. ACCEPT POST REQUESTS ONLY
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: booking.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| 2. CONFIGURATION
|--------------------------------------------------------------------------
*/

$hotelName = 'T@ke9ine';
$currency = 'KES';

$rooms = [
    'single' => [
        'name' => 'Single Room',
        'rate' => 3500,
        'capacity' => 1,
    ],

    'double' => [
        'name' => 'Double Room',
        'rate' => 5000,
        'capacity' => 4,
    ],

    'executive' => [
        'name' => 'Executive Room',
        'rate' => 7500,
        'capacity' => 4,
    ],

    'extra' => [
        'name' => 'Extra Bed',
        'rate' => 1500,
        'capacity' => 1,
    ],
];

$servicePrices = [
    'breakfast_per_guest_per_day' => 700,
    'airport_transfer_per_day' => 2000,
    'laundry_per_day' => 1000,
];

/*
|--------------------------------------------------------------------------
| 3. HELPER FUNCTIONS
|--------------------------------------------------------------------------
*/

/**
 * Safely escape text before displaying it in HTML.
 */
function escapeHtml(string $value): string
{
    return htmlspecialchars(
        $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

/**
 * Format an amount in Kenyan shillings.
 */
function formatMoney(int $amount): string
{
    return 'KSh ' . number_format($amount);
}

/**
 * Return a trimmed string from submitted form data.
 */
function getPostString(string $key): string
{
    $value = $_POST[$key] ?? '';

    if (!is_string($value)) {
        return '';
    }

    return trim($value);
}

/**
 * Validate a date in YYYY-MM-DD format.
 */
function parseBookingDate(string $value): ?DateTimeImmutable
{
    if (
        !preg_match(
            '/^\d{4}-\d{2}-\d{2}$/',
            $value
        )
    ) {
        return null;
    }

    $date = DateTimeImmutable::createFromFormat(
        '!Y-m-d',
        $value,
        new DateTimeZone('Africa/Nairobi')
    );

    $dateErrors = DateTimeImmutable::getLastErrors();

    if (
        $date === false ||
        (
            $dateErrors !== false &&
            (
                $dateErrors['warning_count'] > 0 ||
                $dateErrors['error_count'] > 0
            )
        ) ||
        $date->format('Y-m-d') !== $value
    ) {
        return null;
    }

    return $date;
}

/**
 * Show an error and a way to return to the booking form.
 */
function showBookingNotice(string $title, string $message, int $statusCode): void
{
    header('Cache-Control: no-store, private');
    http_response_code($statusCode);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= escapeHtml($title) ?> | T@ke9ine</title>
        <link rel="stylesheet" href="css/global.css">
        <style>
            .notice-backdrop { position: fixed; inset: 0; z-index: 1000; display: grid; place-items: center; padding: 20px; background: rgba(15, 23, 42, 0.68); }
            .notice-card { width: min(92vw, 520px); padding: 28px 32px 32px; border: 1px solid #bfdbfe; border-top: 5px solid #2563eb; border-radius: 16px; background: #fff; box-shadow: 0 24px 60px rgba(23, 32, 51, 0.28); text-align: center; }
            .notice-logo { display: flex; align-items: center; justify-content: center; gap: 10px; margin-bottom: 18px; color: #1d4ed8; font-size: 1rem; font-weight: 800; letter-spacing: 0.04em; text-transform: uppercase; }
            .notice-logo svg { width: 38px; height: 38px; padding: 7px; border: 1px solid #bfdbfe; border-radius: 50%; background: #eff6ff; fill: none; stroke: #2563eb; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
            .notice-card h1 { margin: 0 0 10px; color: #1e3a8a; }
            .notice-card p { line-height: 1.8; color: #475569; }
            .notice-actions { display: flex; justify-content: center; gap: 10px; margin-top: 20px; }
            .notice-button { display: inline-block; padding: 13px 22px; border-radius: 8px; background: #2563eb; color: #fff; font-weight: 700; text-decoration: none; transition: background-color 0.25s ease; }
            .notice-button:hover { background: #1d4ed8; }
        </style>
    </head>
    <body>
        <main class="notice-backdrop" role="dialog" aria-modal="true" aria-labelledby="booking-notice-title" aria-describedby="booking-notice-message">
            <section class="notice-card">
                <div class="notice-logo" aria-label="T@ke9ine">
                    <svg viewBox="0 0 64 64" aria-hidden="true" focusable="false">
                        <path d="M12 54V24h40v30M8 54h48M20 24V13h24v11M24 32h5m6 0h5M24 41h5m6 0h5M28 54V45h8v9" />
                    </svg>
                    <span>T@ke9ine</span>
                </div>
                <h1 id="booking-notice-title"><?= escapeHtml($title) ?></h1>
                <p id="booking-notice-message"><?= escapeHtml($message) ?></p>
                <div class="notice-actions">
                    <a class="notice-button" href="booking.php">Back to Booking</a>
                </div>
            </section>
        </main>
    </body>
    </html>
    <?php
    exit;
}

if (getPostString('human_verification') !== '1') {
    showBookingNotice(
        'Human Verification Required',
        'Please check the box to confirm you are human, then submit your booking again.',
        400
    );
}

try {
    $pdo = appDatabase();
} catch (RuntimeException $exception) {
    error_log('Booking database connection failed: ' . $exception->getMessage());
    showBookingNotice(
        'Booking Temporarily Unavailable',
        'We could not save your booking right now. Please try again later.',
        503
    );
}

/*
|--------------------------------------------------------------------------
| 4. COLLECT FORM DATA
|--------------------------------------------------------------------------
*/

$fullName = getPostString('full_name');
$email = getPostString('email');
$phone = getPostString('phone');

$guestsRaw = getPostString('guests');
$checkInRaw = getPostString('check_in');
$checkOutRaw = getPostString('check_out');
$roomType = getPostString('room_type');

$specialRequests = getPostString('special_requests');

$breakfast = isset($_POST['breakfast'])
    && $_POST['breakfast'] === '1';

$airportTransfer = isset($_POST['airport_transfer'])
    && $_POST['airport_transfer'] === '1';

$laundry = isset($_POST['laundry'])
    && $_POST['laundry'] === '1';

$termsAccepted = isset($_POST['terms'])
    && $_POST['terms'] === 'accepted';

/*
|--------------------------------------------------------------------------
| 5. VALIDATE CUSTOMER DETAILS
|--------------------------------------------------------------------------
*/

$errors = [];

// Full name.
if ($fullName === '') {
    $errors[] = 'Please enter your full name.';
} elseif (strlen($fullName) > 100) {
    $errors[] = 'Your name must not exceed 100 characters.';
} elseif (mb_strlen($fullName, 'UTF-8') < 2) {
    $errors[] = 'Your name must contain at least 2 characters.';
}

// Email.
if (
    $email === '' ||
    strlen($email) > 254 ||
    filter_var($email, FILTER_VALIDATE_EMAIL) === false
) {
    $errors[] = 'Please enter a valid email address.';
}

// Phone number.
if (
    $phone === '' ||
    !preg_match('/^[+\d][\d\s()+-]{7,19}$/', $phone)
) {
    $errors[] = 'Please enter a valid phone number.';
}

// Guest count.
$guests = filter_var(
    $guestsRaw,
    FILTER_VALIDATE_INT,
    [
        'options' => [
            'min_range' => 1,
            'max_range' => 4,
        ],
    ]
);

if ($guests === false) {
    $errors[] = 'Please select between 1 and 4 guests.';
}

/*
|--------------------------------------------------------------------------
| 6. VALIDATE ROOM TYPE
|--------------------------------------------------------------------------
*/

if (!array_key_exists($roomType, $rooms)) {
    $errors[] = 'Please select a valid room type.';
} elseif (
    $guests !== false &&
    $guests > $rooms[$roomType]['capacity']
) {
    $errors[] = sprintf(
        'The selected room accommodates a maximum of %d guest(s).',
        $rooms[$roomType]['capacity']
    );
}

/*
|--------------------------------------------------------------------------
| 7. VALIDATE BOOKING DATES
|--------------------------------------------------------------------------
*/

$checkIn = parseBookingDate($checkInRaw);
$checkOut = parseBookingDate($checkOutRaw);

$today = new DateTimeImmutable(
    'today',
    new DateTimeZone('Africa/Nairobi')
);

if ($checkIn === null) {
    $errors[] = 'Please provide a valid check-in date.';
} elseif ($checkIn < $today) {
    $errors[] = 'Check-in cannot be in the past.';
}

if ($checkOut === null) {
    $errors[] = 'Please provide a valid check-out date.';
} elseif (
    $checkIn !== null &&
    $checkOut <= $checkIn
) {
    $errors[] = 'Check-out must be after check-in.';
}

/*
|--------------------------------------------------------------------------
| 8. VALIDATE SPECIAL REQUESTS AND TERMS
|--------------------------------------------------------------------------
*/

if (strlen($specialRequests) > 1000) {
    $errors[] = 'Special requests are too long.';
}

if (!$termsAccepted) {
    $errors[] = 'You must accept the terms and conditions.';
}

/*
|--------------------------------------------------------------------------
| 9. STOP IF VALIDATION FAILS
|--------------------------------------------------------------------------
*/

if (!empty($errors)) {
    http_response_code(422);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta
            name="viewport"
            content="width=device-width, initial-scale=1.0"
        >

        <title>Booking Errors | T@ke9ine</title>

        <link rel="stylesheet" href="css/global.css">

        <style>
            .result-container {
                width: min(92%, 760px);
                margin: 70px auto;
            }

            .result-card {
                padding: 28px 35px 35px;
                border: 1px solid #bfdbfe;
                border-top: 5px solid #2563eb;
                border-radius: 16px;
                background: #fff;
                box-shadow: 0 12px 35px rgba(30, 35, 45, 0.07);
                text-align: center;
            }

            .result-card h1 {
                color: #1e3a8a;
                margin: 0 0 10px;
            }

            .notice-logo {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 10px;
                margin-bottom: 18px;
                color: #1d4ed8;
                font-size: 1rem;
                font-weight: 800;
                letter-spacing: 0.04em;
                text-transform: uppercase;
            }

            .notice-logo svg {
                width: 38px;
                height: 38px;
                padding: 7px;
                border: 1px solid #bfdbfe;
                border-radius: 50%;
                background: #eff6ff;
                fill: none;
                stroke: #2563eb;
                stroke-width: 2;
                stroke-linecap: round;
                stroke-linejoin: round;
            }

            .result-card p,
            .result-card li {
                line-height: 1.8;
                color: #475569;
            }

            .result-card li {
                margin-bottom: 8px;
            }

            .result-button {
                display: inline-block;
                margin-top: 15px;
                padding: 13px 22px;
                border-radius: 8px;
                background: #2563eb;
                color: white;
                text-decoration: none;
                font-weight: 700;
                transition: background-color 0.25s ease;
            }

            .result-button:hover {
                background: #1d4ed8;
            }

            @media (max-width: 480px) {
                .result-card {
                    padding: 23px 18px;
                }
            }
        </style>
    </head>

    <body>
        <main class="result-container">
            <section class="result-card" aria-labelledby="error-title">
                <div class="notice-logo" aria-label="T@ke9ine">
                    <svg viewBox="0 0 64 64" aria-hidden="true" focusable="false">
                        <path d="M12 54V24h40v30M8 54h48M20 24V13h24v11M24 32h5m6 0h5M24 41h5m6 0h5M28 54V45h8v9" />
                    </svg>
                    <span>T@ke9ine</span>
                </div>
                <h1 id="error-title">Please Check Your Booking</h1>

                <p>
                    We could not process your submission because
                    some information needs attention.
                </p>

                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= escapeHtml($error) ?></li>
                    <?php endforeach; ?>
                </ul>

                <a
                    class="result-button"
                    href="booking.php"
                >
                    Return to Booking Form
                </a>
            </section>
        </main>
    </body>
    </html>
    <?php
    exit;
}

/*
|--------------------------------------------------------------------------
| 10. CALCULATE THE LENGTH OF STAY
|--------------------------------------------------------------------------
*/

$interval = $checkIn->diff($checkOut);
$nights = (int) $interval->days;

/*
 * The date validation above ensures checkout is after check-in.
 * Still guard against a zero-night stay before billing.
 */

if ($nights < 1) {
    http_response_code(422);
    exit('Invalid booking duration. Please return to the booking form.');
}

/*
|--------------------------------------------------------------------------
| 11. CALCULATE THE BILL ON THE SERVER
|--------------------------------------------------------------------------
*/

$room = $rooms[$roomType];

$roomTotal = $room['rate'] * $nights;

$breakfastTotal = $breakfast
    ? $servicePrices['breakfast_per_guest_per_day'] * $guests * $nights
    : 0;

$airportTransferTotal = $airportTransfer
    ? $servicePrices['airport_transfer_per_day'] * $nights
    : 0;

$laundryTotal = $laundry
    ? $servicePrices['laundry_per_day'] * $nights
    : 0;

$grandTotal =
    $roomTotal +
    $breakfastTotal +
    $airportTransferTotal +
    $laundryTotal;

/*
|--------------------------------------------------------------------------
| 12. SAVE THE BOOKING AND GENERATE A REFERENCE
|--------------------------------------------------------------------------
|
| The reference identifies the saved booking record.
|
*/

$bookingReference = 'SUN-' .
    strtoupper(bin2hex(random_bytes(4)));

try {
    $userId = isset($_SESSION['user_id'])
        ? filter_var($_SESSION['user_id'], FILTER_VALIDATE_INT)
        : false;

    if ($userId === false) {
        $userId = null;
    }

    $pdo->beginTransaction();

    $roomStmt = $pdo->prepare(
        'SELECT room_id
         FROM rooms
         WHERE room_type = ?
           AND capacity >= ?
           AND status <> \'maintenance\'
         ORDER BY room_number
         FOR UPDATE'
    );
    $roomStmt->execute([$roomType, $guests]);
    $availableRooms = $roomStmt->fetchAll();
    $roomId = null;
    $overlapStmt = $pdo->prepare(
        'SELECT COUNT(*)
         FROM bookings
         WHERE room_id = ?
           AND status NOT IN (\'cancelled\', \'completed\')
           AND check_in < ?
           AND check_out > ?'
    );

    foreach ($availableRooms as $availableRoom) {
        $candidateRoomId = (int) $availableRoom['room_id'];
        $overlapStmt->execute([
            $candidateRoomId,
            $checkOut->format('Y-m-d'),
            $checkIn->format('Y-m-d'),
        ]);

        if ((int) $overlapStmt->fetchColumn() === 0) {
            $roomId = $candidateRoomId;
            break;
        }
    }

    if ($roomId === null) {
        $pdo->rollBack();

        $availabilityStmt = $pdo->prepare(
            'SELECT MAX(b.check_out) AS available_from
             FROM rooms r
             LEFT JOIN bookings b
               ON b.room_id = r.room_id
              AND b.status NOT IN (\'cancelled\', \'completed\')
              AND b.check_out >= CURDATE()
             WHERE r.room_type = ?
               AND r.capacity >= ?'
        );
        $availabilityStmt->execute([$roomType, $guests]);
        $latestStay = $availabilityStmt->fetch();
        $availableFrom = null;

        if (!empty($latestStay['available_from'])) {
            $availableFromDate = new DateTimeImmutable(
                (string) $latestStay['available_from'],
                new DateTimeZone('UTC')
            );
            $availableFrom = $availableFromDate
                ->modify('+1 day')
                ->format('l, F j, Y');
        } else {
            $availableFrom = (new DateTimeImmutable('today'))
                ->format('l, F j, Y');
        }

        showBookingNotice(
            'Room Not Available',
            'This room is already booked for the selected dates. ' .
            'The room type is available again on ' . $availableFrom . '. ' .
            'Please go back to booking and select another room or different dates.',
            409
        );
    }

    $bookingStmt = $pdo->prepare(
        'INSERT INTO bookings (
            booking_reference,
            user_id,
            room_id,
            full_name,
            email,
            phone,
            guests,
            check_in,
            check_out,
            room_type,
            breakfast,
            airport_transfer,
            laundry,
            special_requests,
            room_total,
            services_total,
            grand_total,
            status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );

    $bookingStmt->execute([
        $bookingReference,
        $userId,
        $roomId,
        $fullName,
        $email,
        $phone,
        $guests,
        $checkIn->format('Y-m-d'),
        $checkOut->format('Y-m-d'),
        $roomType,
        $breakfast ? 1 : 0,
        $airportTransfer ? 1 : 0,
        $laundry ? 1 : 0,
        $specialRequests !== '' ? $specialRequests : null,
        $roomTotal,
        $breakfastTotal + $airportTransferTotal + $laundryTotal,
        $grandTotal,
        'pending',
    ]);

    $pdo->prepare(
        'UPDATE rooms SET status = \'booked\' WHERE room_id = ?'
    )->execute([$roomId]);

    $pdo->commit();
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(503);
    exit(
        'Your booking could not be saved because the hotel database ' .
        'is unavailable. Please try again shortly.'
    );
}

$services = [];

if ($breakfast) {
    $services[] = [
        'name' => 'Breakfast',
        'description' => sprintf(
            '%s guest(s) x %s day(s)',
            $guests,
            $nights
        ),
        'amount' => $breakfastTotal,
    ];
}

if ($airportTransfer) {
    $services[] = [
        'name' => 'Airport Transfer',
        'description' => sprintf('%s day(s)', $nights),
        'amount' => $airportTransferTotal,
    ];
}

if ($laundry) {
    $services[] = [
        'name' => 'Laundry',
        'description' => sprintf('%s day(s)', $nights),
        'amount' => $laundryTotal,
    ];
}

/*
|--------------------------------------------------------------------------
| 13. DISPLAY BOOKING CONFIRMATION
|--------------------------------------------------------------------------
|
| This page confirms receipt of the submitted details only.
| It does not mean that a room has been reserved or payment
| has been received.
|
*/

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="description"
        content="Review your T@ke9ine booking request and estimated bill."
    >
  
    <title>Booking Summary | T@ke9ine</title>

    <link rel="stylesheet" href="css/global.css">

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap"
        rel="stylesheet"
    >

    <style>
        :root {
            --confirmation-primary: #2563eb;
            --confirmation-primary-dark: #1d4ed8;
            --confirmation-background: #ffffff;
            --confirmation-card: #ffffff;
            --confirmation-text: #292b32;
            --confirmation-muted: #777b83;
            --confirmation-border: #dbe3ef;
        }

        body {
            background: var(--confirmation-background);
            color: var(--confirmation-text);
        }

        .confirmation-container {
            width: min(94%, 900px);
            margin: 55px auto 75px;
        }

        .confirmation-header {
            margin-bottom: 28px;
            text-align: center;
        }

        .confirmation-icon {
            display: flex;
            width: 70px;
            height: 70px;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            border-radius: 50%;
            background: #e4f4e9;
            color: #247a4b;
            font-size: 2rem;
        }

        .confirmation-header h1 {
            margin: 0 0 12px;
            font-family: "Playfair Display", Georgia, serif;
            font-size: clamp(2rem, 5vw, 3rem);
        }

        .confirmation-header p {
            max-width: 650px;
            margin: 0 auto;
            color: var(--confirmation-muted);
            line-height: 1.8;
        }

        .confirmation-reference {
            display: inline-block;
            margin-top: 18px;
            padding: 10px 16px;
            border: 1px solid var(--confirmation-border);
            border-radius: 8px;
            background: var(--confirmation-card);
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .confirmation-card {
            margin-bottom: 24px;
            padding: 30px;
            border: 1px solid var(--confirmation-border);
            border-radius: 16px;
            background: var(--confirmation-card);
            box-shadow: 0 12px 35px rgba(30, 35, 45, 0.06);
        }

        .confirmation-card h2 {
            margin: 0 0 22px;
            font-size: 1.25rem;
        }

        .customer-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 22px;
        }

        .detail-label {
            display: block;
            margin-bottom: 6px;
            color: var(--confirmation-muted);
            font-size: 0.83rem;
        }

        .detail-value {
            display: block;
            overflow-wrap: anywhere;
            font-weight: 600;
            line-height: 1.7;
        }

        .bill-table-wrapper {
            overflow-x: auto;
        }

        .bill-table {
            width: 100%;
            border-collapse: collapse;
        }

        .bill-table th,
        .bill-table td {
            padding: 15px 10px;
            border-bottom: 1px solid var(--confirmation-border);
            text-align: left;
            vertical-align: top;
        }

        .bill-table th {
            color: var(--confirmation-muted);
            font-size: 0.8rem;
            font-weight: 600;
        }

        .bill-table td:last-child,
        .bill-table th:last-child {
            text-align: right;
            white-space: nowrap;
        }

        .bill-description {
            display: block;
            margin-top: 5px;
            color: var(--confirmation-muted);
            font-size: 0.82rem;
            line-height: 1.6;
        }

        .bill-total {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            margin-top: 22px;
            padding-top: 20px;
            border-top: 2px solid var(--confirmation-border);
        }

        .bill-total span {
            font-size: 1.05rem;
            font-weight: 700;
        }

        .bill-total strong {
            color: var(--confirmation-primary-dark);
            font-size: clamp(1.35rem, 3vw, 1.8rem);
            overflow-wrap: anywhere;
            text-align: right;
        }

        .notice {
            margin-top: 20px;
            padding: 16px;
            border-left: 4px solid var(--confirmation-primary);
            border-radius: 6px;
            background: #eff6ff;
            color: #1e3a8a;
            font-size: 0.9rem;
            line-height: 1.8;
        }

        .special-requests {
            margin: 0;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
            line-height: 1.8;
        }

        .confirmation-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 14px;
            margin-top: 30px;
        }

        .confirmation-button {
            display: inline-flex;
            min-height: 48px;
            align-items: center;
            justify-content: center;
            padding: 13px 22px;
            border: 1px solid var(--confirmation-primary);
            border-radius: 8px;
            background: var(--confirmation-primary);
            color: #ffffff;
            font: inherit;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }

        .confirmation-button:hover {
            background: var(--confirmation-primary-dark);
            border-color: var(--confirmation-primary-dark);
        }

        .confirmation-button.secondary {
            background: transparent;
            color: var(--confirmation-primary-dark);
        }

        .confirmation-button.secondary:hover {
            background: #eff6ff;
        }

        @media print {
            body {
                background: #ffffff;
                color: #000000;
            }

            .confirmation-container {
                width: 100%;
                margin: 0;
            }

            .confirmation-card {
                box-shadow: none;
                break-inside: avoid;
            }

            .confirmation-actions {
                display: none;
            }

            .notice {
                background: #ffffff;
                color: #000000;
            }
        }

        @media (max-width: 600px) {
            .confirmation-container {
                margin: 35px auto 45px;
            }

            .confirmation-card {
                padding: 22px 17px;
            }

            .customer-grid {
                grid-template-columns: 1fr;
                gap: 17px;
            }

            .bill-table th,
            .bill-table td {
                padding: 12px 7px;
                font-size: 0.85rem;
            }

            .confirmation-actions {
                flex-direction: column;
            }

            .confirmation-button {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <main class="confirmation-container">

        <header class="confirmation-header">
            <div class="confirmation-icon" aria-hidden="true">
                &#10003;
            </div>

            <h1>Thank You, <?= escapeHtml($fullName) ?>!</h1>

            <p>
                Your booking details have been received by this
                demonstration system. Please review your stay
                information and estimated bill below.
            </p>

            <div class="confirmation-reference">
                Reference: <?= escapeHtml($bookingReference) ?>
            </div>
        </header>

        <section
            class="confirmation-card"
            aria-labelledby="customer-details-heading"
        >
            <h2 id="customer-details-heading">
                Customer and Stay Details
            </h2>

            <div class="customer-grid">
                <div>
                    <span class="detail-label">Full Name</span>
                    <span class="detail-value">
                        <?= escapeHtml($fullName) ?>
                    </span>
                </div>

                <div>
                    <span class="detail-label">Email Address</span>
                    <span class="detail-value">
                        <?= escapeHtml($email) ?>
                    </span>
                </div>

                <div>
                    <span class="detail-label">Phone Number</span>
                    <span class="detail-value">
                        <?= escapeHtml($phone) ?>
                    </span>
                </div>

                <div>
                    <span class="detail-label">Room Type</span>
                    <span class="detail-value">
                        <?= escapeHtml($room['name']) ?>
                    </span>
                </div>

                <div>
                    <span class="detail-label">Check-in</span>
                    <span class="detail-value">
                        <?= escapeHtml($checkIn->format('d M Y')) ?>
                    </span>
                </div>

                <div>
                    <span class="detail-label">Check-out</span>
                    <span class="detail-value">
                        <?= escapeHtml($checkOut->format('d M Y')) ?>
                    </span>
                </div>

                <div>
                    <span class="detail-label">Number of Guests</span>
                    <span class="detail-value">
                        <?= (int) $guests ?>
                    </span>
                </div>

                <div>
                    <span class="detail-label">Length of Stay</span>
                    <span class="detail-value">
                        <?= (int) $nights ?>
                        <?= $nights === 1 ? 'night' : 'nights' ?>
                    </span>
                </div>
            </div>
        </section>

        <section
            class="confirmation-card"
            aria-labelledby="bill-heading"
        >
            <h2 id="bill-heading">Itemised Bill</h2>

            <div class="bill-table-wrapper">
                <table class="bill-table">
                    <thead>
                        <tr>
                            <th scope="col">Description</th>
                            <th scope="col">Amount</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr>
                            <td>
                                <?= escapeHtml($room['name']) ?>

                                <span class="bill-description">
                                    <?= formatMoney($room['rate']) ?>
                                    per night x <?= (int) $nights ?>
                                    <?= $nights === 1 ? 'night' : 'nights' ?>
                                </span>
                            </td>

                            <td><?= formatMoney($roomTotal) ?></td>
                        </tr>

                        <?php if ($breakfast): ?>
                            <tr>
                                <td>
                                    Breakfast

                                    <span class="bill-description">
                                        <?= formatMoney($servicePrices['breakfast_per_guest_per_day']) ?>
                                        per guest per day x
                                        <?= (int) $guests ?> guest(s) x
                                        <?= (int) $nights ?> day(s)
                                    </span>
                                </td>

                                <td>
                                    <?= formatMoney($breakfastTotal) ?>
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php if ($airportTransfer): ?>
                            <tr>
                                <td>
                                    Airport Transfer

                                    <span class="bill-description">
                                        <?= formatMoney($servicePrices['airport_transfer_per_day']) ?>
                                        per day x <?= (int) $nights ?> day(s)
                                    </span>
                                </td>

                                <td>
                                    <?= formatMoney($airportTransferTotal) ?>
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php if ($laundry): ?>
                            <tr>
                                <td>
                                    Laundry

                                    <span class="bill-description">
                                        <?= formatMoney($servicePrices['laundry_per_day']) ?>
                                        per day x <?= (int) $nights ?> day(s)
                                    </span>
                                </td>

                                <td>
                                    <?= formatMoney($laundryTotal) ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="bill-total">
                <span>Estimated Total</span>

                <strong>
                    <?= formatMoney($grandTotal) ?>
                </strong>
            </div>

            <div class="notice">
                <strong>Booking status: Request received - not yet confirmed.</strong>
                <br>
                This booking is saved in our database, but live room availability
                is not checked automatically and no payment is processed.
                Contact the hotel to confirm availability and the final amount payable.
            </div>
        </section>

        <?php if ($specialRequests !== ''): ?>
            <section
                class="confirmation-card"
                aria-labelledby="requests-heading"
            >
                <h2 id="requests-heading">Special Requests</h2>

                <p class="special-requests"><?= escapeHtml($specialRequests) ?></p>
            </section>
        <?php endif; ?>

        <nav
            class="confirmation-actions"
            aria-label="Confirmation actions"
        >
            <a
                href="index.php"
                class="confirmation-button"
            >
                Return to Homepage
            </a>

            <a
                href="booking.php"
                class="confirmation-button secondary"
            >
                Make Another Booking
            </a>

            <button
                type="button"
                class="confirmation-button secondary"
                onclick="window.print()"
            >
                Print Bill
            </button>
        </nav>

    </main>
</body>
</html>
