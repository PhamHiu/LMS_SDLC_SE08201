<?php

namespace App\Services\Admin;

use App\Models\Major;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MajorService
{
    public function paginate(?string $search = null): LengthAwarePaginator
    {
        return Major::withCount(['users', 'subjects', 'classes'])
            ->when($search, fn ($q) => $q->where('major_name', 'like', "%{$search}%")
                ->orWhere('major_code', 'like', "%{$search}%"))
            ->orderBy('major_code')
            ->paginate(10)
            ->withQueryString();
    }

    public function create(array $data): Major
    {
        return Major::create($data);
    }

    public function update(Major $major, array $data): Major
    {
        $major->update($data);

        return $major;
    }

    public function delete(Major $major): void
    {
        $major->delete();
    }
}
