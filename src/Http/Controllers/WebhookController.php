<?php

namespace Yousefkadah\Pelecard\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Yousefkadah\Pelecard\Events\PaymentFailed;
use Yousefkadah\Pelecard\Events\PaymentSucceeded;

class WebhookController extends Controller
{
    /**
     * Handle incoming webhook from Pelecard.
     */
    public function handleWebhook(Request $request)
    {
        // Validate webhook signature if enabled
        if (config('pelecard.webhook.signature_validation')) {
            $this->validateSignature($request);
        }

        $payload = $request->all();

        // Determine event type based on payload
        $eventType = $this->determineEventType($payload);

        // Dispatch appropriate event
        match ($eventType) {
            'payment.succeeded' => event(new PaymentSucceeded(
                new \Yousefkadah\Pelecard\Http\Response($payload)
            )),
            'payment.failed' => event(new PaymentFailed(
                new \Yousefkadah\Pelecard\Http\Response($payload)
            )),
            default => null,
        };

        return response()->json(['status' => 'success']);
    }

    /**
     * Validate webhook signature.
     */
    protected function validateSignature(Request $request): void
    {
        // Pelecard webhook signature validation logic
        // This would depend on Pelecard's specific implementation
        
        // Example:
        // $signature = $request->header('X-Pelecard-Signature');
        // if (!$this->isValidSignature($signature, $request->getContent())) {
        //     abort(403, 'Invalid webhook signature');
        // }
    }

    /**
     * Determine event type from payload.
     */
    protected function determineEventType(array $payload): string
    {
        // Determine event type based on Pelecard's payload structure
        $statusCode = $payload['StatusCode'] ?? $payload['status_code'] ?? null;

        if ($statusCode === '000' || $statusCode === 0) {
            return 'payment.succeeded';
        }

        return 'payment.failed';
    }
}
