@extends('layouts.app')

@section('title', 'Kelola Pengguna')
@section('heading', 'Kelola Pengguna')
@section('breadcrumb', 'Administrasi / Kelola Pengguna')

@section('content')
    <div class="mb-6">
        <p class="text-xs font-semibold uppercase tracking-wider text-emerald-700">Akun dan penugasan</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Kelola Pengguna</h2>
        <p class="mt-1 text-sm text-slate-500">Buat akun, atur peran/cabang, reset kata sandi, dan kelola pengguna aplikasi.</p>
    </div>

    <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 sm:p-6">
        <h3 class="text-sm font-bold text-slate-900">Tambah pengguna</h3>
        <form method="POST" action="{{ route('admin.users.store') }}" class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @csrf
            <div><label for="new-name">Nama lengkap</label><input id="new-name" name="name" value="{{ old('name') }}" required maxlength="120"></div>
            <div><label for="new-email">Email</label><input id="new-email" type="email" name="email" value="{{ old('email') }}" required></div>
            <div><label for="new-role">Peran</label><select id="new-role" name="role" required>@foreach ($roles as $role)<option value="{{ $role }}">{{ ['super_admin' => 'Super Admin', 'admin' => 'Admin Workspace', 'mydent' => 'Toko Mydent', 'warehouse' => 'Gudang', 'gunung_karang' => 'Gunung Karang', 'cibentang' => 'Cibentang'][$role] }}</option>@endforeach</select></div>
            <div><label for="new-branch">Cabang (opsional)</label><select id="new-branch" name="branch_id"><option value="">Belum ditentukan</option>@foreach ($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach</select></div>
            <div><label for="new-password">Kata sandi awal</label><input id="new-password" type="password" name="password" required minlength="8" autocomplete="new-password"></div>
            <div><label for="new-password-confirmation">Konfirmasi kata sandi</label><input id="new-password-confirmation" type="password" name="password_confirmation" required minlength="8" autocomplete="new-password"></div>
            <div class="sm:col-span-2 xl:col-span-3"><button class="rounded-xl bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Simpan pengguna</button></div>
        </form>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="border-b border-slate-100 px-5 py-4"><h3 class="text-sm font-bold text-slate-900">Daftar akun</h3><p class="mt-1 text-xs text-slate-500">{{ $users->total() }} pengguna</p></div>
        @if ($users->isEmpty())
            <p class="px-5 py-10 text-center text-xs text-slate-400">Belum ada akun pengguna.</p>
        @else
            <div class="divide-y divide-slate-100">
                @foreach ($users as $user)
                    <details class="group px-4 py-4 sm:px-5">
                        <summary class="flex cursor-pointer list-none flex-wrap items-center justify-between gap-3">
                            <span class="min-w-0"><span class="block truncate text-xs font-bold text-slate-800">{{ $user->name }}</span><span class="mt-1 block truncate text-[10px] text-slate-500">{{ $user->email }} · {{ $user->roleLabel() }} · {{ $user->branch?->name ?? 'Tanpa cabang' }}</span></span>
                            <span class="rounded-lg border border-slate-200 px-3 py-2 text-[10px] font-semibold text-slate-600 group-open:bg-slate-50">Edit akun</span>
                        </summary>
                        <div class="mt-4 grid gap-4 border-t border-slate-100 pt-4 lg:grid-cols-[minmax(0,1fr)_auto]">
                            <form method="POST" action="{{ route('admin.users.update', $user) }}" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                @csrf
                                @method('PUT')
                                <div><label for="name-{{ $user->id }}">Nama</label><input id="name-{{ $user->id }}" name="name" value="{{ $user->name }}" required></div>
                                <div><label for="email-{{ $user->id }}">Email</label><input id="email-{{ $user->id }}" type="email" name="email" value="{{ $user->email }}" required></div>
                                <div><label for="role-{{ $user->id }}">Peran</label><select id="role-{{ $user->id }}" name="role">@foreach ($roles as $role)<option value="{{ $role }}" @selected($user->role === $role)>{{ ['super_admin' => 'Super Admin', 'admin' => 'Admin Workspace', 'mydent' => 'Toko Mydent', 'warehouse' => 'Gudang', 'gunung_karang' => 'Gunung Karang', 'cibentang' => 'Cibentang'][$role] }}</option>@endforeach</select></div>
                                <div><label for="branch-{{ $user->id }}">Cabang</label><select id="branch-{{ $user->id }}" name="branch_id"><option value="">Belum ditentukan</option>@foreach ($branches as $branch)<option value="{{ $branch->id }}" @selected($user->branch_id === $branch->id)>{{ $branch->name }}</option>@endforeach</select></div>
                                <div><label for="password-{{ $user->id }}">Kata sandi baru (opsional)</label><input id="password-{{ $user->id }}" type="password" name="password" minlength="8" autocomplete="new-password"></div>
                                <div><label for="password-confirmation-{{ $user->id }}">Konfirmasi kata sandi baru</label><input id="password-confirmation-{{ $user->id }}" type="password" name="password_confirmation" minlength="8" autocomplete="new-password"></div>
                                <div class="sm:col-span-2 xl:col-span-3"><button class="rounded-xl bg-blue-700 px-4 py-2 text-xs font-semibold text-white hover:bg-blue-800">Simpan perubahan</button></div>
                            </form>
                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Hapus akun {{ $user->email }}?')" class="self-end">
                                @csrf
                                @method('DELETE')
                                <button class="rounded-xl border border-rose-200 px-4 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-50" @disabled(auth()->id() === $user->id)>Hapus akun</button>
                            </form>
                        </div>
                    </details>
                @endforeach
            </div>
            @if ($users->hasPages())<div class="border-t border-slate-100 px-5 py-4">{{ $users->links() }}</div>@endif
        @endif
    </section>
@endsection
