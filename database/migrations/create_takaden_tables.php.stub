<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Takaden\Enums\PaymentStatus;

return new class extends Migration
{
    public function up()
    {
        Schema::create('takaden_checkouts', function (Blueprint $table) {
            $table->id();
            $table->morphs('orderable');
            $table->string('payment_provider');
            $table->string('payment_status')->default(PaymentStatus::INITIATED->value);
            $table->string('currency')->nullable()->default(config('takaden.defaults.currency'));
            $table->float('amount')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('takaden_checkouts');
    }
};
