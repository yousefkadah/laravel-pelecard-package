# Changelog

All notable changes to `laravel-pelecard` will be documented in this file.

## v1.0.0 - 2025-01-01

### Added
- Initial release
- Cashier-compatible Billable trait
- Multi-tenancy support with encrypted credentials
- Subscription management (create, cancel, resume, swap)
- One-time payment operations (authorize, charge, refund, void, capture)
- **3D Secure authentication** (initiate3DS, get3DSData)
- **Google Pay support** (debitByGooglePay)
- **iFrame integration** (hosted payment pages with Blade component)
- **Type-safe DTOs** (Request objects with validation and IDE autocomplete)
- **Error message retrieval** (getErrorMessage in Hebrew and English)
- **Complete Pelecard API coverage** (64+ services including):
  - Transaction management (abort, delete, complete)
  - EMV operations (contactless, reversal, IntIn)
  - Broadcast operations (Shva, by date, summary)
  - Terminal data (Muhlafim, phone, Sapak number)
  - Ashrait and ParamX validation
  - Track2 and Pelecloud integration
  - Administrative functions
- **Advanced token management** (convertToToken, retrieveToken, updateToken, checkCreditCardForToken)
- **Transaction retrieval** (getTransaction, getCompleteTransData, getTransDataByTrxId)
- **Invoice creation** (ICount, EZCount, Payper)
- **Payment type variations** (debitCreditType, debitPaymentsType, authorizeCreditType, authorizePaymentsType)
- Tokenization for recurring payments
- Webhook support with event dispatching
- Transaction logging
- Payment method management
- Trial period support
- Artisan commands for webhook setup and subscription syncing
- Comprehensive exception handling
- Event system for payment lifecycle
- **Code quality tools** (Laravel Pint, Rector, PHPStan/Larastan)
- Support for Laravel 10.x, 11.x, and 12.x
