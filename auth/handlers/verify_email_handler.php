<?php

require_once __DIR__ . '/../includes/auth_db.php';

function verifyEmailToken(string $token): array
{
    if (empty($token)) {
        return [
            'status' => 'error',
            'message' => 'This verification link is missing a token.',
        ];
    }

    $tokenHash = hash('sha256', $token);

    try {
        $pdo = auth_db();

        $stmt = $pdo->prepare("
            SELECT
                evt.id AS token_id,
                evt.user_id,
                evt.expires_at,
                evt.used_at,
                u.email_verified
            FROM email_verification_tokens evt
            INNER JOIN users u ON u.id = evt.user_id
            WHERE evt.token_hash = :token_hash
            LIMIT 1
        ");

        $stmt->execute([
            ':token_hash' => $tokenHash,
        ]);

        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$record) {
            return [
                'status' => 'error',
                'message' => 'This verification link is invalid or has already been used.',
            ];
        }

        if ($record['email_verified']) {
            return [
                'status' => 'success',
                'message' => 'Your email has already been verified. You can sign in.',
            ];
        }

        if (!empty($record['used_at'])) {
            return [
                'status' => 'error',
                'message' => 'This verification link has already been used.',
            ];
        }

        if (strtotime($record['expires_at']) < time()) {
            return [
                'status' => 'error',
                'message' => 'This verification link has expired. Please request a new one.',
            ];
        }

        $pdo->beginTransaction();

        $updateUserStmt = $pdo->prepare("
            UPDATE users
            SET
                email_verified = TRUE,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :user_id
        ");

        $updateUserStmt->execute([
            ':user_id' => $record['user_id'],
        ]);

        $updateTokenStmt = $pdo->prepare("
            UPDATE email_verification_tokens
            SET used_at = CURRENT_TIMESTAMP
            WHERE id = :token_id
        ");

        $updateTokenStmt->execute([
            ':token_id' => $record['token_id'],
        ]);

        $pdo->commit();

        return [
            'status' => 'success',
            'message' => 'Your email has been verified. You can now sign in.',
        ];
    } catch (Throwable $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log('Email verification failed: ' . $e->getMessage());

        return [
            'status' => 'error',
            'message' => 'Something went wrong while verifying your email. Please try again.',
        ];
    }
}