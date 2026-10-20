<?php

namespace App\Support;

use Illuminate\Support\Str;

final class TerritoryMap
{
    public static function locations(): array
    {
        $places = [
            ['Rapid Falls', [[139, 20, 230, 47]], 'WindClan'],
            ['Marshland', [[324, 32, 406, 57]], 'RiverClan'],
            ['Mountains', [[580, 56, 674, 84]], 'outsider'],
            ['WindClan Camp', [[38, 74, 153, 103]], 'WindClan'],
            ['Twoleg Path', [
                [290, 76, 383, 102], [497, 213, 592, 240], [583, 249, 697, 276],
                [657, 347, 754, 385], [161, 450, 257, 480], [49, 620, 152, 649],
                [242, 631, 350, 664],
            ], 'outsider'],
            ['Placid River', [[250, 102, 354, 140]], 'RiverClan'],
            ['Pond', [[372, 104, 426, 133]], 'RiverClan'],
            ['RiverClan Camp', [[364, 166, 483, 198]], 'RiverClan'],
            ['Roaring River', [[519, 174, 630, 205]], 'RiverClan'],
            ['Moor', [[30, 146, 106, 176]], 'WindClan'],
            ['Leaping Boulders', [[209, 212, 377, 251]], 'WindClan'],
            ['Mist Beach', [[288, 275, 394, 308]], 'WindClan'],
            ['Mint Island', [[394, 316, 488, 347]], 'outsider'],
            ['Moose Island', [[447, 380, 545, 417]], 'outsider'],
            ['Mouse Island', [[485, 425, 592, 458]], 'outsider'],
            ['The Great Ledge', [[620, 278, 752, 307]], 'outsider'],
            ['Oak Forest', [[43, 333, 138, 362]], 'ThunderClan'],
            ['ThunderClan Camp', [[46, 538, 179, 573]], 'ThunderClan'],
            ['Marshes', [[432, 511, 509, 545]], 'ThunderClan'],
            ['Pine Forest', [[640, 518, 768, 561]], 'ShadowClan'],
            ['Leaping Rocks', [[488, 547, 621, 593]], 'ShadowClan'],
            ['Splashing River', [[566, 616, 696, 649]], 'ShadowClan'],
            ['Caves', [[354, 618, 448, 663]], 'ThunderClan'],
            ['ShadowClan Camp', [[376, 677, 517, 717]], 'ShadowClan'],
            ['Rock Bridge', [[616, 670, 738, 707]], 'ShadowClan'],
            ['Moonspring', [[124, 697, 253, 732]], 'outsider'],
        ];

        return array_map(fn (array $place) => [
            'name' => $place[0],
            'slug' => Str::slug($place[0]),
            'areas' => $place[1],
            'territory' => $place[2],
            'category' => str_ends_with($place[0], 'Camp') ? 'camp' : 'landmark',
        ], $places);
    }

    public static function forums(): array
    {
        return [
            'ThunderClan' => ['ThunderClan Camp', 'Twoleg Path', 'Oak Forest', 'Marshes', 'Caves'],
            'WindClan' => ['WindClan Camp', 'Rapid Falls', 'Moor', 'Leaping Boulders', 'Mist Beach'],
            'RiverClan' => ['RiverClan Camp', 'Twoleg Path', 'Marshland', 'Placid River', 'Pond', 'Roaring River'],
            'ShadowClan' => ['ShadowClan Camp', 'Twoleg Path', 'Pine Forest', 'Leaping Rocks', 'Splashing River', 'Rock Bridge'],
            'outsider' => ['Twoleg Path', 'Twoleg Dens', 'Mountains', 'Mint Island', 'Moose Island', 'Mouse Island', 'The Great Ledge', 'Moonspring'],
        ];
    }

    public static function forumSlug(string $territory, string $name): string
    {
        return Str::slug($name === 'Twoleg Path' && $territory !== 'outsider' ? $territory.' '.$name : $name);
    }
}
