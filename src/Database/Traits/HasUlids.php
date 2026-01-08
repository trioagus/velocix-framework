<?php

namespace Velocix\Database\Traits;

trait HasUlids
{
    protected static function bootHasUlids()
    {
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = static::generateUlid();
            }
        });
    }

    public function getIncrementing()
    {
        return false;
    }

    public function getKeyType()
    {
        return 'string';
    }

    public static function generateUlid()
    {
        // ULID generation: 48 bits timestamp + 80 bits randomness
        $timestamp = (int)(microtime(true) * 1000);
        
        // Crockford's Base32 alphabet (without I, L, O, U)
        $base32 = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';
        
        // Time part (10 characters)
        $time = '';
        $ts = $timestamp;
        for ($i = 9; $i >= 0; $i--) {
            $mod = $ts % 32;
            $time = $base32[$mod] . $time;
            $ts = (int)($ts / 32);
        }
        
        // Random part (16 characters)
        $random = '';
        $randomBytes = random_bytes(10);
        $randomInt = 0;
        
        foreach (str_split($randomBytes) as $byte) {
            $randomInt = ($randomInt << 8) | ord($byte);
        }
        
        for ($i = 0; $i < 16; $i++) {
            $random = $base32[$randomInt % 32] . $random;
            $randomInt = (int)($randomInt / 32);
        }
        
        return $time . $random;
    }
}