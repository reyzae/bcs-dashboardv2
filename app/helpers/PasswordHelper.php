<?php
/**
 * Password Helper - Centralized password hashing
 * Menghindari code duplication
 */

class PasswordHelper
{
    /**
     * Hash password dengan bcrypt (cost 12)
     */
    public static function hash($password)
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    /**
     * Verify password
     */
    public static function verify($password, $hash)
    {
        return password_verify($password, $hash);
    }

    /**
     * Check if password needs rehash (jika algorithm berubah)
     */
    public static function needsRehash($hash)
    {
        return password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => 12]);
    }
}
