<?php

namespace App\Http\Controllers;

use App\Actions\DeleteEvidence;
use App\Actions\UploadEvidence;
use App\Enums\EvidenceKind;
use App\Http\Requests\UploadEvidenceRequest;
use App\Models\Evidence;
use App\Models\Trade;
use App\Models\TradeItem;
use App\Services\EvidenceFileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EvidenceController extends Controller
{
    public function store(UploadEvidenceRequest $request, Trade $trade, UploadEvidence $action, EvidenceFileService $files): RedirectResponse
    {
        $data = $request->validated();
        $item = isset($data['trade_item_id']) ? TradeItem::query()->findOrFail($data['trade_item_id']) : null;
        $action->handle($trade, $request->user(), EvidenceKind::from($data['kind']), $data['file'], $files, $item);

        return back()->with('status', 'تم رفع الدليل.');
    }

    public function destroy(Request $request, Evidence $evidence, DeleteEvidence $action): RedirectResponse
    {
        $action->handle($evidence, $request->user());

        return back()->with('status', 'تم حذف الدليل.');
    }

    public function download(Evidence $evidence): StreamedResponse
    {
        Gate::authorize('viewEvidence', $evidence->trade);

        return response()->streamDownload(
            fn () => print Storage::disk('local')->get($evidence->file_path),
            basename($evidence->file_path),
            ['Content-Type' => $evidence->mime_type],
        );
    }
}
