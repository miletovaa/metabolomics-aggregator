<?php

namespace App\Livewire\OptionLists;

use App\Models\OptionList;
use App\Models\OptionValue;
use App\Services\OptionValueRenamer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;

class Show extends Component
{
    public OptionList $optionList;

    /** Group values this value can apply to, keyed by new-value form only — existing rows use toggleScope(). */
    public array $newValueScopes = [];

    public string $newLabel = '';
    public string $newLabelError = '';

    public ?int $editingValueId = null;
    public string $editingLabel = '';
    public string $editingLabelError = '';

    public ?string $successMessage = null;

    public function mount(OptionList $optionList): void
    {
        abort_unless(Auth::user()->hasPermission('options', 'view'), 403);

        $this->optionList = $optionList;
    }

    private function slugify(string $label): string
    {
        return Str::slug($label, '_');
    }

    public function addValue(): void
    {
        abort_unless(Auth::user()->hasPermission('options', 'edit'), 403);

        $this->newLabelError = '';
        $label = trim($this->newLabel);

        if ($label === '') {
            $this->newLabelError = 'Label is required.';

            return;
        }

        if ($this->optionList->is_nested && $this->newValueScopes === []) {
            $this->newLabelError = 'Choose at least one parent value.';

            return;
        }

        $key = $this->slugify($label);

        $exists = OptionValue::where('option_list_id', $this->optionList->id)
            ->where('key', $key)
            ->exists();

        if ($exists) {
            $this->newLabelError = "A value that slugifies to \"{$key}\" already exists here.";

            return;
        }

        $nextOrder = (int) OptionValue::where('option_list_id', $this->optionList->id)->max('sort_order') + 1;

        $value = OptionValue::create([
            'option_list_id' => $this->optionList->id,
            'key' => $key,
            'label' => $label,
            'sort_order' => $nextOrder,
        ]);

        if ($this->optionList->is_nested) {
            $value->scopedTo()->sync($this->newValueScopes);
        }

        $this->newLabel = '';
        $this->newValueScopes = [];
        $this->successMessage = "Added \"{$label}\".";
    }

    public function toggleScope(int $valueId, int $groupValueId): void
    {
        abort_unless(Auth::user()->hasPermission('options', 'edit'), 403);

        $value = OptionValue::findOrFail($valueId);

        if ($value->scopedTo()->where('scope_value_id', $groupValueId)->exists()) {
            $value->scopedTo()->detach($groupValueId);
        } else {
            $value->scopedTo()->attach($groupValueId);
        }

        OptionList::forgetCache($this->optionList->key);
    }

    public function startEdit(int $valueId): void
    {
        $value = OptionValue::findOrFail($valueId);
        $this->editingValueId = $valueId;
        $this->editingLabel = $value->label;
        $this->editingLabelError = '';
    }

    public function cancelEdit(): void
    {
        $this->editingValueId = null;
        $this->editingLabel = '';
        $this->editingLabelError = '';
    }

    public function saveEdit(): void
    {
        abort_unless(Auth::user()->hasPermission('options', 'edit'), 403);

        $this->editingLabelError = '';
        $label = trim($this->editingLabel);

        if ($label === '') {
            $this->editingLabelError = 'Label is required.';

            return;
        }

        $value = OptionValue::findOrFail($this->editingValueId);
        $newKey = $this->slugify($label);
        $oldKey = $value->key;

        if ($newKey !== $oldKey) {
            $exists = OptionValue::where('option_list_id', $value->option_list_id)
                ->where('key', $newKey)
                ->where('id', '!=', $value->id)
                ->exists();

            if ($exists) {
                $this->editingLabelError = "A value that slugifies to \"{$newKey}\" already exists here.";

                return;
            }
        }

        $value->update(['label' => $label, 'key' => $newKey]);

        if ($newKey !== $oldKey) {
            $updated = (new OptionValueRenamer())->cascade($this->optionList->key, $oldKey, $newKey);
            $this->successMessage = "Renamed to \"{$label}\" (\"{$oldKey}\" \u{2192} \"{$newKey}\")."
                . ($updated > 0 ? " Updated {$updated} existing record(s) to match." : '');
        } else {
            $this->successMessage = "Updated \"{$label}\".";
        }

        $this->cancelEdit();
    }

    public function deleteValue(int $valueId): void
    {
        abort_unless(Auth::user()->hasPermission('options', 'delete'), 403);

        $value = OptionValue::findOrFail($valueId);
        $label = $value->label;
        $value->delete();
        $this->successMessage = "Deleted \"{$label}\".";
    }

    public function usageCountFor(OptionValue $value): int
    {
        return (new OptionValueRenamer())->usageCount($this->optionList->key, $value->key);
    }

    public function moveValue(int $valueId, int $direction): void
    {
        abort_unless(Auth::user()->hasPermission('options', 'edit'), 403);

        $value = OptionValue::findOrFail($valueId);

        $sibling = OptionValue::where('option_list_id', $value->option_list_id)
            ->when($direction < 0, fn ($q) => $q->where('sort_order', '<', $value->sort_order)->orderByDesc('sort_order'))
            ->when($direction > 0, fn ($q) => $q->where('sort_order', '>', $value->sort_order)->orderBy('sort_order'))
            ->first();

        if (! $sibling) {
            return;
        }

        [$a, $b] = [$value->sort_order, $sibling->sort_order];
        $value->update(['sort_order' => $b]);
        $sibling->update(['sort_order' => $a]);
    }

    public function dismissNotification(): void
    {
        $this->successMessage = null;
    }

    public function render()
    {
        $parentValues = null;
        $scopesByValue = [];

        $values = OptionValue::where('option_list_id', $this->optionList->id)
            ->orderBy('sort_order')
            ->get();

        if ($this->optionList->is_nested) {
            $parentValues = OptionValue::where('option_list_id', $this->optionList->parent_list_id)
                ->orderBy('sort_order')
                ->get();

            $scopesByValue = DB::table('option_value_scopes')
                ->whereIn('option_value_id', $values->pluck('id'))
                ->get()
                ->groupBy('option_value_id')
                ->map(fn ($rows) => $rows->pluck('scope_value_id')->all())
                ->all();
        }

        return view('livewire.option-lists.show', [
            'values' => $values,
            'parentValues' => $parentValues,
            'scopesByValue' => $scopesByValue,
            'canEdit' => Auth::user()->hasPermission('options', 'edit'),
            'canDelete' => Auth::user()->hasPermission('options', 'delete'),
        ])->layout('layouts.app');
    }
}
