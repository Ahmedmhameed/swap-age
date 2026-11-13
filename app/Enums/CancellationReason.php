<?php

namespace App\Enums;

enum CancellationReason: string
{
    case ChangedMind = 'changed_mind';
    case FoundBetterOption = 'found_better_option';
    case TermsDisagreement = 'terms_disagreement';
    case ItemsChanged = 'items_changed';
    case ItemMismatch = 'item_mismatch';
    case OtherPartyUnresponsive = 'other_party_unresponsive';
    case Other = 'other';
}
