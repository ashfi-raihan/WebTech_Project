<?php
// Run with: php database/seed.php
// Populates the MySQL database with realistic demo data for testing.

require_once __DIR__ . '/../config/database.php'; // provides $pdo

$DEMO_PASSWORD = 'Demo@1234';
$hash = password_hash($DEMO_PASSWORD, PASSWORD_BCRYPT);

function daysFromNow(int $days, int $hour = 10, int $minute = 0): string {
    $d = new DateTime();
    $d->modify("$days days");
    $d->setTime($hour, $minute, 0);
    return $d->format('Y-m-d H:i:s');
}

function uuidLike(): string {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

echo "Clearing existing data...\n";
$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
foreach ([
    'moderation_records','reports','notifications','hiring_records','verification_requests',
    'reviews','barter_exchanges','transactions','wishlists','learning_materials','bookings',
    'teacher_availability','skill_listings','user_skills','skills','categories',
    'user_profiles','users',
] as $table) {
    $pdo->exec("TRUNCATE TABLE $table");
}
$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

echo "Seeding users...\n";
$insertUser = $pdo->prepare("INSERT INTO users (name, email, password_hash, role, phone, is_active, verification_status) VALUES (?, ?, ?, ?, ?, 1, ?)");

$insertUser->execute(['Aisha Rahman', 'admin@example.com', $hash, 'admin', '01700000001', 'approved']);
$adminId = (int)$pdo->lastInsertId();

$insertUser->execute(['Rafiq Hasan', 'teacher@example.com', $hash, 'teacher', '01700000002', 'approved']);
$t1 = (int)$pdo->lastInsertId();

$insertUser->execute(['Nusrat Jahan', 'nusrat.teacher@example.com', $hash, 'teacher', '01700000003', 'approved']);
$t2 = (int)$pdo->lastInsertId();

$insertUser->execute(['Imran Chowdhury', 'imran.teacher@example.com', $hash, 'teacher', '01700000004', 'pending']);
$t3 = (int)$pdo->lastInsertId();

$insertUser->execute(['Sadia Islam', 'student@example.com', $hash, 'student', '01700000005', 'unverified']);
$s1 = (int)$pdo->lastInsertId();

$insertUser->execute(['Tanvir Ahmed', 'tanvir.student@example.com', $hash, 'student', '01700000006', 'unverified']);
$s2 = (int)$pdo->lastInsertId();

$insertUser->execute(['Farhana Karim', 'employer@example.com', $hash, 'employer', '01700000007', 'approved']);
$e1 = (int)$pdo->lastInsertId();

echo "Seeding profiles...\n";
$insertProfile = $pdo->prepare("INSERT INTO user_profiles (user_id, bio, headline, location, education, experience, interests, company_name, website) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
$insertProfile->execute([$adminId, 'Platform administrator.', 'System Administrator', 'Dhaka, BD', null, null, null, null, null]);
$insertProfile->execute([$t1, 'Full-stack developer teaching Python & Web Dev for 5+ years.', 'Senior Software Engineer & Mentor', 'Dhaka, BD', 'BSc in CSE, BUET', '5 years industry, 3 years teaching', 'Python, Web Development, Mentoring', null, null]);
$insertProfile->execute([$t2, 'Professional graphic designer specializing in branding.', 'Graphic Designer & Illustrator', 'Chattogram, BD', 'BFA, Fine Arts', '7 years freelance design', 'Graphic Design, Branding, Illustration', null, null]);
$insertProfile->execute([$t3, 'Data analyst teaching Excel & SQL fundamentals.', 'Data Analyst', 'Sylhet, BD', 'BBA', '4 years', 'Excel, SQL, Data Analysis', null, null]);
$insertProfile->execute([$s1, 'CS undergrad learning web development.', 'CSE Student', 'Dhaka, BD', 'BSc CSE (ongoing)', null, 'Web Dev, UI Design', null, null]);
$insertProfile->execute([$s2, 'Aspiring designer picking up new skills.', 'BBA Student', 'Dhaka, BD', 'BBA (ongoing)', null, 'Design, Marketing', null, null]);
$insertProfile->execute([$e1, 'Hiring manager at a growing tech startup.', 'Hiring Manager', 'Dhaka, BD', null, null, null, 'NextGen Tech Ltd.', 'https://example.com']);

echo "Seeding categories & skills...\n";
$insertCategory = $pdo->prepare('INSERT INTO categories (name, description) VALUES (?, ?)');
$insertCategory->execute(['Programming', 'Software development and coding skills']);
$catProg = (int)$pdo->lastInsertId();
$insertCategory->execute(['Design', 'Graphic, UI/UX and visual design skills']);
$catDesign = (int)$pdo->lastInsertId();
$insertCategory->execute(['Data', 'Data analysis and spreadsheet skills']);
$catData = (int)$pdo->lastInsertId();
$insertCategory->execute(['Languages', 'Spoken and written languages']);
$catLang = (int)$pdo->lastInsertId();

$insertSkill = $pdo->prepare('INSERT INTO skills (name, category_id) VALUES (?, ?)');
$insertSkill->execute(['Python Programming', $catProg]); $skPython = (int)$pdo->lastInsertId();
$insertSkill->execute(['Web Development', $catProg]); $skWeb = (int)$pdo->lastInsertId();
$insertSkill->execute(['Graphic Design', $catDesign]); $skDesign = (int)$pdo->lastInsertId();
$insertSkill->execute(['UI/UX Design', $catDesign]); $skUiux = (int)$pdo->lastInsertId();
$insertSkill->execute(['Excel & Spreadsheets', $catData]); $skExcel = (int)$pdo->lastInsertId();
$insertSkill->execute(['SQL Fundamentals', $catData]); $skSql = (int)$pdo->lastInsertId();
$insertSkill->execute(['English Speaking', $catLang]); $skEnglish = (int)$pdo->lastInsertId();

$insertUserSkill = $pdo->prepare('INSERT INTO user_skills (user_id, skill_id, proficiency) VALUES (?, ?, ?)');
$insertUserSkill->execute([$t1, $skPython, 'expert']);
$insertUserSkill->execute([$t1, $skWeb, 'expert']);
$insertUserSkill->execute([$t2, $skDesign, 'expert']);
$insertUserSkill->execute([$t2, $skUiux, 'advanced']);
$insertUserSkill->execute([$t3, $skExcel, 'advanced']);
$insertUserSkill->execute([$t3, $skSql, 'advanced']);
$insertUserSkill->execute([$s1, $skDesign, 'beginner']);
$insertUserSkill->execute([$s2, $skEnglish, 'advanced']);

echo "Seeding skill listings...\n";
$insertListing = $pdo->prepare("INSERT INTO skill_listings (teacher_id, skill_id, title, description, level, price, session_duration_minutes, allows_paid, allows_barter, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
$insertListing->execute([$t1, $skPython, 'Python for Beginners', 'Learn Python from scratch with hands-on projects.', 'beginner', 500, 60, 1, 1]);
$l1 = (int)$pdo->lastInsertId();
$insertListing->execute([$t1, $skWeb, 'Full-Stack Web Development Bootcamp', 'HTML, CSS, JS, and databases.', 'intermediate', 800, 90, 1, 0]);
$l2 = (int)$pdo->lastInsertId();
$insertListing->execute([$t2, $skDesign, 'Graphic Design Masterclass', 'Branding, logo design and Adobe tools.', 'intermediate', 600, 60, 1, 1]);
$l3 = (int)$pdo->lastInsertId();
$insertListing->execute([$t2, $skUiux, 'UI/UX Design Fundamentals', 'User-centered design and Figma prototyping.', 'beginner', 550, 60, 1, 0]);
$l4 = (int)$pdo->lastInsertId();
$insertListing->execute([$t3, $skExcel, 'Excel for Data Analysis', 'Formulas, pivot tables and dashboards.', 'beginner', 400, 45, 1, 0]);
$l5 = (int)$pdo->lastInsertId();
$insertListing->execute([$t3, $skSql, 'SQL Fundamentals', 'Databases, queries and joins explained simply.', 'beginner', 450, 60, 1, 1]);
$l6 = (int)$pdo->lastInsertId();

echo "Seeding availability...\n";
$insertAvail = $pdo->prepare('INSERT INTO teacher_availability (teacher_id, listing_id, start_time, end_time, is_booked) VALUES (?, ?, ?, ?, ?)');
$insertAvail->execute([$t1, $l1, daysFromNow(-5, 10), daysFromNow(-5, 11), 1]); $av1 = (int)$pdo->lastInsertId();
$insertAvail->execute([$t1, $l2, daysFromNow(3, 15), daysFromNow(3, 16, 30), 1]); $av2 = (int)$pdo->lastInsertId();
$insertAvail->execute([$t1, $l1, daysFromNow(5, 10), daysFromNow(5, 11), 0]);
$insertAvail->execute([$t2, $l3, daysFromNow(-2, 14), daysFromNow(-2, 15), 1]); $av4 = (int)$pdo->lastInsertId();
$insertAvail->execute([$t2, $l4, daysFromNow(4, 11), daysFromNow(4, 12), 0]);
$insertAvail->execute([$t3, $l5, daysFromNow(2, 9), daysFromNow(2, 9, 45), 1]); $av6 = (int)$pdo->lastInsertId();
$insertAvail->execute([$t3, $l6, daysFromNow(6, 9), daysFromNow(6, 10), 0]);

echo "Seeding bookings...\n";
$insertBooking = $pdo->prepare("INSERT INTO bookings (student_id, teacher_id, listing_id, availability_id, start_time, end_time, payment_type, status, meeting_id, meeting_url, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$insertBooking->execute([$s1, $t1, $l1, $av1, daysFromNow(-5, 10), daysFromNow(-5, 11), 'paid', 'completed', uuidLike(), null, 'First Python session']);
$b1 = (int)$pdo->lastInsertId();
$insertBooking->execute([$s1, $t1, $l2, $av2, daysFromNow(3, 15), daysFromNow(3, 16, 30), 'paid', 'confirmed', uuidLike(), null, null]);
$b2 = (int)$pdo->lastInsertId();
$insertBooking->execute([$s2, $t2, $l3, $av4, daysFromNow(-2, 14), daysFromNow(-2, 15), 'barter', 'completed', uuidLike(), null, 'Barter: English lessons for Design']);
$b3 = (int)$pdo->lastInsertId();
$insertBooking->execute([$s2, $t3, $l5, $av6, daysFromNow(2, 9), daysFromNow(2, 9, 45), 'paid', 'confirmed', uuidLike(), null, null]);
$b4 = (int)$pdo->lastInsertId();

$updateMeetingUrl = $pdo->prepare('UPDATE bookings SET meeting_url = ? WHERE id = ?');
foreach ([$b1, $b2, $b3, $b4] as $bid) {
    $updateMeetingUrl->execute(["/shared/meeting.php?id=$bid", $bid]);
}

echo "Seeding transactions...\n";
$insertTxn = $pdo->prepare("INSERT INTO transactions (booking_id, payer_id, payee_id, amount, type, status, reference) VALUES (?, ?, ?, ?, 'session_payment', 'success', ?)");
$insertTxn->execute([$b1, $s1, $t1, 500, 'TXN-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8))]);
$insertTxn->execute([$b4, $s2, $t3, 400, 'TXN-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8))]);

echo "Seeding reviews...\n";
$insertReview = $pdo->prepare('INSERT INTO reviews (booking_id, reviewer_id, reviewee_id, rating, comment) VALUES (?, ?, ?, ?, ?)');
$insertReview->execute([$b1, $s1, $t1, 5, 'Excellent teacher, explained Python concepts very clearly!']);
$insertReview->execute([$b3, $s2, $t2, 4, 'Great design tips, learned a lot about branding.']);

echo "Seeding wishlist...\n";
$insertWishlist = $pdo->prepare('INSERT INTO wishlists (student_id, listing_id) VALUES (?, ?)');
$insertWishlist->execute([$s1, $l4]);
$insertWishlist->execute([$s1, $l6]);
$insertWishlist->execute([$s2, $l2]);

echo "Seeding verification requests...\n";
$insertVerification = $pdo->prepare("INSERT INTO verification_requests (user_id, document_info, status, admin_notes, reviewed_by, reviewed_at) VALUES (?, ?, ?, ?, ?, ?)");
$insertVerification->execute([$t1, 'National ID + teaching certificate uploaded', 'approved', 'Verified credentials.', $adminId, daysFromNow(-10)]);
$insertVerification->execute([$t2, 'Portfolio + National ID uploaded', 'approved', 'Strong portfolio, approved.', $adminId, daysFromNow(-8)]);
$insertVerification->execute([$t3, 'National ID uploaded, awaiting review', 'pending', null, null, null]);
$insertVerification->execute([$e1, 'Company registration document uploaded', 'approved', 'Verified business.', $adminId, daysFromNow(-15)]);

echo "Seeding barter exchanges...\n";
$insertBarter = $pdo->prepare("INSERT INTO barter_exchanges (sender_id, receiver_id, offered_skill_id, requested_skill_id, listing_id, status, message) VALUES (?, ?, ?, ?, ?, ?, ?)");
$insertBarter->execute([$s2, $t2, $skEnglish, $skDesign, $l3, 'completed', 'I can teach you English speaking in exchange for design lessons.']);
$insertBarter->execute([$s1, $t3, $skDesign, $skSql, $l6, 'pending', 'Happy to trade basic design lessons for SQL fundamentals.']);

echo "Seeding hiring records...\n";
$insertHiring = $pdo->prepare("INSERT INTO hiring_records (employer_id, candidate_id, position_title, message, status) VALUES (?, ?, ?, ?, ?)");
$insertHiring->execute([$e1, $t1, 'Part-time Web Development Mentor', 'We would like to hire you for our internal training program.', 'interviewing']);
$insertHiring->execute([$e1, $t2, 'Freelance Brand Designer', 'Interested in your design portfolio for a rebrand project.', 'hired']);

echo "Seeding notifications...\n";
$insertNotification = $pdo->prepare('INSERT INTO notifications (user_id, type, message, link) VALUES (?, ?, ?, ?)');
$insertNotification->execute([$s1, 'booking_confirmed', 'Your booking for "Full-Stack Web Development Bootcamp" is confirmed.', '/student/bookings.php']);
$insertNotification->execute([$t3, 'verification_pending', 'Your verification request is pending admin review.', '/teacher/profile.php']);
$insertNotification->execute([$t1, 'hiring_contact', 'An employer is interested in hiring you.', '/teacher/profile.php']);

echo "Seeding reports & moderation...\n";
$insertReport = $pdo->prepare("INSERT INTO reports (reporter_id, target_type, target_id, reason, status) VALUES (?, ?, ?, ?, ?)");
$insertReport->execute([$s2, 'listing', $l2, 'Misleading price information in listing description.', 'resolved']);
$rep1 = (int)$pdo->lastInsertId();
$pdo->prepare('INSERT INTO moderation_records (report_id, admin_id, action, notes) VALUES (?, ?, ?, ?)')
    ->execute([$rep1, $adminId, 'reviewed_no_action', 'Checked listing, description matches price. No violation found.']);

$insertReport->execute([$s1, 'user', $t3, 'Slow response time to messages.', 'open']);

echo "\nDatabase seeded successfully.\n";
echo "Demo password for all accounts: $DEMO_PASSWORD\n";
