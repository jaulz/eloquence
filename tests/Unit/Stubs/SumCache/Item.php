<?php
namespace Tests\Unit\Stubs\SumCache;

use Jaulz\Eloquence\Behaviours\SumCache\Summable;
use Jaulz\Eloquence\Database\Model;

class Item extends Model
{
    use Summable;

    public function sumCaches()
    {
        return [
            'Tests\Unit\Stubs\SumCache\Order',
            [
                'model' => 'Tests\Unit\Stubs\SumCache\Order',
                'sumField' => 'itemTotalExplicit',
                'columnToSum' => 'total',
                'foreignKey' => 'itemId',
                'key' => 'id',
            ]
        ];
    }
}
