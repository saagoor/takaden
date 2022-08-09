<?php

namespace Takaden\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\JsonResource;
use Takaden\Enums\PaymentStatus;

class PaymentHistoryController extends Controller
{
    public function __invoke()
    {
        $payments = request()
            ->user()
            ->payments()
            ->where('status', '!=', PaymentStatus::INITIATED)
            ->with(['purchase'])
            ->latest('updated_at')
            ->paginate();

        return response()->json(JsonResource::collection($payments));
    }
}
