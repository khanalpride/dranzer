<?php
/** @noinspection PhpMissingFieldTypeInspection */

namespace App\Models;

/**
 * Class Mutation
 * @package App
 *
 * @property string $name;
 * @property string $path;
 * @property mixed $value;
 * @method static auth()
 * @method static create(array $array)
 * @method static whereIn(string $string, array $created)
 * @method static orderBy(string $string, string $string1)
 * @method static take(int $int)
 */
class Mutation extends BaseModel
{
    /**
     * @var string[]
     */
    protected $hidden = [
        '_id',
        'created_at',
        'updated_at',
        'user_id',
        'project_id'
    ];

    /**
     * @param $query
     * @param $projectId
     * @return mixed
     */
    public function scopeProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }
}
