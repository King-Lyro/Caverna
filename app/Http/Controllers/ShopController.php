<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Models\CharacterItem;
use App\Models\Inventory;
use App\Models\ShopItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ShopController extends Controller
{
    public function index(): View
    {
        return view('shop.index', ['items' => ShopItem::where('is_available', true)->orderBy('cost')->get()]);
    }

    public function inventory(Request $request): View
    {
        return view('shop.inventory', [
            'items' => Inventory::with('item')->where('user_id', $request->user()->id)->where('quantity', '>', 0)->get(),
            'characters' => Character::where('user_id', $request->user()->id)->where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function purchase(ShopItem $item, Request $request): RedirectResponse
    {
        abort_unless($request->user()?->status === 'approved' || $request->user()?->isStaff(), 403);
        abort_unless($item->is_available, 404);

        DB::transaction(function () use ($item, $request) {
            $balance = (int) DB::table('cricket_ledger')->where('user_id', $request->user()->id)->lockForUpdate()->sum('amount');
            abort_if($balance < $item->cost, 422, 'You do not have enough crickets for this item.');
            DB::table('cricket_ledger')->insert(['user_id' => $request->user()->id, 'amount' => -$item->cost, 'type' => 'shop_purchase', 'description' => 'Purchased '.$item->name, 'reference_type' => ShopItem::class, 'reference_id' => $item->id, 'created_at' => now(), 'updated_at' => now()]);
            $inventory = Inventory::query()
                ->where('user_id', $request->user()->id)
                ->where('shop_item_id', $item->id)
                ->lockForUpdate()
                ->first();
            if ($inventory) {
                $inventory->increment('quantity');
            } else {
                Inventory::create(['user_id' => $request->user()->id, 'shop_item_id' => $item->id, 'quantity' => 1]);
            }
        });

        return back()->with('status', $item->name.' was added to your inventory.');
    }

    public function useItem(Inventory $inventory, Request $request): RedirectResponse
    {
        abort_unless($inventory->user_id === $request->user()->id, 403);
        $validated = $request->validate(['character_id' => ['required', 'integer', 'exists:characters,id']]);
        $character = Character::where('user_id', $request->user()->id)->findOrFail($validated['character_id']);

        DB::transaction(function () use ($inventory, $character) {
            $inventory = Inventory::query()->whereKey($inventory->id)->lockForUpdate()->firstOrFail();
            abort_if($inventory->quantity < 1, 422, 'This item is no longer in your inventory.');
            $effect = $inventory->item->effect;
            abort_if(CharacterItem::where(['character_id' => $character->id, 'shop_item_id' => $inventory->shop_item_id])->exists(), 422, 'This item has already been applied to this character.');
            $specialEffects = ['Rare eye color', 'Disability', 'Male calico', 'Chimera/mosaicism', 'Karpati/Roan/Salmiak', 'White sepia', 'Albino', 'Purebred', 'Outsider access'];
            abort_unless(in_array($effect, ['Energy restoration', 'Energy return', 'Energy recover'], true) || str_starts_with($effect, 'Rare trait:') || in_array($effect, $specialEffects, true) || in_array($effect, ['Treat fleas', 'Treat ticks', 'Treat sickness'], true), 422, 'This item cannot be used here.');
            $inventory->decrement('quantity');
            $updates = ['care_updated_at' => now()];
            if (in_array($effect, ['Energy restoration', 'Energy return'], true)) {
                $updates['energy'] = min(100, $character->energy + ($effect === 'Energy return' ? 10 : 20));
                $updates['status'] = 'active';
                $updates['inactive_at'] = null;
            } elseif ($effect === 'Energy recover') {
                $updates['energy'] = 100;
                $updates['status'] = 'active';
                $updates['inactive_at'] = null;
            } elseif (str_starts_with($effect, 'Rare trait:')) {
                $traits = $character->traits ?? [];
                $traits[] = trim(str_replace('Rare trait:', '', $effect));
                $updates['traits'] = array_values(array_unique($traits));
            } elseif (in_array($effect, ['Disability', 'Male calico', 'Chimera/mosaicism', 'Karpati/Roan/Salmiak', 'White sepia', 'Albino', 'Purebred'], true)) {
                $profileFields = [
                    'Male calico' => 'male_calico',
                    'Chimera/mosaicism' => 'chimera_mosaicism',
                    'Karpati/Roan/Salmiak' => 'karpati_roan_salmiak',
                    'White sepia' => 'white_sepia',
                    'Albino' => 'albino',
                    'Purebred' => 'purebred',
                ];
                if ($effect === 'Disability') {
                    $updates['disability'] = 'Applied disability item; staff may refine details.';
                } else {
                    $updates[$profileFields[$effect]] = true;
                    $traits = $character->traits ?? [];
                    $updates['traits'] = array_values(array_unique([...$traits, $effect]));
                }
            } elseif ($effect === 'Rare eye color') {
                $updates['eye_color'] = 'Rare color (item applied)';
            } elseif ($effect === 'Outsider access') {
                $updates['allegiance'] = 'outsider';
            } else {
                $ailments = array_values(array_filter($character->ailments ?? [], fn ($ailment) => strtolower($ailment) !== strtolower(str_replace('Treat ', '', $effect))));
                $updates['ailments'] = $ailments;
                $updates['health_status'] = empty($ailments) ? 'healthy' : $character->health_status;
            }
            $character->forceFill($updates)->save();
            if (str_starts_with($effect, 'Rare trait:') || in_array($effect, ['Outsider access', 'Rare eye color', 'Disability', 'Male calico', 'Chimera/mosaicism', 'Karpati/Roan/Salmiak', 'White sepia', 'Albino', 'Purebred'], true) || str_starts_with($effect, 'Energy')) {
                CharacterItem::create(['character_id' => $character->id, 'shop_item_id' => $inventory->shop_item_id, 'applied_by' => $character->user_id, 'applied_at' => now()]);
            }
        });

        return back()->with('status', $inventory->item->name.' restored 20 energy.');
    }
}
