<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\DiscussionRequest;
use App\Models\Assignment;
use App\Models\Discussion;
use App\Services\DiscussionService;
use App\Services\Teacher\AssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DiscussionController extends Controller
{
    public function __construct(
        private readonly DiscussionService $discussionService,
        private readonly AssignmentService $assignmentService,
    ) {
    }

    public function store(DiscussionRequest $request, Assignment $assignment): RedirectResponse
    {
        $this->assignmentService->authorizeAssignment($request->user(), $assignment);

        $this->discussionService->post(
            $request->user(),
            $assignment,
            $request->validated('comment'),
            $request->validated('parent_id'),
        );

        return back()->with('success', 'Đã đăng bình luận.');
    }

    public function destroy(Request $request, Discussion $discussion): RedirectResponse
    {
        $this->discussionService->delete($request->user(), $discussion);

        return back()->with('success', 'Đã xóa bình luận.');
    }
}
