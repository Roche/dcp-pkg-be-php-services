<?php

namespace Dcp\Traits;

use Illuminate\Support\Collection;

trait DataFilterable
{
    /**
     * Filter by a specific property value
     * 
     * @param Collection|array $items
     * @param string $property
     * @param mixed $value
     * @return Collection
     */
    public static function filterByProperty(Collection|array $items, string $property, mixed $value): Collection
    {
        $collection = $items instanceof Collection ? $items : collect($items);
        
        return $collection->filter(
            fn($item) => $item->$property === $value
        );
    }

    /**
     * Filter by site ID
     * 
     * @param Collection|array $items
     * @param int $siteId
     * @return Collection
     */
    public static function filterBySite(Collection|array $items, int $siteId): Collection
    {
        return static::filterByProperty($items, 'siteId', $siteId);
    }

    /**
     * Filter by enabled status
     * 
     * @param Collection|array $items
     * @param bool $isEnabled
     * @return Collection
     */
    public static function filterByEnabled(Collection|array $items, bool $isEnabled = true): Collection
    {
        return static::filterByProperty($items, 'isEnabled', $isEnabled);
    }

    /**
     * Filter by validation status
     * 
     * @param Collection|array $items
     * @param bool $isValidated
     * @return Collection
     */
    public static function filterByValidated(Collection|array $items, bool $isValidated = true): Collection
    {
        return static::filterByProperty($items, 'isValidated', $isValidated);
    }

    /**
     * Find item by ID
     * 
     * @param Collection|array $items
     * @param int $id
     * @param string $idProperty
     * @return static|null
     */
    public static function findById(Collection|array $items, int $id, string $idProperty = 'id'): ?static
    {
        $collection = $items instanceof Collection ? $items : collect($items);
        
        return $collection->first(fn($item) => $item->$idProperty === $id);
    }

    /**
     * Filter using a custom callback
     * 
     * @param Collection|array $items
     * @param callable $callback
     * @return Collection
     */
    public static function filter(Collection|array $items, callable $callback): Collection
    {
        $collection = $items instanceof Collection ? $items : collect($items);
        
        return $collection->filter($callback);
    }
}