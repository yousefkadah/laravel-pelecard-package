<?php

use Illuminate\Support\Facades\Route;
use Yousefkadah\Pelecard\Http\Controllers\WebhookController;

Route::post(
    config('pelecard.webhook.path', 'pelecard/webhook'),
    [WebhookController::class, 'handleWebhook']
)->name('pelecard.webhook');
