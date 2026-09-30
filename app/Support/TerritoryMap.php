<?php

namespace App\Support;

use Illuminate\Support\Str;

final class TerritoryMap
{
    public static function locations(): array
    {
        $places = [
            ['Rapid Falls', [[139, 20, 230, 47]]],
            ['Marshland', [[324, 32, 406, 57]]],
            ['Mountains', [[580, 56, 674, 84]]],
            ['WindClan Camp', [[38, 74, 153, 103]], 'camp'],
            ['Twoleg Path', [
                [290, 76, 383, 102], [497, 213, 592, 240], [583, 249, 697, 276],
                [657, 347, 754, 385], [161, 450, 257, 480], [49, 620, 152, 649],
                [242, 631, 350, 664],
            ]],
            ['Placid River', [[250, 102, 354, 140]]],
            ['Pond', [[372, 104, 426, 133]]],
            ['RiverClan Camp', [[364, 166, 483, 198]], 'camp'],
            ['Roaring River', [[519, 174, 630, 205]]],
            ['Moor', [[30, 146, 106, 176]]],
            ['Leaping Boulders', [[209, 212, 377, 251]]],
            ['Mist Beach', [[288, 275, 394, 308]]],
            ['Mint Island', [[394, 316, 488, 347]]],
            ['Moose Island', [[447, 380, 545, 417]]],
            ['Mouse Island', [[485, 425, 592, 458]]],
            ['The Great Ledge', [[620, 278, 752, 307]]],
            ['Oak Forest', [[43, 333, 138, 362]]],
            ['ThunderClan Camp', [[46, 538, 179, 573]], 'camp'],
            ['Marshes', [[432, 511, 509, 545]]],
            ['Pine Forest', [[640, 518, 768, 561]]],
            ['Leaping Rocks', [[488, 547, 621, 593]]],
            ['Splashing River', [[566, 616, 696, 649]]],
            ['Caves', [[354, 618, 448, 663]]],
            ['ShadowClan Camp', [[376, 677, 517, 717]], 'camp'],
            ['Rock Bridge', [[616, 670, 738, 707]]],
            ['Moonspring', [[124, 697, 253, 732]]],
        ];

        return array_map(fn (array $place) => [
            'name' => $place[0],
            'slug' => Str::slug($place[0]),
            'areas' => $place[1],
            'category' => $place[2] ?? 'landmark',
        ], $places);
    }
}