<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class OptionList extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'description',
        'is_nested',
        'parent_list_id',
    ];

    protected $casts = [
        'is_nested' => 'boolean',
    ];

    public function values(): HasMany
    {
        return $this->hasMany(OptionValue::class);
    }

    public function parentList(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_list_id');
    }

    private static function cacheKey(string $listKey): string
    {
        return "option_list:{$listKey}";
    }

    public static function forgetCache(string $listKey): void
    {
        Cache::forget(self::cacheKey($listKey));
    }

    /**
     * All options for a list, as [key => label], in sort order.
     * Cached — call OptionList::forgetCache($listKey) after any write.
     *
     * @return array<string, string>
     */
    public static function optionsFor(string $listKey): array
    {
        return Cache::rememberForever(self::cacheKey($listKey), function () use ($listKey) {
            $list = self::where('key', $listKey)->first();

            if (! $list) {
                return [];
            }

            return $list->values()
                ->orderBy('sort_order')
                ->pluck('label', 'key')
                ->all();
        });
    }

    /**
     * Options for a nested list, scoped to a specific parent-list value's key (e.g. only the
     * sample subgroups relevant to sample_group "animal"). A value can be scoped to more than
     * one parent value — this is a many-to-many relationship, not a strict hierarchy. Not
     * cached — scoped lookups are small and infrequent.
     *
     * @return array<string, string>
     */
    public static function subOptionsFor(string $listKey, ?string $parentKey): array
    {
        if ($parentKey === null || $parentKey === '') {
            return [];
        }

        $list = self::where('key', $listKey)->first();

        if (! $list || ! $list->parent_list_id) {
            return [];
        }

        $parent = OptionValue::where('option_list_id', $list->parent_list_id)
            ->where('key', $parentKey)
            ->first();

        if (! $parent) {
            return [];
        }

        return $parent->scopedValues()
            ->where('option_list_id', $list->id)
            ->orderBy('sort_order')
            ->pluck('label', 'key')
            ->all();
    }

    /** All nested options grouped by parent key — e.g. subgroups grouped by every sample_group. */
    public static function nestedOptionsFor(string $listKey): array
    {
        $list = self::where('key', $listKey)->first();

        if (! $list || ! $list->parent_list_id) {
            return [];
        }

        $parents = OptionValue::where('option_list_id', $list->parent_list_id)
            ->orderBy('sort_order')
            ->get();

        return $parents->mapWithKeys(function (OptionValue $parent) use ($list) {
            $children = $parent->scopedValues()
                ->where('option_list_id', $list->id)
                ->orderBy('sort_order')
                ->pluck('label', 'key')
                ->all();

            return [$parent->key => $children];
        })->all();
    }
}
