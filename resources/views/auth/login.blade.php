<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk · Konveksi Gallery Anak Rantau</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f5f5f5] antialiased">
    <main class="flex min-h-screen flex-col items-center justify-center px-4 py-10 sm:px-6">
        <div class="w-full max-w-[440px]">
            <header class="mb-6 px-1">
                <a href="{{ route('login') }}" class="inline-flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-900 text-xs font-bold tracking-[0.08em] text-white">GAR</span>
                    <span>
                        <span class="brand-wordmark block text-lg font-semibold leading-tight text-slate-900">Konveksi</span>
                        <span class="brand-wordmark block text-sm font-medium leading-tight text-slate-700">Gallery Anak Rantau</span>
                        <span class="mt-1 block text-xs text-slate-500">Sistem manajemen produksi konveksi</span>
                    </span>
                </a>
            </header>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xl shadow-slate-900/[0.04] sm:p-8">
                <div class="mb-6">
                    <p class="text-xs font-semibold text-slate-500">Workspace operasional</p>
                    <h1 class="mt-1.5 text-2xl font-bold tracking-tight text-slate-900">Masuk ke sistem</h1>
                    <p class="mt-2 text-sm leading-6 text-slate-500">We Know How IT Works ...</p>
                </div>

                <form class="space-y-5" method="POST" action="{{ route('login.store') }}">
                    @csrf
                    <div>
                        <label for="email">Alamat email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus placeholder="nama@perusahaan.com">
                        @error('email')<p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="password">Kata sandi</label>
                        <input id="password" name="password" type="password" autocomplete="current-password" required placeholder="Masukkan kata sandi">
                        @error('password')<p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <label class="flex items-center gap-2 !mb-0 text-xs font-medium text-slate-500">
                        <input name="remember" type="checkbox" value="1" class="h-4 w-4 !w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900">
                        Ingat perangkat ini
                    </label>
                    <button type="submit" class="flex w-full items-center justify-center rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 focus:outline-none focus:ring-4 focus:ring-slate-900/10">
                        Masuk ke workspace
                    </button>
                </form>

                <div class="mt-6 border-t border-slate-100 pt-5">
                    <p class="text-xs leading-5 text-slate-500">Akses dibatasi sesuai peran dan tanggung jawab operasional Anda.</p>
                    @if (app()->isLocal())
                        @php($demoPassword = config('app.demo_password'))
                        <div class="mt-5 border-t border-slate-100 pt-5">
                            <h2 class="text-sm font-bold text-slate-800">Akun Pengujian Bawaan (Klik untuk Isi Cepat)</h2>
                            <div class="mt-3 space-y-2">
                                @foreach ([
                                    ['Admin Workspace', 'admin@konveksi.test', 'Admin'],
                                    ['Super Admin', 'superadmin@konveksi.test', 'Super Admin'],
                                    ['Staf Toko Mydent', 'mydent@konveksi.test', 'Mydent'],
                                    ['Petugas Warehouse', 'warehouse@konveksi.test', 'Warehouse'],
                                    ['Mandor Gunung Karang', 'gunungkarang@konveksi.test', 'Gunung Karang'],
                                    ['Mandor Cibentang', 'cibentang@konveksi.test', 'Cibentang'],
                                ] as [$name, $email, $role])
                                    <button type="button" data-demo-email="{{ $email }}" data-demo-password="{{ $demoPassword }}" class="flex w-full items-center justify-between gap-3 rounded-xl border border-slate-200 px-3 py-2.5 text-left transition hover:border-slate-400 hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-900/5">
                                        <span class="min-w-0">
                                            <span class="block truncate text-xs font-semibold text-slate-800">{{ $name }}</span>
                                            <span class="mt-0.5 block truncate font-mono text-[11px] text-slate-500">{{ $email }}</span>
                                        </span>
                                        <span class="shrink-0 rounded-md bg-slate-700 px-2 py-1 text-[10px] font-semibold text-white">{{ $role }}</span>
                                    </button>
                                @endforeach
                            </div>
                            <p class="mt-3 text-xs text-slate-500">Kata sandi semua akun: <code class="rounded-md bg-slate-100 px-1.5 py-0.5 font-mono text-slate-700">{{ $demoPassword }}</code></p>
                        </div>
                    @endif
                </div>
            </section>
            <p class="mt-5 text-center text-[11px] text-slate-400">Konveksi Gallery Anak Rantau · Kontrol produksi terpadu</p>
        </div>
    </main>
</body>
</html>
