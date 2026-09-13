<?php
require_once __DIR__ . '/../config/config.php';
requireRole('employer');
$eid = currentUser()['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/employer/hiring.php');

$action = $_POST['action'] ?? '';

if ($action === 'create') {
    $candidateId = (int)($_POST['candidate_id'] ?? 0);
    $positionTitle = trim($_POST['position_title'] ?? '');
    $message = trim($_POST['message'] ?? '');

    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'teacher'");
    $stmt->execute([$candidateId]);
    if (!$stmt->fetch()) {
        setFlashErrors(['Selected candidate not found.']);
        redirectBack('/employer/find_talent.php');
    }
    if (mb_strlen($positionTitle) < 2) {
        setFlashErrors(['Please provide a position title.']);
        redirectBack('/employer/candidate.php?id=' . $candidateId);
    }

    $stmt = $pdo->prepare("INSERT INTO hiring_records (employer_id, candidate_id, position_title, message, status) VALUES (?, ?, ?, ?, 'contacted')");
    $stmt->execute([$eid, $candidateId, $positionTitle, $message ?: null]);

    notify($pdo, $candidateId, 'hiring_contact', "An employer is interested in hiring you for \"$positionTitle\".", '/teacher/profile.php');

    redirect('/employer/hiring.php');
}

if ($action === 'update_status') {
    $hiringId = (int)($_POST['hiring_id'] ?? 0);
    $status = $_POST['status'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM hiring_records WHERE id = ? AND employer_id = ?');
    $stmt->execute([$hiringId, $eid]);
    $record = $stmt->fetch();
    if (!$record) { http_response_code(404); include __DIR__ . '/../includes/error_404.php'; exit; }

    $validStatuses = ['contacted','interviewing','hired','declined','completed'];
    if (!in_array($status, $validStatuses, true)) {
        setFlashErrors(['Invalid status.']);
        redirect('/employer/hiring.php');
    }

    $pdo->prepare('UPDATE hiring_records SET status = ? WHERE id = ?')->execute([$status, $hiringId]);
    notify($pdo, (int)$record['candidate_id'], 'hiring_status', "Your hiring engagement status changed to \"$status\".", '/teacher/profile.php');

    redirect('/employer/hiring.php');
}

redirect('/employer/hiring.php');
