<?php

namespace App\Models\Builders;

use Illuminate\Database\Eloquent\Builder;
use LogicException;

/**
 * @template TModel of \App\Models\Trade
 *
 * @extends Builder<TModel>
 */
class TradeBuilder extends Builder
{
    public function forceDelete(): never
    {
        throw new LogicException('Trades cannot be permanently deleted.');
    }
}
