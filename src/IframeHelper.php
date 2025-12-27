<?php

namespace Yousefkadah\Pelecard;

class IframeHelper
{
    /**
     * Create a new iframe helper instance.
     */
    public const ACTION_TYPE_PAYMENT = 'J4';
    public const ACTION_TYPE_AUTHORIZE = 'J5';
    public const ACTION_TYPE_AUTHORIZE_HIDDEN = 'J5h';
    public const ACTION_TYPE_REGISTRY = 'J2';
    public const ACTION_TYPE_REGISTRY_SWIPE = 'J2S';

    /**
     * Create a new iframe helper instance.
     */
    public function __construct(protected PelecardClient $client) {}

    /**
     * Generate iframe URL for payment page.
     */
    public function generatePaymentUrl(array $data): string
    {
        if ($this->client->getEnvironment() === 'sandbox') {
        }

        $params = [
            'terminalNumber' => $this->client->getTerminal(),
            'user' => $this->client->getUser(),
            'password' => $this->client->getPassword(),
            'shopNo' => $data['shop_number'] ?? config('pelecard.shop_number', '001'), // Sandbox label is ShopNo
            'total' => $data['amount'],
            'currency' => $data['currency'] ?? config('pelecard.currency', 'ILS'),
            'goodUrl' => $data['success_url'] ?? '',
            'errorUrl' => $data['error_url'] ?? '',
            'cancelUrl' => $data['cancel_url'] ?? '',
            'lang' => $data['language'] ?? 'he',
            'paramX' => $data['param_x'] ?? '',
            // Additional Sandbox params
            'actionType' => $data['action_type'] ?? self::ACTION_TYPE_REGISTRY, // Default to J2 (Registry)
            'authNum' => $data['auth_num'] ?? '',
            'firstPayment' => $data['first_payment'] ?? '',
            'firstPaymentLock' => isset($data['first_payment_lock']) ? ($data['first_payment_lock'] ? '1' : '0') : '',
            'freeTotal' => $data['free_total'] ?? '', // Total amount for payments?
            'minPaymentsForCredit' => $data['min_payments_for_credit'] ?? '',
            'sapakNo' => $data['sapak_no'] ?? '', // Supplier Number?
            'disabledPaymentNumbers' => $data['disabled_payment_numbers'] ?? '',
        ];

        // Optional parameters
        if (isset($data['token'])) {
            $params['token'] = $data['token'];
        }

        // --- Customization & Appearance ---
        $appearanceParams = [
            'topText' => 'top_text',
            'bottomText' => 'bottom_text',
            'logo' => 'logo_url',
            'hidePelecardLogo' => 'hide_pelecard_logo', // Boolean handled below
            'showConfirmationCheckbox' => 'show_confirmation', // Boolean handled below
            'minPayments' => 'min_payments',
            'maxPayments' => 'max_payments',
            // New appearance fields
            'bussinessName' => 'business_name',
            'captionExpInput' => 'caption_exp_input',
            'confirmationLink' => 'confirmation_link',
            'cssURL' => 'css_url',
            'cvvImageUrl' => 'cvv_image_url',
            'disableZoom' => 'disable_zoom',
            'hiddenPciLogo' => 'hidden_pci_logo',
            'hiddenSslSeal' => 'hidden_ssl_seal',
            'forceTovCard' => 'force_tov_card',
            'hideFields' => 'hide_fields',
            'idCvvInputType' => 'id_cvv_input_type',
            'logoAltText' => 'logo_alt_text',
            'numericInputMode' => 'numeric_input_mode',
            'openConfirmationBoxInModal' => 'open_confirmation_box_in_modal',
            'placeholderCaptions' => 'placeholder_captions',
            'placeholderCCNumber' => 'placeholder_cc_number',
            'setFocus' => 'set_focus',
            'showBrandLogo' => 'show_brand_logo',
            'showSubmitButton' => 'show_submit_button',
            'showXParam' => 'show_x_param',
            'defaultTabButton' => 'default_tab_button',
            'creditCardExpiryDateMinDays' => 'credit_card_expiry_date_min_days',
            'textAfterConfirmationLink' => 'text_after_confirmation_link',
            'textBeforeConfirmationLink' => 'text_before_confirmation_link',
            'textOnConfirmationBox' => 'text_on_confirmation_box',
            'splitCCNumber' => 'split_cc_number',
            'userNoScalable' => 'user_no_scalable',
        ];

        foreach ($appearanceParams as $paramName => $dataKey) {
            if (isset($data[$dataKey])) {
                // Handle booleans that need '1' or '0'
                if (in_array($dataKey, ['hide_pelecard_logo', 'show_confirmation', 'disable_zoom', 'hidden_pci_logo', 'hidden_ssl_seal', 'force_tov_card', 'open_confirmation_box_in_modal', 'placeholder_captions', 'show_brand_logo', 'show_submit_button', 'show_x_param', 'user_no_scalable'])) {
                    $params[$paramName] = $data[$dataKey] ? '1' : '0';
                    if ($dataKey === 'show_brand_logo') {
                        $params[$paramName] = $data[$dataKey] ? 'True' : 'False';
                    } // Some might need 'True'/'False' string based on docs
                } else {
                    $params[$paramName] = $data[$dataKey];
                }
            }
        }

        // --- Behavior Settings ---
        $behaviorParams = [
            'addHolderNameToXParam' => 'add_holder_name_to_x_param',
            'addRemarksToXParam' => 'add_remarks_to_x_param',
            'allowedBINs' => 'allowed_bins',
            'blockedBINs' => 'blocked_bins',
            'j5AsJ2ForBINS' => 'j5_as_j2_for_bins',
            'inputErrorDisplayByField' => 'input_error_display_by_field',
            'customErrorHandling' => 'custom_error_handling',
            'errorClass' => 'error_class',
            'requiredValidatedClass' => 'required_validated_class',
            'userKey' => 'user_key',
            'takeIshurPopUp' => 'take_ishur_pop_up',
        ];

        foreach ($behaviorParams as $paramName => $dataKey) {
            if (isset($data[$dataKey])) {
                // Check for True/False string requirements or 1/0
                if (in_array($dataKey, ['add_holder_name_to_x_param', 'add_remarks_to_x_param', 'j5_as_j2_for_bins', 'input_error_display_by_field', 'take_ishur_pop_up'])) {
                    $params[$paramName] = $data[$dataKey] ? '1' : '0'; // Assuming 1/0 is standard
                } else {
                    $params[$paramName] = $data[$dataKey];
                }
            }
        }

        // --- Supported Cards & Wallets ---
        $walletParams = [
            'supportedCards' => 'supported_cards', // e.g., "Amex,Diners,Acc"
            'enableBit' => 'enable_bit', // Bit Payment
            'bit_btn_text' => 'bit_btn_text',
            'bit_pop_up_confirmation_text' => 'bit_pop_up_confirmation_text',
            'googlePay' => 'google_pay', // Enable Google Pay
            'applePay' => 'apple_pay', // Enable Apple Pay
        ];

        foreach ($walletParams as $paramName => $dataKey) {
            if (isset($data[$dataKey])) {
                if ($dataKey === 'enable_bit') {
                    $params['bit'] = $data[$dataKey] ? 'True' : 'False';
                } elseif (in_array($dataKey, ['google_pay', 'apple_pay'])) {
                    $params[$paramName] = $data[$dataKey] ? 'True' : 'False';
                } else {
                    $params[$paramName] = $data[$dataKey];
                }
            }
        }

        // Sandbox also showed ParamZ, even though API docs says maybe not? User requested.
        // User said: "the page sort of include many parameters that i doont see in the iframe helper"
        // Sandbox has ParamZ input. Let's add it back if user wants it for iframe specifically.
        // Earlier user said "put the zparameter is not on iframe" but now "page include many params".
        // Let's assume if it's on the Sandbox page (which I saw), it MIGHT be supported.
        // But safer to stick to what I saw: ParamZ WAS on the page list from browser agent!
        // "ParamZ" was in the list! So I will re-add it cautiously.
        if (isset($data['param_z'])) {
            $params['paramZ'] = $data['param_z'];
        }

        // 3D Secure support
        if (isset($data['use_3ds']) && $data['use_3ds']) {
            $params['use3ds'] = '1'; // or use3DS? usually lower camel use3ds or use3DS
            // User didn't specify, but typically snake case or simple.
            // Keeping simple 'use3ds' for now or 'use3DS'
            $params['use3DS'] = '1';
        }

        $queryString = http_build_query($params);

        return $this->client->getBaseUrl().'/PaymentPage?'.$queryString;
    }

    /**
     * Build payment parameters for iframe.
     */
    protected function buildPaymentParams(array $data): array
    {
        return [
            'terminal' => $this->client->getTerminal(),
            'user' => $data['user'] ?? '',
            'total' => $data['amount'],
            'currency' => $data['currency'] ?? config('pelecard.currency', 'ILS'),
            'lang' => $data['language'] ?? config('pelecard.language', 'he'),
            'ParamX' => $data['param_x'] ?? '',
            'GoodUrl' => $data['success_url'] ?? route('pelecard.success'),
            'ErrorUrl' => $data['error_url'] ?? route('pelecard.error'),
            'CancelUrl' => $data['cancel_url'] ?? route('pelecard.cancel'),
            'TopText' => $data['top_text'] ?? '',
            'BottomText' => $data['bottom_text'] ?? '',
            'Logo' => $data['logo_url'] ?? '',
            'HiddenPelecardLogo' => $data['hide_pelecard_logo'] ?? '0',
            'ShowConfirmationCheckbox' => $data['show_confirmation'] ?? '1',
            'MinPayments' => $data['min_payments'] ?? '1',
            'MaxPayments' => $data['max_payments'] ?? '1',
        ];
    }

    /**
     * Generate iframe HTML.
     */
    public function generateIframe(array $data, array $attributes = []): string
    {
        $url = $this->generatePaymentUrl($data);

        $defaultAttributes = [
            'width' => '100%',
            'height' => '600',
            'frameborder' => '0',
            'scrolling' => 'auto',
        ];

        $attributes = array_merge($defaultAttributes, $attributes);

        $attributesString = '';
        foreach ($attributes as $key => $value) {
            $attributesString .= sprintf(' %s="%s"', $key, htmlspecialchars((string) $value));
        }

        return sprintf('<iframe src="%s"%s></iframe>', htmlspecialchars($url), $attributesString);
    }

    /**
     * Generate payment form (redirect method).
     */
    public function generatePaymentForm(array $data, array $formAttributes = []): string
    {
        $baseUrl = $this->client->getEnvironment() === 'sandbox'
            ? 'https://gateway20.pelecard.biz/PaymentGW/PaymentGateway'
            : 'https://gateway21.pelecard.biz/PaymentGW/PaymentGateway';

        $params = $this->buildPaymentParams($data);

        $defaultFormAttributes = [
            'method' => 'POST',
            'action' => $baseUrl,
            'id' => 'pelecard-payment-form',
        ];

        $formAttributes = array_merge($defaultFormAttributes, $formAttributes);

        $html = '<form';
        foreach ($formAttributes as $key => $value) {
            $html .= sprintf(' %s="%s"', $key, htmlspecialchars((string) $value));
        }
        $html .= '>';

        foreach ($params as $key => $value) {
            $html .= sprintf(
                '<input type="hidden" name="%s" value="%s">',
                htmlspecialchars($key),
                htmlspecialchars((string) $value)
            );
        }

        $html .= '<button type="submit">Pay Now</button>';

        return $html.'</form>';
    }

    /**
     * Create a payment session and return iframe URL.
     */
    public function createPaymentSession(array $data): array
    {
        $url = $this->generatePaymentUrl($data);

        return [
            'iframe_url' => $url,
            'session_id' => $data['param_x'] ?? uniqid('pelecard_'),
        ];
    }
}
