<?php

namespace App\Services;

use App\Enums\EvidenceKind;
use App\Exceptions\DomainRuleViolation;
use App\Models\Evidence;
use App\Models\Trade;
use App\Models\TradeItem;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class EvidenceFileService
{
    public function store(Trade $trade, ?TradeItem $item, EvidenceKind $kind, UploadedFile $file, User $uploader): Evidence
    {
        $mimeType = (string) $file->getMimeType();
        $size = (int) $file->getSize();
        $this->validateFile($kind, $mimeType, $size);
        $extension = match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
            'audio/mpeg' => 'mp3',
            'audio/wav', 'audio/x-wav' => 'wav',
            'audio/ogg' => 'ogg',
            default => throw new DomainRuleViolation('نوع ملف الدليل غير مسموح.'),
        };
        $path = "evidence/{$trade->id}/".Str::uuid().'.'.$extension;
        $contents = str_starts_with($mimeType, 'image/')
            ? $this->withoutExif($file, $mimeType)
            : file_get_contents($file->getRealPath());

        if ($contents === false) {
            throw new RuntimeException('تعذر قراءة ملف الدليل.');
        }

        $evidence = Evidence::create([
            'trade_id' => $trade->id,
            'trade_item_id' => $item?->id,
            'kind' => $kind,
            'file_path' => $path,
            'mime_type' => $mimeType,
            'size' => strlen($contents),
            'uploaded_by_id' => $uploader->id,
        ]);

        DB::afterCommit(function () use ($path, $contents): void {
            if (! Storage::disk('local')->put($path, $contents)) {
                throw new RuntimeException('تعذر حفظ ملف الدليل بعد اعتماد العملية.');
            }
        });

        return $evidence;
    }

    private function validateFile(EvidenceKind $kind, string $mimeType, int $size): void
    {
        $isImage = in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true);
        $isPdf = $mimeType === 'application/pdf';
        $isAudio = in_array($mimeType, ['audio/mpeg', 'audio/wav', 'audio/x-wav', 'audio/ogg'], true);
        $isValid = match ($kind) {
            EvidenceKind::ProductPhoto => $isImage && $size <= 5 * 1024 * 1024,
            EvidenceKind::Document => $isPdf && $size <= 10 * 1024 * 1024,
            EvidenceKind::Recording => $isAudio && $size <= 10 * 1024 * 1024,
            EvidenceKind::CancellationProof => ($isImage && $size <= 5 * 1024 * 1024)
                || (($isPdf || $isAudio) && $size <= 10 * 1024 * 1024),
        };

        if (! $isValid) {
            throw new DomainRuleViolation('نوع الملف أو حجمه لا يطابق شروط الدليل.');
        }
    }

    private function withoutExif(UploadedFile $file, string $mimeType): string
    {
        $image = imagecreatefromstring((string) file_get_contents($file->getRealPath()));

        if ($image === false) {
            throw new DomainRuleViolation('تعذر قراءة الصورة المرفوعة.');
        }

        ob_start();
        $saved = match ($mimeType) {
            'image/png' => imagepng($image),
            'image/webp' => imagewebp($image),
            default => imagejpeg($image, quality: 90),
        };
        $contents = ob_get_clean();
        imagedestroy($image);

        if (! $saved || $contents === false) {
            throw new RuntimeException('تعذر معالجة الصورة المرفوعة.');
        }

        return $contents;
    }
}
