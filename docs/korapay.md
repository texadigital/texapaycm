Jump to Content
Kora Developer Documentation
API Reference
Create Account
Log In
Home
Guides

Search
⌘K
🟢 Get Started
Introduction
API Keys
Test & Live Modes

Webhooks
Errors
Frequently Asked Questions (FAQ)

↙️ Pay-Ins
Overview
Checkouts

Payment Links
Virtual Bank Accounts

Pool Accounts
Bank Transfers

Card Payments

Pay with Bank
Pay with Bank (Instant EFT)
Mobile Money Payments

Pay-ins History API
Refunds API

↗️ Payouts
Overview
Payout API

Withdrawals
Bulk Payouts via API
💳 Card Issuing
Overview
Issuing Balance
Virtual Card Creation
Virtual Card Funding
Virtual Card Withdrawals
Virtual Card Management

Testing Virtual Cards
👤 Identity
Overview
KYC Verification (eIDV) in Nigeria

KYB Verification (eIDV) in Nigeria

KYC Verification (eIDV) in South Africa

KYC Verification (eIDV) in Ghana

KYC Verification (eIDV) in Kenya

Testing Identity on the Sandbox Environment
Managing Verifications
💰 Balance
Overview
Balance API
Balance History API
🔀 Conversions
Overview
Exchange Rate API
Currency Conversion API
Dynamic Currency Conversion
Initiating and Managing Conversions on the Dashboard
Testing Conversions on the Sandbox Environment
☑️ Settlements
Overview
Settlements
🌍 Dashboard
Overview
Audit Logs
⚡️ Plugins
Kora WooCommerce Plugin
Powered by 

Introduction
Welcome to Kora!

In this documentation, you’ll find comprehensive information on how to successfully integrate our services with your web and mobile applications. It's developer-friendly and we've filled it with helpful examples, but if you have any questions, you can head over to our Help Center to get more information or better, contact our Support Team.

Kora offers everything you'll need to create delightful payment experiences for your customers. Whether you're building a simple platform to collect payments from your customers or creating a powerful application to manage your business payments, we will provide you with all the resources needed to achieve your payment goals.

👍
Perfect time to create an account!
For a smooth integration experience, you’ll need your API keys which you can receive by signing up on the dashboard or contacting us.

Let's take a quick look at some of the ways you could use our products:

Accept Payments with Pay-ins
Send Money with Payouts
Issue Virtual Cards to Customers [Beta]
Verify Identities of Customers and Businesses
Retrieve Balance Information with API
Get Settled
Ready to build? Let's go 🚀

Updated 4 months ago

API Keys
Did this page help you?
Jump to Content
Kora Developer Documentation
API Reference
Create Account
Log In
Home
Guides

Search
⌘K
🟢 Get Started
Introduction
API Keys
Test & Live Modes

Webhooks
Errors
Frequently Asked Questions (FAQ)

↙️ Pay-Ins
Overview
Checkouts

Payment Links
Virtual Bank Accounts

Pool Accounts
Bank Transfers

Card Payments

Pay with Bank
Pay with Bank (Instant EFT)
Mobile Money Payments

Pay-ins History API
Refunds API

↗️ Payouts
Overview
Payout API

Withdrawals
Bulk Payouts via API
💳 Card Issuing
Overview
Issuing Balance
Virtual Card Creation
Virtual Card Funding
Virtual Card Withdrawals
Virtual Card Management

Testing Virtual Cards
👤 Identity
Overview
KYC Verification (eIDV) in Nigeria

KYB Verification (eIDV) in Nigeria

KYC Verification (eIDV) in South Africa

KYC Verification (eIDV) in Ghana

KYC Verification (eIDV) in Kenya

Testing Identity on the Sandbox Environment
Managing Verifications
💰 Balance
Overview
Balance API
Balance History API
🔀 Conversions
Overview
Exchange Rate API
Currency Conversion API
Dynamic Currency Conversion
Initiating and Managing Conversions on the Dashboard
Testing Conversions on the Sandbox Environment
☑️ Settlements
Overview
Settlements
🌍 Dashboard
Overview
Audit Logs
⚡️ Plugins
Kora WooCommerce Plugin
Powered by 

API Keys
To enable communication between your application and Korapay, you'll need your API Keys. Kora authenticates every request your application makes with these keys. Generally, every account comes with two sets of API keys - public and secret API keys for Test and Live modes.

Your public keys are non-sensitive identifiers that can be used on the client-side of your application. By design, public keys cannot modify any part of your account besides initiating transactions for you. Your secret keys, on the other hand, are to be kept secret and confidential. They are required for accessing any financial data and can be used to make any API call on your account. Your secret keys should never be shared on any client-side code. Treat them like any other password. And, if for any reason you believe your secret key has been compromised simply reset them by generating new keys. You can generate new API keys from your dashboard.

Obtaining your API Keys
Your API keys are always available on your dashboard. To find your API keys,

Login to your dashboard.
Navigate to Settings on the side menu.
Go to the API Configuration tab on the Settings page.
In the Korapay APIs section, you’d see both your Public and Secret keys, and a button to Generate New API Keys.
🚧
The API keys in test mode are different from the API keys in Live mode. So you need to always ensure that you do not misuse the keys when you switch between modes.

Generating new API Keys
You should always keep your API keys safe and protect your account. However, in the event where your API keys have been compromised, you can easily generate new API keys. Simply click the 'Generate New API Keys' button under the API Configuration tab on the Settings page.


Once you generate new API keys, the old keys become void - you can no longer use them to make API calls. Make sure to update your application to use the newly generated keys.

Updated 8 months ago

Introduction
Test & Live Modes
Did this page help you?
Table of Contents
Obtaining your API Keys
Generating new API Keys


Jump to Content
Kora Developer Documentation
API Reference
Create Account
Log In
Home
Guides

Search
⌘K
🟢 Get Started
Introduction
API Keys
Test & Live Modes

Testing your Integration
Webhooks
Errors
Frequently Asked Questions (FAQ)

↙️ Pay-Ins
Overview
Checkouts

Payment Links
Virtual Bank Accounts

Pool Accounts
Bank Transfers

Card Payments

Pay with Bank
Pay with Bank (Instant EFT)
Mobile Money Payments

Pay-ins History API
Refunds API

↗️ Payouts
Overview
Payout API

Withdrawals
Bulk Payouts via API
💳 Card Issuing
Overview
Issuing Balance
Virtual Card Creation
Virtual Card Funding
Virtual Card Withdrawals
Virtual Card Management

Testing Virtual Cards
👤 Identity
Overview
KYC Verification (eIDV) in Nigeria

KYB Verification (eIDV) in Nigeria

KYC Verification (eIDV) in South Africa

KYC Verification (eIDV) in Ghana

KYC Verification (eIDV) in Kenya

Testing Identity on the Sandbox Environment
Managing Verifications
💰 Balance
Overview
Balance API
Balance History API
🔀 Conversions
Overview
Exchange Rate API
Currency Conversion API
Dynamic Currency Conversion
Initiating and Managing Conversions on the Dashboard
Testing Conversions on the Sandbox Environment
☑️ Settlements
Overview
Settlements
🌍 Dashboard
Overview
Audit Logs
⚡️ Plugins
Kora WooCommerce Plugin
Powered by 

Testing your Integration
It is important to test your integration before going live to make sure it works properly. That’s why we created test bank accounts, mobile money numbers and test cards for you to simulate different payment scenarios as you integrate with Kora.

Testing Payouts to Bank Accounts
Use the following bank accounts to test these scenarios for your Bank Transfer payout integration:

Scenario	Currency	Bank Code	Account Number
Successful Payout	NGN	033	0000000000
Failed Payout	NGN	035	0000000000
Error: Invalid Account	NGN	011	9999999999
Successful Payout	KES	0068	000000000000
Failed Payout	KES	0053	000000000000
Testing Payouts to Mobile Money
Use the following mobile money details to test these scenarios for your Mobile Money payout integration:

Scenario	Currency	Mobile Money Operator	Mobile Number
Successful Payout	KES	safaricom-ke	254711111111
Failed Payout	KES	airtel-ke	254722222222
Successful Payout	GHS	airtel-gh	233242426222
Failed Payout	GHS	mtn-gh	233722222222
Testing Pay-in Mobile Money
Use the following mobile money numbers to test different scenarios for your Mobile Money pay-in integration:

Scenario	Mobile Number	Currency	OTP	PIN
Successful Payment	254700000000	KES	N/A	1234
Failed Payment	254734611986	KES	N/A	1234
Successful Payment	233240000000	GHS	123456	1234
Failed Payment	233274611986	GHS	123456	1234
Test Cards
Real payment cards would not work in Test mode. If you need to test your card payment integration, you can use any of the following test cards:

For Successful Payment (No Authentication) - Visa
Card Number: 4084 1278 8317 2787
Expiry Date: 09/30
CVV: 123

For Successful Payment (with PIN) - Mastercard
Card Number: 5188 5136 1855 2975
Expiry Date: 09/30
CVV: 123
PIN: 1234

For Successful Payment (with OTP) - Mastercard
Card Number: 5442 0561 0607 2595
Expiry Date: 09/30
CVV: 123
PIN: 1234
OTP: 123456

For Successful Payment (with 3D Secure) - Visa
Card Number: 4562 5437 5547 4674
Expiry Date: 09/30
CVV: 123
OTP: 1234

Successful (with Address Verification Service, AVS) - Mastercard
Card Number: 5384 0639 2893 2071
Expiry Date: 09/30
CVV: 123
PIN: 1234

For Address
City: Lekki
Address: Osapa, Lekki
State: Lagos
Country: Nigeria
Zip Code: 101010

Successful (with Card Enroll) - Verve
Card Number: 5061 4604 1012 0223 210
Expiry Date: 09/30
CVV: 123
PIN: 1234
OTP: 123456

For Failed Payment (Insufficient Funds) - Verve
Card Number: 5060 6650 6066 5060 67
Expiry Date: 09/30
CVV: 408

To simplify testing your card integrations in Test mode, we already created these scenarios on the test Checkout and prefilled the card details for each scenario.


🚧
It is important to note that, just as real payment instruments do not work in Test mode, test cards and bank accounts cannot be used in Live mode or for real payments.

Testing Identity
To test identity verification scenarios on the sandbox environment, the test data below should be used:

For Kenya

Document Type	Scenario	ID Number
International Passport	Valid	A2011111
International Passport	Invalid	A0000000
National ID	Valid	25219766
National ID	Invalid	00000000
Tax PIN	Valid	A009274635J
Tax PIN	Invalid	A0000000000
Phone Number	Valid	0723818211

For Ghana

Document Type	Scenario	ID Number
SSNIT	Valid	C987464748977
SSNIT	Invalid	C000000000000
Driver's License	Valid	070667
Driver's License	Invalid	000000
International Passport	Valid	G0000555
International Passport	Invalid	G0000000
Voters Card	Valid	9001330422
Voters Card	Invalid	0000000000

For Nigeria

Document Type	Scenario	ID Number
BVN	Valid	22222222222
BVN	Invalid	00000000000
vNIN	Valid	KO111111111111IL
vNIN	Invalid	KO000000000000II
NIN	Valid	55555555555
NIN	Invalid	00000000000
International Passport	Valid	A01234567
International Passport	Invalid	A00000000
Voters Card (PVC)	Valid	00A0A0A000000000011
Voters Card (PVC)	Invalid	11A1A1A111111111111
Phone Number	Valid	08000000000
Phone Number	Invalid	08000000001
CAC (RC Number)	Valid	RC00000011
CAC (RC Number)	Invalid	RC11111111

For South Africa

Document Type	Scenario	ID Number
SAID	Valid	8012185201077
SAID	Invalid	8000000000001
Updated 8 months ago

Test & Live Modes
Webhooks
Did this page help you?
Table of Contents
Testing Payouts to Bank Accounts
Testing Payouts to Mobile Money
Testing Pay-in Mobile Money
Test Cards
Testing Identity


Jump to Content
Kora Developer Documentation
API Reference
Create Account
Log In
Home
Guides

Search
⌘K
🟢 Get Started
Introduction
API Keys
Test & Live Modes

Webhooks
Errors
Frequently Asked Questions (FAQ)

↙️ Pay-Ins
Overview
Checkouts

Payment Links
Virtual Bank Accounts

Pool Accounts
Bank Transfers

Card Payments

Pay with Bank
Pay with Bank (Instant EFT)
Mobile Money Payments

Pay-ins History API
Refunds API

↗️ Payouts
Overview
Payout API

Withdrawals
Bulk Payouts via API
💳 Card Issuing
Overview
Issuing Balance
Virtual Card Creation
Virtual Card Funding
Virtual Card Withdrawals
Virtual Card Management

Testing Virtual Cards
👤 Identity
Overview
KYC Verification (eIDV) in Nigeria

KYB Verification (eIDV) in Nigeria

KYC Verification (eIDV) in South Africa

KYC Verification (eIDV) in Ghana

KYC Verification (eIDV) in Kenya

Testing Identity on the Sandbox Environment
Managing Verifications
💰 Balance
Overview
Balance API
Balance History API
🔀 Conversions
Overview
Exchange Rate API
Currency Conversion API
Dynamic Currency Conversion
Initiating and Managing Conversions on the Dashboard
Testing Conversions on the Sandbox Environment
☑️ Settlements
Overview
Settlements
🌍 Dashboard
Overview
Audit Logs
⚡️ Plugins
Kora WooCommerce Plugin
Powered by 

Webhooks
Webhooks provide a way to receive notifications for your transactions in real time. While your transaction is being processed, its status progresses until it is completed. This makes it very important to get the final status for that transaction, and that's where webhooks are very beneficial.

Put simply, a webhook URL is an endpoint on your server that can receive API requests from Korapay’s server. Note that the request is going to be an HTTP POST request.

Setting your Webhook URL
You can specify your webhook URL in the API Configuration tab of the Settings page of your dashboard. Make sure that the webhook URL is unauthenticated and publicly available.


The request to the webhook URL comes with a payload, and this payload contains the details of the transaction for which you are being notified.

Webhook Notification Request Payload Definitions
Field	Data Type	Description
event	String	transfer.success, transfer.failed, charge.success or charge.failed, refund.success, refund.failed
data	Object	The object containing transaction details: amount, fee, currency, status, reference
data.amount	Number	Transaction amount
data.fee	Number	Transaction fee
data.currency	String	Transaction currency
data.status	String	Transaction status. This can be success or failed.
data.reference	String	Transaction reference. This reference can be used to query the transaction

Sample Webhook Notification Payloads
Single Payout
Bulk Payout
Pay-in (NG Virtual Bank Account)
Pay-in (Cards, Bank Transfer, Mobile Money)
Refunds

/*
* Applicable Events: "transfer.success", "transfer.failed"
*/
{
  "event": "transfer.success",
  "data": {
    "fee": 15,
    "amount": 150.99,
    "status": "success",
    "currency": "NGN",
    "reference": "Z78EYMAUBQ5"
  }
}

Verifying a Webhook Request
It is important to verify that requests are coming from Korapay to avoid delivering value based on a counterfeit request. To verify our requests, you need to validate the signature assigned to the request.
Valid requests are sent with a header x-korapay-signature which is essentially an HMAC SHA256 signature of ONLY the data object in response payload signed using your secret key.

JavaScript
Java
PHP

const crypto = require(“crypto”);
const secretKey = sk_live_******

router.post(‘/your_webhook_url’, (req, res, next) => {
  const hash = crypto.createHmac('sha256', secretKey).update(JSON.stringify(req.body.data)).digest('hex');

   If (hash === req.headers[‘x-korapay-signature’]) {
     // Continue with the request functionality
   } else {
     // Don’t do anything, the request is not from us.
   }
});
Responding to a Webhook Request
It is important to respond to the requests with a 200 status code to acknowledge that you have received the requests. Korapay does not pay attention to any request parameters apart from the request status code.

Please note that if any other response code is received, or there’s a timeout while sending the request, we retry the request periodically within 72 hours after which retries stop.

Resending a Webhook via the Kora Dashboard
For every transaction, Kora sends a webhook notification to the merchant’s configured webhook URL. If the transaction status is Pending/Failed, the ‘Resend Webhook’ button becomes activated on the dashboard, allowing you to manually trigger the webhook notification again.

Conditions for Resending Webhooks
For Payouts

When channel is api and status is successful or failed.
For Pay-ins

When channel is api and status is successful or failed.
When channel is modal and status is successful.
How to Resend a Webhook Notification on a Transaction
On your Kora Dashboard, navigate to the Webhook / Metadata section of the transaction's detail.
Locate the webhook notification for the transaction in question.
If the status is Pending/Failed, the Resend Webhook button will be displayed in the top-right corner.
Click the Resend Webhook button to manually trigger a new webhook notification. Be sure that you need to resend the webhook to avoid duplicate transactions on your end.
The webhook request will be reattempted and the updated status can be reviewed in the dashboard.

Best Practices
It is recommended to do the following when receiving webhook notifications from us:

Keep track of all notifications received: It’s important to keep track of all notifications you’ve received. When a new notification is received proceed to check that this has not been processed before giving value. A retry of already processed notifications can happen if we do not get a 200 HTTP Status code from your notification URL, or if there was a request time out.
Acknowledge receipt of notifications with a 200 HTTP status code: It’s recommended you immediately acknowledge receipt of the notification by returning a 200 HTTP Status code before proceeding to perform other logics, failure to do so might result in a timeout which would trigger a retry of such notification.
Updated 6 months ago

Testing your Integration
Errors
Did this page help you?
Table of Contents
Setting your Webhook URL
Webhook Notification Request Payload Definitions
Sample Webhook Notification Payloads
Verifying a Webhook Request
Responding to a Webhook Request
Resending a Webhook via the Kora Dashboard
Conditions for Resending Webhooks
How to Resend a Webhook Notification on a Transaction
Best Practices
Jump to Content
Kora Developer Documentation
API Reference
Create Account
Log In
Home
Guides

Search
⌘K
🟢 Get Started
Introduction
API Keys
Test & Live Modes

Webhooks
Errors
Frequently Asked Questions (FAQ)

↙️ Pay-Ins
Overview
Checkouts

Payment Links
Virtual Bank Accounts

Pool Accounts
Bank Transfers

Card Payments

Pay with Bank
Pay with Bank (Instant EFT)
Mobile Money Payments

Pay-ins History API
Refunds API

↗️ Payouts
Overview
Payout API

Withdrawals
Bulk Payouts via API
💳 Card Issuing
Overview
Issuing Balance
Virtual Card Creation
Virtual Card Funding
Virtual Card Withdrawals
Virtual Card Management

Testing Virtual Cards
👤 Identity
Overview
KYC Verification (eIDV) in Nigeria

KYB Verification (eIDV) in Nigeria

KYC Verification (eIDV) in South Africa

KYC Verification (eIDV) in Ghana

KYC Verification (eIDV) in Kenya

Testing Identity on the Sandbox Environment
Managing Verifications
💰 Balance
Overview
Balance API
Balance History API
🔀 Conversions
Overview
Exchange Rate API
Currency Conversion API
Dynamic Currency Conversion
Initiating and Managing Conversions on the Dashboard
Testing Conversions on the Sandbox Environment
☑️ Settlements
Overview
Settlements
🌍 Dashboard
Overview
Audit Logs
⚡️ Plugins
Kora WooCommerce Plugin
Powered by 

Errors
The possible errors returned from Korapay’s API can be grouped into three main categories - General errors, Payout errors, Pay-in errors, and Refund errors.

General Errors
Internal Server Error
This response does not indicate any error with your request, so you can requery the transaction to get a final status or you can report this to us.

Invalid authorization key
This response does not indicate any error with your request. Requery the transaction to get the final status.

Invalid request data
This error occurs when the request is sent with invalid data, more details of the error can be found in the data object which is also sent back as a response. Try the request again once the errors returned in the data object is resolved.

Pay-In Errors
Charge not found
This error occurs when the deposit order ID sent in the request does not exist on our system. This can be treated as a failed transaction.

Duplicate payment reference
This error occurs when the reference sent in the request has already been used for a previous transaction.

*You can see more specific API errors under the guide for each pay-in type.

Payout Errors
Errors that occur before the Payout is initiated
Unable to resolve bank account.
This error occurs when our system is unable to successfully validate a customer’s bank account to determine if it’s valid or not. This can be treated as a failed withdrawal. There would be no need to query for a final status as the withdrawal would not exist on our system. Querying the withdrawal will return the error “Transaction not found”.

Transaction not found
This error occurs when the withdraw order ID attached to the request does not exist on our system. This can be treated as a failed transaction

Invalid account.
This error occurs when the bank account details provided for a withdrawal is not valid. This can be treated as a failed transaction. There would be no need to query for a final status as the transaction would not exist on our system. Querying the withdrawal will return the error “Transaction not found”.

Invalid bank provided.
This error occurs when the destination bank provided for withdrawal is not supported on our system or the bank code is invalid. This can be treated as a failed transaction. There would be no need to query for a final status as the transaction would not exist on our system. Querying the withdrawal will return the error “Transaction not found”

Invalid mobile money operator.
This error occurs when the mobile money operator provided for mobile money payout is not supported on our system or the operator code is invalid. This can be treated as a failed transaction. There would be no need to query for a final status as the transaction would not exist on our system. Querying the withdrawal will return the error “Transaction not found”

Insufficient funds in disbursement wallet
This error occurs when the funds available in your wallet is not enough to process a withdrawal request. This can be treated as a failed withdrawal. Try the request again with a new order ID once funds have been added to your wallet.

Duplicate Transaction Reference. Please use a unique reference
This error occurs when the reference sent in the request has already been used for a previous transaction.

Reasons a Payout could fail after it is initiated
After a payout is initiated, it is possible to get any of the following error responses when you query the transaction using the payment reference.

Insufficient funds in disbursement wallet
This means that the funds available in your merchant wallet are not enough to process the transaction. Try the request again with a new order ID once funds have been added to your wallet

Dormant account
This means that the destination bank account details provided has been marked as dormant by the destination bank and is unable to accept payments for that purpose. Try the request again with a new reference and bank details or have the customer reach out to their bank for further assistance.

Timeout waiting for response from destination
This means that the destination bank did not respond on time. Have the customer try again at a later time or with a different bank.

Destination bank is not available
This means that the destination bank could not be contacted. Have the customer try again at a later time or with a different bank.

Payout terminated due to suspected fraud
This means that the transaction was flagged as fraudulent.

Do not honor
This means that the bank declined the transaction for reasons best known to them, or when a restriction has been placed on a customer’s account. Try the request again at a later time with a new reference or have the customer provide a different bank.

If the problem persists, please advise the customer to contact their bank.

Payout limit exceeded
This means that the transaction being attempted will bring the customer's bank balance above the maximum limit set by their bank or that they have exceeded their limit for that day. Try the request again with a new reference or have the customer provide a different bank.

Unable to complete this transaction
This means the transaction could not be completed successfully due to downtime with the payment switch as of when the transaction was attempted. Try the request again with a new reference at a later time.

Invalid transaction
This is an error from the payment switch. Try the request again with a new reference.

Payout failed
This means the transaction could not be completed successfully for some unknown reason. Try the request again with a new reference

Refund Initiation Errors
Transaction has not yet been settled
This means that the transaction you are attempting to refund has not yet been settled to your balance. Until the transaction is settled, funds cannot be reversed. Please attempt the refund again after the expected settlement date.

Refund can only be requested on a successful transaction
This means that the original transactions wasn't processed successfully. You can only request a refund for a transaction that was successfully processed.

Transaction not found
This error occurs when the transaction reference submitted for the refund does not exist on our system. This can be treated as a failed refund.

Refund already exists with reference **reference submitted**
This error occurs when the reference sent in the request has already been used for a previously initiated refund. Please attempt the refund again with a different reference.

Refund not supported for this currency, please contact support
This error occurs when refund is not supported for the transaction currency. Please get in touch with our support team. They'll be able to guide on how to proceed.

Refund amount cannot be more than **{currency} {transactionAmountCollected}**
This means that the refund amount provided exceeds the value of the original successful transaction amount. Please update the refund amount you are trying to process and try again

Refund amount cannot be less than **{currency} {minimumRefundAmount}**
This means that the refund amount specified is below the minimum allowed value for processing. The system enforces a minimum refund amount for the currencies supported as shown here. Please get in touch with our support team if you need this reviewed

A full reversal has already been processed for this transaction
This means that a reversal equivalent to the original transaction amount has already been successfully initiated. Please verify the details of the reversal(s) on the transaction details page of the dashboard

A full refund cannot be initiated for this transaction. Please enter an amount less than or equal to **{currency} {transactionAmountCollected}**
This error occurs when no refund amount is passed in the request and the system tries to process a full refund but the total remaining refundable amount for the transaction is less than amount collected. To resolve this, please enter the specific amount you wish to refund. This amount must be less or equal to the amount left to be refunded.

The maximum refundable amount for this transaction is **{currency} {transactionAmountCollected minus amountAlreadyReversed}**
This error occurs when the amount requested to be refunded is more than the maximum refundable amount. To resolve this, please enter the specific amount you wish to refund. This amount must be less or equal to the amount left to be refunded.

Insufficient funds in disbursement wallet
This error occurs when the funds available in your wallet is not enough to process a refund request. This can be treated as a failed refund. Try the request again with the same refund reference once funds have been added to your wallet.

Updated 6 months ago

Webhooks
Frequently Asked Questions (FAQ)
Did this page help you?
Table of Contents
General Errors
Pay-In Errors
Payout Errors
Errors that occur before the Payout is initiated
Reasons a Payout could fail after it is initiated
Refund Initiation Errors

Jump to Content
Kora Developer Documentation
API Reference
Create Account
Log In
Home
Guides

Search
⌘K
🟢 Get Started
Introduction
API Keys
Test & Live Modes

Webhooks
Errors
Frequently Asked Questions (FAQ)

↙️ Pay-Ins
Overview
Checkouts

Payment Links
Virtual Bank Accounts

Pool Accounts
Bank Transfers

Card Payments

Pay with Bank
Pay with Bank (Instant EFT)
Mobile Money Payments

Pay-ins History API
Refunds API

↗️ Payouts
Overview
Payout API

Withdrawals
Bulk Payouts via API
💳 Card Issuing
Overview
Issuing Balance
Virtual Card Creation
Virtual Card Funding
Virtual Card Withdrawals
Virtual Card Management

Testing Virtual Cards
👤 Identity
Overview
KYC Verification (eIDV) in Nigeria

KYB Verification (eIDV) in Nigeria

KYC Verification (eIDV) in South Africa

KYC Verification (eIDV) in Ghana

KYC Verification (eIDV) in Kenya

Testing Identity on the Sandbox Environment
Managing Verifications
💰 Balance
Overview
Balance API
Balance History API
🔀 Conversions
Overview
Exchange Rate API
Currency Conversion API
Dynamic Currency Conversion
Initiating and Managing Conversions on the Dashboard
Testing Conversions on the Sandbox Environment
☑️ Settlements
Overview
Settlements
🌍 Dashboard
Overview
Audit Logs
⚡️ Plugins
Kora WooCommerce Plugin
Powered by 

Overview
Pay-ins (also called Collections) are in-bound payments or fund transfers that you receive into your merchant account. Kora’s Pay-ins service offers a flexible suite of products that enable you to manage payments efficiently, whether through the dashboard or by integrating Kora’s secure APIs.

You can embed a simple Checkout widget to your website/app with minimal technical expertise or create a custom integration using our robust processing engine. The API allows you to securely accept payments from multiple sources, offering your customers flexibility through various payment methods like cards, bank transfers and mobile money.

To receive Pay-ins, you’ll need:

A verified Kora merchant account (Create your account here).
Configured payment methods such as card payments, mobile money, or bank transfers. Kindly reach out to our Support Team for inquiries on this configuration.
💡
Currently, Kora supports the receiving payments via:

Card Payments: Available in Nigeria (NGN).
Mobile Money: Available in Kenya (KES), Ghana (GHS), Cameroon (XAF), Ivory Coast (XOF).
Bank Transfers: Available in Nigeria (NGN).
Pay with Bank: Available in Nigeria (NGN).
EFTs: Available in South Africa (ZAR).
Virtual Bank Account: Available in Nigerian Naira (NGN)
Pay-in Channels
Kora offers multiple ways to securely accept payments. These are known as Channels, and is shown in your transaction details. They include:

Checkouts
A simple and secure gateway for customers to complete transactions through a variety of payment methods.

Payment Links
No-code, shareable links that allow customers to pay without needing an API integration.

Virtual Bank Accounts
Designated virtual bank accounts into which customers can safely transfer funds without hassle.

API
Programmatically receive payments via Kora’s secure API, supporting cards, mobile money, virtual bank accounts, and EFTs.

Updated 8 months ago

How can I obtain test data to simulate transactions in the Sandbox environment?
Checkouts
Did this page help you?
Table of Contents
Pay-in Channels
Checkouts
Payment Links
Virtual Bank Accounts
API


Jump to Content
Kora Developer Documentation
API Reference
Create Account
Log In
Home
Guides

Search
⌘K
🟢 Get Started
Introduction
API Keys
Test & Live Modes

Webhooks
Errors
Frequently Asked Questions (FAQ)

↙️ Pay-Ins
Overview
Checkouts

Payment Links
Virtual Bank Accounts

Pool Accounts
Bank Transfers

Card Payments

Pay with Bank
Pay with Bank (Instant EFT)
Mobile Money Payments

Accept Mobile Money Payment with Checkout
Accept Mobile Money Payment with APIs
Pay-ins History API
Refunds API

↗️ Payouts
Overview
Payout API

Withdrawals
Bulk Payouts via API
💳 Card Issuing
Overview
Issuing Balance
Virtual Card Creation
Virtual Card Funding
Virtual Card Withdrawals
Virtual Card Management

Testing Virtual Cards
👤 Identity
Overview
KYC Verification (eIDV) in Nigeria

KYB Verification (eIDV) in Nigeria

KYC Verification (eIDV) in South Africa

KYC Verification (eIDV) in Ghana

KYC Verification (eIDV) in Kenya

Testing Identity on the Sandbox Environment
Managing Verifications
💰 Balance
Overview
Balance API
Balance History API
🔀 Conversions
Overview
Exchange Rate API
Currency Conversion API
Dynamic Currency Conversion
Initiating and Managing Conversions on the Dashboard
Testing Conversions on the Sandbox Environment
☑️ Settlements
Overview
Settlements
🌍 Dashboard
Overview
Audit Logs
⚡️ Plugins
Kora WooCommerce Plugin
Powered by 

Accept Mobile Money Payment with APIs
Another way to accept payments is by using our Mobile Money APIs. The comprehensive guide below will walk you through the process of successfully accepting mobile money on Kora using our APIs.

Get started with accepting Mobile money using APIs
We currently support payments in Kenyan Shillings, Ghanaian Cedis, Cameroonian CFA Franc & Ivorian CFA Franc. For Kenya, we support the following wallets; Mpesa, Airtel, and Equitel. While for Ghana we support; MTN Momo and Airtel Tigo. For Cameroon and Ivory Coast we support both MTN and Orange. Here are some test mobile money numbers we created to help you simulate different scenarios for mobile money payments as you integrate.

Step 1: Collect payment data required to initiate payment
To charge a customer, you will need to collect the necessary payment information from the customer.
Here are the request parameters.

Parameter

Type

Required

Description

reference

String

True

A unique reference for the payment. The reference must be at least 8 characters long.

amount

Number

True

The amount for the charge

currency

String

True

The currency for the charge

redirect_url

String

False

A URL to which we can redirect your customer after their payment is complete

customer

Object

True

The information of the customer you want to charge

customer.name

String

False

The name of your customer

customer.email

String

True

The email of your customer

mobile_money

Object

True

Object holding the mobile money wallet details

mobile_money.number

String

True

The mobile number of the customer to be charged e.g 254700000000

notification_url

String

False

The webhook URL to be called when the transaction is complete.

merchant_bears_cost

Boolean

False

This sets who bear the fees of the transaction. If it is set to true, the merchant will bear the fee. If it is set to false, the customer will bear the fee. By default, it is false.

description

String

False

Information/narration about the transaction

metadata

Object

False

It takes a JSON object with a maximum of 5 fields/keys. Empty JSON objects are not allowed.

Each field name has a maximum length of 20 characters. Allowed characters: A-Z, a-z, 0-9, and -.

After collecting the necessary mobile money payment information from your customer, prepare your data object to look like the example shown below.

JSON

{
    "amount": 701,
    "currency": "GHS",
    "reference": "idkMain27105551011",
    "description": "Payment for a emilokan",
    "notification_url": "https://webhook.site/1c209942-1a66-4cdf-a7ab-78d9cc684ad2",
    "redirect_url": "https://webhook.site",
    "customer": {
        "name": "John Doe",
        "email": "John@yahoo.com"
    },
    "merchant_bears_cost": true,
    "mobile_money": {
      "number": "254700000000"
    }
}
To charge the number make a POST request with the payload to our charge mobile money endpoint. Ensure to include the relevant country currency.

Endpoint - POST https://api.korapay.com/merchant/api/v1/charges/mobile-money

If the request is successful, you should receive a message with either OTP or STK_PROMPT as the auth model.

Sample OTP Auth Response:

JSON

{
    "status": true,
    "code": "AA001",
    "message": "Authorization required",
    "data": {
        "amount": 701,
        "amount_expected": 701,
        "currency": "GHS",
        "fee": 1.87,
        "vat": 0.13,
        "auth_model": "OTP",
        "transaction_reference": "KPY-PAY-rYF4c5ZWioeb",
        "payment_reference": "idkMain27105551021",
        "status": "processing",
        "narration": "Payment for a emilokan",
        "message": "Token generated and sent out successfully",
        "mobile_money": {
            "number": "+254700000000"
        },
        "customer": {
            "name": "John Doe",
            "email": "John@yahoo.com"
        }
    }
}
Sample STK_PROMPT Auth Response:

JSON

{  
    "status": true,  
    "code": "AA001",  
    "message": "Authorization required",  
    "data": {  
        "amount": 10,  
        "amount_expected": 10,  
        "currency": "KES",  
        "fee": 0.25,  
        "vat": 0.04,  
        "auth_model": "STK_PROMPT",  
        "transaction_reference": "KPY-PAY-rYF4c5ZWioeb",  
        "payment_reference": "KPY-PAY-79lsPQSqHXSz",  
        "status": "processing",  
        "narration": "Live Test Link",  
        "message": "You will receive a pin prompt on your mobile number +25470000000 for GHS 10. Kindly enter your wallet PIN to authorize the payment",  
        "mobile_money": {  
            "number": "254700000000"  
        },  
        "customer": {  
            "name": "John Doe",  
            "email": "John@yahoo.com"
        }  
    }  
}
Step 2: Authorize mobile money transaction
The next step is based on the auth model returned in the previous response after initiating the charge. There are 2 ways of authorizing a transaction OTP and STK_PROMPT.

Authorizing an OTP transaction
After making the request to charge the number, if the status of the transaction is processing and auth_model is OTP, this means an OTP has been sent to the wallet owner's phone. You would need to collect the OTP in order to authorize the transaction.

Collect the OTP sent to the customer’s phone and make a request to our authorize endpoint with the OTP and the transaction reference.

Endpoint - https://api.korapay.com/merchant/api/v1/charges/mobile-money/authorize

Authorize OTP transaction

curl --location 'https://api.korapay.com/merchant/api/v1/charges/mobile-money/authorize' \
--header 'Content-Type: application/json' \
--header 'Authorization: Bearer YOUR_KORAPAY_SECRET_KEY' \
--data '{
    "reference": "KPY-PAY-rYF4c5ZWioeb",
    "token": "123456"
}'
-X POST
If the OTP verification is successful, an STK prompt will be sent to the wallet owner's phone for him to enter his PIN.

Sample response

Valid OTP
Invalid OTP

{
    "status": true,
    "message": "Authorization required",
    "data": {
        "amount": "10.00",
        "amount_expected": "10.00",
        "currency": "GHS",
        "fee": 1.75,
        "vat": 0.12,
        "auth_model": "STK_PROMPT",
        "transaction_reference": "KPY-PAY-rYF4c5ZWioeb",
        "payment_reference": "KPY-PAY-rYF4c5ZWioeb",
        "status": "processing",
        "message": "You will receive a prompt on mobile number. Kindly enter your wallet PIN to authorize the payment",
        "mobile_money": {
            "number": "+254700000000"
        }
    }
}
Authorizing an STK transaction
After making the request to charge/authorize the number, if the status of the transaction is processing and auth_model is STK, this means an STK has been sent to the wallet owner's phone.

Step 3: Verify Payment
The final step after receiving payment is to ensure that the payment was successful by making a verification request to our verification charge endpoint. The reference here should be your transaction reference.

Here's a sample request and response for verifying a mobile money payment:

cURL

curl https://api.korapay.com/merchant/api/v1/charges/:reference
-H "Authorization: Bearer YOUR_KORAPAY_SECRET_KEY"
-X GET
Sample response for a successful transaction:

Successful Payment Response
Failed Payment Response

{
  "status": true,
  "message": "Charge retrieved successfully",
  "data": {
    "reference": "KPY-PAY-rYF4c5ZWioeb",
    "status": "success",
    "amount": "10.00",
    "amount_paid": 0,
    "fee": 0.29,
    "currency": "GHS",
    "description": "Payment for a emilokan",
    "mobile_money": {
      "number": "+254700000000"
    },
    "customer": {
      "name": "John Doe",
      "email": "John@yahoo.com"
    }
  }
}
Step 4: Setup Webhook
You can set your application to receive a confirmation via webhooks when a mobile money payment is successful. Please visit Webhooks to see more information about the webhook request body and how to verify and handle the webhook request.

Error Responses
Invalid OTP
Payment Completed

{ 
  "status": false,
  "message": "The OTP you provided is invalid",
  "data": null
}
Updated 8 months ago

Accept Mobile Money Payment with Checkout
Pay-ins History API
Did this page help you?
Table of Contents
Get started with accepting Mobile money using APIs
Step 1: Collect payment data required to initiate payment
Step 2: Authorize mobile money transaction
Authorizing an OTP transaction
Authorizing an STK transaction
Step 3: Verify Payment
Step 4: Setup Webhook
Error Responses
Jump to Content
Kora Developer Documentation
API Reference
Create Account
Log In
Home
Guides

Search
⌘K
🟢 Get Started
Introduction
API Keys
Test & Live Modes

Webhooks
Errors
Frequently Asked Questions (FAQ)

↙️ Pay-Ins
Overview
Checkouts

Payment Links
Virtual Bank Accounts

Pool Accounts
Bank Transfers

Card Payments

Pay with Bank
Pay with Bank (Instant EFT)
Mobile Money Payments

Pay-ins History API
Refunds API

↗️ Payouts
Overview
Payout API

Withdrawals
Bulk Payouts via API
💳 Card Issuing
Overview
Issuing Balance
Virtual Card Creation
Virtual Card Funding
Virtual Card Withdrawals
Virtual Card Management

Testing Virtual Cards
👤 Identity
Overview
KYC Verification (eIDV) in Nigeria

KYB Verification (eIDV) in Nigeria

KYC Verification (eIDV) in South Africa

KYC Verification (eIDV) in Ghana

KYC Verification (eIDV) in Kenya

Testing Identity on the Sandbox Environment
Managing Verifications
💰 Balance
Overview
Balance API
Balance History API
🔀 Conversions
Overview
Exchange Rate API
Currency Conversion API
Dynamic Currency Conversion
Initiating and Managing Conversions on the Dashboard
Testing Conversions on the Sandbox Environment
☑️ Settlements
Overview
Settlements
🌍 Dashboard
Overview
Audit Logs
⚡️ Plugins
Kora WooCommerce Plugin
Powered by 

Pay-ins History API
The Pay-ins History API lets you fetch all pay-in transactions that have been made to your Kora account without having to get them from the dashboard. Use the following endpoint to fetch your transactions:

{{baseurl}}/merchant/api/v1/pay-ins

The parameters for this request include:

Field	Data Type	Description
currency	String	Optional - transaction currency e.g. NGN, KES, etc.
date_from	String	Optional - fetch transactions from this date. Use format YYYY-MM-DD-HH-MM-SS.
date_to	String	Optional - fetch transactions up to this date. Use format YYYY-MM-DD-HH-MM-SS.
limit	Number	Optional - If not passed, we send a limit of 10.
starting_after	String	Optional - used for pagination, must be used separately with ending_before, it expects the pointer from the response.
ending_before	String	Optional - used for pagination, must be used separately with starting_after, it expects the pointer from the response.
An example of the response to this request would look like this:

Sample Response

{
  "has_more": true,
    "data": {
      "pointer": "cus_7087",
      "reference": "KPY-PAY-gL3oDeoAlWYz",
      "status": "success",
      "amount": "5000.00",
      "amount_paid": "5000.00",
      "amount_expected": "5000.00",
      "fee": "26.88",
      "currency": "NGN",
      "description": "Test",
      "payment_method": "bank_transfer",
      "message": "Successful",
      "date_created": "2023-09-28 12:24:29",
      "date_completed": "2023-09-28 12:25:10"
    }
}
Updated 8 months ago

Accept Mobile Money Payment with APIs
Refunds API
Did this page help you?
Jump to Content
Kora Developer Documentation
API Reference
Create Account
Log In
Home
Guides

Search
⌘K
🟢 Get Started
Introduction
API Keys
Test & Live Modes

Webhooks
Errors
Frequently Asked Questions (FAQ)

↙️ Pay-Ins
Overview
Checkouts

Payment Links
Virtual Bank Accounts

Pool Accounts
Bank Transfers

Card Payments

Pay with Bank
Pay with Bank (Instant EFT)
Mobile Money Payments

Pay-ins History API
Refunds API

Refunds in Sandbox
↗️ Payouts
Overview
Payout API

Withdrawals
Bulk Payouts via API
💳 Card Issuing
Overview
Issuing Balance
Virtual Card Creation
Virtual Card Funding
Virtual Card Withdrawals
Virtual Card Management

Testing Virtual Cards
👤 Identity
Overview
KYC Verification (eIDV) in Nigeria

KYB Verification (eIDV) in Nigeria

KYC Verification (eIDV) in South Africa

KYC Verification (eIDV) in Ghana

KYC Verification (eIDV) in Kenya

Testing Identity on the Sandbox Environment
Managing Verifications
💰 Balance
Overview
Balance API
Balance History API
🔀 Conversions
Overview
Exchange Rate API
Currency Conversion API
Dynamic Currency Conversion
Initiating and Managing Conversions on the Dashboard
Testing Conversions on the Sandbox Environment
☑️ Settlements
Overview
Settlements
🌍 Dashboard
Overview
Audit Logs
⚡️ Plugins
Kora WooCommerce Plugin
Powered by 

Refunds API
The Refunds API allows merchants to refund successful and settled transactions via API. The API enables merchants to automate refunds and retrieve refund details when needed.


Refund Status
The different statuses for a refund include:

Processing: This indicates that the refund is being processed.
Failed: This indicates that the refund failed due to an error or rejection (e.g., invalid source account).
Success: This indicates that the refund is successfully completed and the customer has been refunded.

Initiate a Refund
To initiate a refund for a pay-in transaction using the API, make a request to the following endpoint:

https://api.korapay.com/merchant/api/v1/refunds/initiate


The request body should have the following parameters:

Field	Type	Required?	Description
amount	Number	Optional	The amount to refund (the complete amount will be refunded if this is not specified).
payment_reference	String	Required	This is the reference of the payment on which you are making a refund.
reference	String	Required	This is a unique reference for the refund that should be generated by the merchant (Maximum of 50 characters).
reason	String	Optional	This is the reason for the refund (Maximum of 200 characters).
webhook_url	String	Optional	This is the webhook to receive refund notification (Maximum of 200 characters).

Here are examples of responses for this request:

Success
Failed

{
    "status": true,
    "message": "Refund successfully initiated",
    "data": {
        "refund_reference": "TXN-123456",
        "refund_date": "2025-04-03T08:27:22.646Z",
        "status": "processing",
        "payment_reference": "TXN-234",
        "reason": "Customer requested refund",
        "amount_returned": 120,
        "currency": "NGN"
    }
}

Refund Amount Requirements
Currency	Minimum Amount
NGN	100

Retrieving Details of a Refund
To retrieve the details of a refund, send a request to the following endpoint:

https://api.korapay.com/merchant/api/v1/refunds/:reference


This request should include the following parameters:

Parameter	Type	Required?	Description
reference	String	Yes	This is the ID/reference of the refund to be fetched.

The response to this request could look like this:

Success
Failed

{
   "status": true,
   "message": "Refund details retrieved successfully",
   "data": {
      "amount": "2000.00",
      "status": "processing",
      "currency": "NGN",
      "destination": "customer",
      "reference": "TXN-123456",
      "reason": "Customer requested refund",
      "payment_reference": "TXN-234",
      "transaction_amount": "2000.00",
      "payment_method": "card",
      "created_at": "2025-04-03 09:27:22",
      "completed_at": null
   }
}

Retrieving the Details of a List of Refunds
You can also retrieve a list of refunds within a specified time period. To do this, make a request to the following endpoint:

https://api.korapay.com/merchant/api/v1/refunds


The request should have the following parameters:

Parameter	Type	Required?	Description
currency	String	No	This specifies the currency for filtering the refunds.
date_from	String	No	Start date for fetching the refunds (YYYY-MM-DD)
date_to	String	No	End date for fetching the refunds (YYYY-MM-DD)
limit	Number	No	This defines the maximum number of refund records to return on a single request.
starting_after	String	No	This returns results starting after the provided refund cursor.
ending_before	String	No	This returns results ending before the provided refund cursor.
status	String	No	This specifies the status for filtering refunds. The status can either be processing, failed or success.

Here's an example of the response to this request:

Success
Failed

{
   "status": true,
  	"message": "Refunds retrieved successfully",
   "data": {
       "has_more": true,
       "refunds": [
           {
               "pointer": "cus_706",
               "reference": "TXN-123456",
               "status": "success",
               "amount": "120.00",
               "currency": "NGN",
               "payment_reference": "TXN-234",
               "reason": "Customer requested refund",
               "transaction_amount": "2000.00",
               "created_at": "2025-04-03 09:27:22",
               "completed_at": 2025-04-03 09:30:22
           }
          ]
         }
}

Webhook Notification for Refunds
We send webhook notifications on successful and failed refunds initiated via the API. See a sample webhook response below

Success Refund Payload

{  
  "event": "refund.success",
  "data": {
    "amount": 100,
    "status": "success",
    "currency": "NGN",
    "reference": "123456",
    "payment_reference": "KPY-PAY-0lIrV1pRG",
    "refund_date": "2025-01-10 10:00:04",
    "completion_date": "2025-01-10 11:30:00"
  }
}
Updated 3 months ago

Pay-ins History API
Refunds in Sandbox
Did this page help you?
Table of Contents
Refund Status
Initiate a Refund
Refund Amount Requirements
Retrieving Details of a Refund
Retrieving the Details of a List of Refunds
Webhook Notification for Refunds
Jump to Content
Kora Developer Documentation
API Reference
Create Account
Log In
Home
Guides

Search
⌘K
🟢 Get Started
Introduction
API Keys
Test & Live Modes

Webhooks
Errors
Frequently Asked Questions (FAQ)

↙️ Pay-Ins
Overview
Checkouts

Payment Links
Virtual Bank Accounts

Pool Accounts
Bank Transfers

Card Payments

Pay with Bank
Pay with Bank (Instant EFT)
Mobile Money Payments

Pay-ins History API
Refunds API

Refunds in Sandbox
↗️ Payouts
Overview
Payout API

Withdrawals
Bulk Payouts via API
💳 Card Issuing
Overview
Issuing Balance
Virtual Card Creation
Virtual Card Funding
Virtual Card Withdrawals
Virtual Card Management

Testing Virtual Cards
👤 Identity
Overview
KYC Verification (eIDV) in Nigeria

KYB Verification (eIDV) in Nigeria

KYC Verification (eIDV) in South Africa

KYC Verification (eIDV) in Ghana

KYC Verification (eIDV) in Kenya

Testing Identity on the Sandbox Environment
Managing Verifications
💰 Balance
Overview
Balance API
Balance History API
🔀 Conversions
Overview
Exchange Rate API
Currency Conversion API
Dynamic Currency Conversion
Initiating and Managing Conversions on the Dashboard
Testing Conversions on the Sandbox Environment
☑️ Settlements
Overview
Settlements
🌍 Dashboard
Overview
Audit Logs
⚡️ Plugins
Kora WooCommerce Plugin
Powered by 

Refunds in Sandbox
Refunds can also be initiated in the sandbox environment. Here's how:

1. Initiate a refund
To refund a pay-in transaction in the sandbox environment, simply make a request to this endpoint:

https://api.korapay.com/merchant/api/v1/refunds/initiate


The request would have the following parameters:

Parameter

Type

Required

Description

amount

Decimal

Optional

This is the amount of the refund. When this is passed, a partial refund is processed if this amount is less than the initial payment amount.

However, when it is not passed, or the amount passed matches the payment amount, a full refund is processed.

payment_reference

String

Required

This is the reference of the payment to be refunded.

reference

String

Required

(Maximum of 50 characters) This is a unique reference for the refund that should be generated by the merchant.

reason

String

Optional

(Maximum of 200 characters) This is the reason for the refund.

webhook_url

String

Optional

(Maximum of 200 characters) The webhook to receive refund notification

completion_status

String

Optional

(Can either be success of failed) This specifies the simulated final status of the refund. When provided, it triggers a webhook event corresponding to the specified status.

status_reason

String

Optional

(Maximum of 200 characters) This provides a custom reason for the simulated completion_status.This mimics the status reason that would normally be returned by the system in a real refund scenario. It is included in the webhook payload.


Here's what the response could look like:

Success Response
Failure Response

{
    "status": true,
    "message": "Refund successfully initiated",
    "data": {
        "refund_reference": "TXN-123456",
        "refund_date": "2025-04-03T08:27:22.646Z",
        "status": "processing",
        "payment_reference": "TXN-234,
        "reason": "Customer requested refund",
        "amount_returned": 120,
        "currency": "NGN"
    }
}

For the webhooks, here's how the response could look like at initiation:

Successful Initiation Webhook Response
Failed Initiation Webhook Response

{
   "event": "refund.success",
   "data": {
       "amount": 100,
       "status": "success",
       "status_reason": "success test transaction",
       "currency": "NGN",
       "reference": "TXN-123456",
       "payment_reference": "TXN-1",
       "created_at": "2025-07-03T06:44:59.285Z",
       "completed_at": "2025-07-03T06:44:59.342Z"
   }
}
Updated 3 months ago

Refunds API
Overview
Did this page help you?
Table of Contents
1. Initiate a refund


Jump to Content
Kora Developer Documentation
API Reference
Create Account
Log In
Home
Guides

Search
⌘K
🟢 Get Started
Introduction
API Keys
Test & Live Modes

Webhooks
Errors
Frequently Asked Questions (FAQ)

↙️ Pay-Ins
Overview
Checkouts

Payment Links
Virtual Bank Accounts

Pool Accounts
Bank Transfers

Card Payments

Pay with Bank
Pay with Bank (Instant EFT)
Mobile Money Payments

Pay-ins History API
Refunds API

↗️ Payouts
Overview
Payout API

Withdrawals
Bulk Payouts via API
💳 Card Issuing
Overview
Issuing Balance
Virtual Card Creation
Virtual Card Funding
Virtual Card Withdrawals
Virtual Card Management

Testing Virtual Cards
👤 Identity
Overview
KYC Verification (eIDV) in Nigeria

KYB Verification (eIDV) in Nigeria

KYC Verification (eIDV) in South Africa

KYC Verification (eIDV) in Ghana

KYC Verification (eIDV) in Kenya

Testing Identity on the Sandbox Environment
Managing Verifications
💰 Balance
Overview
Balance API
Balance History API
🔀 Conversions
Overview
Exchange Rate API
Currency Conversion API
Dynamic Currency Conversion
Initiating and Managing Conversions on the Dashboard
Testing Conversions on the Sandbox Environment
☑️ Settlements
Overview
Settlements
🌍 Dashboard
Overview
Audit Logs
⚡️ Plugins
Kora WooCommerce Plugin
Powered by 

Overview
Your Balance on Korapay simply represents how much funds you have in your Korapay account. Funds must first be available in your Balance before you can successfully make payouts, and these funds get into your Balance through Pay-ins - funding instructions (top-ups) and settled payments received from your customers. You can either check your dashboard or use the Balance API to get up-to-the-minute data on available funds and your current positions with Korapay.

Getting balance information can be useful for reconciliatory purposes, preventing failures from insufficient funds, or even fraud detection.

Available and Pending Balances
As the name suggests, your available balance represents funds that are available and ready for you to use - to withdraw or to make payouts. This available balance increases when you top up your balance or receive a settlement for your pay-ins in your balance.

Your pending balance, on the other hand, refers to funds that have been received but are yet to be settled to you. Payments received from your customers are settled to you based on the settlement schedule of your account. Once you’ve been settled, your pending balance is affected as funds become available for use.

3024
Here's an illustration of how your pending balance works. Let’s say your available balance is NGN5,000 and your pending balance is NGN0, for example. If your customer successfully pays NGN2,500 to you, your available balance remains NGN5,000 while your pending balance becomes NGN2,500. Once your settlement schedule is due, funds are moved from your pending balance to your available balance. Your available balance then becomes NGN7,500.

🚧
Note that if your settlement destination has been set to your bank account, funds in your pending balance go right into the bank account you set, and not into your available balance.
Funding your Balance
To make funds readily available for your payouts, you can conveniently fund your balance right from your dashboard. One way to fund your balance is by making a transfer to your Reserved Bank Account (RBA), a special type of bank account attached to your Korapay account that is exclusively reserved for funding your balance.

3024
To fund your balance:

Go to the Balances page of your dashboard.
In the Balance Detail box, click the ‘Add Funds’ button.
The details of your Reserved Bank Account will be displayed on the funding pop up. Make a bank transfer to the account and your balance will be funded.
Exporting your Balance History
Your balance history shows the record of debit and credit transactions that make up your available balance. Businesses find this record very useful for reconciliation purposes - you can use it to check if your balances and transactions add up correctly. Korapay also gives you the option to export this record in a preferred format (CSV or Excel) for transactions within any timeframe of your choice. Aside from the amount of the credit or debit transactions, this record also provides information on the balance before and after each transaction.

To export your Balance History, click the ‘Export’ button in the Balance Detail box on your Balances page. Select your preferred export format, choose a date range, and the table columns to be shown in the export.

Note that pending or processing transactions do not affect your balance.

3024
Updated 8 months ago

Managing Verifications
Balance API
Did this page help you?
Table of Contents
Available and Pending Balances
Funding your Balance
Exporting your Balance History

Jump to Content
Kora Developer Documentation
API Reference
Create Account
Log In
Home
Guides

Search
⌘K
🟢 Get Started
Introduction
API Keys
Test & Live Modes

Webhooks
Errors
Frequently Asked Questions (FAQ)

↙️ Pay-Ins
Overview
Checkouts

Payment Links
Virtual Bank Accounts

Pool Accounts
Bank Transfers

Card Payments

Pay with Bank
Pay with Bank (Instant EFT)
Mobile Money Payments

Pay-ins History API
Refunds API

↗️ Payouts
Overview
Payout API

Withdrawals
Bulk Payouts via API
💳 Card Issuing
Overview
Issuing Balance
Virtual Card Creation
Virtual Card Funding
Virtual Card Withdrawals
Virtual Card Management

Testing Virtual Cards
👤 Identity
Overview
KYC Verification (eIDV) in Nigeria

KYB Verification (eIDV) in Nigeria

KYC Verification (eIDV) in South Africa

KYC Verification (eIDV) in Ghana

KYC Verification (eIDV) in Kenya

Testing Identity on the Sandbox Environment
Managing Verifications
💰 Balance
Overview
Balance API
Balance History API
🔀 Conversions
Overview
Exchange Rate API
Currency Conversion API
Dynamic Currency Conversion
Initiating and Managing Conversions on the Dashboard
Testing Conversions on the Sandbox Environment
☑️ Settlements
Overview
Settlements
🌍 Dashboard
Overview
Audit Logs
⚡️ Plugins
Kora WooCommerce Plugin
Powered by 

Balance API
Retrieve your balance information with the Balance API

Balance API is Kora’s product for receiving real-time Balance information. This real-time Balance data can be helpful, for example, when checking to see if your account has sufficient funds before using it as a funding source for a payout. With the Balance API, you can easily access your balance without having to log into your dashboard. It also provides you with the liberty and flexibility to incorporate your Korapay Balance into your application however you deem fit.


Balance Request
This endpoint returns your Korapay balances (available and pending) and requires secret key authentication.

Endpoint: Request Balance

{{baseurl}}/merchant/api/v1/balances

Response
The Balance API automatically returns both the available and pending balances across all supported currencies in the response. By default, balances are provided for NGN, but merchants with multi-currency accounts will also receive balances for other supported currencies, including USD, GHS, KES, XAF, XOF and ZAR.

Here's a sample response:

Sample Response

{
    "status": true,
    "message": "success",
    "data": {
  			"NGN": {
            "pending_balance": 100000.78,
            "available_balance": 400300.90
        },
        "USD": {
            "pending_balance": 10000.78,
            "available_balance": 10093756.06,
            "issuing_balance": 17639.67
        }
    }
}
Updated 5 months ago

Overview
Balance History API
Did this page help you?
Table of Contents
Balance Request
Response

Jump to Content
Kora Developer Documentation
API Reference
Create Account
Log In
Home
Guides

Search
⌘K
🟢 Get Started
Introduction
API Keys
Test & Live Modes

Webhooks
Errors
Frequently Asked Questions (FAQ)

↙️ Pay-Ins
Overview
Checkouts

Payment Links
Virtual Bank Accounts

Pool Accounts
Bank Transfers

Card Payments

Pay with Bank
Pay with Bank (Instant EFT)
Mobile Money Payments

Pay-ins History API
Refunds API

↗️ Payouts
Overview
Payout API

Withdrawals
Bulk Payouts via API
💳 Card Issuing
Overview
Issuing Balance
Virtual Card Creation
Virtual Card Funding
Virtual Card Withdrawals
Virtual Card Management

Testing Virtual Cards
👤 Identity
Overview
KYC Verification (eIDV) in Nigeria

KYB Verification (eIDV) in Nigeria

KYC Verification (eIDV) in South Africa

KYC Verification (eIDV) in Ghana

KYC Verification (eIDV) in Kenya

Testing Identity on the Sandbox Environment
Managing Verifications
💰 Balance
Overview
Balance API
Balance History API
🔀 Conversions
Overview
Exchange Rate API
Currency Conversion API
Dynamic Currency Conversion
Initiating and Managing Conversions on the Dashboard
Testing Conversions on the Sandbox Environment
☑️ Settlements
Overview
Settlements
🌍 Dashboard
Overview
Audit Logs
⚡️ Plugins
Kora WooCommerce Plugin
Powered by 

Balance History API
The Balance History API gives merchants the ability to fetch transactions (pay-ins or payouts) that affect their balance without having to go on the dashboard to get them. The API request provides flexibility for merchants to choose the time frame, currency or type of transactions they want to fetch.

Use this endpoint to get your balance history: {{baseurl}}/merchant/api/v1/balances/history

You may also use these parameters in your request to fetch your transactions:

Field	Data Type	Description
currency	String	Optional - transaction currency e.g. NGN, KES, etc.
date_from	String	Optional - fetch transactions from this date. Use format YYYY-MM-DD-HH-MM-SS.
date_to	String	Optional - fetch transactions up to this date. Use format YYYY-MM-DD-HH-MM-SS.
limit	Number	Optional - If not passed, we send a limit of 10.
starting_after	String	Optional - used for pagination, must be used separately with ending_before, it expects the pointer from the response.
ending_before	String	Optional - used for pagination, must be used separately with starting_after, it expects the pointer from the response.
direction	String	Optional - the type of transaction - debit or credit.
Here's an example of how the response could look like:

Sample Response

{
  "has_more": true,
  "data":{
    "pointer": "cus_1B89",
    "amount": "3000.00",
    "currency": "NGN",
    "balance_before": "10811758.99",
    "balance_after": "10808758.99",
    "date_created": "2023-09-08 07:20:00",
    "description": "Chargeback deduction for KPY-CM-s2Ao7BlFCm5FdfG",
    "direction": "debit",
    "source": "chargeback",
    "source_reference": "KPY-CHB-3Xuop6zkvlsyP"
  }
}
Updated 8 months ago

Balance API
Overview
Did this page help you?

Jump to Content
Kora Developer Documentation
API Reference
Create Account
Log In
Home
Guides

Search
⌘K
🟢 Get Started
Introduction
API Keys
Test & Live Modes

Webhooks
Errors
Frequently Asked Questions (FAQ)

↙️ Pay-Ins
Overview
Checkouts

Payment Links
Virtual Bank Accounts

Pool Accounts
Bank Transfers

Card Payments

Pay with Bank
Pay with Bank (Instant EFT)
Mobile Money Payments

Pay-ins History API
Refunds API

↗️ Payouts
Overview
Payout API

Withdrawals
Bulk Payouts via API
💳 Card Issuing
Overview
Issuing Balance
Virtual Card Creation
Virtual Card Funding
Virtual Card Withdrawals
Virtual Card Management

Testing Virtual Cards
👤 Identity
Overview
KYC Verification (eIDV) in Nigeria

KYB Verification (eIDV) in Nigeria

KYC Verification (eIDV) in South Africa

KYC Verification (eIDV) in Ghana

KYC Verification (eIDV) in Kenya

Testing Identity on the Sandbox Environment
Managing Verifications
💰 Balance
Overview
Balance API
Balance History API
🔀 Conversions
Overview
Exchange Rate API
Currency Conversion API
Dynamic Currency Conversion
Initiating and Managing Conversions on the Dashboard
Testing Conversions on the Sandbox Environment
☑️ Settlements
Overview
Settlements
🌍 Dashboard
Overview
Audit Logs
⚡️ Plugins
Kora WooCommerce Plugin
Powered by 

Exchange Rate API
You can use the exchange rate API to get the latest rate for any currency pair. It can be used on its own or before starting a currency conversion. However, fetching the exchange rate is a prerequisite to initiating a currency conversion transaction.

To get the latest exchange rate for a currency pair, make a POST request to the exchange rate endpoint;

{{baseurl}}/api/v1/conversions/rates

The request body should have the following parameters:

Field	Data Type	Description
from_currency	String	The currency from which the user is converting. For example, if you wanted to convert Nigerian Naira to US Dollar, the from_currency will be NGN.
to_currency	String	The currency to which the user wants to convert funds. For example, if you wanted to convert Nigerian Naira to US Dollar, the to_currency will be USD.
amount	Number	The amount to be converted (referring to the from_currency).
reference	String	A string to reference this user. This can be a customer ID, a session ID, or similar, and can be used to reconcile the conversion.
Here's an example of a response to the request.

Sample Response

{
  "status": true,
  "message": "Conversion rate retrieved successfully",
  "data": {
    "from_currency": "USD",
    "to_currency": "NGN",
    "from_amount": 10,
    "to_amount": 14400,
    "rate": 1440,
    "reference": "1ccdb7da-e732-4610-a17f-5abfb7617d99",
    "expiry_in_seconds": 25,
    "expiry_date": "2025-01-01T15:58:18.264Z"
  }
}
Updated 6 months ago

Overview
Currency Conversion API
Did this page help you?

Jump to Content
Kora Developer Documentation
API Reference
Create Account
Log In
Home
Guides

Search
⌘K
🟢 Get Started
Introduction
API Keys
Test & Live Modes

Webhooks
Errors
Frequently Asked Questions (FAQ)

↙️ Pay-Ins
Overview
Checkouts

Payment Links
Virtual Bank Accounts

Pool Accounts
Bank Transfers

Card Payments

Pay with Bank
Pay with Bank (Instant EFT)
Mobile Money Payments

Pay-ins History API
Refunds API

↗️ Payouts
Overview
Payout API

Withdrawals
Bulk Payouts via API
💳 Card Issuing
Overview
Issuing Balance
Virtual Card Creation
Virtual Card Funding
Virtual Card Withdrawals
Virtual Card Management

Testing Virtual Cards
👤 Identity
Overview
KYC Verification (eIDV) in Nigeria

KYB Verification (eIDV) in Nigeria

KYC Verification (eIDV) in South Africa

KYC Verification (eIDV) in Ghana

KYC Verification (eIDV) in Kenya

Testing Identity on the Sandbox Environment
Managing Verifications
💰 Balance
Overview
Balance API
Balance History API
🔀 Conversions
Overview
Exchange Rate API
Currency Conversion API
Dynamic Currency Conversion
Initiating and Managing Conversions on the Dashboard
Testing Conversions on the Sandbox Environment
☑️ Settlements
Overview
Settlements
🌍 Dashboard
Overview
Audit Logs
⚡️ Plugins
Kora WooCommerce Plugin
Powered by 

Currency Conversion API
Initiate Currency Conversion
Before initiating a conversion, you need to first get the exchange rate for the currency pair you want to convert. Once you have the exchange rate for the currency pair, you can start the conversion by making a POST request to the Initiate Currency Conversion endpoint;

{{baseurl}}/api/v1/conversions/

The request body should have the following parameters:

Field	Data Type	Description
rate_reference	String	A string to reference the rate that was gotten for the amount.
amount	Number	The amount to be converted.
from_currency	String	The currency from which the user is converting. For example, if you wanted to convert Nigerian Naira to US Dollar, the from_currency will be NGN.
to_currency	String	The currency to which the user wants to convert funds. For example, if you wanted to convert Nigerian Naira to US Dollar, the to_currency will be USD.
customer_name	String	The name of customer initiating the conversion.
customer_email	String	The email of the customer initiating the conversion.
narration	String	The description of the transaction.
Here's an example of a response to the request:

Sample Response

{
  "status": true,
  "message": "Conversion processed successfully",
  "data": {
    "converted_amount": "14400.00",
    "source_amount": "10.00",
    "destination_currency": "NGN",
    "exchange_rate": "1440.00",
    "reference": "1ccdb7da-e732-4610-a17f-5abfb7617d90",
    "source_currency": "USD",
    "status": "success"
  }
}
An email notification will also be sent to you immediately the currency conversion is completed.

🚧
Always ensure that you have sufficient funds in your available balance for the currency you want to convert from.

Retrieving Currency Conversion Transaction
You can retrieve a conversion transaction to get the status and the details of that transaction using its reference.

To do this, make a GET request to the Retrieve Conversion Transaction endpoint;

{{baseurl}}/api/v1/conversions/:reference

The request should have the following parameters:

Field	Data Type	Description
reference	String	Required - the reference of the transaction.
Here's an example of the response to the request to retrieve a conversion transaction:

Sample Response

{
  "status": true,
  "code": "AA000",
  "message": "Conversion retrieved successfully",
  "data": {
    "source_currency": "NGN",
    "destination_currency": "USD",
    "exchange_rate": "1870.00",
    "source_amount": "4000.00",
    "converted_amount": "2.14",
    "status": "success",
    "reference": "2ccdb7da-e732-4610-a17f-5abfb7617d90",
    "channel": "api",
    "customer_name": "John",
    "customer_email": "jdoe@gmail.com",
    "narration": "sample narration",
    "transaction_date": "2025-02-26 13:36:57"
  }
}
Fetching Conversion Transaction History
You can retrieve the history of your currency conversion transactions within a given period using the Conversion History endpoint.

To do this, make a GET request to the Conversion history endpoint with the desired period;

{{baseurl}}/api/v1/conversions

The request body should have the following parameters:

Field	Data Type	Description
date	String	Required- The specific date or date range (start date and end date)
limit	String	Required- The number of logs to return (Default value: 20 ,Maximum Value: 100)
page	String	Required- The page number of the requested page (Default value: 1)
Here's an example of a response to the request to fetch conversions history:

Sample Response

{
  "status": true,
  "message": "Conversion transactions retrieved successfully",
  "data": {
    "history": [
      {
        "source_currency": "NGN",
        "destination_currency": "USD",
        "exchange_rate": "1870.00",
        "source_amount": "4000.00",
        "converted_amount": "2.14",
        "status": "success",
        "reference": "2ccdb7da-e732-4610-a17f-5abfb7617d90",
        "channel": "api",
        "customer_name": "John",
        "customer_email": "jdoe@gmail.com",
        "narration": "sample narration",
        "transaction_date": "2025-02-26 13:36:57",
        "account": {
          "name": "Demo Merchant",
          "email": "demo@korapay.com"
        }
      },
      {
        "source_currency": "USD",
        "destination_currency": "NGN",
        "exchange_rate": "1440.00",
        "source_amount": "10.00",
        "converted_amount": "14400.00",
        "status": "success",
        "reference": "1ccdb7da-e732-4610-a17f-5abfb7617d90",
        "channel": "api",
        "customer_name": "John",
        "customer_email": "jdoe@gmail.com",
        "narration": "sample narration",
        "transaction_date": "2025-02-26 13:33:20",
        "account": {
          "name": "Demo Merchant",
          "email": "demo@korapay.com"
        }
      }
    ]
  }
}
Updated 7 months ago

Exchange Rate API
Dynamic Currency Conversion
Did this page help you?
Table of Contents
Initiate Currency Conversion
Retrieving Currency Conversion Transaction
Fetching Conversion Transaction History

Jump to Content
Kora Developer Documentation
API Reference
Create Account
Log In
Home
Guides

Search
⌘K
🟢 Get Started
Introduction
API Keys
Test & Live Modes

Webhooks
Errors
Frequently Asked Questions (FAQ)

↙️ Pay-Ins
Overview
Checkouts

Payment Links
Virtual Bank Accounts

Pool Accounts
Bank Transfers

Card Payments

Pay with Bank
Pay with Bank (Instant EFT)
Mobile Money Payments

Pay-ins History API
Refunds API

↗️ Payouts
Overview
Payout API

Withdrawals
Bulk Payouts via API
💳 Card Issuing
Overview
Issuing Balance
Virtual Card Creation
Virtual Card Funding
Virtual Card Withdrawals
Virtual Card Management

Testing Virtual Cards
👤 Identity
Overview
KYC Verification (eIDV) in Nigeria

KYB Verification (eIDV) in Nigeria

KYC Verification (eIDV) in South Africa

KYC Verification (eIDV) in Ghana

KYC Verification (eIDV) in Kenya

Testing Identity on the Sandbox Environment
Managing Verifications
💰 Balance
Overview
Balance API
Balance History API
🔀 Conversions
Overview
Exchange Rate API
Currency Conversion API
Dynamic Currency Conversion
Initiating and Managing Conversions on the Dashboard
Testing Conversions on the Sandbox Environment
☑️ Settlements
Overview
Settlements
🌍 Dashboard
Overview
Audit Logs
⚡️ Plugins
Kora WooCommerce Plugin
Powered by 

Dynamic Currency Conversion
Dynamic Currency Conversion (DCC) is a real-time conversion service that allows your customers to pay in their local currency while you get settled in your preferred currency. This offers a seamless and familiar checkout experience for your customers.

Here's how Dynamic Currency Conversion works:

Kora converts transaction amount: Our system automatically converts the transaction amount to the customer's local currency using the real-time exchange rate.
Customer makes payment: The customer sees the total amount in their local currency and proceeds to make the payment.
Transaction processed: The payment is processed as usual, with the settlement amount converted back to your preferred settlement currency.

Getting Started with Dynamic Currency Conversions
To get started with DCC, follow these steps:

First, you need access to the Currency Conversions service. See here.

Once you have access to Currency Conversions,

Go to Settings > Settlements
Select the currency for which you want to set this conversions setting (Swap Settlements), and check the "Allow this merchant to settle payments in another currency" checkbox.
Save changes to enable your preference to take effect.

Initiate a DCC transaction and include the following additional parameters in your payment request:

payment_currency: The currency that the customer pays in (their local currency).
settlement_currency: The currency in which you want to receive settlement.

Here's an example of a request initiating a DCC transaction:

Sample Request

{
    "amount": 10000,
    "currency": "USD",
    "payment_currency": "NGN",
    "settlement_currency": "USD",
    "reference": "your-transaction-reference-001",
    "narration": "Payment for product Y",
    "channels": "card",
    "default_channel": "card",
    "customer": {
        "name": "John Doe",
        "email": "john@email.com"
    },
    "notification_url": "https://webhook.site/8d321d8d-397f-4bab-bf4d-7e9ae3afbd50",
    "metadata":{
        "key0": "test0"
    }
}

The responses to this request would look like this:

Sample Response

{
    "status": true,
    "message": "Charge created successfully",
    "customer": {
        "reference": "your-transaction-reference-001",
        "checkout_url": "https://checkout.korapay.com/KPY-PI-202501231hXn08399/pay"
    }
}

Some important things to note:

The currency field should indicate the currency of the amount sent for collection.
Both payment_currency and settlement_currency must be supported by Kora.
The exchange rate can be marked up in the settlement settings on the dashboard.

If you have any questions or feedback on currency conversions, kindly email our support team at support@korapay.com.

Updated 7 months ago

Currency Conversion API
Initiating and Managing Conversions on the Dashboard
Did this page help you?
Table of Contents
Getting Started with Dynamic Currency Conversions

Jump to Content
Kora Developer Documentation
API Reference
Create Account
Log In
Home
Guides

Search
⌘K
🟢 Get Started
Introduction
API Keys
Test & Live Modes

Webhooks
Errors
Frequently Asked Questions (FAQ)

↙️ Pay-Ins
Overview
Checkouts

Payment Links
Virtual Bank Accounts

Pool Accounts
Bank Transfers

Card Payments

Pay with Bank
Pay with Bank (Instant EFT)
Mobile Money Payments

Pay-ins History API
Refunds API

↗️ Payouts
Overview
Payout API

Withdrawals
Bulk Payouts via API
💳 Card Issuing
Overview
Issuing Balance
Virtual Card Creation
Virtual Card Funding
Virtual Card Withdrawals
Virtual Card Management

Testing Virtual Cards
👤 Identity
Overview
KYC Verification (eIDV) in Nigeria

KYB Verification (eIDV) in Nigeria

KYC Verification (eIDV) in South Africa

KYC Verification (eIDV) in Ghana

KYC Verification (eIDV) in Kenya

Testing Identity on the Sandbox Environment
Managing Verifications
💰 Balance
Overview
Balance API
Balance History API
🔀 Conversions
Overview
Exchange Rate API
Currency Conversion API
Dynamic Currency Conversion
Initiating and Managing Conversions on the Dashboard
Testing Conversions on the Sandbox Environment
☑️ Settlements
Overview
Settlements
🌍 Dashboard
Overview
Audit Logs
⚡️ Plugins
Kora WooCommerce Plugin
Powered by 

Initiating and Managing Conversions on the Dashboard
You can initiate, view and manage conversion transactions from your Kora dashboard. To do so, simply go to the Conversions page from the side menu. You will be able to perform a currency conversion transaction and also view and manage all conversion transactions performed via API and on the dashboard.


If you wish to view a specific conversion transaction, simply go to the details page of the transaction.


Updated 7 months ago

Dynamic Currency Conversion
Testing Conversions on the Sandbox Environment
Did this page help you?

Jump to Content
Kora Developer Documentation
API Reference
Create Account
Log In
Home
Guides

Search
⌘K
🟢 Get Started
Introduction
API Keys
Test & Live Modes

Webhooks
Errors
Frequently Asked Questions (FAQ)

↙️ Pay-Ins
Overview
Checkouts

Payment Links
Virtual Bank Accounts

Pool Accounts
Bank Transfers

Card Payments

Pay with Bank
Pay with Bank (Instant EFT)
Mobile Money Payments

Pay-ins History API
Refunds API

↗️ Payouts
Overview
Payout API

Withdrawals
Bulk Payouts via API
💳 Card Issuing
Overview
Issuing Balance
Virtual Card Creation
Virtual Card Funding
Virtual Card Withdrawals
Virtual Card Management

Testing Virtual Cards
👤 Identity
Overview
KYC Verification (eIDV) in Nigeria

KYB Verification (eIDV) in Nigeria

KYC Verification (eIDV) in South Africa

KYC Verification (eIDV) in Ghana

KYC Verification (eIDV) in Kenya

Testing Identity on the Sandbox Environment
Managing Verifications
💰 Balance
Overview
Balance API
Balance History API
🔀 Conversions
Overview
Exchange Rate API
Currency Conversion API
Dynamic Currency Conversion
Initiating and Managing Conversions on the Dashboard
Testing Conversions on the Sandbox Environment
☑️ Settlements
Overview
Settlements
🌍 Dashboard
Overview
Audit Logs
⚡️ Plugins
Kora WooCommerce Plugin
Powered by 

Testing Conversions on the Sandbox Environment
You can test Currency Conversions in our easy-to-use sandbox environment before integrating the live APIs. We recommend performing these tests to ensure a seamless integration of the service.

In the sandbox environment, you can simulate the following experiences;

Get exchange rate
Initiate currency conversion
Get conversion transaction
Fetch conversion transaction history
To test the Currency Conversion product on the sandbox environment, you need to switch your Kora dashboard to test mode and access your test secret key in the API configurations section. Use the test secret key for authorization instead of your live secret key.

Updated 5 months ago

Initiating and Managing Conversions on the Dashboard
Overview
Did this page help you?
