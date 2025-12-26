# Laravel Pelecard Payment Gateway

[![Latest Version on Packagist](https://img.shields.io/packagist/v/yousefkadah/laravel-pelecard.svg?style=flat-square)](https://packagist.org/packages/yousefkadah/laravel-pelecard)
[![Total Downloads](https://img.shields.io/packagist/dt/yousefkadah/laravel-pelecard.svg?style=flat-square)](https://packagist.org/packages/yousefkadah/laravel-pelecard)

A comprehensive Laravel package for integrating with the Pelecard payment gateway. Built with **Laravel Cashier-level code quality**, featuring subscription billing, multi-tenancy support, and a familiar Billable trait interface.

## Features

- 🔐 **Multi-Tenancy Support** - Multiple Pelecard accounts in one application
- 💳 **Cashier-Compatible** - Familiar `Billable` trait interface
- 📦 **Subscription Management** - Create, cancel, resume, and swap subscriptions
- 🔄 **Recurring Payments** - Tokenization and automated billing
- 💰 **One-Time Payments** - Authorize, charge, refund, and void transactions
- 🖼️ **iFrame Integration** - Hosted payment pages with full customization
- 🔒 **3D Secure** - Enhanced security authentication
- 📱 **Google Pay** - Digital wallet support
- 🎯 **Type-Safe DTOs** - Request objects with validation and IDE autocomplete
- 🪝 **Webhook Support** - Automated payment notifications
- 📊 **Transaction Logging** - Complete payment history
- 🎯 **Events** - Listen to payment lifecycle events
- 🔒 **Secure** - Encrypted credential storage

## Requirements

- PHP 8.1 or higher
- Laravel 10.x, 11.x, or 12.x
- Pelecard merchant account

## Installation

Install the package via Composer:

```bash
composer require yousefkadah/laravel-pelecard
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=pelecard-config
```

Run the migrations:

```bash
php artisan migrate
```

## Configuration

Add your Pelecard credentials to your `.env` file:

```env
PELECARD_TERMINAL=your_terminal_number
PELECARD_USER=your_api_user
PELECARD_PASSWORD=your_api_password
PELECARD_ENV=sandbox  # or 'production'
```

### Multi-Tenancy Configuration

For multi-tenant applications, enable multi-tenancy in `config/pelecard.php`:

```php
'multi_tenant' => true,
```

## Usage

### Basic Setup

Add the `Billable` trait to your User model:

```php
use Yousefkadah\Pelecard\Billable;

class User extends Authenticatable
{
    use Billable;
}
```

### One-Time Payments

**Security Note:** Always use tokens for payments. Only collect card details for the initial tokenization.

#### Charge a Customer (Using Token)

```php
// Recommended: Use saved token
$user->charge(10000, 'Product purchase', [
    'token' => $user->pelecard_token,
]);
```

#### First-Time Payment (Tokenize Card)

```php
// Only for first payment: tokenize the card
$response = Pelecard::convertToToken([
    'card_number' => '4580000000000000',
    'expiry_month' => '12',
    'expiry_year' => '2025',
]);

$token = $response->get('Token');
$user->update(['pelecard_token' => $token]);

// Then charge using the token
$user->charge(10000, 'Product purchase', [
    'token' => $token,
]);
```

#### Automatic Card Saving (Recommended)

The package automatically extracts tokens from payment responses (J2/J4/J5):

```php
// Option 1: Charge and save card in one step
$response = $user->chargeAndSaveCard(10000, 'Product purchase', [
    'card_number' => '4580000000000000',
    'expiry_month' => '12',
    'expiry_year' => '2025',
    'cvv' => '123',
]);

// Token is automatically extracted and saved!
// Future payments can now use: $user->charge(amount, desc)

// Option 2: Save card from any payment response
$response = Pelecard::charge([
    'amount' => 10000,
    'currency' => 'ILS',
    'card_number' => '4580000000000000',
    'expiry_month' => '12',
    'expiry_year' => '2025',
    'cvv' => '123',
]);

if ($response->isSuccessful()) {
    // Automatically extract and save token
    $user->updateDefaultPaymentMethodFromResponse($response);
}

// Option 3: Manual token extraction
use Yousefkadah\Pelecard\Helpers\TokenExtractor;

$token = TokenExtractor::extractToken($response);
$cardDetails = TokenExtractor::extractCardDetails($response);

if ($token) {
    $user->updateDefaultPaymentMethod($token, $cardDetails);
}
```

#### Authorize (Hold Funds)

```php
// Use token for authorization
$response = Pelecard::authorize([
    'amount' => 5000,
    'currency' => 'ILS',
    'token' => $user->pelecard_token,
]);

$transactionId = $response->getTransactionId();
```

#### Capture Authorization

```php
Pelecard::capture($transactionId, 5000);
```

#### Refund a Transaction

```php
$user->refund($transactionId, 5000);
```

### Using DTOs (Type-Safe Requests)

The package provides Data Transfer Objects for type-safe API requests with IDE autocomplete:

```php
use Yousefkadah\Pelecard\DTO\ChargeRequestDTO;
use Yousefkadah\Pelecard\DTO\AuthorizeRequestDTO;
### Custom Parameters (ParamX & ParamZ)

Pelecard supports custom parameters that are returned in callbacks and webhooks for tracking:

#### API Calls (ParamX & ParamZ)

Both ParamX and ParamZ are supported in direct API calls:

```php
// Using ParamX and ParamZ for order tracking
$response = Pelecard::charge([
    'amount' => 10000,
    'currency' => 'ILS',
    'token' => $user->pelecard_token,
    'param_x' => 'order_12345',      // Order ID
    'param_z' => json_encode([        // Additional metadata
        'customer_id' => $user->id,
        'source' => 'mobile_app',
    ]),
]);

// In webhook callback, retrieve the parameters
$paramX = $request->input('ParamX'); // 'order_12345'
$paramZ = $request->input('ParamZ'); // JSON data

// With DTOs
$charge = new ChargeRequestDTO(
    amount: 10000,
    currency: 'ILS',
    token: $user->pelecard_token,
);
$charge->paramX = 'order_12345';
$charge->paramZ = json_encode(['user_id' => $user->id]);

$response = Pelecard::charge($charge->toArray());
```

#### iFrame (ParamX Only)

**Note:** iFrame only supports **ParamX**, not ParamZ:

```blade
{{-- Only ParamX is supported in iframe --}}
<x-pelecard::payment-iframe
    :amount="10000"
    param-x="order_12345"
/>
```
use Yousefkadah\Pelecard\DTO\ThreeDSRequestDTO;

// Charge with token (recommended for security)
$chargeRequest = new ChargeRequestDTO(
    amount: 10000,
    currency: 'ILS',
    token: $user->pelecardToken, // Use saved token
    email: 'customer@example.com',
    payments: 1
);

$response = Pelecard::charge($chargeRequest->toArray());

// Or charge with card details (for first-time payments)
$chargeRequest = new ChargeRequestDTO(
    amount: 10000,
    currency: 'ILS',
    cardNumber: '4580000000000000',
    expiryMonth: '12',
    expiryYear: '2025',
    cvv: '123',
    email: 'customer@example.com'
);

$response = Pelecard::charge($chargeRequest->toArray());

// Authorize with token
$authorizeRequest = new AuthorizeRequestDTO(
    amount: 5000,
    currency: 'ILS',
    cardNumber: '4580000000000000',
    expiryMonth: '12',
    expiryYear: '2025',
    cvv: '123'
);

$response = Pelecard::authorize($authorizeRequest->toArray());

// 3DS with DTO
$threeDSRequest = new ThreeDSRequestDTO(
    amount: 10000,
    currency: 'ILS',
    cardNumber: '4580000000000000',
    expiryMonth: '12',
    expiryYear: '2025',
    email: 'customer@example.com'
);

$response = Pelecard::initiate3DS($threeDSRequest->toArray());
```



### 3D Secure Authentication

3D Secure provides an additional layer of security for online card transactions.

#### Using 3DS with Saved Token

```php
// Use saved token with 3DS authentication
$response = Pelecard::initiate3DS([
    'amount' => 10000,
    'currency' => 'ILS',
    'token' => $user->pelecard_token,
]);

// Redirect user to 3DS authentication page
$redirectUrl = $response->get('RedirectUrl');
return redirect($redirectUrl);
```

#### 3DS with iFrame (Recommended)

Enable 3DS in the iframe payment page:

```blade
<x-pelecard::payment-iframe
    :amount="10000"
    currency="ILS"
    :success-url="route('payment.success')"
    :error-url="route('payment.error')"
    :use-3ds="true"
    {{-- This enables 3D Secure authentication --}}
/>
```

Or programmatically:

```php
$iframeHelper = Pelecard::for($user)->iframe();

$url = $iframeHelper->generatePaymentUrl([
    'amount' => 10000,
    'currency' => 'ILS',
    'success_url' => route('payment.success'),
    'use_3ds' => true, // Enable 3DS
    'token' => $user->pelecard_token, // Use saved token
]);
```

#### Get 3DS Data After Authentication

```php
// After 3DS authentication completes
$data = Pelecard::get3DSData($transactionId);

// The response contains the token
if ($data->isSuccessful()) {
    $token = $data->get('Token');
    if ($token) {
        $user->updateDefaultPaymentMethodFromResponse($data);
    }
}
```

### Google Pay

```php
$response = Pelecard::debitByGooglePay([
    'amount' => 10000,
    'currency' => 'ILS',
    'google_pay_token' => $googlePayToken,
]);
```

### Advanced Token Management

**Best Practice:** Always tokenize cards and use tokens for recurring payments to comply with PCI-DSS.

#### Convert Card to Token

```php
// First time: convert card to token
$response = Pelecard::convertToToken([
    'card_number' => '4580000000000000',
    'expiry_month' => '12',
    'expiry_year' => '2025',
]);

$token = $response->get('Token');

// Save token to user
$user->update(['pelecard_token' => $token]);
```

#### Charge Using Token

```php
// Subsequent payments: use token (no card details needed)
$response = Pelecard::charge([
    'amount' => 10000,
    'currency' => 'ILS',
    'token' => $user->pelecard_token,
]);
```

#### Retrieve Token Details

```php
$tokenDetails = Pelecard::retrieveToken($user->pelecard_token);
```

#### Update Token

```php
// Update expiry date when card is renewed
Pelecard::updateToken($user->pelecard_token, [
    'expiry_month' => '01',
    'expiry_year' => '2026',
]);
```


### Transaction Retrieval

```php
// Get complete transaction data
$transactions = Pelecard::getCompleteTransData([
    'from_date' => '2025-01-01',
    'to_date' => '2025-01-31',
]);

// Get specific transaction
$transaction = Pelecard::getTransaction($uniqueId);
```

### Invoice Creation

The package provides a flexible invoice builder with customizable templates.

#### Creating an Invoice

```php
use Yousefkadah\Pelecard\Helpers\InvoiceBuilder;

$invoice = (new InvoiceBuilder())
    ->number('INV-2025-001')
    ->date(now())
    ->dueDate(now()->addDays(30))
    ->vendor([
        'name' => 'Your Company',
        'address' => '123 Main St, City',
        'phone' => '+972-50-1234567',
        'email' => 'billing@company.com',
    ])
    ->customer([
        'name' => $user->name,
        'email' => $user->email,
        'address' => $user->address,
    ])
    ->addItem('Premium Subscription', 1, 10000)
    ->addItem('Setup Fee', 1, 5000)
    ->taxRate(17) // 17% VAT
    ->notes('Thank you for your business!')
    ->terms('Payment due within 30 days')
    ->currency('ILS')
    ->build();

// Render as HTML
$html = $invoice->render();

// Or use the builder directly
$html = (new InvoiceBuilder())
    ->number('INV-001')
    ->customer(['name' => 'John Doe'])
    ->addItem('Service', 1, 10000)
    ->render();
```

#### Customizing Invoice Template

Publish the invoice templates:

```bash
php artisan vendor:publish --tag=pelecard-invoices
```

This creates `resources/views/vendor/pelecard/invoices/default.blade.php` which you can customize:

```blade
{{-- resources/views/vendor/pelecard/invoices/default.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <title>Invoice #{{ $invoice->number() }}</title>
    <style>
        /* Your custom styles */
        .invoice-header { 
            background: #your-brand-color;
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <!-- Add your logo -->
        <img src="{{ asset('images/logo.png') }}" alt="Logo">
        
        <!-- Customize layout -->
        <h1>Invoice #{{ $invoice->number() }}</h1>
        
        @foreach($invoice->items() as $item)
            <div>{{ $item['description'] }}</div>
        @endforeach
        
        <div>Total: {{ $invoice->formatAmount($invoice->total()) }}</div>
    </div>
</body>
</html>
```

#### Using Custom Template

```php
$html = (new InvoiceBuilder())
    ->template('invoices.custom') // Use your custom template
    ->number('INV-001')
    ->build()
    ->render();
```

### Error Messages

```php
// Get error message in Hebrew
$errorMessage = Pelecard::getErrorMessageHe('001');

// Get error message in English
$errorMessage = Pelecard::getErrorMessageEn('001');

// Auto-detect language from config
$errorMessage = Pelecard::getErrorMessage('001');
```


### Invoice Creation

```php
// Create ICount invoice
Pelecard::createICountInvoice([
    'customer_name' => 'John Doe',
    'amount' => 10000,
    'items' => [...],
]);
```

### iFrame Payment Integration

Pelecard's iframe integration allows customers to complete payments directly on your website without being redirected to an external payment page.

#### Using the Blade Component

```blade
<x-pelecard::payment-iframe
    :amount="10000"
    currency="ILS"
    :success-url="route('payment.success')"
    :error-url="route('payment.error')"
    :cancel-url="route('payment.cancel')"
    language="he"
    top-text="Complete Your Payment"
    bottom-text="Secure payment powered by Pelecard"
    width="100%"
    height="600"
/>
```

#### Using the Helper Directly

```php
$client = Pelecard::for($user);
$iframeHelper = $client->iframe();

// Generate iframe URL
$url = $iframeHelper->generatePaymentUrl([
    'amount' => 10000,
    'currency' => 'ILS',
    'success_url' => route('payment.success'),
    'error_url' => route('payment.error'),
    'cancel_url' => route('payment.cancel'),
    'language' => 'he',
    'param_x' => 'order_123',
]);

// Generate iframe HTML
$iframeHtml = $iframeHelper->generateIframe([
    'amount' => 10000,
    'currency' => 'ILS',
    'success_url' => route('payment.success'),
], [
    'width' => '100%',
    'height' => '600',
]);

// Or generate a payment form (redirect method)
$formHtml = $iframeHelper->generatePaymentForm([
    'amount' => 10000,
    'currency' => 'ILS',
]);
```

#### Customization Options

- `top_text` - Text displayed at the top of the payment page
- `bottom_text` - Text displayed at the bottom
- `logo_url` - Your company logo URL
- `hide_pelecard_logo` - Hide Pelecard branding (boolean)
- `show_confirmation` - Show confirmation checkbox (boolean)
- `min_payments` - Minimum number of installments
- `max_payments` - Maximum number of installments
- `param_x` - Custom parameter to track the transaction



### Subscriptions

#### Create a Subscription

```php
$user->newSubscription('default', 'plan_123')
    ->trialDays(14)
    ->create($paymentMethod);
```

#### Check Subscription Status

```php
if ($user->subscribed('default')) {
    // User has an active subscription
}

if ($user->onTrial('default')) {
    // User is on trial
}
```

#### Cancel a Subscription

```php
// Cancel at end of billing period
$user->subscription('default')->cancel();

// Cancel immediately
$user->subscription('default')->cancelNow();
```

#### Resume a Cancelled Subscription

```php
$user->subscription('default')->resume();
```

#### Swap Plans

```php
$user->subscription('default')->swap('plan_456');
```

#### Update Quantity

```php
$subscription = $user->subscription('default');

$subscription->incrementQuantity();
$subscription->decrementQuantity();
$subscription->updateQuantity(5);
```

### Multi-Tenancy

#### Per-Team Credentials (SaaS Applications)

```php
// Team model
use Yousefkadah\Pelecard\Concerns\ManagesPelecardCredentials;

class Team extends Model
{
    use ManagesPelecardCredentials;
}

// Setup team credentials
$team->createPelecardCredentials(
    terminal: '1234567',
    user: 'api_user',
    password: 'api_password'
);

// User model
class User extends Model
{
    use Billable;
    
    public function pelecardCredentials()
    {
        return $this->team->pelecardCredentials;
    }
}

// Usage - automatically uses team's credentials
$user->charge(10000, 'Payment');
```

#### Direct Client Usage

```php
// Create client for specific tenant
$client = PelecardClient::for($user);
$client = PelecardClient::for($team);

// Use client
$response = $client->charge([
    'amount' => 10000,
    'currency' => 'ILS',
    'token' => 'payment_token',
]);
```

### Webhooks

Display webhook URL:

```bash
php artisan pelecard:webhook
```

Configure the webhook URL in your Pelecard dashboard to receive payment notifications.

#### Listen to Webhook Events

```php
use Yousefkadah\Pelecard\Events\PaymentSucceeded;
use Yousefkadah\Pelecard\Events\PaymentFailed;

// In EventServiceProvider
protected $listen = [
    PaymentSucceeded::class => [
        SendPaymentConfirmation::class,
    ],
    PaymentFailed::class => [
        NotifyPaymentFailure::class,
    ],
];
```

### Payment Methods

#### Update Default Payment Method

```php
$user->updateDefaultPaymentMethod($token, [
    'type' => 'card',
    'last_four' => '4242',
]);
```

#### Check for Payment Method

```php
if ($user->hasDefaultPaymentMethod()) {
    // User has a payment method on file
}
```

### Transaction History

```php
// Get all transactions for a user
$transactions = $user->pelecardTransactions;

// Get successful transactions
$successful = $user->pelecardTransactions()->successful()->get();

// Get transactions of specific type
$charges = $user->pelecardTransactions()->ofType('charge')->get();
```

## Artisan Commands

### Display Webhook URL

```bash
php artisan pelecard:webhook
```

### Sync Subscriptions

```bash
# Sync all subscriptions
php artisan pelecard:sync-subscriptions

# Sync for specific user
php artisan pelecard:sync-subscriptions --user=1
```

## Events

The package dispatches the following events:

- `PaymentSucceeded` - When a payment is successful
- `PaymentFailed` - When a payment fails
- `SubscriptionCreated` - When a subscription is created
- `SubscriptionCancelled` - When a subscription is cancelled
- `SubscriptionUpdated` - When a subscription is updated

## Code Quality Tools

This package uses industry-standard code quality tools to maintain high code standards:

### Laravel Pint (Code Formatting)

```bash
# Format code automatically
composer format

# Check formatting without making changes
composer format-check
```

### Rector (Automated Refactoring)

```bash
# Apply automated refactoring
composer refactor

# Preview changes without applying
composer refactor-dry
```

### PHPStan (Static Analysis)

```bash
# Run static analysis
composer analyse
```

### Run All Quality Checks

```bash
# Run formatting, refactoring check, analysis, and tests
composer quality
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Security

If you discover any security-related issues, please email yousef@example.com instead of using the issue tracker.

## Credits

- [Yousef Kadah](https://github.com/yousefkadah)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
