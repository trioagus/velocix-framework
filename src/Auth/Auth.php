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

        // Get password hash (bypass hidden attributes)
        $hashedPassword = null;
        
        if (is_object($user)) {
            // Access attributes property
            $reflection = new \ReflectionClass($user);
            if ($reflection->hasProperty('attributes')) {
                $attributesProperty = $reflection->getProperty('attributes');
                // PHP 8.1+ - properties are accessible by default via getValue
                $attributes = $attributesProperty->getValue($user);
                $hashedPassword = $attributes['password'] ?? null;
            }
        }
        
        // Fallback
        if (!$hashedPassword && is_object($user) && property_exists($user, 'password')) {
            $hashedPassword = $user->password;
        }
        
        if (!$hashedPassword && is_array($user)) {
            $hashedPassword = $user['password'] ?? null;
        }

        if (!$hashedPassword) {
            return false;
        }

        if (!Hash::check($password, $hashedPassword)) {
            return false;
        }

        // Convert user to array for session
        if (is_object($user)) {
            $reflection = new \ReflectionClass($user);
            if ($reflection->hasProperty('attributes')) {
                $attributesProperty = $reflection->getProperty('attributes');
                $userData = $attributesProperty->getValue($user);
            } else {
                $userData = (array) $user;
            }
        } else {
            $userData = $user;
        }

        static::login($userData);
        return true;
    }

    public static function login($user)
    {
        static::startSession();
        
        $userData = is_array($user) ? $user : (is_object($user) && method_exists($user, 'toArray') ? $user->toArray() : (array) $user);
        
        $_SESSION['user_id'] = $userData['id'];
        $_SESSION['user_email'] = $userData['email'];
        $_SESSION['user_name'] = $userData['name'] ?? '';
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

        // Get user data (bypass hidden attributes)
        if (is_object($user)) {
            $reflection = new \ReflectionClass($user);
            if ($reflection->hasProperty('attributes')) {
                $attributesProperty = $reflection->getProperty('attributes');
                static::$user = $attributesProperty->getValue($user);
            } else {
                static::$user = (array) $user;
            }
        } else {
            static::$user = is_array($user) ? $user : (array) $user;
        }

        return static::$user;
    }

    public static function id()
    {
        $user = static::user();
        return $user ? ($user['id'] ?? null) : null;
    }
    
    /**
     * Get user data from session
     */
    public static function sessionUser()
    {
        static::startSession();
        
        return [
            'id' => $_SESSION['user_id'] ?? null,
            'email' => $_SESSION['user_email'] ?? null,
            'name' => $_SESSION['user_name'] ?? null
        ];
    }

    protected static function startSession()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}