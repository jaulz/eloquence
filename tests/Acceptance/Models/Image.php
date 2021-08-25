<?php

namespace Tests\Acceptance\Models;

use Jaulz\Eloquence\Traits\IsCacheableTrait;
use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    use IsCacheableTrait;

    public static function caches()
    {
        return [
            [
                'function' => 'COUNT',
                'relation' => 'imageable',
                'summary' => 'images_count',
            ],

            [
                'function' => 'COUNT',
                'relation' => 'tags',
                'summary' => 'taggables_count',
            ],

            [
                'function' => 'MAX',
                'relation' => 'tags',
                'summary' => 'last_created_at',
                'value' => 'created_at',
            ],

            [
                'function' => 'MIN',
                'relation' => 'tags',
                'summary' => 'first_created_at',
                'value' => 'created_at',
            ],
        ];
    }

    public function imageable()
    {
        return $this->morphTo();
    }
    
    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable')->using(Taggable::class);
    }
}
