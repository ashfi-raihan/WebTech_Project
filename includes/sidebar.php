<?php
$__role = currentUser()['role'];
$__menus = [
    'student' => [
        ['/student/dashboard.php', 'Overview'],
        ['/listings.php', 'Browse Skills'],
        ['/student/wishlist.php', 'Wishlist'],
        ['/student/bookings.php', 'My Bookings'],
        ['/student/calendar.php', 'Calendar'],
        ['/student/barter.php', 'Barter'],
        ['/student/materials.php', 'Learning Materials'],
        ['/student/transactions.php', 'Transactions'],
        ['/student/reviews.php', 'Reviews'],
        ['/student/profile.php', 'Profile'],
    ],
    'teacher' => [
        ['/teacher/dashboard.php', 'Overview'],
        ['/teacher/skills.php', 'My Skills'],
        ['/teacher/availability.php', 'Availability'],
        ['/teacher/bookings.php', 'Bookings'],
        ['/teacher/materials.php', 'Learning Materials'],
        ['/teacher/barter.php', 'Barter'],
        ['/teacher/transactions.php', 'Transactions'],
        ['/teacher/reviews.php', 'Reviews'],
        ['/teacher/profile.php', 'Profile'],
    ],
    'employer' => [
        ['/employer/dashboard.php', 'Overview'],
        ['/employer/find_talent.php', 'Find Talent'],
        ['/employer/hiring.php', 'Hiring / Engagements'],
        ['/employer/reviews.php', 'Reviews'],
        ['/employer/profile.php', 'Profile'],
    ],
    'admin' => [
        ['/admin/dashboard.php', 'Overview'],
        ['/admin/users.php', 'Users'],
        ['/admin/skills.php', 'Skills & Categories'],
        ['/admin/listings.php', 'Listings'],
        ['/admin/verification.php', 'Verification'],
        ['/admin/reports.php', 'Reports & Moderation'],
        ['/admin/transactions.php', 'Transactions'],
        ['/admin/profile.php', 'Profile'],
    ],
];
$__items = $__menus[$__role] ?? [];
$__currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
?>
<aside class="sidebar">
  <?php foreach ($__items as [$href, $label]): ?>
    <a href="<?= BASE_URL . $href ?>" class="<?= str_ends_with($__currentPath, $href) ? 'active' : '' ?>"><?= e($label) ?></a>
  <?php endforeach; ?>
</aside>
