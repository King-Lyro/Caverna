<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class ContentController extends Controller
{
    public function show(string $page): View
    {
        if (in_array($page, ['privacy', 'contact'], true)) {
            return view('content.page', ['page' => array_merge(trans('site.pages.'.$page), ['sections' => []])]);
        }

        abort_unless(array_key_exists($page, $this->pages()), 404);
        $pageData = $this->pages()[$page];
        $localized = trans('site.pages.'.$page);
        if (is_array($localized)) {
            $pageData = array_merge($pageData, $localized);
        }

        return view('content.page', ['page' => $pageData]);
    }

    private function pages(): array
    {
        return [
            'rules' => ['eyebrow' => 'Before you enter', 'title' => "The rules of\nCavernas.", 'intro' => 'A clear set of expectations keeps the world generous, collaborative, and easy to join.', 'sections' => [
                ['heading' => 'Write with care', 'body' => 'Keep IC posts at least 70 words, respect the people behind the characters, and leave room for other writers to contribute.'],
                ['heading' => 'Let stories breathe', 'body' => 'Characters can change, fail, and surprise one another. Staff are here to protect the shared world, not to dictate every story.'],
            ]],
            'guide' => ['eyebrow' => 'Your first path', 'title' => "A guide for\nnew paws.", 'intro' => 'Start with the rules, choose an allegiance, then build the character you want to follow through the seasons.', 'sections' => [
                ['heading' => '1. Read the world', 'body' => 'Learn the four allegiances and the territory they share before creating your first character.'],
                ['heading' => '2. Make a character', 'body' => 'Your first three non-adopted characters are free. Give each one enough history to make their next choice interesting.'],
            ]],
            'clans' => ['eyebrow' => 'Four allegiances', 'title' => "Choose the path\nthat calls.", 'intro' => 'Each clan carries a different relationship with the land. Allegiance shapes where your character belongs and how they spend their energy.', 'sections' => [
                ['heading' => 'ThunderClan', 'body' => 'Steady hearts, open clearings, and a long memory for those who stand beside them.'],
                ['heading' => 'RiverClan', 'body' => 'A life shaped by water, patience, and the glittering edges of change.'],
                ['heading' => 'ShadowClan', 'body' => 'The pines hold their secrets close. ShadowClan values resilience and quiet observation.'],
                ['heading' => 'WindClan', 'body' => 'Wide ground and quick feet. WindClan knows the sky is never as far away as it looks.'],
            ]],
            'map' => ['eyebrow' => 'The territory', 'title' => "A world with\nroom to wander.", 'intro' => 'The map is not a border. It is an invitation: paths cross, seasons shift, and every place remembers who passed through.', 'sections' => [
                ['heading' => 'The hollow', 'body' => 'A central meeting place beneath a pale moon, where news travels quickly.'],
                ['heading' => 'The border paths', 'body' => 'Shared ground where the four allegiances can meet, trade stories, or make trouble.'],
            ]],
            'outsiders' => ['eyebrow' => 'Beyond the clans', 'title' => "The roads less\ntravelled.", 'intro' => 'Kittypets, loners, and rogues live outside the four allegiances. Their paths are available through the site shop and staff-approved play.', 'sections' => [
                ['heading' => 'A different beginning', 'body' => 'Outsider characters bring a view of Cavernas shaped by places beyond clan territory.'],
                ['heading' => 'Join the story', 'body' => 'Read the guide first, then watch the shop for the items that open these character paths.'],
            ]],
        ];
    }
}
