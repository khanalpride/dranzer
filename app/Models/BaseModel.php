<?php
/** @noinspection PhpMissingFieldTypeInspection */

namespace App\Models;

use Illuminate\Support\Str;
use Jenssegers\Mongodb\Eloquent\Model;
use Illuminate\Auth\AuthenticationException;

/**
 * Class BaseModel
 * @package App
 *
 * @property string $uuid
 * @property string $projectId
 */
class BaseModel extends Model
{
    /**
     * @var bool
     */
    protected static $withUserId = true;
    /**
     * @var string[]
     */
    protected $guarded = ['_id'];
    /**
     * @var string[]
     */
    protected $hidden = [
        '_id',
        'user_id'
    ];

    /**
     *
     */
    public static function booted(): void
    {
        static::creating(static function ($model) {
            $model->uuid = (string) Str::uuid();

            if (static::$withUserId === false) {
                return;
            }

            $user = auth()->user();
            $model->user_id = $user ? $user->getAuthIdentifier() : null;
        });
    }

    /**
     * @param $query
     * @param bool $validate
     * @return mixed
     * @throws AuthenticationException
     */
    public function scopeAuth($query, $validate = true)
    {
        $user = auth()->user();
        if (!$user && $validate) {
            /** @noinspection PhpUnhandledExceptionInspection */
            throw new AuthenticationException('Unauthorized.');
        }
        return $query->where('user_id', $user->getAuthIdentifier());
    }

    /**
     * @param $query
     * @param $projectId
     * @return mixed
     */
    public function scopeProject($query, $projectId)
    {
        return $query->where('projectId', $projectId);
    }

    /**
     * @param $query
     * @param $uuid
     * @return mixed
     *
     * @noinspection PhpUnused
     * @noinspection UnknownInspectionInspection
     */
    public function scopeUUID($query, $uuid)
    {
        return $query->where('uuid', $uuid);
    }

    /**
     * @return string
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
