<?php

namespace Takaden;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface Orderable extends Arrayable
{
    public function getKey();

    public function handleSuccessPayment(array $payload);

    public function handleFailPayment(array $payload);

    public function handleCancelPayment(array $payload);

    public function getTakadenAmount(): float|int;

    public function getTakadenCurrency(): string;

    public function getTakadenPaymentTitle(): string;

    public function getTakadenCustomer(): Model;

    public function getTakadenNotifiables(): Collection|array;
}
