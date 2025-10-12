Introduction
Welcome to Safe Haven's API 🙌🏾

As a Safe Haven MFB customer, you can use our API to automate your business processes.

Our API can be used for a number of use cases such as :

Creating accounts
Managing beneficiaries
Making transfers
Viewing balances
This saves time and reduces costs and errors.

Here, you will get the documentation and resources to get you started with our easy to integrate APIs

The Safe Haven API is organized around REST. Our API has predictable resource-oriented URLs, accepts JSON request bodies, returns JSON-encoded responses, and uses standard HTTP response codes, authentication, and verbs.

You can use the Safe Haven sandbox environment which does not interact with live banking networks while integrating our APIs.

To set up a sandbox account, go to https://online.sandbox.safehavenmfb.com.


API Base Url

LIVE ENVIRONMENT
SANDBOX ENVIRONMENT

https://api.safehavenmfb.com
You can easily access the Safe Haven Postman collection here



Environments
The Safe Haven's API and dashboard are available on two environments:

Environment	Sandbox	Production
Dashboard URL	https://online.sandbox.safehavenmfb.com	https://online.safehavenmfb.com
API URL	https://api.sandbox.safehavenmfb.com	https://api.safehavenmfb.com

The sandbox environments allow you to easily test and simulate the available features without making any breaking changes.

Errors
Safe Haven uses conventional HTTP response codes to indicate the success or failure of an API request. In general: Codes in the 2xx range indicate success. Codes in the 4xx range indicate an error that failed given the information provided (e.g., a required parameter was omitted, a charge failed, etc.). Codes in the 5xx range indicate an error with Safe Haven's servers (these are rare).


Code	Description
200 - OK	Everything worked as expected.
400 - Bad Request	The request was unacceptable, often due to missing a required parameter.
401 - Unauthorized	No valid API key was provided.
402 - Request Failed	The parameters were valid but the request failed.
403 - Forbidden	The API key doesn't have permission to perform the request.
404 - Not Found	The requested resource doesn't exist.
409 - Conflict	The request conflicts with another request.
429 - Too Many Requests	Too many requests hit the API too quickly. We recommend an exponential backoff of your requests.
500, 502, 503, 504 - Server Errors	Something went wrong on Safe Haven's end. These are rare and if they happen, please contact us immediately.

Pagination
Safe Haven supports fetch of API resources like Accounts, Transfers. These endpoints share a common structure, taking at least these two parameters: page and limit. By default, the page is set to 0 and a limit of 25. You can fetch a maximum of 100 records at once. The resulting response will always include a pagination object with the total records count, the number of pages, the current page, and the limit set.

JSON RESPONSE

{
    "statusCode": 200,
    "message": "Accounts fetched successfully.",
    "data": [
    {.....},
    {.....},
    {.....}
    ],
    "pagination": {
        "total": 1,
        "pages": 1,
        "page": "0",
        "limit": "25"
    }
}

Exchange Client Credentials/ Refresh Access Token
post
https://api.sandbox.safehavenmfb.com/oauth2/token
This endpoint exchanges your client assertion generated earlier for an api token used to access the endpoints. Also call this endpoint to refresh your api token when it expires

Log in to see full request history
time	status	user agent	
Make a request to see history.

Body Params
grant_type
string
enum
required
Defaults to client_credentials
This is the type of your app. Use 'client_credentials' for private apps and 'authorization_code' for public apps.


client_credentials
Allowed:
client_credentials
authorization_code
refresh_token
client_id
string
required
This is your OAuth Client ID which can be gotten from your app page on the Safe Haven dashboard.

client_assertion
string
required
This is the client assertion you generated earlier.

client_assertion_type
string
required
Defaults to urn:ietf:params:oauth:client-assertion-type:jwt-bearer
This is the client assertion type.

urn:ietf:params:oauth:client-assertion-type:jwt-bearer
refresh_token
string
Pass your refresh token when you want to generate a new api token.

Response

200
200


Get Accounts
get
https://api.sandbox.safehavenmfb.com/accounts
This returns the list of accounts based on the specified parameters.

Log in to see full request history
time	status	user agent	
Make a request to see history.
0 Requests This Month

Query Params
page
int32
Defaults to 0
This represents the position the results should begin from. 0 represents the first position.

0
limit
int32
Defaults to 100
This is the maximum number of accounts to be fetched for this request.

100
isSubAccount
boolean
required
Defaults to false
Set it to true to fetch sub accounts and virtual accounts and false to fetch main accounts


false
Headers
ClientID
string
required
This is your 'ibs_client_id' returned in the response when you generate an api token

Responses

200
200


400
400

Updated over 3 years ago

Get Account
get
https://api.sandbox.safehavenmfb.com/accounts/{id}
This returns a specific account object using the specified id.

Log in to see full request history
time	status	user agent	
Make a request to see history.
0 Requests This Month

Path Params
id
string
required
The _id of the account to fetch.

Headers
ClientID
string
required
This is your 'ibs_client_id' returned in the response when you generate an api token

Responses

200
200


400
400

Updated over 3 years ago

Get Accounts

Update Account
put
https://api.sandbox.safehavenmfb.com/accounts/{id}
Log in to see full request history
time	status	user agent	
Make a request to see history.
0 Requests This Month

Path Params
id
string
required
This is the _id of the account you wish to update.

Body Params
notificationSettings
object

notificationSettings object
Headers
ClientID
string
required
This is your 'ibs_client_id' returned in the response when you generate an api token

Responses

200
200


400
400


Get Account Statement
get
https://api.sandbox.safehavenmfb.com/accounts/{id}/statement
This returns the account statement for a set date range for the specified account

Log in to see full request history
time	status	user agent	
Make a request to see history.
0 Requests This Month

Path Params
id
string
required
The _id of the account.

Query Params
page
int32
required
Defaults to 0
This represents the position the results should begin from. 0 represents the first position.

0
limit
int32
required
Defaults to 100
This is the maximum number of accounts to be fetched for this request.

100
fromDate
date
This is the start date of the period you wish to fetch the statement for.

toDate
string
This is the end date of the period you wish to fetch the statement for.

type
string
enum
The type of transaction you wish to fetch for


Allowed:
Credit
Debit
Headers
ClientID
string
required
This is your 'ibs_client_id' returned in the response when you generate an api token

Responses

200
200


400
400

Beneficiaries
This are the account that transfers are made into. They can be saved after successful transfers. The list of all saved beneficiaries can also be fetched and updated.


Endpoints

Method	Url
GET	/transfers/beneficiaries
DELETE	/transfers/beneficiaries/:id


Get Beneficiaries
get
https://api.sandbox.safehavenmfb.com/transfers/beneficiaries
This returns the list of all saved beneficiaries.

Log in to see full request history
time	status	user agent	
Make a request to see history.
0 Requests This Month

Headers
ClientID
string
required
This is your 'ibs_client_id' returned in the response when you generate an api token

Responses

200
200


400
400

Updated over 3 years ago

Beneficiaries
Delete Beneficiary

Delete Beneficiary
delete
https://api.sandbox.safehavenmfb.com/transfers/beneficiaries/{id}
This removes a beneficiary object with the specified id from your list of saved beneficiaries.

Log in to see full request history
time	status	user agent	
Make a request to see history.
0 Requests This Month

Path Params
id
string
required
The _id of the beneficiary object to delete.

Headers
ClientID
string
required
This is your 'ibs_client_id' returned in the response when you generate an api token

Responses

200
200


400
400

Updated over 3 years ago

Get Beneficiaries
Transfers
Did this page help you?

Transfers
Transfers represent movement of funds from one account to another. It can be intra bank (within Safe Haven) or NIP (which are just transfers to external banks).


Endpoints

Method	Url
GET	/transfers/banks
POST	/transfers/name-enquiry
POST	/transfers
POST	/transfers/tqs
GET	/transfers


Bank List
get
https://api.sandbox.safehavenmfb.com/transfers/banks
This returns the list of all banks in Nigeria.

Log in to see full request history
time	status	user agent	
Make a request to see history.
0 Requests This Month

Headers
ClientID
string
required
This is your 'ibs_client_id' returned in the response when you generate an api token

Responses

200
200


400
400

Name Enquiry
post
https://api.sandbox.safehavenmfb.com/transfers/name-enquiry
Log in to see full request history
time	status	user agent	
Make a request to see history.
0 Requests This Month

Body Params
bankCode
string
required
The bank code of the bank for the account you want to perform an enquiry for.

accountNumber
string
required
The account number of the account to perform the enquiry for.

Headers
ClientID
string
required
This is your 'ibs_client_id' returned in the response when you generate an api token

Responses

200
200


400
400

Updated about 2 years ago

Transfer
post
https://api.sandbox.safehavenmfb.com/transfers
This can be used to make an intra bank transfer or NIP transfer.

Before calling the transfer endpoint, you must call the name enquiry endpoint for the account you want to transfer funds into. The sessionId gotten in the response will be used in this request.

Log in to see full request history
time	status	user agent	
Make a request to see history.
0 Requests This Month

Body Params
nameEnquiryReference
string
required
This is the sessionId returned in the name enquiry endpoint response.

debitAccountNumber
string
required
The account to debit the funds from.

beneficiaryBankCode
string
required
The bank code of the beneficiary account.

beneficiaryAccountNumber
string
required
The account number of the beneficiary account.

amount
float
required
The amount to transfer.

saveBeneficiary
boolean
required
Defaults to false
Set to true to save the beneficiary.


false
narration
string
The transfer narration.

paymentReference
string
A payment reference to attach to the transfer.

Headers
ClientID
string
required
This is your 'ibs_client_id' returned in the response when you generate an api token

Responses

200
200


400
400

Updated about 2 years ago

Name Enquiry
Transfer Status


Transfer Status
post
https://api.sandbox.safehavenmfb.com/transfers/status
Log in to see full request history
time	status	user agent	
Make a request to see history.
0 Requests This Month

Body Params
sessionId
string
required
The sessionId of the transfer.

Headers
ClientID
string
required
This is your 'ibs_client_id' returned in the response when you generate an api token

Responses

200
200


400
400

Updated almost 3 years ago

Transfer
Get Transfers
get
https://api.sandbox.safehavenmfb.com
This returns the transfer history for a specific account according to the set parameters

Log in to see full request history
time	status	user agent	
Make a request to see history.
0 Requests This Month

Updated over 3 years ago

Transfer Status
Identity and Credit Check




Webhooks
If you enable webhook notifications and set an webhook url, Safe Haven will send webhook events to notify updates, transfers, transactions.


Webhook Type

transfer is sent as the webhook type when a transfer is made.


Webhook Object

Webhook object

{
  "type": "transfer",
  "data": {
    "_id": "616f6ad6d5c1fb4ba1f00076",
    "client": "61fbc386dab3430a31406018",
    "account": "613bdab34c38b5140663001f",
    "type": "Inwards",
    "sessionId": "000004211020011401570815591371",
    "nameEnquiryReference": "000004211020011401345232274414",
    "paymentReference": "000004211020011401570815591371",
    "mandateReference": null,
    "isReversed": false,
    "reversalReference": null,
    "provider": "NIBSS",
    "providerChannel": "NIP",
    "providerChannelCode": "3",
    "destinationInstitutionCode": "090286",
    "creditAccountName": "JOHN DOE",
    "creditAccountNumber": "1234567890",
    "creditBankVerificationNumber": null,
    "creditKYCLevel": "3",
    "debitAccountName": "ACME LTD",
    "debitAccountNumber": "0987654321",
    "debitBankVerificationNumber": null,
    "debitKYCLevel": "1",
    "transactionLocation": "",
    "narration": "MOB2/UTO/To JOHN DOE/Test Notification",
    "amount": 100,
    "fees": 0,
    "vat": 0,
    "stampDuty": 0,
    "responseCode": "00",
    "responseMessage": "Approved or completed successfully",
    "status": "Completed",
    "isDeleted": false,
    "createdAt": "2021-10-20T01:14:04.054Z",
    "updatedAt": "2021-10-20T01:15:10.954Z",
    "__v": 0,
    "approvedAt": "2021-10-20T01:15:10.954Z"
  }
}
Webook for Transfer to Virtual Account

JSON

{
    "type": "virtualAccount.transfer",
    "data": {
        "_id": "65b76ed3c0a4440024e45e75",
        "client": "61e5a83ac6f0ec001ee90fac",
        "virtualAccount": "65b76ebbc0a4440024e45e52",
        "sessionId": "999240240129092434550231308787",
        "nameEnquiryReference": "999240240129092425585215261320",
        "paymentReference": "999240240129092434550231308787",
        "isReversed": false,
        "reversalReference": "",
        "provider": "BANK",
        "providerChannel": "TRANSFER",
        "providerChannelCode": "IBS",
        "destinationInstitutionCode": "999240",
        "creditAccountName": "BITAKOTECHNOLOG / OmaTech",
        "creditAccountNumber": "8060376145",
        "creditBankVerificationNumber": null,
        "creditKYCLevel": "3",
        "debitAccountName": "ZEALVEND",
        "debitAccountNumber": "0119536306",
        "debitBankVerificationNumber": null,
        "debitKYCLevel": "3",
        "transactionLocation": "9.0932,7.4429",
        "narration": "",
        "amount": 1001,
        "fees": 5,
        "vat": 0,
        "stampDuty": 0,
        "responseCode": "00",
        "responseMessage": "Approved or completed successfully",
        "status": "Completed",
        "isDeleted": false,
        "createdAt": "2024-01-29T09:24:35.910Z",
        "declinedAt": "2024-01-29T09:24:35.910Z",
        "updatedAt": "2024-01-29T09:24:37.994Z",


        Virtual Accounts
If you're new to working with this API and need to create virtual accounts, here's a quick guide to get you started. This part of the documentation focuses primarily on time-based accounts, which are designed to expire after a certain period. These time-based accounts are particularly useful for situations that involve temporary access, such as facilitating one-time payment processes or handling access during a payment checkout.

For creating virtual accounts that do not expire, you'll want to look into the "Sub Accounts" section under the "Accounts" category.

Create Virtual Account
post
https://api.sandbox.safehavenmfb.com/virtual-accounts
Log in to see full request history
time	status	user agent	
Make a request to see history.
0 Requests This Month

Body Params
validFor
int32
Defaults to 900
Account validity time in seconds. Default 900 = 15 Mins

900
callbackUrl
string
required
An endpoint to receive payment webhook notifications. Must start with https://

settlementAccount
object
Settlement account details


settlementAccount object
amountControl
string
enum
required
This is required if the account is only valid for a specified time.


Fixed
Allowed:

Fixed

UnderPayment

OverPayment
amount
int32
required
This is required if the account is valid for a specified time.

externalReference
string
Unique per account. This will be returned on the transfer object

Headers
ClientID
string
required
This is your 'ibs_client_id' returned in the response when you generate an api token

Responses

200
200


400
400

Updated about 1 year ago

Virtual Accounts
Get Virtual Account


Get Virtual Account
get
https://api.sandbox.safehavenmfb.com/virtual-accounts/{id}
Log in to see full request history
time	status	user agent	
Make a request to see history.
0 Requests This Month

Path Params
id
string
required
The _id of the account to fetch.

Headers
ClientID
string
required
This is your 'ibs_client_id' returned in the response when you generate an api token

Responses

200
200


400
400

Updated about 1 year ago

Create Virtual Account
Virtual Account Transfer Status


Virtual Account Transfer Status
post
https://api.sandbox.safehavenmfb.com/virtual-accounts/status
The sessionId of the transfer.

Log in to see full request history
time	status	user agent	
Make a request to see history.
0 Requests This Month

Body Params
sessionId
string
required
Headers
ClientID
string
required
This is your 'ibs_client_id' returned in the response when you generate an api token

Responses

200
200


400
400

Updated over 1 year ago

Get Virtual Account
Get Virtual Transaction
Did this page help you?


Get Virtual Transaction
get
https://api.sandbox.safehavenmfb.com/virtual-accounts/{virtualAccountId}/transaction
Log in to see full request history
time	status	user agent	
Make a request to see history.
0 Requests This Month

Path Params
virtualAccountId
string
required
_id of the virtual account

Responses

200
200


400
400

Updated over 1 year ago

Virtual Account Transfer Status
Update Virtual Account
Did this page help you?


Update Virtual Account
put
https://api.sandbox.safehavenmfb.com/virtual-accounts/{id}
Log in to see full request history
time	status	user agent	
Make a request to see history.
0 Requests This Month

Path Params
id
string
required
The _id of the account to update.

Body Params
callbackUrl
string
An endpoint to receive payment webhook notifications. Must start with https://

Headers
ClientID
string
required
This is your 'ibs_client_id' returned in the response when you generate an api token

Responses

200
200


400
400

Updated about 1 year ago

Get Virtual Transaction
Delete Virtual Account
Did this page help you?


Delete Virtual Account
delete
https://api.sandbox.safehavenmfb.com/virtual-accounts/{id}
Log in to see full request history
time	status	user agent	
Make a request to see history.
0 Requests This Month

Path Params
id
string
required
The _id of the account to delete.

Responses

200
200


400
400

Updated over 3 years ago

Update Virtual Account
Checkout.js


Checkout.js
To accept payments online using the SafeHaven Checkout, add the SafeHaven Checkout JS script to your HTML page and configure the checkout as in the examples below.

Example 1 - With Redirect URL

index.html

<!DOCTYPE html>
<html lang="en">
  <head>
    <title>SafeHaven Checkout Demo</title>
  </head>
  <body>
    <h1>Checkout Demo</h1>
    <button onclick="payWithSafeHaven()">Click Me!</button>
    
    <script src="https://checkout.safehavenmfb.com/assets/checkout.min.js"></script>
    <script type="text/javascript">
    	let payWithSafeHaven = () => {
            let checkOut = SafeHavenCheckout({
                environment: "production", //sandbox || production
                clientId: "{{ OAuth2 ClientID }}",
                referenceCode: ''+Math.floor((Math.random() * 1000000000) + 1),
                customer: {
                    firstName: "John",
                    lastName: "Doe",
                    emailAddress: "johndoe@example.com",
                    phoneNumber: "+2348032273616"
                },
                currency: "NGN", // Must be NGN
                amount: 100,
  	            //feeBearer: "account", // account = We charge you, customer = We charge the customer
                settlementAccount: {
                    bankCode: "090286", // 999240 = Sandbox || 090286 = Production
                    accountNumber: "{{ 10 Digits SafeHaven Account Number }}"
                },
                redirectUrl: "https://example.com/redirect",
	              //webhookUrl: "",
                //customIconUrl: "https://safehavenmfb.com/assets/images/logo1.svg",
              	//metadata: { "foo": "bar" }
            });
        }
    </script>
  </body>
</html>
Example 2 - With Callback Function

index.html

<!DOCTYPE html>
<html lang="en">
  <head>
    <title>SafeHaven Checkout Demo</title>
  </head>
  <body>
    <h1>Checkout Demo</h1>
    <button onclick="payWithSafeHaven()">Click Me!</button>
    
    <script src="https://checkout.safehavenmfb.com/assets/checkout.min.js"></script>
    <script type="text/javascript">
    	let payWithSafeHaven = () => {
            let checkOut = SafeHavenCheckout({
                environment: "production", //sandbox || production
                clientId: "{{ OAuth2 ClientID }}",
                referenceCode: ''+Math.floor((Math.random() * 1000000000) + 1),
                customer: {
                    firstName: "John",
                    lastName: "Doe",
                    emailAddress: "johndoe@example.com",
                    phoneNumber: "+2348032273616"
                },
                currency: "NGN", // Must be NGN
                amount: 100,
  	            //feeBearer: "account", // account = We charge you, customer = We charge the customer
                settlementAccount: {
                    bankCode: "090286", // 999240 = Sandbox || 090286 = Production
                    accountNumber: "{{ 10 Digits SafeHaven Account Number }}"
                },
	              //webhookUrl: "",
                //customIconUrl: "https://safehavenmfb.com/assets/images/logo1.svg",
              	//metadata: { "foo": "bar" },
              	onClose: () => { console.log("Checkout Closed") },
              	callback: (response) => { console.log(response) }
            });
        }
    </script>
  </body>
</html>
📘


Verify Checkout Transaction
get
https://api.sandbox.safehavenmfb.com/checkout/{referenceCode}/verify
Log in to see full request history
time	status	user agent	
Make a request to see history.
0 Requests This Month

Path Params
referenceCode
string
required
Checkout referenceCode from Checkout.js

Responses

200
200


400
400

Updated over 3 years ago

Checkout.js
VAS Transactions



