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

