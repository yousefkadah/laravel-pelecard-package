<?php

namespace Yousefkadah\Pelecard;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Yousefkadah\Pelecard\Exceptions\AuthenticationException;
use Yousefkadah\Pelecard\Exceptions\PelecardException;
use Yousefkadah\Pelecard\Http\Request;
use Yousefkadah\Pelecard\Http\Response;

class PelecardClient
{
    protected GuzzleClient $httpClient;
    protected string $terminal;
    protected string $user;
    protected string $password;
    protected string $environment;
    protected string $baseUrl;

    /**
     * Create a new Pelecard client instance.
     */
    public function __construct(
        ?string $terminal = null,
        ?string $user = null,
        ?string $password = null,
        string $environment = 'sandbox'
    ) {
        $this->terminal = $terminal ?? config('pelecard.terminal');
        $this->user = $user ?? config('pelecard.user');
        $this->password = $password ?? config('pelecard.password');
        $this->environment = $environment;

        if (! $this->terminal || ! $this->user || ! $this->password) {
            throw AuthenticationException::missingCredentials();
        }

        $this->baseUrl = config("pelecard.gateway_urls.{$this->environment}");

        $this->httpClient = new GuzzleClient([
            'base_uri' => $this->baseUrl,
            'timeout' => 30,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * Create client for a specific billable entity (multi-tenant).
     */
    public static function for(mixed $billable): static
    {
        if (! config('pelecard.multi_tenant')) {
            return app(static::class);
        }

        $resolver = app(CredentialsResolver::class);
        $credentials = $resolver->resolve($billable);

        return new static(
            terminal: $credentials->getTerminal(),
            user: $credentials->getUser(),
            password: $credentials->getPassword(),
            environment: $credentials->isSandbox() ? 'sandbox' : 'production'
        );
    }

    /**
     * Authorize a credit card (hold funds without charging).
     */
    public function authorize(array $data): Response
    {
        $request = Request::make(array_merge($data, $this->getAuthData()))
            ->setRequiredFields(['amount', 'currency', 'card_number', 'expiry_month', 'expiry_year', 'cvv']);

        $request->validate();

        return $this->post('/AuthorizeCreditCard', $request->toPelecardFormat());
    }

    /**
     * Charge a credit card (debit transaction).
     */
    public function charge(array $data): Response
    {
        $request = Request::make(array_merge($data, $this->getAuthData()))
            ->setRequiredFields(['amount', 'currency']);

        $request->validate();

        return $this->post('/DebitRegularType', $request->toPelecardFormat());
    }

    /**
     * Refund a transaction.
     */
    public function refund(string $transactionId, int $amount, ?string $currency = null): Response
    {
        $data = array_merge([
            'pelecard_transaction_id' => $transactionId,
            'amount' => $amount,
            'currency' => $currency ?? config('pelecard.currency'),
        ], $this->getAuthData());

        $request = Request::make($data);

        return $this->post('/CreditTransaction', $request->toPelecardFormat());
    }

    /**
     * Void/cancel an authorization.
     */
    public function void(string $transactionId): Response
    {
        $data = array_merge([
            'pelecard_transaction_id' => $transactionId,
        ], $this->getAuthData());

        $request = Request::make($data);

        return $this->post('/VoidTransaction', $request->toPelecardFormat());
    }

    /**
     * Capture a pre-authorized amount.
     */
    public function capture(string $transactionId, int $amount): Response
    {
        $data = array_merge([
            'pelecard_transaction_id' => $transactionId,
            'amount' => $amount,
        ], $this->getAuthData());

        $request = Request::make($data);

        return $this->post('/CaptureTransaction', $request->toPelecardFormat());
    }

    /**
     * Get transaction status.
     */
    public function getTransactionStatus(string $transactionId): Response
    {
        $data = array_merge([
            'pelecard_transaction_id' => $transactionId,
        ], $this->getAuthData());

        $request = Request::make($data);

        return $this->post('/GetTransactionStatus', $request->toPelecardFormat());
    }

    /**
     * Create a payment token for recurring payments.
     */
    public function createToken(array $cardData): Response
    {
        $request = Request::make(array_merge($cardData, $this->getAuthData()))
            ->setRequiredFields(['card_number', 'expiry_month', 'expiry_year']);

        $request->validate();

        return $this->post('/CreateToken', $request->toPelecardFormat());
    }

    /**
     * Charge using a token.
     */
    public function chargeToken(string $token, int $amount, ?string $currency = null): Response
    {
        $data = array_merge([
            'token' => $token,
            'amount' => $amount,
            'currency' => $currency ?? config('pelecard.currency'),
        ], $this->getAuthData());

        $request = Request::make($data);

        return $this->post('/ChargeToken', $request->toPelecardFormat());
    }

    /**
     * Initiate 3DS authentication process.
     */
    public function initiate3DS(array $data): Response
    {
        $request = Request::make(array_merge($data, $this->getAuthData()))
            ->setRequiredFields(['amount', 'currency', 'card_number', 'expiry_month', 'expiry_year']);

        $request->validate();

        return $this->post('/Initiate3DSAuthenticationProcess', $request->toPelecardFormat());
    }

    /**
     * Get 3DS data for a transaction.
     */
    public function get3DSData(string $transactionId): Response
    {
        $data = array_merge([
            'pelecard_transaction_id' => $transactionId,
        ], $this->getAuthData());

        $request = Request::make($data);

        return $this->post('/Get3dsData', $request->toPelecardFormat());
    }

    /**
     * Debit by Google Pay.
     */
    public function debitByGooglePay(array $data): Response
    {
        $request = Request::make(array_merge($data, $this->getAuthData()))
            ->setRequiredFields(['amount', 'currency', 'google_pay_token']);

        $request->validate();

        return $this->post('/DebitByGooglePay', $request->toPelecardFormat());
    }

    /**
     * Convert card to token.
     */
    public function convertToToken(array $cardData): Response
    {
        $request = Request::make(array_merge($cardData, $this->getAuthData()))
            ->setRequiredFields(['card_number', 'expiry_month', 'expiry_year']);

        $request->validate();

        return $this->post('/ConvertToToken', $request->toPelecardFormat());
    }

    /**
     * Convert to token without card validation check.
     */
    public function convertToTokenNoCheck(array $cardData): Response
    {
        $request = Request::make(array_merge($cardData, $this->getAuthData()))
            ->setRequiredFields(['card_number', 'expiry_month', 'expiry_year']);

        $request->validate();

        return $this->post('/ConvertToTokenNoCheck', $request->toPelecardFormat());
    }

    /**
     * Retrieve token details.
     */
    public function retrieveToken(string $token): Response
    {
        $data = array_merge([
            'token' => $token,
        ], $this->getAuthData());

        $request = Request::make($data);

        return $this->post('/RetrieveToken', $request->toPelecardFormat());
    }

    /**
     * Update token information.
     */
    public function updateToken(string $token, array $cardData): Response
    {
        $data = array_merge([
            'token' => $token,
        ], $cardData, $this->getAuthData());

        $request = Request::make($data);

        return $this->post('/UpdateToken', $request->toPelecardFormat());
    }

    /**
     * Update token without validation check.
     */
    public function updateTokenNoCheck(string $token, array $cardData): Response
    {
        $data = array_merge([
            'token' => $token,
        ], $cardData, $this->getAuthData());

        $request = Request::make($data);

        return $this->post('/UpdateTokenNoCheck', $request->toPelecardFormat());
    }

    /**
     * Check credit card for token eligibility.
     */
    public function checkCreditCardForToken(array $cardData): Response
    {
        $request = Request::make(array_merge($cardData, $this->getAuthData()))
            ->setRequiredFields(['card_number']);

        $request->validate();

        return $this->post('/CheckCreditCardForToken', $request->toPelecardFormat());
    }

    /**
     * Get transaction by unique ID.
     */
    public function getTransaction(string $uniqueId): Response
    {
        $data = array_merge([
            'unique_id' => $uniqueId,
        ], $this->getAuthData());

        $request = Request::make($data);

        return $this->post('/GetTransaction', $request->toPelecardFormat());
    }

    /**
     * Get transaction by UID.
     */
    public function getTransactionByUid(string $uid): Response
    {
        $data = array_merge([
            'uid' => $uid,
        ], $this->getAuthData());

        $request = Request::make($data);

        return $this->post('/GetTransactionByUid', $request->toPelecardFormat());
    }

    /**
     * Get complete transaction data.
     */
    public function getCompleteTransData(array $filters = []): Response
    {
        $data = array_merge($filters, $this->getAuthData());
        $request = Request::make($data);

        return $this->post('/GetCompleteTransData', $request->toPelecardFormat());
    }

    /**
     * Get transaction data by transaction ID.
     */
    public function getTransDataByTrxId(string $transactionId): Response
    {
        $data = array_merge([
            'pelecard_transaction_id' => $transactionId,
        ], $this->getAuthData());

        $request = Request::make($data);

        return $this->post('/GetTransDataByTrxId', $request->toPelecardFormat());
    }

    /**
     * Get transaction details (EMV).
     */
    public function getTransDetailsEmv(string $transactionId): Response
    {
        $data = array_merge([
            'pelecard_transaction_id' => $transactionId,
        ], $this->getAuthData());

        $request = Request::make($data);

        return $this->post('/GetTransDetailsEMV', $request->toPelecardFormat());
    }

    /**
     * Refund by transaction ID.
     */
    public function refundById(string $transactionId, ?int $amount = null): Response
    {
        $data = array_merge([
            'pelecard_transaction_id' => $transactionId,
        ], $this->getAuthData());

        if ($amount !== null) {
            $data['amount'] = $amount;
        }

        $request = Request::make($data);

        return $this->post('/RefundByID', $request->toPelecardFormat());
    }

    /**
     * Refund by transaction ID (EMV).
     */
    public function refundByIdEmv(string $transactionId, ?int $amount = null): Response
    {
        $data = array_merge([
            'pelecard_transaction_id' => $transactionId,
        ], $this->getAuthData());

        if ($amount !== null) {
            $data['amount'] = $amount;
        }

        $request = Request::make($data);

        return $this->post('/RefundByIdEmv', $request->toPelecardFormat());
    }

    /**
     * Cancel transaction.
     */
    public function cancelTransaction(string $transactionId): Response
    {
        $data = array_merge([
            'pelecard_transaction_id' => $transactionId,
        ], $this->getAuthData());

        $request = Request::make($data);

        return $this->post('/CancelTransaction', $request->toPelecardFormat());
    }

    /**
     * Create ICount invoice.
     */
    public function createICountInvoice(array $invoiceData): Response
    {
        $request = Request::make(array_merge($invoiceData, $this->getAuthData()));

        return $this->post('/CreateICountInvoice', $request->toPelecardFormat());
    }

    /**
     * Create EZCount invoice.
     */
    public function createEZCountInvoice(array $invoiceData): Response
    {
        $request = Request::make(array_merge($invoiceData, $this->getAuthData()));

        return $this->post('/CreateEZCountInvoice', $request->toPelecardFormat());
    }

    /**
     * Create Payper invoice.
     */
    public function createPayperInvoice(array $invoiceData): Response
    {
        $request = Request::make(array_merge($invoiceData, $this->getAuthData()));

        return $this->post('/CreatePayperInvoice', $request->toPelecardFormat());
    }

    /**
     * Check card balance.
     */
    public function checkCardBalance(array $cardData): Response
    {
        $request = Request::make(array_merge($cardData, $this->getAuthData()))
            ->setRequiredFields(['card_number']);

        $request->validate();

        return $this->post('/CheckCardBalance', $request->toPelecardFormat());
    }

    /**
     * Validate by unique key.
     */
    public function validateByUniqueKey(string $uniqueKey): Response
    {
        $data = array_merge([
            'unique_key' => $uniqueKey,
        ], $this->getAuthData());

        $request = Request::make($data);

        return $this->post('/ValidateByUniqueKey', $request->toPelecardFormat());
    }

    /**
     * Complete partial approval.
     */
    public function completePartialApproval(string $transactionId, int $amount): Response
    {
        $data = array_merge([
            'pelecard_transaction_id' => $transactionId,
            'amount' => $amount,
        ], $this->getAuthData());

        $request = Request::make($data);

        return $this->post('/CompletePartialApproval', $request->toPelecardFormat());
    }

    /**
     * Initiate bank transfer.
     */
    public function initiateBankTransfer(array $transferData): Response
    {
        $request = Request::make(array_merge($transferData, $this->getAuthData()));

        return $this->post('/InitiateBankTransfer', $request->toPelecardFormat());
    }

    /**
     * Debit credit type (J4).
     */
    public function debitCreditType(array $data): Response
    {
        $request = Request::make(array_merge($data, $this->getAuthData()))
            ->setRequiredFields(['amount', 'currency']);

        $request->validate();

        return $this->post('/DebitCreditType', $request->toPelecardFormat());
    }

    /**
     * Debit payments type (J4).
     */
    public function debitPaymentsType(array $data): Response
    {
        $request = Request::make(array_merge($data, $this->getAuthData()))
            ->setRequiredFields(['amount', 'currency', 'payments']);

        $request->validate();

        return $this->post('/DebitPaymentsType', $request->toPelecardFormat());
    }

    /**
     * Authorize credit type (J5).
     */
    public function authorizeCreditType(array $data): Response
    {
        $request = Request::make(array_merge($data, $this->getAuthData()))
            ->setRequiredFields(['amount', 'currency']);

        $request->validate();

        return $this->post('/AuthorizeCreditType', $request->toPelecardFormat());
    }

    /**
     * Authorize payments type (J5).
     */
    public function authorizePaymentsType(array $data): Response
    {
        $request = Request::make(array_merge($data, $this->getAuthData()))
            ->setRequiredFields(['amount', 'currency', 'payments']);

        $request->validate();

        return $this->post('/AuthorizePaymentsType', $request->toPelecardFormat());
    }

    /**
     * Get error message in Hebrew.
     */
    public function getErrorMessageHe(string $errorCode): Response
    {
        $data = array_merge([
            'error_code' => $errorCode,
        ], $this->getAuthData());

        $request = Request::make($data);

        return $this->post('/GetErrorMessage', $request->toPelecardFormat());
    }

    /**
     * Get error message in English.
     */
    public function getErrorMessageEn(string $errorCode): Response
    {
        $data = array_merge([
            'error_code' => $errorCode,
        ], $this->getAuthData());

        $request = Request::make($data);

        return $this->post('/GetErrorMessageEN', $request->toPelecardFormat());
    }

    /**
     * Get error message (auto-detect language from config).
     */
    public function getErrorMessage(string $errorCode, ?string $language = null): Response
    {
        $language = $language ?? config('pelecard.language', 'he');

        return $language === 'en' 
            ? $this->getErrorMessageEn($errorCode)
            : $this->getErrorMessageHe($errorCode);
    }

    /**
     * Abort transaction.
     */
    public function abortTransaction(string $transactionId): Response
    {
        $data = array_merge([
            'pelecard_transaction_id' => $transactionId,
        ], $this->getAuthData());

        $request = Request::make($data);

        return $this->post('/AbortTransaction', $request->toPelecardFormat());
    }

    /**
     * Add debit transaction receipt.
     */
    public function addDebitTrxReceipt(array $data): Response
    {
        $request = Request::make(array_merge($data, $this->getAuthData()));

        return $this->post('/AddDebitTrxReceipt', $request->toPelecardFormat());
    }

    /**
     * Authorize Isracredit card (J5).
     */
    public function authorizeIsracreditCard(array $data): Response
    {
        $request = Request::make(array_merge($data, $this->getAuthData()))
            ->setRequiredFields(['amount', 'currency']);

        $request->validate();

        return $this->post('/AuthorizeIsracreditCard', $request->toPelecardFormat());
    }

    /**
     * Broadcast to Shva.
     */
    public function broadcastToShva(array $data): Response
    {
        $request = Request::make(array_merge($data, $this->getAuthData()));

        return $this->post('/BroadcastToShva', $request->toPelecardFormat());
    }

    /**
     * Check Ashrait.
     */
    public function checkAshrait(array $data): Response
    {
        $request = Request::make(array_merge($data, $this->getAuthData()));

        return $this->post('/CheckAshrait', $request->toPelecardFormat());
    }

    /**
     * Check Good ParamX.
     */
    public function checkGoodParamX(string $paramX): Response
    {
        $data = array_merge([
            'param_x' => $paramX,
        ], $this->getAuthData());

        $request = Request::make($data);

        return $this->post('/CheckGoodParamX', $request->toPelecardFormat());
    }

    /**
     * Check Good ParamX EMV.
     */
    public function checkGoodParamXEmv(string $paramX): Response
    {
        $data = array_merge([
            'param_x' => $paramX,
        ], $this->getAuthData());

        $request = Request::make($data);

        return $this->post('/CheckGoodParamXEMV', $request->toPelecardFormat());
    }

    /**
     * Check Good ParamX List.
     */
    public function checkGoodParamXList(array $paramXList): Response
    {
        $data = array_merge([
            'param_x_list' => $paramXList,
        ], $this->getAuthData());

        $request = Request::make($data);

        return $this->post('/CheckGoodParamXList', $request->toPelecardFormat());
    }

    /**
     * Complete debit by UID.
     */
    public function completeDebitByUid(string $uid, int $amount): Response
    {
        $data = array_merge([
            'uid' => $uid,
            'amount' => $amount,
        ], $this->getAuthData());

        $request = Request::make($data);

        return $this->post('/CompleteDebitByUid', $request->toPelecardFormat());
    }

    /**
     * Contactless debit.
     */
    public function contactLessDebit(array $data): Response
    {
        $request = Request::make(array_merge($data, $this->getAuthData()))
            ->setRequiredFields(['amount', 'currency']);

        $request->validate();

        return $this->post('/ContactLessDebit', $request->toPelecardFormat());
    }

    /**
     * Debit by IntIn.
     */
    public function debitByIntIn(array $data): Response
    {
        $request = Request::make(array_merge($data, $this->getAuthData()))
            ->setRequiredFields(['amount', 'currency']);

        $request->validate();

        return $this->post('/DebitByIntIn', $request->toPelecardFormat());
    }

    /**
     * Debit by IntIn EMV.
     */
    public function debitByIntInEmv(array $data): Response
    {
        $request = Request::make(array_merge($data, $this->getAuthData()))
            ->setRequiredFields(['amount', 'currency']);

        $request->validate();

        return $this->post('/DebitByIntInEmv', $request->toPelecardFormat());
    }

    /**
     * Debit Isracredit type (J4).
     */
    public function debitIsracreditType(array $data): Response
    {
        $request = Request::make(array_merge($data, $this->getAuthData()))
            ->setRequiredFields(['amount', 'currency']);

        $request->validate();

        return $this->post('/DebitIsracreditType', $request->toPelecardFormat());
    }

    /**
     * Delete Ishur (authorization).
     */
    public function deleteIshur(string $transactionId): Response
    {
        $data = array_merge([
            'pelecard_transaction_id' => $transactionId,
        ], $this->getAuthData());

        $request = Request::make($data);

        return $this->post('/DeleteIshur', $request->toPelecardFormat());
    }

    /**
     * Delete transaction.
     */
    public function deleteTran(string $transactionId): Response
    {
        $data = array_merge([
            'pelecard_transaction_id' => $transactionId,
        ], $this->getAuthData());

        $request = Request::make($data);

        return $this->post('/DeleteTran', $request->toPelecardFormat());
    }

    /**
     * EMV reversal.
     */
    public function emvReversal(string $transactionId): Response
    {
        $data = array_merge([
            'pelecard_transaction_id' => $transactionId,
        ], $this->getAuthData());

        $request = Request::make($data);

        return $this->post('/EmvReversal', $request->toPelecardFormat());
    }

    /**
     * Get Ashrait version.
     */
    public function getAshraitVersion(): Response
    {
        $request = Request::make($this->getAuthData());

        return $this->post('/GetAshraitVersion', $request->toPelecardFormat());
    }

    /**
     * Get broadcast.
     */
    public function getBroadcast(string $broadcastId): Response
    {
        $data = array_merge([
            'broadcast_id' => $broadcastId,
        ], $this->getAuthData());

        $request = Request::make($data);

        return $this->post('/GetBroadcast', $request->toPelecardFormat());
    }

    /**
     * Get broadcasts by date.
     */
    public function getBroadcastsByDate(string $fromDate, string $toDate): Response
    {
        $data = array_merge([
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ], $this->getAuthData());

        $request = Request::make($data);

        return $this->post('/GetBroadcastsByDate', $request->toPelecardFormat());
    }

    /**
     * Get broadcasts summary by date.
     */
    public function getBroadcastsSummaryByDate(string $fromDate, string $toDate): Response
    {
        $data = array_merge([
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ], $this->getAuthData());

        $request = Request::make($data);

        return $this->post('/GetBroadcastsSummaryByDate', $request->toPelecardFormat());
    }

    /**
     * Get company phone number.
     */
    public function getCompanyPhoneNumber(): Response
    {
        $request = Request::make($this->getAuthData());

        return $this->post('/GetCompanyPhoneNumber', $request->toPelecardFormat());
    }

    /**
     * Get complete trans data by broadcast date.
     */
    public function getCompleteTransDataByBroadcastDate(string $broadcastDate): Response
    {
        $data = array_merge([
            'broadcast_date' => $broadcastDate,
        ], $this->getAuthData());

        $request = Request::make($data);

        return $this->post('/GetCompleteTransDataByBroadcastDate', $request->toPelecardFormat());
    }

    /**
     * Get deleted trans data.
     */
    public function getDeletedTransData(array $filters = []): Response
    {
        $data = array_merge($filters, $this->getAuthData());
        $request = Request::make($data);

        return $this->post('/GetDeletedTransData', $request->toPelecardFormat());
    }

    /**
     * Get Sapak number.
     */
    public function getSapakNumber(): Response
    {
        $request = Request::make($this->getAuthData());

        return $this->post('/GetSapakNumber', $request->toPelecardFormat());
    }

    /**
     * Get statis record.
     */
    public function getStatisRecord(array $filters = []): Response
    {
        $data = array_merge($filters, $this->getAuthData());
        $request = Request::make($data);

        return $this->post('/GetStatisRecord', $request->toPelecardFormat());
    }

    /**
     * Get terminal Muhlafim.
     */
    public function getTerminalMuhlafim(): Response
    {
        $request = Request::make($this->getAuthData());

        return $this->post('/GetTerminalMuhlafim', $request->toPelecardFormat());
    }

    /**
     * Get Track2 by Pelecloud (J20).
     */
    public function getTrack2ByPelecloud(array $data): Response
    {
        $request = Request::make(array_merge($data, $this->getAuthData()));

        return $this->post('/GetTrack2ByPelecloud', $request->toPelecardFormat());
    }

    /**
     * Get trans data.
     */
    public function getTransData(array $filters = []): Response
    {
        $data = array_merge($filters, $this->getAuthData());
        $request = Request::make($data);

        return $this->post('/GetTransData', $request->toPelecardFormat());
    }

    /**
     * Get trans data by Ricuz number.
     */
    public function getTransDataByRicuzNo(string $ricuzNo): Response
    {
        $data = array_merge([
            'ricuz_no' => $ricuzNo,
        ], $this->getAuthData());

        $request = Request::make($data);

        return $this->post('/GetTransDataByRicuzNo', $request->toPelecardFormat());
    }

    /**
     * Get trans details (Switch).
     */
    public function getTransDetailsSwitch(string $transactionId): Response
    {
        $data = array_merge([
            'pelecard_transaction_id' => $transactionId,
        ], $this->getAuthData());

        $request = Request::make($data);

        return $this->post('/GetTransDetailsSwitch', $request->toPelecardFormat());
    }

    /**
     * Get trans report data before broadcast.
     */
    public function getTransReportDataBeforeBc(array $filters = []): Response
    {
        $data = array_merge($filters, $this->getAuthData());
        $request = Request::make($data);

        return $this->post('/GetTransReportDataBeforeBc', $request->toPelecardFormat());
    }



    /**
     * Make a POST request to Pelecard API.
     */
    protected function post(string $endpoint, array $data): Response
    {
        try {
            $this->logRequest($endpoint, $data);

            $response = $this->httpClient->post($endpoint, [
                'json' => $data,
            ]);

            $body = (string) $response->getBody();
            $responseData = json_decode($body, true) ?? [];

            $this->logResponse($endpoint, $responseData);

            return new Response($responseData, $response->getStatusCode(), $body);
        } catch (GuzzleException $e) {
            $this->logError($endpoint, $e);

            throw new PelecardException(
                "HTTP request failed: {$e->getMessage()}",
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Get authentication data for API requests.
     */
    protected function getAuthData(): array
    {
        return [
            'terminal' => $this->terminal,
            'user' => $this->user,
            'password' => $this->password,
        ];
    }

    /**
     * Log API request.
     */
    protected function logRequest(string $endpoint, array $data): void
    {
        if (! config('pelecard.logging.enabled')) {
            return;
        }

        $sanitized = $this->sanitizeLogData($data);

        Log::channel(config('pelecard.logging.channel'))
            ->info("Pelecard API Request: {$endpoint}", $sanitized);
    }

    /**
     * Log API response.
     */
    protected function logResponse(string $endpoint, array $data): void
    {
        if (! config('pelecard.logging.enabled')) {
            return;
        }

        Log::channel(config('pelecard.logging.channel'))
            ->info("Pelecard API Response: {$endpoint}", $data);
    }

    /**
     * Log API error.
     */
    protected function logError(string $endpoint, \Throwable $exception): void
    {
        if (! config('pelecard.logging.enabled')) {
            return;
        }

        Log::channel(config('pelecard.logging.channel'))
            ->error("Pelecard API Error: {$endpoint}", [
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
            ]);
    }

    /**
     * Sanitize sensitive data for logging.
     */
    protected function sanitizeLogData(array $data): array
    {
        $sensitive = ['password', 'card_number', 'cvv', 'CardNumber', 'Cvv', 'Password'];

        foreach ($sensitive as $field) {
            if (isset($data[$field])) {
                $data[$field] = '***REDACTED***';
            }
        }

        return $data;
    }

    /**
     * Get iframe helper instance.
     */
    public function iframe(): IframeHelper
    {
        return new IframeHelper($this);
    }

    /**
     * Get terminal number.
     */
    public function getTerminal(): string
    {
        return $this->terminal;
    }

    /**
     * Get environment.
     */
    public function getEnvironment(): string
    {
        return $this->environment;
    }
}
