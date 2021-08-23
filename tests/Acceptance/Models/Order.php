<?php
namespace Tests\Acceptance\Models;

use Illuminate\Database\Eloquent\Model;
use Jaulz\Eloquence\Traits\IsCacheableTrait;

class Order extends Model
{
    use IsCacheableTrait;
    
    public function items()
    {
        return $this->hasMany('Tests\Acceptance\Models\Item');
    }
}
