<?php

namespace Velocix\Auth;

class Auth
{
    protected static $user = null;
    protected static $userModel = 'App\\Models\\User';

    public static function attempt($credentials)
    {
        $email = $credentials['email'] ?? null;
        $password = $credentials['password'] ?? null;

        if (!$email || !$password) {
            return false;
        }

        $userModel = static::$userModel;
        $user = $userModel::where('email', $email)->first();

        if (!$user) {
            return false;
        }

        $userData = is_array($user) ? $user : (is_object($user) && method_exists($user, 'toArray') ? $user->toArray() : (array) $user);

        if (!Hash::check($password, $userData['password'])) {
            return false;
        }

        static::login($userData);
        return true;
    }

    public static function login($user)
    {
        static::startSession();
        
        // Ensure $user is array
        $userData = is_array($user) ? $user : (is_object($user) && method_exists($user, 'toArray') ? $user->toArray() : (array) $user);
        
        $_SESSION['user_id'] = $userData['id'];
        $_SESSION['user_email'] = $userData['email'];
        $_SESSION['authenticated'] = true;
        
        static::$user = $userData;
    }

    public static function logout()
    {
        static::startSession();
        
        session_unset();
        session_destroy();
        
        static::$user = null;
    }

    public static function check()
    {
        static::startSession();
        return isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
    }

    public static function guest()
    {
        return !static::check();
    }

    public static function user()
    {
        if (static::$user !== null) {
            return static::$user;
        }

        if (!static::check()) {
            return null;
        }

        static::startSession();
        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            return null;
        }

        $userModel = static::$userModel;
        $user = $userModel::find($userId);

        if (!$user) {
            static::$user = null;
            return null;
        }

        // Fix: handle both object and array
        static::$user = is_array($user) ? $user : (is_object($user) && method_exists($user, 'toArray') ? $user->toArray() : (array) $user);

        return static::$user;
    }

    public static function id()
    {
        $user = static::user();
        return $user ? ($user['id'] ?? null) : null;
    }

    protected static function startSession()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}