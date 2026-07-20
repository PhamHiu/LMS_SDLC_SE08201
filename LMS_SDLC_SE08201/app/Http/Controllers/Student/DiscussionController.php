<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\DiscussionRequest;
use App\Models\Assignment;
use App\Services\DiscussionService;
use App\Services\Student\StudentAccessService;
use Illuminate\Http\RedirectResponse;

class DiscussionController extends Controller
{
    public function __construct(
        private readonly DiscussionService $discussionService,
        private readonly StudentAccessService $access,
    ) {
    }

    public function store(DiscussionRequest $request, Assignment $assignment): RedirectResponse
    {
        $this->access->authorizeAssignment($request->user(), $assignment);

        $this->discussionService->post(
            $request->user(),
            $assignment,
            $request->validated('comment'),
            $request->validated('parent_id'),
        );

        return back()->with('success', 'Đã đăng bình luận.');
    }
}
