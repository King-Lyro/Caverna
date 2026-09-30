<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Models\CharacterItem;
use App\Models\Inventory;
use App\Models\ShopItem;
use App\Services\CharacterEnhancementService;
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

        DB::transaction(function () use ($inventory, $character, $request) {
            $inventory = Inventory::query()->whereKey($inventory->id)->lockForUpdate()->firstOrFail();
            abort_if($inventory->quantity < 1, 422, 'This item is no longer in your inventory.');
            $effect = $inventory->item->effect;
            if (CharacterEnhancementService::supports($effect)) {
                app(CharacterEnhancementService::class)->apply($inventory, $character, $request->only('eye_color', 'disability', 'outsider_role'));

                return;
            }
            abort_if(in_array($effect, ['Energy restoration', 'Energy return', 'Energy recover'], true) && ($character->status === 'deceased' || $character->is_frozen), 422, 'Energy items cannot be used on deceased or frozen characters.');
            abort_unless(in_array($effect, ['Energy restoration', 'Energy return', 'Energy recover', 'Treat fleas', 'Treat ticks', 'Treat sickness'], true), 422, 'This item cannot be used here.');
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
            } else {
                $ailments = array_values(array_filter($character->ailments ?? [], fn ($ailment) => strtolower($ailment) !== strtolower(str_replace('Treat ', '', $effect))));
                $updates['ailments'] = $ailments;
                $updates['health_status'] = empty($ailments) ? 'healthy' : $character->health_status;
            }
            $character->forceFill($updates)->save();
        });

        return back()->with('status', $inventory->item->name.' was used.');
    }
}
