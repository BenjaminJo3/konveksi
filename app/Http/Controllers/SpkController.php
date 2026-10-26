<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ProductionOrderGroup;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class SpkController extends Controller
{
    public function index(): View
    {
        return view('spk.index', [
            'groups' => ProductionOrderGroup::query()
                ->whereHas('orders', fn ($query) => $query->whereNotNull('spk_quantity'))
                ->with(['creator', 'orders', 'signer'])
                ->orderByDesc('po_date')
                ->paginate(20),
        ]);
    }

    public function show(ProductionOrderGroup $group): View
    {
        $orders = $this->spkOrders($group);
        $signatureDataUri = $this->signatureDataUri($group);

        return view('spk.show', [
            'group' => $group->load(['creator', 'signer']),
            'orders' => $orders,
            'spkNumber' => $this->numberFor($group),
            'signatureDataUri' => $signatureDataUri,
            'canSign' => auth()->user()->canPermission('spk.sign'),
        ]);
    }

    public function pdf(ProductionOrderGroup $group): Response
    {
        $orders = $this->spkOrders($group);

        return Pdf::loadView('spk.pdf', [
            'group' => $group->load(['creator', 'signer']),
            'orders' => $orders,
            'spkNumber' => $this->numberFor($group),
            'totalQuantity' => $orders->sum('spk_quantity'),
            'signatureDataUri' => $this->signatureDataUri($group),
        ])->setPaper('a4', 'portrait')
            ->download('SPK-'.Str::slug($group->po_number, '-').'.pdf');
    }

    public function sign(Request $request, ProductionOrderGroup $group): RedirectResponse
    {
        abort_unless($request->user()->canPermission('spk.sign'), 403);
        $orders = $this->spkOrders($group);
        $values = $request->validate([
            'signature_image' => ['nullable', 'image', 'mimes:jpg,jpeg', 'dimensions:max_width=3000,max_height=1500', 'max:2048'],
            'signature_data' => ['nullable', 'string', 'max:1500000'],
        ]);
        abort_if(
            ! $request->hasFile('signature_image') && blank($values['signature_data'] ?? null),
            422,
            'Tanda tangan diperlukan. Silakan tanda tangani di layar atau unggah JPG/PNG.',
        );

        DB::transaction(function () use ($request, $group, $values, $orders): void {
            $lockedGroup = ProductionOrderGroup::query()->lockForUpdate()->findOrFail($group->id);
            abort_if($lockedGroup->spk_signature_path !== null, 409, 'SPK ini sudah ditandatangani dan dikunci.');
            abort_if($orders->isEmpty(), 422, 'SPK tidak memiliki target kerja yang valid.');

            $signaturePath = $request->hasFile('signature_image')
                ? $request->file('signature_image')->store('spk-signatures', 'local')
                : $this->storeCanvasSignature($values['signature_data']);

            if (! $signaturePath) {
                throw new \RuntimeException('Berkas tanda tangan tidak dapat disimpan.');
            }

            $lockedGroup->update([
                'spk_signature_path' => $signaturePath,
                'spk_signed_by' => $request->user()->id,
                'spk_signed_at' => now(),
            ]);

            AuditLog::query()->create([
                'user_id' => $request->user()->id,
                'event' => 'spk_signed',
                'subject_type' => 'production_order_group',
                'subject_id' => (string) $lockedGroup->id,
                'description' => 'SPK '.$this->numberFor($lockedGroup).' ditandatangani.',
                'metadata' => [
                    'po_number' => $lockedGroup->po_number,
                    'signature_path' => $signaturePath,
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        return redirect()
            ->route('spk.show', $group)
            ->with('success', 'Tanda tangan SPK berhasil disimpan.');
    }

    private function spkOrders(ProductionOrderGroup $group)
    {
        $orders = $group->orders()->whereNotNull('spk_quantity')->get();
        abort_if($orders->isEmpty(), 404, 'SPK belum diterbitkan untuk PO ini.');

        return $orders;
    }

    private function numberFor(ProductionOrderGroup $group): string
    {
        return 'SPK-'.Str::upper(Str::slug($group->po_number, '-'));
    }

    private function signatureDataUri(ProductionOrderGroup $group): ?string
    {
        if ($group->spk_signature_path === null) {
            return null;
        }

        $disk = Storage::disk('local');
        $contents = $disk->get($group->spk_signature_path);
        $mimeType = $disk->mimeType($group->spk_signature_path);
        abort_unless($mimeType === 'image/jpeg', 500, 'Berkas tanda tangan SPK harus berupa JPEG yang valid.');

        return 'data:'.$mimeType.';base64,'.base64_encode($contents);
    }

    private function storeCanvasSignature(string $signatureData): string
    {
        if (! preg_match('/^data:image\/jpeg;base64,([A-Za-z0-9+\/=]+)$/', $signatureData, $matches)) {
            abort(422, 'Data tanda tangan digital tidak valid.');
        }

        $binary = base64_decode($matches[1], true);
        if ($binary === false || strlen($binary) > 1024 * 1024) {
            abort(422, 'Ukuran atau format tanda tangan digital tidak valid.');
        }

        $image = getimagesizefromstring($binary);
        if ($image === false || $image['mime'] !== 'image/jpeg' || $image[0] > 3000 || $image[1] > 1500) {
            abort(422, 'Tanda tangan digital harus berupa gambar JPG yang valid.');
        }

        $path = 'spk-signatures/'.Str::uuid().'.jpg';
        if (! Storage::disk('local')->put($path, $binary)) {
            throw new \RuntimeException('Berkas tanda tangan digital tidak dapat disimpan.');
        }

        return $path;
    }
}
