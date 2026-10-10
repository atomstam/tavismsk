<?php

declare(strict_types=1);

namespace App\Models;

use PDOException;

final class UserOAuth
{
    /** @return array<string,mixed>|null */
    public function findByProvider(string $provider, string $providerUserId): ?array
    {
        try {
            return db()->fetch(
                'SELECT oauth_id, user_id, provider, provider_user_id, email, display_name
                 FROM user_oauth WHERE provider = ? AND provider_user_id = ? LIMIT 1',
                [$provider, $providerUserId]
            );
        } catch (PDOException) {
            return null;
        }
    }

    public function link(
        int $userId,
        string $provider,
        string $providerUserId,
        string $email = '',
        string $displayName = '',
    ): void {
        $existing = $this->findByProvider($provider, $providerUserId);
        $now = date('Y-m-d H:i:s');
        if ($existing !== null) {
            db()->query(
                'UPDATE user_oauth SET user_id = ?, email = ?, display_name = ?, updated_at = ? WHERE oauth_id = ?',
                [
                    $userId,
                    $email !== '' ? $email : null,
                    $displayName !== '' ? $displayName : null,
                    $now,
                    (int) ($existing['oauth_id'] ?? 0),
                ]
            );

            return;
        }

        db()->insert('user_oauth', [
            'user_id' => $userId,
            'provider' => $provider,
            'provider_user_id' => $providerUserId,
            'email' => $email !== '' ? $email : null,
            'display_name' => $displayName !== '' ? $displayName : null,
            'created_at' => $now,
        ]);
    }

    /** @return array<string,mixed>|null */
    public function findByUserAndProvider(int $userId, string $provider): ?array
    {
        try {
            return db()->fetch(
                'SELECT oauth_id, user_id, provider, provider_user_id, email, display_name, created_at, updated_at
                 FROM user_oauth WHERE user_id = ? AND provider = ? LIMIT 1',
                [$userId, $provider]
            );
        } catch (PDOException) {
            return null;
        }
    }

    public function unlinkUserProvider(int $userId, string $provider): bool
    {
        try {
            $stmt = db()->query(
                'DELETE FROM user_oauth WHERE user_id = ? AND provider = ?',
                [$userId, $provider]
            );

            return $stmt->rowCount() > 0;
        } catch (PDOException) {
            return false;
        }
    }

    public function tableExists(): bool
    {
        try {
            db()->fetch('SELECT 1 FROM user_oauth LIMIT 1');

            return true;
        } catch (PDOException) {
            return false;
        }
    }
}
