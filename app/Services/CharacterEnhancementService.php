<?php

namespace App\Services;

use App\Models\Character;
use App\Models\CharacterItem;
use App\Models\Inventory;
use Illuminate\Validation\ValidationException;

class CharacterEnhancementService
{
    private const PROFILE_FIELDS = [
        'Male calico' => 'male_calico',
        'Chimera/mosaicism' => 'chimera_mosaicism',
        'Karpati/Roan/Salmiak' => 'karpati_roan_salmiak',
        'White sepia' => 'white_sepia',
        'Albino' => 'albino',
        'Purebred' => 'purebred',
    ];

    public static function supports(?string $effect): bool
    {
        return $effect && (str_starts_with($effect, 'Rare trait:') || isset(self::PROFILE_FIELDS[$effect]) || in_array($effect, ['Rare eye color', 'Disability', 'Outsider access', 'Time freeze'], true));
    }

    public static function requiresRareEyeItem(string $color): bool
    {
        return (bool) preg_match('/violet|purple|lavender|heterochrom|albino|pink|red eyes?/i', $color);
    }

    public function apply(Inventory $inventory, Character $character, array $details = []): void
    {
        $effect = $inventory->item->effect;
        abort_unless(self::supports($effect), 422, 'This item cannot be applied to a profile.');
        abort_if($character->status === 'deceased', 422, 'Items cannot be applied to deceased characters.');
        abort_if($inventory->quantity < 1, 422, 'This item is no longer in your inventory.');
        abort_if(CharacterItem::withTrashed()->where(['character_id' => $character->id, 'shop_item_id' => $inventory->shop_item_id])->exists(), 422, 'This item has already been applied to this character.');
        abort_if($effect === 'Male calico' && $character->sex !== 'male', 422, 'Male calico can only be applied to a tom.');
        abort_if($effect === 'Time freeze' && $character->is_frozen, 422, 'This character is already frozen.');

        $updates = ['care_updated_at' => now()];
        if (isset(self::PROFILE_FIELDS[$effect])) {
            $updates[self::PROFILE_FIELDS[$effect]] = true;
            $updates['traits'] = array_values(array_unique([...($character->traits ?? []), $effect]));
        } elseif ($effect === 'Rare eye color' || $effect === 'Disability') {
            $field = $effect === 'Rare eye color' ? 'eye_color' : 'disability';
            $value = trim((string) ($details[$field] ?? ''));
            if ($value === '' || mb_strlen($value) > ($field === 'eye_color' ? 80 : 255)) {
                throw ValidationException::withMessages([$field => 'Enter a valid '.$field.' before applying this item.']);
            }
            $updates[$field] = $value;
        } elseif ($effect === 'Outsider access') {
            $role = strtolower((string) ($details['outsider_role'] ?? ''));
            if (! in_array($role, ['kittypet', 'loner', 'rogue'], true)) {
                throw ValidationException::withMessages(['outsider_role' => 'Choose a Kittypet, Loner, or Rogue role.']);
            }
            $updates['allegiance'] = 'outsider';
            $updates['role'] = $role;
        } elseif ($effect === 'Time freeze') {
            $updates['is_frozen'] = true;
            $updates['frozen_reason'] = 'item';
        } else {
            $traits = $character->traits ?? [];
            $traits[] = trim(substr($effect, strlen('Rare trait:')));
            $updates['traits'] = array_values(array_unique($traits));
        }

        $character->forceFill($updates)->save();
        $inventory->decrement('quantity');
        CharacterItem::create(['character_id' => $character->id, 'shop_item_id' => $inventory->shop_item_id, 'applied_by' => $character->user_id, 'applied_at' => now()]);
    }
}