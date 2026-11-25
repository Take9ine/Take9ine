<?php

declare(strict_types=1);

require_once __DIR__ . '/guard.php';
require_once dirname(__DIR__) . '/config/bootstrap.php';

header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');

$bookings = [];
$users = [];
$rooms = [];
$admins = [];
$staff = [];
$databaseError = false;
$flashMessage = '';
$flashType = 'success';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (isset($_SESSION['admin_flash'])) {
    $flashMessage = (string) $_SESSION['admin_flash']['message'];
    $flashType = (string) ($_SESSION['admin_flash']['type'] ?? 'success');
    unset($_SESSION['admin_flash']);
}

function escapeHtml(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function validCsrfToken(array $session, array $post): bool
{
    return isset($session['csrf_token'], $post['csrf_token'])
        && is_string($post['csrf_token'])
        && hash_equals($session['csrf_token'], $post['csrf_token']);
}

function syncRoomAvailability(PDO $pdo, int $roomId): void
{
    $pdo->prepare(
        'UPDATE rooms
         SET status = CASE
             WHEN status = \'maintenance\' THEN \'maintenance\'
             WHEN EXISTS (
                 SELECT 1
                 FROM bookings
                 WHERE room_id = rooms.room_id
                   AND status NOT IN (\'cancelled\', \'completed\')
                   AND check_out > CURDATE()
             ) THEN \'booked\'
             ELSE \'available\'
         END
         WHERE room_id = ?'
    )->execute([$roomId]);
}

try {
    $pdo = appDatabase();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!validCsrfToken($_SESSION, $_POST)) {
            throw new InvalidArgumentException('The form session expired. Refresh and try again.');
        }

        $entity = (string) ($_POST['entity'] ?? '');
        $action = (string) ($_POST['action'] ?? '');
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($action === 'delete') {
            if ($id === false) {
                throw new InvalidArgumentException('Invalid record selected.');
            }
            if ($entity === 'booking') {
                $pdo->beginTransaction();
                $roomStmt = $pdo->prepare('SELECT room_id FROM bookings WHERE id = ?');
                $roomStmt->execute([$id]);
                $deletedRoomId = filter_var($roomStmt->fetchColumn(), FILTER_VALIDATE_INT);
                $pdo->prepare('DELETE FROM bookings WHERE id = ?')->execute([$id]);
                if ($deletedRoomId !== false) {
                    syncRoomAvailability($pdo, (int) $deletedRoomId);
                }
                $pdo->commit();
            } elseif ($entity === 'room') {
                $pdo->prepare('DELETE FROM rooms WHERE room_id = ?')->execute([$id]);
            } elseif ($entity === 'user') {
                $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
            } elseif ($entity === 'staff') {
                $pdo->prepare('DELETE FROM staff WHERE staff_id = ?')->execute([$id]);
            } elseif ($entity === 'admin') {
                if ($id === (int) $_SESSION['admin_id']) {
                    throw new InvalidArgumentException('You cannot delete the currently signed-in administrator.');
                }
                $pdo->prepare('DELETE FROM admins WHERE id = ?')->execute([$id]);
            } else {
                throw new InvalidArgumentException('Invalid delete request.');
            }
        } elseif (in_array($action, ['create', 'update'], true)) {
            if ($action === 'update' && $id === false) {
                throw new InvalidArgumentException('Invalid record selected.');
            }

            if ($entity === 'room') {
                $roomNumber = trim((string) ($_POST['room_number'] ?? ''));
                $roomType = trim((string) ($_POST['room_type'] ?? ''));
                $description = trim((string) ($_POST['description'] ?? ''));
                $price = filter_var($_POST['price_per_night'] ?? null, FILTER_VALIDATE_FLOAT);
                $capacity = filter_var($_POST['capacity'] ?? null, FILTER_VALIDATE_INT);
                $status = (string) ($_POST['status'] ?? '');
                $imagePath = trim((string) ($_POST['image_path'] ?? ''));
                if ($roomNumber === '' || !in_array($roomType, ['single', 'double', 'executive', 'extra'], true)
                    || $price === false || $price < 0 || $capacity === false || $capacity < 1
                    || !in_array($status, ['available', 'booked', 'maintenance'], true)) {
                    throw new InvalidArgumentException('Enter valid room details.');
                }
                if ($action === 'create') {
                    $stmt = $pdo->prepare('INSERT INTO rooms (room_number, room_type, description, price_per_night, capacity, status, image_path) VALUES (?, ?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$roomNumber, $roomType, $description ?: null, $price, $capacity, $status, $imagePath ?: null]);
                } else {
                    $stmt = $pdo->prepare('UPDATE rooms SET room_number=?, room_type=?, description=?, price_per_night=?, capacity=?, status=?, image_path=? WHERE room_id=?');
                    $stmt->execute([$roomNumber, $roomType, $description ?: null, $price, $capacity, $status, $imagePath ?: null, $id]);
                    syncRoomAvailability($pdo, (int) $id);
                }
            } elseif ($entity === 'staff') {
                $name = trim((string) ($_POST['name'] ?? ''));
                $department = trim((string) ($_POST['department'] ?? ''));
                $dutyRoles = trim((string) ($_POST['duty_roles'] ?? ''));
                if ($name === '' || $department === '' || $dutyRoles === '') {
                    throw new InvalidArgumentException('Enter the staff name, department, and duty roles.');
                }
                if ($action === 'create') {
                    $stmt = $pdo->prepare('INSERT INTO staff (name, department, duty_roles) VALUES (?, ?, ?)');
                    $stmt->execute([$name, $department, $dutyRoles]);
                } else {
                    $stmt = $pdo->prepare('UPDATE staff SET name=?, department=?, duty_roles=? WHERE staff_id=?');
                    $stmt->execute([$name, $department, $dutyRoles, $id]);
                }
            } elseif ($entity === 'booking') {
                if ($action === 'create') {
                    throw new InvalidArgumentException('Bookings should be created from the website booking form.');
                }
                $fullName = trim((string) ($_POST['full_name'] ?? ''));
                $email = trim((string) ($_POST['email'] ?? ''));
                $phone = trim((string) ($_POST['phone'] ?? ''));
                $guests = filter_var($_POST['guests'] ?? null, FILTER_VALIDATE_INT);
                $checkIn = (string) ($_POST['check_in'] ?? '');
                $checkOut = (string) ($_POST['check_out'] ?? '');
                $roomType = (string) ($_POST['room_type'] ?? '');
                $status = (string) ($_POST['status'] ?? 'pending');
                $specialRequests = trim((string) ($_POST['special_requests'] ?? ''));
                $roomId = filter_var($_POST['room_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                $roomTotal = filter_var($_POST['room_total'] ?? null, FILTER_VALIDATE_INT);
                $servicesTotal = filter_var($_POST['services_total'] ?? null, FILTER_VALIDATE_INT);
                $grandTotal = filter_var($_POST['grand_total'] ?? null, FILTER_VALIDATE_INT);
                if ($fullName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)
                    || $guests === false || $guests < 1
                    || $roomId === false
                    || $roomTotal === false || $servicesTotal === false || $grandTotal === false
                    || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $checkIn)
                    || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $checkOut)
                    || strlen($specialRequests) > 1000
                    || $checkOut <= $checkIn) {
                    throw new InvalidArgumentException('Enter valid booking details.');
                }
                $pdo->beginTransaction();
                $roomStmt = $pdo->prepare('SELECT room_id, room_type, capacity, status FROM rooms WHERE room_id=? FOR UPDATE');
                $roomStmt->execute([$roomId]);
                $selectedRoom = $roomStmt->fetch();
                if (!$selectedRoom || $selectedRoom['status'] === 'maintenance' || (int) $selectedRoom['capacity'] < $guests) {
                    $pdo->rollBack();
                    throw new InvalidArgumentException('The selected room is unavailable or cannot accommodate this booking.');
                }
                $overlapStmt = $pdo->prepare(
                    'SELECT COUNT(*) FROM bookings
                     WHERE room_id=? AND id<>? AND status NOT IN (\'cancelled\', \'completed\')
                       AND check_in < ? AND check_out > ?'
                );
                $overlapStmt->execute([$roomId, $id, $checkOut, $checkIn]);
                if ((int) $overlapStmt->fetchColumn() > 0) {
                    $pdo->rollBack();
                    throw new InvalidArgumentException('This room already has a booking overlapping the selected dates.');
                }
                $oldStmt = $pdo->prepare('SELECT room_id FROM bookings WHERE id=?');
                $oldStmt->execute([$id]);
                $oldRoomId = filter_var($oldStmt->fetchColumn(), FILTER_VALIDATE_INT);
                $stmt = $pdo->prepare('UPDATE bookings SET room_id=?, full_name=?, email=?, phone=?, guests=?, check_in=?, check_out=?, room_type=?, special_requests=?, status=?, room_total=?, services_total=?, grand_total=? WHERE id=?');
                $stmt->execute([$roomId, $fullName, $email, $phone, $guests, $checkIn, $checkOut, $roomType, $specialRequests, $status, $roomTotal, $servicesTotal, $grandTotal, $id]);
                if ($status === 'cancelled' || $status === 'completed') {
                    syncRoomAvailability($pdo, (int) $roomId);
                } else {
                    $pdo->prepare('UPDATE rooms SET status=\'booked\' WHERE room_id=?')->execute([$roomId]);
                }
                if ($oldRoomId !== false && (int) $oldRoomId !== (int) $roomId) {
                    syncRoomAvailability($pdo, (int) $oldRoomId);
                }
                $pdo->commit();
            } elseif ($entity === 'user') {
                $fullName = trim((string) ($_POST['full_name'] ?? ''));
                $email = trim((string) ($_POST['email'] ?? ''));
                $phone = trim((string) ($_POST['phone'] ?? ''));
                $role = (string) ($_POST['role'] ?? 'guest');
                $password = (string) ($_POST['password'] ?? '');
                $isVerified = isset($_POST['is_verified']) ? 1 : 0;
                if ($fullName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)
                    || !in_array($role, ['admin', 'staff', 'guest'], true)) {
                    throw new InvalidArgumentException('Enter valid user details.');
                }
                if ($action === 'create') {
                    if (strlen($password) < 12) {
                        throw new InvalidArgumentException('Password must be at least 12 characters.');
                    }
                    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                    $stmt = $pdo->prepare('INSERT INTO users (full_name, email, phone, password_hash, role, is_verified) VALUES (?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$fullName, $email, $phone, $hash, $role, $isVerified]);
                } elseif ($password !== '') {
                    if (strlen($password) < 12) {
                        throw new InvalidArgumentException('Password must be at least 12 characters.');
                    }
                    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                    $stmt = $pdo->prepare('UPDATE users SET full_name=?, email=?, phone=?, password_hash=?, role=?, is_verified=? WHERE id=?');
                    $stmt->execute([$fullName, $email, $phone, $hash, $role, $isVerified, $id]);
                } else {
                    $stmt = $pdo->prepare('UPDATE users SET full_name=?, email=?, phone=?, role=?, is_verified=? WHERE id=?');
                    $stmt->execute([$fullName, $email, $phone, $role, $isVerified, $id]);
                }
            } elseif ($entity === 'admin') {
                $username = strtolower(trim((string) ($_POST['username'] ?? '')));
                $email = trim((string) ($_POST['email'] ?? ''));
                $role = (string) ($_POST['role'] ?? 'admin');
                $password = (string) ($_POST['password'] ?? '');
                if (
                    !preg_match('/^[a-z0-9][a-z0-9_-]{2,49}$/', $username) ||
                    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
                    !in_array($role, ['admin', 'staff'], true)
                ) {
                    throw new InvalidArgumentException('Enter a valid username, email, and role.');
                }
                if ($action === 'create') {
                    if (strlen($password) < 12) {
                        throw new InvalidArgumentException('Password must be at least 12 characters.');
                    }
                    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                    $stmt = $pdo->prepare('INSERT INTO admins (username, email, password_hash, role) VALUES (?, ?, ?, ?)');
                    $stmt->execute([$username, $email, $hash, $role]);
                } elseif ($password !== '') {
                    if (strlen($password) < 12) {
                        throw new InvalidArgumentException('Password must be at least 12 characters.');
                    }
                    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                    $stmt = $pdo->prepare('UPDATE admins SET username=?, email=?, password_hash=?, role=? WHERE id=?');
                    $stmt->execute([$username, $email, $hash, $role, $id]);
                } else {
                    $stmt = $pdo->prepare('UPDATE admins SET username=?, email=?, role=? WHERE id=?');
                    $stmt->execute([$username, $email, $role, $id]);
                }
                if ($action === 'update' && $id === (int) $_SESSION['admin_id']) {
                    $_SESSION['admin_email'] = $email;
                    $_SESSION['admin_username'] = $username;
                    $_SESSION['admin_role'] = $role;
                }
            } else {
                throw new InvalidArgumentException('Invalid update request.');
            }
        } else {
            throw new InvalidArgumentException('Invalid action.');
        }

        if ($entity === 'staff') {
            $staffMessages = [
                'create' => 'Staff member added successfully.',
                'update' => 'Staff member updated successfully.',
                'delete' => 'Staff member removed successfully.',
            ];
            $_SESSION['admin_flash'] = [
                'message' => $staffMessages[$action] ?? 'Staff list synchronized successfully.',
                'type' => 'success',
            ];
        }

        header('Location: admin_dashboard.php');
        exit;
    }

    $bookings = $pdo->query('SELECT * FROM bookings ORDER BY created_at DESC, id DESC')->fetchAll();
    $users = $pdo->query('SELECT id, full_name, email, phone, role, is_verified, created_at FROM users ORDER BY id DESC')->fetchAll();
    $rooms = $pdo->query('SELECT * FROM rooms ORDER BY CAST(room_number AS UNSIGNED), room_number')->fetchAll();
    $admins = $pdo->query('SELECT id, username, email, role, created_at, updated_at FROM admins ORDER BY id')->fetchAll();
    $staff = $pdo->query('SELECT staff_id, name, department, duty_roles, created_at, updated_at FROM staff ORDER BY name')->fetchAll();
} catch (InvalidArgumentException $exception) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $flashMessage = $exception->getMessage();
    $flashType = 'error';
} catch (RuntimeException $exception) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Admin dashboard database error: ' . $exception->getMessage());
    $databaseError = true;
    $flashMessage = 'Database operation could not be completed.';
    $flashType = 'error';
}

$totalGuests = 0;
$totalRevenue = 0;
foreach ($bookings as $booking) {
    $totalGuests += (int) $booking['guests'];
    $totalRevenue += (int) $booking['grand_total'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | T@ke9ine</title>
    <link rel="stylesheet" href="css/admin_dashboard.css">
    <script src="js/global.js" defer></script>
    <script src="js/admin_dashboard.js" defer></script>
</head>
<body>
<main class="dashboard">
    <header class="dashboard-header">
        <div>
            <p class="eyebrow">T@ke9ine</p>
            <h1>Website administration</h1>
            <p class="signed-in">Signed in as <strong><?= escapeHtml((string) ($_SESSION['admin_username'] ?? $_SESSION['admin_email'])) ?></strong></p>
        </div>
        <div class="header-actions">
            <button class="theme-toggle" id="theme-toggle" type="button" aria-label="Switch to dark mode" aria-pressed="false">
                <span class="theme-icon" aria-hidden="true">☾</span>
                <span class="theme-label">Dark mode</span>
            </button>
            <form action="admin_logout.php" method="post">
                <button class="sign-out-button" type="submit">Sign out</button>
            </form>
        </div>
    </header>

    <?php if ($flashMessage !== ''): ?>
        <p class="flash <?= $flashType ?>" role="alert"><?= escapeHtml($flashMessage) ?></p>
    <?php endif; ?>

    <?php if ($databaseError): ?>
        <p class="dashboard-message dashboard-error" role="alert">Database data could not be loaded.</p>
    <?php else: ?>
        <div class="dashboard-search">
            <label for="dashboard-search-input">Search all dashboard data</label>
            <div class="search-control">
                <span class="search-icon" aria-hidden="true">⌕</span>
                <input
                    id="dashboard-search-input"
                    type="search"
                    autocomplete="off"
                    placeholder="Search bookings, guests, rooms, staff, users, and administrators"
                    aria-describedby="dashboard-search-status"
                >
                <button id="dashboard-search-clear" type="button" hidden>Clear</button>
            </div>
            <p id="dashboard-search-status" class="search-status" role="status" aria-live="polite">
                Search across all records currently loaded from the database.
            </p>
        </div>
        <section class="summary" aria-label="Website summary">
            <article class="summary-card"><span class="summary-label">Reservations</span><strong><?= count($bookings) ?></strong></article>
            <article class="summary-card"><span class="summary-label">Website users</span><strong><?= count($users) ?></strong></article>
            <article class="summary-card"><span class="summary-label">Rooms</span><strong><?= count($rooms) ?></strong></article>
            <article class="summary-card"><span class="summary-label">Staff</span><strong><?= count($staff) ?></strong></article>
            <article class="summary-card"><span class="summary-label">Administrators</span><strong><?= count($admins) ?></strong></article>
            <article class="summary-card"><span class="summary-label">Guests</span><strong><?= $totalGuests ?></strong></article>
            <article class="summary-card"><span class="summary-label">Grand total</span><strong>KES <?= number_format($totalRevenue) ?></strong></article>
        </section>

        <nav class="admin-tabs" aria-label="Data sections">
            <a href="#bookings">Bookings</a>
            <a href="#rooms">Rooms</a>
            <a href="#staff">Staff</a>
            <a href="#users">Users</a>
            <a href="#admins">Administrators</a>
        </nav>

        <section class="data-panel" id="bookings">
            <div class="panel-heading"><div><p class="eyebrow">Guest bookings</p><h2>Reservations</h2></div><span class="count"><?= count($bookings) ?></span></div>
            <div class="table-scroll">
                <table>
                    <thead><tr><th>Reference</th><th>Guest</th><th>Email</th><th>Phone</th><th>Room type</th><th>Room number</th><th>Guests</th><th>Dates</th><th>Special requests</th><th>Status</th><th>Grand total</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($bookings as $booking): ?>
                        <tr>
                            <td><strong><?= escapeHtml((string) $booking['booking_reference']) ?></strong></td>
                            <td><?= escapeHtml((string) $booking['full_name']) ?></td>
                            <td><?= escapeHtml((string) $booking['email']) ?></td>
                            <td><?= escapeHtml((string) $booking['phone']) ?></td>
                            <td><?= escapeHtml((string) $booking['room_type']) ?></td>
                            <td>
                                <?php
                                $bookingRoomNumber = '-';
                                foreach ($rooms as $room) {
                                    if ((int) $room['room_id'] === (int) ($booking['room_id'] ?? 0)) {
                                        $bookingRoomNumber = (string) $room['room_number'];
                                        break;
                                    }
                                }
                                ?>
                                <?= escapeHtml($bookingRoomNumber) ?>
                            </td>
                            <td><?= (int) $booking['guests'] ?></td>
                            <td><?= escapeHtml((string) $booking['check_in']) ?> to <?= escapeHtml((string) $booking['check_out']) ?></td>
                            <td class="special-requests">
                                <?= trim((string) ($booking['special_requests'] ?? '')) !== ''
                                    ? escapeHtml((string) $booking['special_requests'])
                                    : '<span class="empty-value">None listed</span>' ?>
                            </td>
                            <td><?= escapeHtml((string) $booking['status']) ?></td>
                            <td>KES <?= number_format((int) $booking['grand_total']) ?></td>
                            <td>
                                <details class="edit-row">
                                    <summary>Edit</summary>
                                    <form method="post" class="inline-form">
                                        <input type="hidden" name="csrf_token" value="<?= escapeHtml($_SESSION['csrf_token']) ?>">
                                        <input type="hidden" name="entity" value="booking"><input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?= (int) $booking['id'] ?>">
                                        <label>Full name<input name="full_name" value="<?= escapeHtml((string) $booking['full_name']) ?>" required></label>
                                        <label>Email<input type="email" name="email" value="<?= escapeHtml((string) $booking['email']) ?>" required></label>
                                        <label>Phone<input name="phone" value="<?= escapeHtml((string) $booking['phone']) ?>"></label>
                                        <label>Guests<input type="number" name="guests" min="1" value="<?= (int) $booking['guests'] ?>" required></label>
                                        <label>Check in<input type="date" name="check_in" value="<?= escapeHtml((string) $booking['check_in']) ?>" required></label>
                                        <label>Check out<input type="date" name="check_out" value="<?= escapeHtml((string) $booking['check_out']) ?>" required></label>
                                        <label>Room type<select name="room_type"><option <?= $booking['room_type'] === 'single' ? 'selected' : '' ?>>single</option><option <?= $booking['room_type'] === 'double' ? 'selected' : '' ?>>double</option><option <?= $booking['room_type'] === 'executive' ? 'selected' : '' ?>>executive</option><option <?= $booking['room_type'] === 'extra' ? 'selected' : '' ?>>extra</option></select></label>
                                        <label>Assigned room<select name="room_id" required>
                                            <?php foreach ($rooms as $room): ?>
                                                <?php if ((int) $room['room_id'] === (int) ($booking['room_id'] ?? 0) || $room['status'] === 'available'): ?>
                                                    <option value="<?= (int) $room['room_id'] ?>" <?= (int) $room['room_id'] === (int) ($booking['room_id'] ?? 0) ? 'selected' : '' ?>>
                                                        <?= escapeHtml((string) $room['room_number']) ?> - <?= escapeHtml((string) $room['room_type']) ?> (<?= escapeHtml((string) $room['status']) ?>)
                                                    </option>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </select></label>
                                        <label>Special requests<textarea name="special_requests" maxlength="1000" rows="4"><?= escapeHtml((string) ($booking['special_requests'] ?? '')) ?></textarea></label>
                                        <label>Status<input name="status" value="<?= escapeHtml((string) $booking['status']) ?>" required></label>
                                        <label>Room total<input type="number" name="room_total" min="0" value="<?= (int) $booking['room_total'] ?>" required></label>
                                        <label>Services total<input type="number" name="services_total" min="0" value="<?= (int) $booking['services_total'] ?>" required></label>
                                        <label>Grand total<input type="number" name="grand_total" min="0" value="<?= (int) $booking['grand_total'] ?>" required></label>
                                        <button class="button primary" type="submit">Save booking</button>
                                    </form>
                                </details>
                                <form method="post" class="delete-form" onsubmit="return confirm('Delete this booking?');">
                                    <input type="hidden" name="csrf_token" value="<?= escapeHtml($_SESSION['csrf_token']) ?>"><input type="hidden" name="entity" value="booking"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $booking['id'] ?>">
                                    <button class="button danger" type="submit">Remove</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="data-panel" id="rooms">
            <div class="panel-heading"><div><p class="eyebrow">Inventory</p><h2>Rooms</h2></div></div>
            <details class="create-record"><summary>Add room</summary>
                <form method="post" class="record-form">
                    <input type="hidden" name="csrf_token" value="<?= escapeHtml($_SESSION['csrf_token']) ?>"><input type="hidden" name="entity" value="room"><input type="hidden" name="action" value="create">
                    <label>Room number<input name="room_number" required></label><label>Type<select name="room_type"><option>single</option><option>double</option><option>executive</option><option>extra</option></select></label>
                    <label>Price per night<input type="number" name="price_per_night" min="0" step="0.01" required></label><label>Capacity<input type="number" name="capacity" min="1" required></label>
                    <label>Status<select name="status"><option>available</option><option>booked</option><option>maintenance</option></select></label><label>Image path<input name="image_path"></label>
                    <label class="wide">Description<textarea name="description"></textarea></label><button class="button primary" type="submit">Create room</button>
                </form>
            </details>
            <div class="table-scroll"><table><thead><tr><th>Number</th><th>Type</th><th>Description</th><th>Price/night</th><th>Capacity</th><th>Status</th><th>Image</th><th>Actions</th></tr></thead><tbody>
            <?php foreach ($rooms as $room): ?>
                <tr><td><strong><?= escapeHtml((string) $room['room_number']) ?></strong></td><td><?= escapeHtml((string) $room['room_type']) ?></td><td><?= escapeHtml((string) ($room['description'] ?? '')) ?></td><td>KES <?= number_format((float) $room['price_per_night'], 2) ?></td><td><?= (int) $room['capacity'] ?></td><td><?= escapeHtml((string) $room['status']) ?></td><td><?= escapeHtml((string) ($room['image_path'] ?? '')) ?></td><td>
                    <details class="edit-row"><summary>Edit</summary><form method="post" class="inline-form">
                        <input type="hidden" name="csrf_token" value="<?= escapeHtml($_SESSION['csrf_token']) ?>"><input type="hidden" name="entity" value="room"><input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?= (int) $room['room_id'] ?>">
                        <label>Room number<input name="room_number" value="<?= escapeHtml((string) $room['room_number']) ?>" required></label>
                        <label>Type<select name="room_type"><option <?= $room['room_type'] === 'single' ? 'selected' : '' ?>>single</option><option <?= $room['room_type'] === 'double' ? 'selected' : '' ?>>double</option><option <?= $room['room_type'] === 'executive' ? 'selected' : '' ?>>executive</option><option <?= $room['room_type'] === 'extra' ? 'selected' : '' ?>>extra</option></select></label>
                        <label>Description<textarea name="description"><?= escapeHtml((string) ($room['description'] ?? '')) ?></textarea></label><label>Price/night<input type="number" name="price_per_night" min="0" step="0.01" value="<?= escapeHtml((string) $room['price_per_night']) ?>" required></label>
                        <label>Capacity<input type="number" name="capacity" min="1" value="<?= (int) $room['capacity'] ?>" required></label><label>Status<select name="status"><option <?= $room['status'] === 'available' ? 'selected' : '' ?>>available</option><option <?= $room['status'] === 'booked' ? 'selected' : '' ?>>booked</option><option <?= $room['status'] === 'maintenance' ? 'selected' : '' ?>>maintenance</option></select></label>
                        <label>Image path<input name="image_path" value="<?= escapeHtml((string) ($room['image_path'] ?? '')) ?>"></label><button class="button primary" type="submit">Save room</button>
                    </form></details>
                    <form method="post" class="delete-form" onsubmit="return confirm('Delete this room?');"><input type="hidden" name="csrf_token" value="<?= escapeHtml($_SESSION['csrf_token']) ?>"><input type="hidden" name="entity" value="room"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $room['room_id'] ?>"><button class="button danger" type="submit">Remove</button></form>
                </td></tr>
            <?php endforeach; ?>
            </tbody></table></div>
        </section>

        <section class="data-panel" id="staff">
            <div class="panel-heading"><div><p class="eyebrow">Hotel team</p><h2>Staff</h2></div><span class="count"><?= count($staff) ?></span></div>
            <details class="create-record">
                <summary>Add staff member</summary>
                <form method="post" class="record-form">
                    <input type="hidden" name="csrf_token" value="<?= escapeHtml($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="entity" value="staff">
                    <input type="hidden" name="action" value="create">
                    <label>Name<input name="name" required></label>
                    <label>Department<input name="department" required></label>
                    <label>Duty roles<textarea name="duty_roles" required></textarea></label>
                    <button class="button primary" type="submit">Create staff member</button>
                </form>
            </details>
            <div class="table-scroll">
                <table>
                    <thead><tr><th>Name</th><th>Department</th><th>Duty roles</th><th>Last updated</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($staff as $member): ?>
                        <tr>
                            <td><strong><?= escapeHtml((string) $member['name']) ?></strong></td>
                            <td><?= escapeHtml((string) $member['department']) ?></td>
                            <td><?= escapeHtml((string) $member['duty_roles']) ?></td>
                            <td><?= escapeHtml((string) $member['updated_at']) ?></td>
                            <td>
                                <details class="edit-row">
                                    <summary>Edit</summary>
                                    <form method="post" class="inline-form">
                                        <input type="hidden" name="csrf_token" value="<?= escapeHtml($_SESSION['csrf_token']) ?>">
                                        <input type="hidden" name="entity" value="staff">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="id" value="<?= (int) $member['staff_id'] ?>">
                                        <label>Name<input name="name" value="<?= escapeHtml((string) $member['name']) ?>" required></label>
                                        <label>Department<input name="department" value="<?= escapeHtml((string) $member['department']) ?>" required></label>
                                        <label>Duty roles<textarea name="duty_roles" required><?= escapeHtml((string) $member['duty_roles']) ?></textarea></label>
                                        <button class="button primary" type="submit">Save staff member</button>
                                    </form>
                                </details>
                                <form method="post" class="delete-form" onsubmit="return confirm('Remove this staff member?');">
                                    <input type="hidden" name="csrf_token" value="<?= escapeHtml($_SESSION['csrf_token']) ?>">
                                    <input type="hidden" name="entity" value="staff">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $member['staff_id'] ?>">
                                    <button class="button danger" type="submit">Remove</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="data-panel" id="users">
            <div class="panel-heading"><div><p class="eyebrow">Accounts</p><h2>Website users</h2></div></div>
            <details class="create-record"><summary>Add user</summary><form method="post" class="record-form">
                <input type="hidden" name="csrf_token" value="<?= escapeHtml($_SESSION['csrf_token']) ?>"><input type="hidden" name="entity" value="user"><input type="hidden" name="action" value="create">
                <label>Full name<input name="full_name" required></label><label>Email<input type="email" name="email" required></label><label>Phone<input name="phone"></label><label>Password<input type="password" name="password" minlength="12" required></label>
                <label>Role<select name="role"><option>guest</option><option>staff</option><option>admin</option></select></label><label class="checkbox"><input type="checkbox" name="is_verified"> Verified</label><button class="button primary" type="submit">Create user</button>
            </form></details>
            <div class="table-scroll"><table><thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Verified</th><th>Actions</th></tr></thead><tbody>
            <?php foreach ($users as $user): ?>
                <tr><td><?= escapeHtml((string) $user['full_name']) ?></td><td><?= escapeHtml((string) $user['email']) ?></td><td><?= escapeHtml((string) $user['phone']) ?></td><td><?= escapeHtml((string) $user['role']) ?></td><td><?= (int) $user['is_verified'] ? 'Yes' : 'No' ?></td><td>
                    <details class="edit-row"><summary>Edit</summary><form method="post" class="inline-form">
                        <input type="hidden" name="csrf_token" value="<?= escapeHtml($_SESSION['csrf_token']) ?>"><input type="hidden" name="entity" value="user"><input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
                        <label>Full name<input name="full_name" value="<?= escapeHtml((string) $user['full_name']) ?>" required></label><label>Email<input type="email" name="email" value="<?= escapeHtml((string) $user['email']) ?>" required></label><label>Phone<input name="phone" value="<?= escapeHtml((string) $user['phone']) ?>"></label>
                        <label>Role<select name="role"><option <?= $user['role'] === 'guest' ? 'selected' : '' ?>>guest</option><option <?= $user['role'] === 'staff' ? 'selected' : '' ?>>staff</option><option <?= $user['role'] === 'admin' ? 'selected' : '' ?>>admin</option></select></label>
                        <label>New password<input type="password" name="password" minlength="12" placeholder="Leave blank to keep"></label><label class="checkbox"><input type="checkbox" name="is_verified" <?= (int) $user['is_verified'] ? 'checked' : '' ?>> Verified</label><button class="button primary" type="submit">Save user</button>
                    </form></details>
                    <form method="post" class="delete-form" onsubmit="return confirm('Delete this user account?');"><input type="hidden" name="csrf_token" value="<?= escapeHtml($_SESSION['csrf_token']) ?>"><input type="hidden" name="entity" value="user"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $user['id'] ?>"><button class="button danger" type="submit">Remove</button></form>
                </td></tr>
            <?php endforeach; ?>
            </tbody></table></div>
        </section>

        <section class="data-panel" id="admins">
            <div class="panel-heading"><div><p class="eyebrow">Access</p><h2>Administrators</h2></div></div>
            <details class="create-record"><summary>Add administrator</summary><form method="post" class="record-form">
                <input type="hidden" name="csrf_token" value="<?= escapeHtml($_SESSION['csrf_token']) ?>"><input type="hidden" name="entity" value="admin"><input type="hidden" name="action" value="create">
                <label>Username<input type="text" name="username" minlength="3" maxlength="50" pattern="[A-Za-z0-9_-]+" required></label><label>Email<input type="email" name="email" required></label><label>Password<input type="password" name="password" minlength="12" required></label><label>Role<select name="role"><option>admin</option><option>staff</option></select></label><button class="button primary" type="submit">Create administrator</button>
            </form></details>
            <div class="table-scroll"><table><thead><tr><th>Username</th><th>Email</th><th>Role</th><th>Created</th><th>Actions</th></tr></thead><tbody>
            <?php foreach ($admins as $admin): ?>
                <tr><td><?= escapeHtml((string) $admin['username']) ?></td><td><?= escapeHtml((string) $admin['email']) ?></td><td><?= escapeHtml((string) $admin['role']) ?></td><td><?= escapeHtml((string) $admin['created_at']) ?></td><td>
                    <details class="edit-row"><summary>Edit</summary><form method="post" class="inline-form">
                        <input type="hidden" name="csrf_token" value="<?= escapeHtml($_SESSION['csrf_token']) ?>"><input type="hidden" name="entity" value="admin"><input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?= (int) $admin['id'] ?>">
                        <label>Username<input type="text" name="username" value="<?= escapeHtml((string) $admin['username']) ?>" minlength="3" maxlength="50" pattern="[A-Za-z0-9_-]+" required></label><label>Email<input type="email" name="email" value="<?= escapeHtml((string) $admin['email']) ?>" required></label><label>Role<select name="role"><option <?= $admin['role'] === 'admin' ? 'selected' : '' ?>>admin</option><option <?= $admin['role'] === 'staff' ? 'selected' : '' ?>>staff</option></select></label><label>New password<input type="password" name="password" minlength="12" placeholder="Leave blank to keep"></label><button class="button primary" type="submit">Save administrator</button>
                    </form></details>
                    <?php if ((int) $admin['id'] !== (int) $_SESSION['admin_id']): ?>
                        <form method="post" class="delete-form" onsubmit="return confirm('Delete this administrator?');"><input type="hidden" name="csrf_token" value="<?= escapeHtml($_SESSION['csrf_token']) ?>"><input type="hidden" name="entity" value="admin"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $admin['id'] ?>"><button class="button danger" type="submit">Remove</button></form>
                    <?php endif; ?>
                </td></tr>
            <?php endforeach; ?>
            </tbody></table></div>
        </section>
    <?php endif; ?>
</main>
</body>
</html>
