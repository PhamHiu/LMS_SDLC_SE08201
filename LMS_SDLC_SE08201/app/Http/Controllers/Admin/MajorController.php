<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MajorRequest;
use App\Models\Major;
use App\Services\Admin\MajorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MajorController extends Controller
{
    public function __construct(private readonly MajorService $majorService)
    {
    }

    public function index(Request $request): View
    {
        return view('admin.majors.index', [
            'majors' => $this->majorService->paginate($request->query('search')),
            'search' => $request->query('search'),
        ]);
    }

    public function create(): View
    {
        return view('admin.majors.form', ['major' => null]);
    }

    public function store(MajorRequest $request): RedirectResponse
    {
        $this->majorService->create($request->validated());

        return redirect()->route('admin.majors.index')->with('success', 'Tạo chuyên ngành thành công.');
    }

    public function edit(Major $major): View
    {
        return view('admin.majors.form', ['major' => $major]);
    }

    public function update(MajorRequest $request, Major $major): RedirectResponse
    {
        $this->majorService->update($major, $request->validated());

        return redirect()->route('admin.majors.index')->with('success', 'Cập nhật chuyên ngành thành công.');
    }

    public function destroy(Major $major): RedirectResponse
    {
        $this->majorService->delete($major);

        return back()->with('success', 'Đã xóa chuyên ngành.');
    }
}
