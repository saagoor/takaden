<?php

namespace Takaden\Controllers;

use Takaden\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use Takaden\Resources\PurchaseResource;

class PurchaseHistoryController extends Controller
{
    public function __invoke()
    {
        $purchases = request()
            ->user()
            ->purchases()
            ->with(['purchasable', 'payment'])
            ->whereRelation('payment', 'status', '!=', PaymentStatus::INITIATED)
            ->latest()
            ->paginate();

        return response()->json(PurchaseResource::collection($purchases));
    }
}
