<?php
namespace Jaulz\Eloquence\Behaviours\SumCache;

use Jaulz\Eloquence\Behaviours\Cacheable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class SumCache
{
    use Cacheable;

    /**
     * @var Model
     */
    private $model;

    /**
     * @param Model $model
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Rebuild the count caches from the database
     */
    public function rebuild()
    {
        $this->apply('sum', function($config) {
            $this->rebuildCacheRecord($config, $this->model, 'SUM', $config['columnToSum']);
        });
    }

    /**
     * Update the cache for all operations.
     */
    public function update()
    {
        $this->apply('sum', function ($config, $isRelevant, $wasRelevant) {
            $foreignKey = Str::snake($this->key($config['foreignKey']));

            // In case the foreign key changed, we just transfer the values from one model to the other
            if ($this->model->getOriginal($foreignKey) && $this->model->{$foreignKey} != $this->model->getOriginal($foreignKey)) {
                $amount = $this->model->{$config['columnToSum']};
                $this->updateCacheRecord($config, '-', $amount, $this->model->getOriginal($foreignKey));
                $this->updateCacheRecord($config, '+', $amount, $this->model->{$foreignKey});
            } else {
                if ($isRelevant && $wasRelevant) {
                    // We need to add the difference in case it is as relevant as before
                    $difference = $this->model->{$config['columnToSum']} - $this->model->getOriginal($config['columnToSum']);
                    $this->updateCacheRecord($config, '+', $difference, $this->model->{$foreignKey});
                } else if ($isRelevant && !$wasRelevant) {
                    // Increment because it was not relevant before but now it is
                    $this->updateCacheRecord($config, '+', $this->model->{$config['columnToSum']}, $this->model->{$foreignKey});
                } else if (!$isRelevant && $wasRelevant) {
                    // Decrement because it was relevant before but now it is not anymore
                    $this->updateCacheRecord($config, '-', $this->model->getOriginal($config['columnToSum']), $this->model->{$foreignKey});
                }

            }
        });
    }

    /**
     * Takes a registered sum cache, and setups up defaults.
     *
     * @param string $cacheKey
     * @param array $cacheOptions
     * @return array
     */
    protected function config($cacheKey, $cacheOptions)
    {
        $opts = [];

        if (is_numeric($cacheKey)) {
            if (is_array($cacheOptions)) {
                // Most explicit configuration provided
                $opts = $cacheOptions;
                $relatedModel = Arr::get($opts, 'model');
            } else {
                // Smallest number of options provided, figure out the rest
                $relatedModel = $cacheOptions;
            }
        } else {
            // Semi-verbose configuration provided
            $relatedModel = $cacheOptions;
            $opts['field'] = $cacheKey;

            if (is_array($cacheOptions)) {
                if (isset($cacheOptions[3])) {
                    $opts['key'] = $cacheOptions[3];
                }
                if (isset($cacheOptions[2])) {
                    $opts['foreignKey'] = $cacheOptions[2];
                }
                if (isset($cacheOptions[1])) {
                    $opts['columnToSum'] = $cacheOptions[1];
                }
                if (isset($cacheOptions[0])) {
                    $relatedModel = $cacheOptions[0];
                }
            }
        }

        return $this->defaults($opts, $relatedModel);
    }

    /**
     * Returns necessary defaults, overwritten by provided options.
     *
     * @param array $options
     * @param string $relatedModel
     * @return array
     */
    protected function defaults($options, $relatedModel)
    {
        $defaults = [
            'model' => $relatedModel,
            'columnToSum' => 'total',
            'field' => $this->field($this->model, 'total'),
            'foreignKey' => $this->field($relatedModel, 'id'),
            'key' => 'id',
            'where' => []
        ];

        return array_merge($defaults, $options);
    }
}
