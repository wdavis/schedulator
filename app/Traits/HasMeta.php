<?php

namespace App\Traits;

use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Model;

trait HasMeta
{
    /**
     * Get the name of the meta column.
     *
     * @return string
     */
    protected function getMetaColumnName()
    {
        return property_exists($this, 'metaColumnName') ? $this->metaColumnName : 'meta';
    }

    /**
     * Set meta values using dot notation
     * path.to.key => value
     *
     * @param  array  $items
     * @return $this
     */
    public function setMeta(array $items)
    {
        $meta = $this->getAttribute($this->getMetaColumnName()) ?: [];

        foreach ($items as $key => $value) {
            Arr::set($meta, $key, $value);
        }

        $this->setAttribute($this->getMetaColumnName(), $meta);

        return $this;
    }

    /**
     * Update meta values.
     *
     * @param  array  $items
     * @return bool
     */
    public function updateMeta(array $items)
    {
        $this->setMeta($items);

        return $this->save();
    }

    public function getMeta(string $key, $default = null)
    {
        return Arr::get($this->getAttribute($this->getMetaColumnName()), $key, $default);
    }

    public function hasMeta(string $key): bool
    {
        return Arr::has($this->getAttribute($this->getMetaColumnName()), $key);
    }
}
