<?php
// ============================================================
// Shared helper functions used across the whole application.
// ============================================================

/** Redirect to a path relative to the site root and stop execution. */
function redirect(string $path): void {
    header('Location: ' . BASE_URL . $path);
    exit;
}

/** Escape output for safe HTML rendering (XSS protection). */
function e($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/** Is someone logged in? */
function isLoggedIn(): bool {
    return isset($_SESSION['user']);
}

/** Get the current logged-in user array, or null. */
function currentUser(): ?array {
    return $_SESSION['user'] ?? null;
}

/** Require login; redirect to login page (preserving return URL) if not authenticated. */
function requireLogin(): void {
    if (!isLoggedIn()) {
        $_SESSION['return_to'] = $_SERVER['REQUEST_URI'];
        redirect('/auth/login.php');
    }
}

/** Require the current user to have one of the given roles (RBAC). */
function requireRole(string ...$roles): void {
    requireLogin();
    if (!in_array(currentUser()['role'], $roles, true)) {
        global $pdo;
        http_response_code(403);
        include __DIR__ . '/../includes/error_403.php';
        exit;
    }
}

// ---------- Flash messages (errors / success across a redirect) ----------
function setFlashErrors(array $errors): void {
    $_SESSION['flash_errors'] = $errors;
}
function getFlashErrors(): array {
    $errors = $_SESSION['flash_errors'] ?? [];
    unset($_SESSION['flash_errors']);
    return $errors;
}
function setFlashSuccess(string $message): void {
    $_SESSION['flash_success'] = $message;
}
function getFlashSuccess(): ?string {
    $msg = $_SESSION['flash_success'] ?? null;
    unset($_SESSION['flash_success']);
    return $msg;
}
function setOldInput(array $input): void {
    $_SESSION['old_input'] = $input;
}
function getOldInput(): array {
    $old = $_SESSION['old_input'] ?? [];
    unset($_SESSION['old_input']);
    return $old;
}

/** Redirect back to the referring page (or a fallback). */
function redirectBack(string $fallback = '/'): void {
    $ref = $_SERVER['HTTP_REFERER'] ?? null;
    if ($ref) {
        header('Location: ' . $ref);
    } else {
        header('Location: ' . BASE_URL . $fallback);
    }
    exit;
}

// ---------- Notifications ----------
function notify(PDO $pdo, int $userId, string $type, string $message, ?string $link = null): void {
    $stmt = $pdo->prepare('INSERT INTO notifications (user_id, type, message, link) VALUES (?, ?, ?, ?)');
    $stmt->execute([$userId, $type, $message, $link]);
}

function getUnreadNotificationCount(PDO $pdo, int $userId): int {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

// ---------- Demo Payment Service ----------
// Simulates a payment gateway for coursework. NEVER stores card data — only
// a generated reference and outcome, matching the project's payment requirements.
function processDemoPayment(float $amount): array {
    if ($amount <= 0) {
        return ['success' => false, 'reference' => null];
    }
    $reference = 'TXN-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    return ['success' => true, 'reference' => $reference];
}

/** Generate a UUID-like meeting id (no external package needed). */
function generateMeetingId(): string {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/** Format a datetime string for display. */
function formatDateTime(?string $dt): string {
    if (!$dt) return '-';
    return date('M j, Y g:i A', strtotime($dt));
}
function formatDate(?string $dt): string {
    if (!$dt) return '-';
    return date('M j, Y', strtotime($dt));
}

/** Run a query and return a single scalar value (COUNT/SUM/etc.). */
function scalar(PDO $pdo, string $sql, array $params = []) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

/** Fetch profile data used by every role's profile page. */
function getProfileData(PDO $pdo, int $userId): array {
    $stmt = $pdo->prepare('SELECT id, name, email, phone, role, verification_status, created_at FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    $stmt = $pdo->prepare('SELECT * FROM user_profiles WHERE user_id = ?');
    $stmt->execute([$userId]);
    $profile = $stmt->fetch() ?: [];

    $stmt = $pdo->prepare('SELECT us.*, s.name AS skill_name FROM user_skills us JOIN skills s ON s.id = us.skill_id WHERE us.user_id = ?');
    $stmt->execute([$userId]);
    $skills = $stmt->fetchAll();

    $stmt = $pdo->prepare('SELECT COALESCE(AVG(rating), 0) AS avg_rating, COUNT(*) AS review_count FROM reviews WHERE reviewee_id = ?');
    $stmt->execute([$userId]);
    $ratingRow = $stmt->fetch();

    $stmt = $pdo->prepare('SELECT * FROM verification_requests WHERE user_id = ? ORDER BY created_at DESC LIMIT 1');
    $stmt->execute([$userId]);
    $verification = $stmt->fetch() ?: null;

    return compact('user', 'profile', 'skills', 'ratingRow', 'verification');
}

/** Shared profile_action.php logic used identically by all 4 roles. */
function handleProfileAction(PDO $pdo, string $role): void {
    $uid = currentUser()['id'];
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect("/$role/profile.php");

    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $name = trim($_POST['name'] ?? '');
        if (mb_strlen($name) < 2) {
            setFlashErrors(['Name must be at least 2 characters.']);
            redirectBack("/$role/profile.php");
        }
        $phone = trim($_POST['phone'] ?? '');
        $pdo->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?")->execute([$name, $phone ?: null, $uid]);

        $fields = ['bio', 'headline', 'location', 'education', 'experience', 'interests', 'company_name', 'website'];
        $values = array_map(fn($f) => trim($_POST[$f] ?? '') ?: null, $fields);

        $stmt = $pdo->prepare('SELECT id FROM user_profiles WHERE user_id = ?');
        $stmt->execute([$uid]);
        if ($stmt->fetch()) {
            $sql = 'UPDATE user_profiles SET ' . implode(', ', array_map(fn($f) => "$f = ?", $fields)) . ' WHERE user_id = ?';
            $pdo->prepare($sql)->execute([...$values, $uid]);
        } else {
            $sql = 'INSERT INTO user_profiles (user_id, ' . implode(', ', $fields) . ') VALUES (?, ' . implode(', ', array_fill(0, count($fields), '?')) . ')';
            $pdo->prepare($sql)->execute([$uid, ...$values]);
        }

        $_SESSION['user']['name'] = $name;
        redirectBack("/$role/profile.php");
    }

    if ($action === 'add_skill') {
        $skillId = (int)($_POST['skill_id'] ?? 0);
        $proficiency = $_POST['proficiency'] ?? 'intermediate';
        try {
            $pdo->prepare('INSERT INTO user_skills (user_id, skill_id, proficiency) VALUES (?, ?, ?)')->execute([$uid, $skillId, $proficiency]);
        } catch (PDOException $e) {
            setFlashErrors(['That skill is already on your profile.']);
        }
        redirectBack("/$role/profile.php");
    }

    if ($action === 'remove_skill') {
        $userSkillId = (int)($_POST['user_skill_id'] ?? 0);
        $pdo->prepare('DELETE FROM user_skills WHERE id = ? AND user_id = ?')->execute([$userSkillId, $uid]);
        redirectBack("/$role/profile.php");
    }

    if ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_new_password'] ?? '';

        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$uid]);
        $userRow = $stmt->fetch();

        if (!password_verify($current, $userRow['password_hash'])) {
            setFlashErrors(['Current password is incorrect.']);
            redirectBack("/$role/profile.php");
        }
        if (strlen($new) < 6) {
            setFlashErrors(['New password must be at least 6 characters.']);
            redirectBack("/$role/profile.php");
        }
        if ($new !== $confirm) {
            setFlashErrors(['New passwords do not match.']);
            redirectBack("/$role/profile.php");
        }

        $hash = password_hash($new, PASSWORD_BCRYPT);
        $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$hash, $uid]);
        setFlashSuccess('Password updated successfully.');
        redirectBack("/$role/profile.php");
    }

    if ($action === 'verify') {
        $documentInfo = trim($_POST['document_info'] ?? '') ?: 'No additional details provided.';

        $stmt = $pdo->prepare("SELECT id FROM verification_requests WHERE user_id = ? AND status = 'pending'");
        $stmt->execute([$uid]);
        if ($stmt->fetch()) {
            setFlashErrors(['You already have a pending verification request.']);
            redirectBack("/$role/profile.php");
        }

        $pdo->prepare("INSERT INTO verification_requests (user_id, document_info, status) VALUES (?, ?, 'pending')")->execute([$uid, $documentInfo]);
        $pdo->prepare("UPDATE users SET verification_status = 'pending' WHERE id = ?")->execute([$uid]);
        $_SESSION['user']['verification_status'] = 'pending';

        redirectBack("/$role/profile.php");
    }

    redirect("/$role/profile.php");
}

/** Shared barter offer response handler (accept/reject/cancel) - used by student & teacher. */
function handleBarterRespond(PDO $pdo, string $redirectPath): void {
    $uid = currentUser()['id'];
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect($redirectPath);

    $offerId = (int)($_POST['offer_id'] ?? 0);
    $respondAction = $_POST['respond_action'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM barter_exchanges WHERE id = ?');
    $stmt->execute([$offerId]);
    $offer = $stmt->fetch();

    if (!$offer) { http_response_code(404); include __DIR__ . '/error_404.php'; exit; }

    $isReceiver = (int)$offer['receiver_id'] === $uid;
    $isSender = (int)$offer['sender_id'] === $uid;
    if (!$isReceiver && !$isSender) { http_response_code(403); include __DIR__ . '/error_403.php'; exit; }

    if ($offer['status'] !== 'pending') {
        setFlashErrors(['This barter offer has already been resolved.']);
        redirectBack($redirectPath);
    }

    $newStatus = null;
    if ($respondAction === 'accept' && $isReceiver) $newStatus = 'accepted';
    elseif ($respondAction === 'reject' && $isReceiver) $newStatus = 'rejected';
    elseif ($respondAction === 'cancel' && $isSender) $newStatus = 'cancelled';

    if (!$newStatus) {
        setFlashErrors(['Invalid action for this offer.']);
        redirectBack($redirectPath);
    }

    $pdo->prepare('UPDATE barter_exchanges SET status = ? WHERE id = ?')->execute([$newStatus, $offerId]);
    $otherParty = $isReceiver ? (int)$offer['sender_id'] : (int)$offer['receiver_id'];
    notify($pdo, $otherParty, 'barter_update', "Your barter offer #$offerId was $newStatus.", $redirectPath);

    redirectBack($redirectPath);
}

/** Simple star-badge helper for status pills (returns a CSS class suffix). */
function statusBadgeClass(string $status): string {
    return match ($status) {
        'active', 'approved', 'confirmed', 'completed', 'success', 'hired', 'resolved' => 'badge-success',
        'pending', 'contacted', 'interviewing', 'rescheduled' => 'badge-warning',
        'rejected', 'cancelled', 'removed', 'declined', 'failed' => 'badge-danger',
        'inactive', 'unverified', 'dismissed' => 'badge-muted',
        default => 'badge-info',
    };
}
