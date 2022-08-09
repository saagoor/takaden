<?php

namespace Takaden\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            ...parent::toArray($request),
            'title'     => $this->getPaymentTitle(),
            'amount'    => $this->payment->amount + $this->payment->discount,
        ];
    }
}
