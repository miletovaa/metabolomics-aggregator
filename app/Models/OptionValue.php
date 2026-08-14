<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class OptionValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'option_list_id',
        'key',
        'label',
        'sort_order',
    ];

    protected static function booted(): void
    {
        static::saved(fn (OptionValue $value) => OptionList::forgetCache($value->optionList->key));
        static::deleted(fn (OptionValue $value) => OptionList::forgetCache($value->optionList->key));
    }

    public function optionList(): BelongsTo
    {
        return $this->belongsTo(OptionList::class);
    }

    /** Parent-list values this value is relevant under (e.g. which sample_groups a subgroup applies to). */
    public function scopedTo(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'option_value_scopes', 'option_value_id', 'scope_value_id')
            ->withTimestamps();
    }

    /** Values in a nested list that are scoped to this value (e.g. this group's applicable subgroups). */
    public function scopedValues(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'option_value_scopes', 'scope_value_id', 'option_value_id')
            ->withTimestamps();
    }
}
