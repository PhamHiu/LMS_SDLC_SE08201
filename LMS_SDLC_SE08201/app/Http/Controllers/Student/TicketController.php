<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\BtecSupportTicket;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function index(Request $request): View
    {
        $student = $request->user();

        $tickets = BtecSupportTicket::with(['classRoom.subject', 'assignedTeacher', 'resolvedByUser'])
            ->where('user_id', $student->user_id)
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('student.tickets.index', compact('tickets'));
    }
}
