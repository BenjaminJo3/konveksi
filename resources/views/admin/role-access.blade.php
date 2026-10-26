@extends('layouts.app')

@section('title', 'Role & Akses')
@section('heading', 'Role & Akses')
@section('breadcrumb', 'Administrasi / Role & Akses')

@section('content')
    <div class="mb-6">
        <p class="text-xs font-semibold uppercase tracking-wider text-emerald-700">Kontrol akses berbasis peran</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Matriks Peran & Hak Akses (RBAC)</h2>
        <p class="mt-1 text-sm text-slate-500">Hak akses di bawah ini diterapkan langsung pada menu dan tindakan di server.</p>
    </div>

    @if ($canEdit)
        <div class="mb-4 flex flex-col justify-between gap-3 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 sm:flex-row sm:items-center">
            <p class="text-xs leading-5 text-blue-900">Super Admin dapat mengatur akses role lain. Hak akses Super Admin selalu aktif dan tidak dapat dicabut.</p>
            <span class="shrink-0 rounded-full bg-blue-100 px-3 py-1 text-[10px] font-bold uppercase tracking-wide text-blue-800">Mode edit</span>
        </div>
    @else
        <div class="mb-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-600">Matriks hanya dapat diubah oleh Super Admin.</div>
    @endif

    <form method="POST" action="{{ route('admin.role-access.update') }}">
        @csrf
        @method('PUT')
        <input type="hidden" name="permissions[_present]" value="1">
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[980px] text-left text-xs">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="sticky left-0 z-10 min-w-72 bg-slate-50 px-5 py-4 text-[10px] font-bold uppercase tracking-wide text-slate-500">Hak akses</th>
                            @foreach ($roles as $role)
                                <th class="min-w-36 px-3 py-4 text-center">
                                    <span class="block text-[11px] font-bold text-slate-800">{{ $role['label'] }}</span>
                                    <span class="mt-1 block text-[9px] font-normal text-slate-400">{{ $role['description'] }}</span>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($permissions as $permission)
                            <tr>
                                <th class="sticky left-0 z-10 bg-white px-5 py-4 text-left font-semibold leading-5 text-slate-700">{{ $permission['label'] }}</th>
                                @foreach ($roles as $role)
                                    <td class="px-3 py-4 text-center">
                                        @if ($role['key'] === 'super_admin')
                                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-blue-50 text-sm font-bold text-blue-700" aria-label="Selalu diizinkan">✓</span>
                                        @elseif ($canEdit)
                                            <label class="inline-flex min-h-10 min-w-10 cursor-pointer items-center justify-center" aria-label="{{ $role['label'] }}: {{ $permission['label'] }}">
                                                <input type="checkbox" name="permissions[{{ $permission['key'] }}][{{ $role['key'] }}]" value="1" @checked($permission[$role['key']]) class="!h-5 !w-5 !rounded !border-slate-300 !p-0 text-blue-700 focus:ring-blue-500">
                                            </label>
                                        @elseif ($permission[$role['key']])
                                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-emerald-50 text-sm font-bold text-emerald-700" aria-label="Diizinkan">✓</span>
                                        @else
                                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-slate-50 text-xs text-slate-300" aria-label="Tidak diizinkan">—</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
        @if ($canEdit)
            <div class="mt-4 flex justify-end">
                <button class="w-full rounded-xl bg-blue-700 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-800 sm:w-auto">Simpan hak akses</button>
            </div>
        @endif
    </form>
    <p class="mt-4 text-[11px] leading-5 text-slate-500">Hak akses diubah akan berlaku pada permintaan berikutnya. Pastikan setiap role hanya memperoleh akses yang diperlukan untuk tugasnya.</p>
@endsection
