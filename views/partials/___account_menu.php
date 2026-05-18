<?php

$displayName = trim($currentUser['display_name'] ?? '');
$email = trim($currentUser['email'] ?? '');
$profileImage = trim($currentUser['profile_image'] ?? '');

$firstName = '';

if ($displayName !== '') {
    $nameParts = preg_split('/\s+/', $displayName);
    $firstName = $nameParts[0] ?? '';
}

$initial = strtoupper(substr($firstName ?: $email ?: 'U', 0, 1));

?>

<div class="nav-item dropdown account-nav-item">
    <a
        class="nav-link dropdown-toggle account-nav-toggle d-flex align-items-center"
        href="#"
        id="accountDropdown"
        role="button"
        data-toggle="dropdown"
        aria-haspopup="true"
        aria-expanded="false">
        <?php if ($profileImage): ?>
            <img
                src="<?= htmlspecialchars($profileImage, ENT_QUOTES, 'UTF-8') ?>"
                alt="Profile"
                class="account-nav-avatar">
        <?php else: ?>
            <span class="account-nav-avatar account-nav-initial">
                <?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') ?>
            </span>
        <?php endif; ?>

        <span class="account-nav-label ml-2">
            <?= htmlspecialchars($firstName ?: 'Account', ENT_QUOTES, 'UTF-8') ?>
        </span>
    </a>

    <div class="dropdown-menu dropdown-menu-right account-nav-menu" aria-labelledby="accountDropdown">
        <a class="dropdown-item" href="/account/">
            <i class="fa-solid fa-user mr-2"></i> Account
        </a>

        <a class="dropdown-item" href="/account/activity.php">
            <i class="fa-solid fa-wave-square mr-2"></i> Your Activity
        </a>

        <a class="dropdown-item" href="/auth/change-password.php">
            <i class="fa-solid fa-lock mr-2"></i> Change Password
        </a>

        <div class="dropdown-divider"></div>

        <a class="dropdown-item text-danger" href="/auth/logout.php">
            <i class="fa-solid fa-right-from-bracket mr-2"></i> Sign Out
        </a>
    </div>
</div>