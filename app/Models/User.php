<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Jenssegers\Mongodb\Auth\User as Authenticatable;

/**
 * Class User
 * @package App
 *
 * @property string $name
 * @property string $username
 * @property string $email
 * @property string $token
 * @method static where(string $string, string $email)
 * @method static create(array $array)
 */
class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [];
}
