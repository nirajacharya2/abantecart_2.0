<?php
namespace abc\models\casts;
use abc\core\engine\Registry;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Concerns\HasAttributes;
use Illuminate\Database\Eloquent\Model;

class Serialized implements CastsAttributes
{
    use HasAttributes;
    /**
     * Cast the given value.
     *
     * @param  Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return array
     */
    public function get($model, $key, $value, $attributes)
    {
        $output = unserialize($value);
        return  $output === false ? [] : $output;
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  Model  $model
     * @param  string  $key
     * @param  array  $value
     * @param  array  $attributes
     * @return string
     */
    public function set($model, $key, $value, $attributes)
    {
        return $this->asSerialized($value);
    }

    protected function asSerialized($value)
    {
        return serialize($value);
    }
}
