<?php

namespace App\Enums;

enum EvidenceKind: string
{
    case ProductPhoto = 'product_photo';
    case Document = 'document';
    case Recording = 'recording';
    case CancellationProof = 'cancellation_proof';
}
