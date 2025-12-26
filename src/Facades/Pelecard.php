<?php

namespace Yousefkadah\Pelecard\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Yousefkadah\Pelecard\Http\Response authorize(array $data)
 * @method static \Yousefkadah\Pelecard\Http\Response charge(array $data)
 * @method static \Yousefkadah\Pelecard\Http\Response refund(string $transactionId, int $amount)
 * @method static \Yousefkadah\Pelecard\Http\Response void(string $transactionId)
 * @method static \Yousefkadah\Pelecard\Http\Response capture(string $transactionId, int $amount)
 * @method static \Yousefkadah\Pelecard\Http\Response getTransactionStatus(string $transactionId)
 * @method static \Yousefkadah\Pelecard\Http\Response initiate3DS(array $data)
 * @method static \Yousefkadah\Pelecard\Http\Response get3DSData(string $transactionId)
 * @method static \Yousefkadah\Pelecard\Http\Response debitByGooglePay(array $data)
 * @method static \Yousefkadah\Pelecard\Http\Response convertToToken(array $cardData)
 * @method static \Yousefkadah\Pelecard\Http\Response retrieveToken(string $token)
 * @method static \Yousefkadah\Pelecard\Http\Response updateToken(string $token, array $cardData)
 * @method static \Yousefkadah\Pelecard\Http\Response checkCreditCardForToken(array $cardData)
 * @method static \Yousefkadah\Pelecard\Http\Response getTransaction(string $uniqueId)
 * @method static \Yousefkadah\Pelecard\Http\Response getCompleteTransData(array $filters = [])
 * @method static \Yousefkadah\Pelecard\Http\Response refundById(string $transactionId, ?int $amount = null)
 * @method static \Yousefkadah\Pelecard\Http\Response cancelTransaction(string $transactionId)
 * @method static \Yousefkadah\Pelecard\Http\Response createICountInvoice(array $invoiceData)
 * @method static \Yousefkadah\Pelecard\Http\Response checkCardBalance(array $cardData)
 * @method static \Yousefkadah\Pelecard\Http\Response debitCreditType(array $data)
 * @method static \Yousefkadah\Pelecard\Http\Response debitPaymentsType(array $data)
 * @method static \Yousefkadah\Pelecard\Http\Response authorizeCreditType(array $data)
 * @method static \Yousefkadah\Pelecard\Http\Response authorizePaymentsType(array $data)
 * @method static \Yousefkadah\Pelecard\PelecardClient for(mixed $billable)
 *
 * @see \Yousefkadah\Pelecard\PelecardClient
 */
class Pelecard extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'pelecard';
    }
}
