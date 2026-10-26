<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BranchController extends Controller
{
    public function index(): View
    {
        return view('admin.branches.index', [
            'branches' => Branch::query()->withCount(['users', 'orders'])->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $values = $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:branches,name'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:40'],
        ]);
        $branch = Branch::query()->create([
            ...$values,
            'code' => $this->generateCode(),
        ]);
        $this->recordAudit($request, 'branch_created', $branch, 'Cabang '.$branch->name.' dibuat.');

        return redirect()->route('admin.branches.index')->with('success', 'Cabang berhasil dibuat.');
    }

    public function update(Request $request, Branch $branch): RedirectResponse
    {
        $values = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('branches', 'name')->ignore($branch->id)],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:40'],
        ]);
        $branch->update($values);
        $this->recordAudit($request, 'branch_updated', $branch, 'Data cabang '.$branch->name.' diperbarui.');

        return redirect()->route('admin.branches.index')->with('success', 'Cabang berhasil diperbarui.');
    }

    public function destroy(Request $request, Branch $branch): RedirectResponse
    {
        abort_if($branch->users()->exists() || $branch->orders()->exists(), 422, 'Cabang yang masih dipakai pengguna atau PO tidak dapat dihapus.');

        $name = $branch->name;
        $id = $branch->id;
        $branch->delete();
        $this->recordAudit($request, 'branch_deleted', $branch, 'Cabang '.$name.' dihapus.', ['branch_id' => $id]);

        return redirect()->route('admin.branches.index')->with('success', 'Cabang berhasil dihapus.');
    }

    private function generateCode(): string
    {
        do {
            $code = 'CBG-'.Str::upper(Str::random(6));
        } while (Branch::query()->where('code', $code)->exists());

        return $code;
    }

    private function recordAudit(Request $request, string $event, Branch $branch, string $description, array $metadata = []): void
    {
        AuditLog::query()->create([
            'user_id' => $request->user()->id,
            'event' => $event,
            'subject_type' => 'branch',
            'subject_id' => (string) $branch->id,
            'description' => $description,
            'metadata' => $metadata,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
