<?php

namespace App\Http\Controllers;

use App\Models\Rule;
use App\Models\RuleCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminRulesController extends Controller
{
    public function index(): View
    {
        return view('admin.rules.index', [
            'categories' => RuleCategory::with(['rules' => fn ($query) => $query->orderBy('sort_order')->orderBy('id')])
                ->orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        RuleCategory::create($request->validate(['name' => ['required', 'string', 'max:120'], 'sort_order' => ['required', 'integer', 'min:0']]));

        return back()->with('status', 'Rule category created.');
    }

    public function updateCategory(Request $request, RuleCategory $category): RedirectResponse
    {
        $category->update($request->validate(['name' => ['required', 'string', 'max:120'], 'sort_order' => ['required', 'integer', 'min:0']]));

        return back()->with('status', 'Rule category updated.');
    }

    public function destroyCategory(RuleCategory $category): RedirectResponse
    {
        $category->delete();

        return back()->with('status', 'Rule category and its rules deleted.');
    }

    public function storeRule(Request $request): RedirectResponse
    {
        Rule::create($request->validate($this->ruleFields()));

        return back()->with('status', 'Rule created.');
    }

    public function updateRule(Request $request, Rule $rule): RedirectResponse
    {
        $rule->update($request->validate($this->ruleFields()));

        return back()->with('status', 'Rule updated.');
    }

    public function bulkUpdate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'categories' => ['nullable', 'array'],
            'categories.*.name' => ['required', 'string', 'max:120'],
            'categories.*.sort_order' => ['required', 'integer', 'min:0'],
            'rules' => ['nullable', 'array'],
            'rules.*.rule_category_id' => ['required', 'exists:rule_categories,id'],
            'rules.*.title' => ['required', 'string', 'max:120'],
            'rules.*.description' => ['required', 'string', 'max:5000'],
            'rules.*.sort_order' => ['required', 'integer', 'min:0'],
        ]);
        DB::transaction(function () use ($validated): void {
            foreach ($validated['categories'] ?? [] as $id => $fields) {
                RuleCategory::findOrFail($id)->update($fields);
            }
            foreach ($validated['rules'] ?? [] as $id => $fields) {
                Rule::findOrFail($id)->update($fields);
            }
        });

        return back()->with('status', 'Rule categories and entries updated.');
    }

    public function destroyRule(Rule $rule): RedirectResponse
    {
        $rule->delete();

        return back()->with('status', 'Rule deleted.');
    }

    private function ruleFields(): array
    {
        return [
            'rule_category_id' => ['required', 'exists:rule_categories,id'],
            'title' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:5000'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ];
    }
}