<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\WorkflowEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:200'],
            'event' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);
        $search = trim($filters['q'] ?? '');
        $event = $filters['event'] ?? '';
        $from = $filters['from'] ?? null;
        $to = $filters['to'] ?? null;

        $systemLogs = AuditLog::query()->with('user')->latest();
        $workflowLogs = WorkflowEntry::query()->with(['user', 'order'])->latest();

        if ($search !== '') {
            $systemLogs->where(fn (Builder $query) => $query
                ->where('event', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhere('subject_id', 'like', "%{$search}%")
                ->orWhereHas('user', fn (Builder $userQuery) => $userQuery
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")));
            $workflowLogs->where(fn (Builder $query) => $query
                ->where('action', 'like', "%{$search}%")
                ->orWhere('reference', 'like', "%{$search}%")
                ->orWhere('notes', 'like', "%{$search}%")
                ->orWhereHas('user', fn (Builder $userQuery) => $userQuery
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"))
                ->orWhereHas('order', fn (Builder $orderQuery) => $orderQuery
                    ->where('po_number', 'like', "%{$search}%")
                    ->orWhere('product_name', 'like', "%{$search}%")));
        }

        if ($event !== '') {
            $systemLogs->where('event', $event);
            $workflowLogs->where('action', $event);
        }

        if ($from) {
            $systemLogs->whereDate('created_at', '>=', $from);
            $workflowLogs->whereDate('created_at', '>=', $from);
        }

        if ($to) {
            $systemLogs->whereDate('created_at', '<=', $to);
            $workflowLogs->whereDate('created_at', '<=', $to);
        }

        return view('admin.audit-logs', [
            'systemLogs' => $systemLogs->paginate(15, ['*'], 'system_page')->withQueryString(),
            'workflowLogs' => $workflowLogs->paginate(20, ['*'], 'workflow_page')->withQueryString(),
            'events' => AuditLog::query()->distinct()->orderBy('event')->pluck('event')
                ->merge(WorkflowEntry::query()->distinct()->orderBy('action')->pluck('action'))
                ->unique()
                ->sort()
                ->values(),
            'filters' => compact('search', 'event', 'from', 'to'),
        ]);
    }
}
