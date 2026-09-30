<?php

namespace App\Http\Controllers;

use App\Models\ForumPost;
use App\Models\ModerationReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ModerationController extends Controller
{
    public function reportPost(ForumPost $post, Request $request): RedirectResponse
    {
        abort_unless($request->user(), 403);
        $validated = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:2000']]);
        ModerationReport::firstOrCreate(['user_id' => $request->user()->id, 'reportable_type' => ForumPost::class, 'reportable_id' => $post->id, 'status' => 'open'], ['reason' => $validated['reason']]);

        return back()->with('status', 'Your report has been sent to the staff team.');
    }

    public function index(Request $request): View
    {
        abort_unless($request->user()->isStaff(), 403);

        return view('staff.reports', ['reports' => ModerationReport::with(['reporter', 'reportable'])->where('status', 'open')->latest()->paginate(20)]);
    }

    public function resolve(ModerationReport $report, Request $request): RedirectResponse
    {
        abort_unless($request->user()->isStaff(), 403);
        $validated = $request->validate(['status' => ['required', 'in:resolved,dismissed'], 'resolution' => ['required', 'string', 'min:3', 'max:2000']]);
        $report->update([...$validated, 'reviewed_by' => $request->user()->id]);

        return back()->with('status', 'Report updated.');
    }

    public function bulkResolve(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isStaff(), 403);
        $validated = $request->validate([
            'reports' => ['required', 'array'],
            'reports.*.status' => ['nullable', 'in:resolved,dismissed'],
            'reports.*.resolution' => ['nullable', 'string', 'max:2000'],
        ]);
        DB::transaction(function () use ($validated, $request): void {
            foreach ($validated['reports'] as $id => $fields) {
                if (empty($fields['status'])) {
                    continue;
                }
                if (mb_strlen(trim((string) ($fields['resolution'] ?? ''))) < 3) {
                    throw ValidationException::withMessages(['reports.'.$id.'.resolution' => 'Enter a resolution note of at least 3 characters.']);
                }
                ModerationReport::findOrFail($id)->update(['status' => $fields['status'], 'resolution' => $fields['resolution'], 'reviewed_by' => $request->user()->id]);
            }
        });

        return back()->with('status', 'Selected reports updated.');
    }
}
