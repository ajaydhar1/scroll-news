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

    </a>

    <div class="dropdown-menu dropdown-menu-right account-nav-menu account-mega-menu" aria-labelledby="accountDropdown">

        <div class="dropdown-header account-dropdown-header">
            <div class="font-weight-bold">
                <?= htmlspecialchars($displayName ?: 'Account', ENT_QUOTES, 'UTF-8') ?>
            </div>

            <?php if ($email): ?>
                <div class="small text-muted">
                    <?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="dropdown-divider"></div>

        <div class="account-mega-grid">

            <a class="account-mega-link" href="/account/">
                <i class="fa-solid fa-gauge-high"></i>
                <span>
                    <strong>Dashboard</strong>
                    <small>Account overview</small>
                </span>
            </a>

            <a class="account-mega-link" href="/account/reading-history.php" data-loading>
                <i class="fa-solid fa-book-open-reader"></i>
                <span>
                    <strong>Reading History</strong>
                    <small>Articles you opened</small>
                </span>
            </a>

            <a class="account-mega-link" href="/account/saved-headlines.php" data-loading>
                <i class="fa-regular fa-bookmark"></i>
                <span>
                    <strong>Saved Headlines</strong>
                    <small>Your bookmarked stories</small>
                </span>
            </a>

            <a class="account-mega-link" href="/account/search-history.php" data-loading>
                <i class="fa-solid fa-magnifying-glass"></i>
                <span>
                    <strong>Search History</strong>
                    <small>Past news searches</small>
                </span>
            </a>

            <a class="account-mega-link" href="/account/shuffle-history.php" data-loading>
                <i class="fa-solid fa-shuffle"></i>
                <span>
                    <strong>Shuffle History</strong>
                    <small>Saved discovery sessions</small>
                </span>
            </a>

            <a class="account-mega-link" href="/auth/change-password.php">
                <i class="fa-solid fa-lock"></i>
                <span>
                    <strong>Password</strong>
                    <small>Change login password</small>
                </span>
            </a>

        </div>

        <div class="dropdown-divider"></div>

        <a class="dropdown-item text-danger" href="/auth/logout.php">
            <i class="fa-solid fa-right-from-bracket mr-2"></i> Sign Out
        </a>
    </div>
</div>