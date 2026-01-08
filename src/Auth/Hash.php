<?php

namespace Velocix\Auth;

class Hash
{
    public static function make($value)
    {
        return password_hash($value, PASSWORD_DEFAULT);
    }

    public static function check($value, $hashedValue)
    {
        return password_verify($value, $hashedValue);
    }

    public static function needsRehash($hashedValue)
    {
        return password_needs_rehash($hashedValue, PASSWORD_DEFAULT);
    }
}