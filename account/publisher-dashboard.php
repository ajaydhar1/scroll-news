<?php

define('BASE_PATH', dirname(__DIR__));
$theme_experiment_enabled = true;
require_once BASE_PATH . '/auth/includes/require_auth.php';
require_once __DIR__ . '/publisher.php';

$userId = (int) ($_SESSION['user_id'] ?? 0);
$pdo = auth_db();

if (empty($_SESSION['publisher_profile_csrf'])) {
    $_SESSION['publisher_profile_csrf'] = bin2hex(random_bytes(32));
}

$profileErrors = [];
$openEditModal = false;
$urlFields = publisher_profile_url_fields();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_profile') {
    $postPublisherId = (int) ($_POST['publisher_id'] ?? 0);
    $csrfOk = hash_equals((string) $_SESSION['publisher_profile_csrf'], (string) ($_POST['csrf_token'] ?? ''));
    $publisherForUpdate = $csrfOk ? publisher_find_verified($pdo, $postPublisherId, $userId) : null;

    if (!$csrfOk) {
        $profileErrors[] = 'Your session expired. Please refresh the page and try again.';
    } elseif (!$publisherForUpdate) {
        http_response_code(403);
        $profileErrors[] = 'You do not have access to update this publisher profile.';
    } else {
        $description = trim((string) ($_POST['description'] ?? ''));
        $location = trim((string) ($_POST['location'] ?? ''));

        if (mb_strlen($description) > 1000) {
            $profileErrors[] = 'Description must be 1000 characters or fewer.';
        }

        if (mb_strlen($location) > 160) {
            $profileErrors[] = 'Location must be 160 characters or fewer.';
        }

        $sanitizedUrls = [];

        foreach ($urlFields as $field => $label) {
            $value = trim((string) ($_POST[$field] ?? ''));

            if ($value === '') {
                $sanitizedUrls[$field] = null;
                continue;
            }

            $normalized = publisher_normalize_url($value);

            if ($normalized === null) {
                $profileErrors[] = 'Please enter a valid URL (including https://) for ' . $label . '.';
                continue;
            }

            $sanitizedUrls[$field] = $normalized;
        }

        if (!$profileErrors) {
            $stmt = $pdo->prepare('
                INSERT INTO publisher_profiles (
                    publisher_id, description, location, website_url, rss_url, x_url,
                    instagram_url, facebook_url, linkedin_url, youtube_url, tiktok_url, threads_url
                ) VALUES (
                    :publisher_id, :description, :location, :website_url, :rss_url, :x_url,
                    :instagram_url, :facebook_url, :linkedin_url, :youtube_url, :tiktok_url, :threads_url
                )
                ON CONFLICT (publisher_id) DO UPDATE SET
                    description = EXCLUDED.description,
                    location = EXCLUDED.location,
                    website_url = EXCLUDED.website_url,
                    rss_url = EXCLUDED.rss_url,
                    x_url = EXCLUDED.x_url,
                    instagram_url = EXCLUDED.instagram_url,
                    facebook_url = EXCLUDED.facebook_url,
                    linkedin_url = EXCLUDED.linkedin_url,
                    youtube_url = EXCLUDED.youtube_url,
                    tiktok_url = EXCLUDED.tiktok_url,
                    threads_url = EXCLUDED.threads_url,
                    updated_at = CURRENT_TIMESTAMP
            ');

            $stmt->execute([
                ':publisher_id' => $postPublisherId,
                ':description' => $description !== '' ? $description : null,
                ':location' => $location !== '' ? $location : null,
                ':website_url' => $sanitizedUrls['website_url'],
                ':rss_url' => $sanitizedUrls['rss_url'],
                ':x_url' => $sanitizedUrls['x_url'],
                ':instagram_url' => $sanitizedUrls['instagram_url'],
                ':facebook_url' => $sanitizedUrls['facebook_url'],
                ':linkedin_url' => $sanitizedUrls['linkedin_url'],
                ':youtube_url' => $sanitizedUrls['youtube_url'],
                ':tiktok_url' => $sanitizedUrls['tiktok_url'],
                ':threads_url' => $sanitizedUrls['threads_url'],
            ]);

            $_SESSION['publisher_profile_csrf'] = bin2hex(random_bytes(32));
            header('Location: /account/publisher-dashboard.php?publisher_id=' . $postPublisherId . '&profile_saved=1');
            exit;
        }

        $openEditModal = true;
    }
}

$publisherId = (int) ($_GET['publisher_id'] ?? 0);
$publisher = publisher_find_verified($pdo, $publisherId, $userId);
$profile = $publisher ? publisher_load_profile($pdo, $publisherId) : null;
$profileSaved = isset($_GET['profile_saved']);

// Repopulate the modal with the just-submitted (invalid) values instead of the stored profile.
if ($openEditModal && $publisher) {
    $profile = array_merge(publisher_empty_profile(), [
        'description' => trim((string) ($_POST['description'] ?? '')),
        'location' => trim((string) ($_POST['location'] ?? '')),
    ]);

    foreach (array_keys($urlFields) as $field) {
        $profile[$field] = trim((string) ($_POST[$field] ?? ''));
    }
}

if (!$publisher) {
    http_response_code(403);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php require_once BASE_PATH . '/views/partials/___google_analytics.php'; ?>

    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Publisher Dashboard — Scroll News</title>
    <link rel="icon" type="image/png" href="/assets/img/play-green.png" />

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://use.fontawesome.com/releases/v6.7.2/js/all.js" crossorigin="anonymous"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css?family=Roboto+Slab:400,100,300,700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600&family=Open+Sans&display=swap" rel="stylesheet" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">

    <link href="/assets/css/styles.css?v=<?= filemtime(BASE_PATH . '/assets/css/styles.css'); ?>" rel="stylesheet" />
    <link href="/assets/css/custom.css?v=<?= filemtime(BASE_PATH . '/assets/css/custom.css'); ?>" rel="stylesheet" />
    <link id="dark-typography-theme" href="/assets/css/dark-typography.css?v=<?= filemtime(BASE_PATH . '/assets/css/dark-typography.css'); ?>" rel="stylesheet" />

    <script src="/assets/js/dark-theme.js?v=<?= filemtime(BASE_PATH . '/assets/js/dark-theme.js'); ?>"></script>

    <link href="/assets/css/auth.css?v=<?= filemtime(BASE_PATH . '/assets/css/auth.css'); ?>" rel="stylesheet" />
    <link href="/assets/css/account.css?v=<?= filemtime(BASE_PATH . '/assets/css/account.css'); ?>" rel="stylesheet" />
</head>
<body class="auth-page account-page">
    <div class="page">
        <?php require_once BASE_PATH . '/views/partials/___topnav_full.php'; ?>

        <main class="auth-shell container my-5">
            <div class="auth-card card border-0 rounded-3">
                <div class="card-body p-4 p-md-5">
                    <?php if (!$publisher): ?>
                        <div class="alert alert-danger" role="alert">You do not have access to this publisher dashboard.</div>
                        <a href="/account/" class="btn btn-outline-secondary">Return to Account</a>
                    <?php else: ?>
                        <p class="text-muted mb-2"><a href="/account/">Account</a> / Publisher Tools</p>
                        <h1 class="h3 mb-2"><?= publisher_h($publisher['name'] ?: $publisher['domain']); ?></h1>
                        <p class="mb-4"><span class="badge badge-success">Verified Publisher</span> <span class="text-muted"><?= publisher_h($publisher['domain']); ?></span></p>

                        <?php if ($profileSaved): ?>
                            <div class="alert alert-success" role="alert">Publisher profile saved.</div>
                        <?php endif; ?>

                        <h2 class="h5">Publisher Profile</h2>

                        <div class="card publisher-profile-panel mb-4">
                            <div class="card-body">
                                <div class="d-flex align-items-start flex-wrap">
                                    <img class="publisher-profile-logo mr-3 mb-2" src="<?= publisher_h(publisher_favicon_url($publisher['domain'])); ?>" alt="<?= publisher_h($publisher['domain']); ?> logo" />
                                    <div class="flex-grow-1 mb-2">
                                        <h3 class="h5 mb-1"><?= publisher_h($publisher['name'] ?: $publisher['domain']); ?></h3>
                                        <p class="mb-2"><span class="badge badge-success">Verified Publisher</span> <span class="text-muted"><?= publisher_h($publisher['domain']); ?></span></p>

                                        <?php if (!empty($profile['description'])): ?>
                                            <p class="mb-2"><?= nl2br(publisher_h($profile['description'])); ?></p>
                                        <?php endif; ?>

                                        <?php if (!empty($profile['location'])): ?>
                                            <p class="mb-2 text-muted"><i class="fa-solid fa-location-dot mr-1"></i><?= publisher_h($profile['location']); ?></p>
                                        <?php endif; ?>

                                        <?php if (!empty($profile['website_url'])): ?>
                                            <p class="mb-2"><a href="<?= publisher_h($profile['website_url']); ?>" target="_blank" rel="noopener"><?= publisher_h($profile['website_url']); ?></a></p>
                                        <?php endif; ?>

                                        <?php
                                        $socialIcons = [
                                            'rss_url' => 'fa-solid fa-rss',
                                            'x_url' => 'fa-brands fa-x-twitter',
                                            'instagram_url' => 'fa-brands fa-instagram',
                                            'facebook_url' => 'fa-brands fa-facebook',
                                            'linkedin_url' => 'fa-brands fa-linkedin',
                                            'youtube_url' => 'fa-brands fa-youtube',
                                            'tiktok_url' => 'fa-brands fa-tiktok',
                                            'threads_url' => 'fa-brands fa-threads',
                                        ];
                                        $hasSocial = false;
                                        foreach ($socialIcons as $field => $icon) {
                                            if (!empty($profile[$field])) {
                                                $hasSocial = true;
                                                break;
                                            }
                                        }
                                        ?>
                                        <?php if ($hasSocial): ?>
                                            <div class="mb-2">
                                                <?php foreach ($socialIcons as $field => $icon): ?>
                                                    <?php if (!empty($profile[$field])): ?>
                                                        <a class="btn btn-black btn-social mx-1" title="<?= publisher_h($urlFields[$field]); ?>" href="<?= publisher_h($profile[$field]); ?>" target="_blank" rel="noopener"><i class="<?= $icon; ?>"></i></a>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>

                                        <button type="button" class="btn btn-outline-secondary btn-sm mt-2" data-toggle="modal" data-target="#editProfileModal">Edit Profile</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <h2 class="h5">Publisher Tools</h2>
                        <ul class="text-muted">
                            <li>Content Statistics — Coming soon</li>
                            <li>Content Controls — Coming soon</li>
                            <li>Article Visibility — Coming soon</li>
                        </ul>

                        <div class="modal fade" id="editProfileModal" tabindex="-1" role="dialog" aria-labelledby="editProfileModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
                                <div class="modal-content">
                                    <form method="post" action="/account/publisher-dashboard.php?publisher_id=<?= (int) $publisher['id']; ?>">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="editProfileModalLabel">Edit Publisher Profile</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                        </div>
                                        <div class="modal-body">
                                            <?php if ($profileErrors): ?>
                                                <div class="alert alert-danger" role="alert">
                                                    <ul class="mb-0">
                                                        <?php foreach ($profileErrors as $error): ?>
                                                            <li><?= publisher_h($error); ?></li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                </div>
                                            <?php endif; ?>

                                            <input type="hidden" name="action" value="update_profile" />
                                            <input type="hidden" name="publisher_id" value="<?= (int) $publisher['id']; ?>" />
                                            <input type="hidden" name="csrf_token" value="<?= publisher_h($_SESSION['publisher_profile_csrf']); ?>" />

                                            <h6 class="publisher-profile-modal-section">Profile Information</h6>

                                            <div class="form-group">
                                                <label>Verified domain</label>
                                                <input type="text" class="form-control" value="<?= publisher_h($publisher['domain']); ?>" disabled readonly />
                                            </div>

                                            <div class="form-group">
                                                <label for="description">Description</label>
                                                <textarea class="form-control" id="description" name="description" maxlength="1000" rows="3"><?= publisher_h($profile['description'] ?? ''); ?></textarea>
                                            </div>

                                            <div class="form-row">
                                                <div class="form-group col-md-6">
                                                    <label for="location">Location</label>
                                                    <input type="text" class="form-control" id="location" name="location" maxlength="160" value="<?= publisher_h($profile['location'] ?? ''); ?>" />
                                                </div>
                                                <div class="form-group col-md-6">
                                                    <label for="website_url"><?= publisher_h($urlFields['website_url']); ?> URL</label>
                                                    <input type="url" class="form-control" id="website_url" name="website_url" maxlength="2048" placeholder="https://" value="<?= publisher_h($profile['website_url'] ?? ''); ?>" />
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label for="rss_url"><?= publisher_h($urlFields['rss_url']); ?> URL</label>
                                                <input type="url" class="form-control" id="rss_url" name="rss_url" maxlength="2048" placeholder="https://" value="<?= publisher_h($profile['rss_url'] ?? ''); ?>" />
                                            </div>

                                            <h6 class="publisher-profile-modal-section">Social Media</h6>

                                            <?php
                                            $socialFieldPairs = [
                                                ['x_url', 'instagram_url'],
                                                ['facebook_url', 'linkedin_url'],
                                                ['youtube_url', 'tiktok_url'],
                                                ['threads_url', null],
                                            ];
                                            ?>
                                            <?php foreach ($socialFieldPairs as [$leftField, $rightField]): ?>
                                                <div class="form-row">
                                                    <div class="form-group col-md-6">
                                                        <label for="<?= $leftField; ?>"><?= publisher_h($urlFields[$leftField]); ?> URL</label>
                                                        <input type="url" class="form-control" id="<?= $leftField; ?>" name="<?= $leftField; ?>" maxlength="2048" placeholder="https://" value="<?= publisher_h($profile[$leftField] ?? ''); ?>" />
                                                    </div>
                                                    <?php if ($rightField !== null): ?>
                                                        <div class="form-group col-md-6">
                                                            <label for="<?= $rightField; ?>"><?= publisher_h($urlFields[$rightField]); ?> URL</label>
                                                            <input type="url" class="form-control" id="<?= $rightField; ?>" name="<?= $rightField; ?>" maxlength="2048" placeholder="https://" value="<?= publisher_h($profile[$rightField] ?? ''); ?>" />
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary">Save</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>

        <?php require_once BASE_PATH . '/views/partials/___footer.php'; ?>
    </div>

    <?php require_once BASE_PATH . '/views/partials/___modals.php'; ?>

    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.bundle.min.js"></script>
    <?php if ($publisher && $openEditModal): ?>
        <script>
            $(function () {
                $('#editProfileModal').modal('show');
            });
        </script>
    <?php endif; ?>
</body>
</html>