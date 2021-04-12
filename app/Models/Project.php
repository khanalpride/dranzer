<?php

namespace App\Models;

use Jenssegers\Mongodb\Relations\HasMany;

/**
 * @property mixed updated_at
 * @property HasMany mutations
 * @property mixed created_at
 * @method static create(array $array)
 * @method static desc()
 * @method static auth()
 */
class Project extends BaseModel
{
    /**
     * @var string[]
     */
    protected $hidden = [
        '_id',
        'user_id',
        'paid'
    ];

    /**
     * @var string[]
     */
    protected $appends = [
        'humanFriendlyCreatedTS',
        'formattedCreatedTS'
    ];

    /**
     * @param $query
     * @param $name
     * @return mixed
     */
    public function scopeName($query, $name)
    {
        return $query->where('name', $name);
    }

    /**
     * @param $query
     * @param $type
     * @return mixed
     */
    public function scopeType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopeDesc($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|HasMany
     */
    public function mutations()
    {
        return $this->hasMany(Mutation::class, 'project_id', 'uuid');
    }

    /**
     * @return mixed
     */
    public function getHumanFriendlyCreatedTSAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * @return mixed
     */
    public function getFormattedCreatedTSAttribute()
    {
        return $this->created_at->toDayDateTimeString();
    }
}
