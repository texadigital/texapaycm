API Introduction
Open Exchange Rates provides a simple, lightweight and portable JSON API with live and historical foreign exchange (forex) rates, via a simple and easy-to-integrate API, in JSON format. Data are tracked and blended algorithmically from multiple reliable sources, ensuring fair and unbiased consistency.

Exchange rates published through the Open Exchange Rates API are collected from multiple reliable providers, blended together and served up in JSON format for everybody to use. There are no complex queries, confusing authentication methods or long-term contracts.

End-of-day rates are available historically for all days going back to 1st January, 1999.

Common Use Cases
Data from the Open Exchange Rates API are suitable for use in every framework, language and application, and have been successfully integrated in:

Shopping carts from WooCommerce to Shopify, and thousands of individual web stores
Overseas campaigns from the smallest startups to Fortune 500 heavyweights
Accounting departments for multinational brands and shipping/logistics firms
Open source projects and charities
Enterprise-level analytics software
Hundreds of smartphone, tablet and desktop apps
School and university research projects across the world
Our clients range from freelancers and the smallest one-man development shops, to international sports networks and post-IPO startups.

Connecting To The API
We serve our data in JSON format via a simple URL-based interface over HTTPS, which enables you to use the rates in whichever way you require.

This is the high-level introduction – for more in-depth guides, please see the relevant Documentation sections.

Connection Types

Any language or software that can make HTTP requests or fetch web addresses can access our API (for example, you can visit any of the API routes in your browser to verify they’re working as expected).

For your integration, you can use whichever library you require. This will vary depending on your development environment. There are guides and a wide range of open source integrations available, also covered in our documentation.

URLs (routes) are requested once over HTTPS, and deliver all their data in one go, just like a normal web request.

We do not currently support websockets, webhooks or any other keep-alive or push-notification style connections – in other words, when you want fresh data, you simply request it from our server. We're considering these methods for a future version of our API, so please email us if interested.

URL Format

The API base path is https://openexchangerates.org/api/.

API routes/endpoints are then appended to this base path, like so:

HTTP

https://openexchangerates.org/api/
                                  latest.json
                                  currencies.json
                                  historical/2013-02-16.json
Query parameters (such as your App ID, requested base currency, or JSONP callback) are appended as GET request parameters, for example:

HTTP

https://openexchangerates.org/api/latest.json
                                             ?app_id=YOUR_APP_ID
                                             &base=GBP
                                             &callback=someCallbackFunction
If your request is valid and permitted, you will receive a JSON-formatted response to work with. If something is wrong with the request, you will receive an error message.

API Response Formats
Responses are delivered over HTTPS as plain-text JSON (JavaScript Object Notation) format, ready to be used however your integration requires.

This format doesn't limit how and where you can use the data in any way: JSON is simply a fast, simple and lightweight delivery mechanism, which is supported in every major language and framework.

We designed these responses to be simple to integrate into a variety of apps and software. If needed, you can also programmatically convert JSON data to CSV/spreadsheet format, or any other format.

There are several main response styles/formats: latest/historical rates, currencies list, time-series and currency conversion. These are detailed individually on each relevant documentation page, and you can see an example (for latest.json) below.

Here's an example basic API request for all the latest rates, relative to USD (default):

HTTP

https://openexchangerates.org/api/latest.json?app_id=YOUR_APP_ID
When requesting this URL (assuming your App ID is valid) you will receive a JSON object containing a UNIX timestamp (UTC seconds), base currency (3-letter ISO code), and a rates object with symbol:value pairs, relative to the requested base currency:

JSON - latest.json

{
    disclaimer: "https://openexchangerates.org/terms/",
    license: "https://openexchangerates.org/license/",
    timestamp: 1449877801,
    base: "USD",
    rates: {
        AED: 3.672538,
        AFN: 66.809999,
        ALL: 125.716501,
        AMD: 484.902502,
        ANG: 1.788575,
        AOA: 135.295998,
        ARS: 9.750101,
        AUD: 1.390866,
        /* ... */
    }
}
The response format is the same for Historical Data (historical/YYYY-MM-DD.json) requests.

Other API routes – i.e. currencies.json, time-series.json and convert/ – have a different request and response format. Please see their relevant pages for details and examples.
Authentication
The Open Exchange Rates API currently supports basic App ID authentication via the app_id parameter.

App IDs are 32 hexadecimal (0-9/A-F) characters long, and are unique to each account.

📘
API Explorer
If you have an App ID already, you can enter it into the API Explorer in the API endpoint documentation pages to have it pre-filled and sent with your test requests.

Use the 'Key' icon and enter it next to app_id.

Register for an App ID
You can sign up here for your App ID.

If you've already signed up, you can visit your account dashboard at any time to view your App ID.

Using Your App ID
To access any of the API routes, simply append your App ID as a parameter on the end of each request, like so:

HTTP
cURL

https://openexchangerates.org/api/latest.json?app_id=YOUR_APP_ID
Most code samples, extensions, plugins and libraries built for our API have a setting or variable where you can enter your App ID.

App IDs should be kept as secret as possible, but if you're developing in client-side JavaScript, your App ID will be visible in your public source code. We haven't found this to be an issue, but we're working on more advanced authentication for the next version of the API. If you suspect somebody is using your App ID without your permission, we can regenerate it for you.

HTTP Header Authentication
If you do not wish to specify your App ID in the URL parameters, you may instead provide it as a Token in the HTTP Authorization Header. For example:

HTTP
cURL

"Authorization: Token YOUR_APP_ID"
Please note: The format of the HTTP header must be exactly as above (replacing YOUR_APP_ID with a valid Open Exchange Rates App ID). The App ID should be unquoted. If both HTTP header and URL parameter are provided, we will use the value from the URL and ignore the header.

Tracking App ID Usage
To track the usage of your App ID, you can log in to your account dashboard and visit the Usage Statistics page.

You can also use our usage.json API endpoint to request general usage and quota information about an Open Exchange Rates App ID.

👍
If your account usage goes over the monthly threshold for your plan, we'll email you to discuss options that would best suit your current usage.
Regenerating Your App ID
If you need to create or deactivate an App ID, please visit your Account Dashboard.

Errors
The Open Exchange Rates API will return JSON error messages if something goes wrong, to help you debug your applications and raise alerts.

All Open Exchange Rates API errors currently use the same format.

Here's an example, produced when an invalid app_id is provided:

JSON

{
  "error": true,
  "status": 401,
  "message": "invalid_app_id",
  "description": "Invalid App ID provided - please sign up at https://openexchangerates.org/signup, or contact support@openexchangerates.org."
}
Error Status Codes Reference
There are several potential errors, the most common listed below:

Message

Status Code

Details

"not_found"

404

Client requested a non-existent resource/route

"missing_app_id"

401

Client did not provide an App ID

"invalid_app_id"

401

Client provided an invalid App ID

"not_allowed"

429

Client doesn’t have permission to access requested route/feature

"access_restricted"

403

Access restricted for repeated over-use (status: 429), or other reason given in ‘description’ (403).

"invalid_base"

400

Client requested rates for an unsupported base currency

📘
If you get an error that is not documented here, or experience some other issue with the API, please contact us.


Errors
The Open Exchange Rates API will return JSON error messages if something goes wrong, to help you debug your applications and raise alerts.

All Open Exchange Rates API errors currently use the same format.

Here's an example, produced when an invalid app_id is provided:

JSON

{
  "error": true,
  "status": 401,
  "message": "invalid_app_id",
  "description": "Invalid App ID provided - please sign up at https://openexchangerates.org/signup, or contact support@openexchangerates.org."
}
Error Status Codes Reference
There are several potential errors, the most common listed below:

Message

Status Code

Details

"not_found"

404

Client requested a non-existent resource/route

"missing_app_id"

401

Client did not provide an App ID

"invalid_app_id"

401

Client provided an invalid App ID

"not_allowed"

429

Client doesn’t have permission to access requested route/feature

"access_restricted"

403

Access restricted for repeated over-use (status: 429), or other reason given in ‘description’ (403).

"invalid_base"

400

Client requested rates for an unsupported base currency

📘
If you get an error that is not documented here, or experience some other issue with the API, please contact us.

/latest.json
get
https://openexchangerates.org/api/latest.json
Get the latest exchange rates available from the Open Exchange Rates API.

The most simple route in our API, latest.json provides a standard response object containing all the conversion rates for all of the currently available symbols/currencies, labeled by their international-standard 3-letter ISO currency codes.

The latest rates will always be the most up-to-date data available on your plan.

Log in to see full request history
time	status	user agent	
Make a request to see history.

The base property provides the 3-letter currency code to which all the delivered exchange rates are relative. This base currency is also given in the rates object by default (e.g. "USD": 1).

The rates property is an object (hash/dictionary/associative array) containing all the conversion or exchange rates for all of the available (or requested) currencies, labeled by their international-standard 3-letter currency codes. All the values are relative to 1 unit of the requested base currency.

The timestamp property indicates the time (UNIX) that the rates were published. (If you’re using the timestamp value in JavaScript, remember to multiply it by 1000, because JavaScript uses time in milliseconds instead of seconds.)

📘
Additional Parameters
Choosing specific symbols and fetching extra rates with show_alternative are available for all plans, including free. Changing the base currency is available for all clients of paid plans.

Basic Code Samples
jQuery
PHP
Ruby
Others...

$.get('https://openexchangerates.org/api/latest.json', {app_id: 'YOUR_APP_ID'}, function(data) {
    console.log("1 US Dollar equals " + data.rates.GBP + " British Pounds");
});
To request rates for a different base currency, please see Changing Base Currency.

Errors
Please see API Error Messages for a list of possible errors.

Query Params
app_id
string
Defaults to Required
Your unique App ID

Required
base
string
Defaults to Optional
Change base currency (3-letter code, default: USD)

Optional
symbols
string
Defaults to Optional
Limit results to specific currencies (comma-separated list of 3-letter codes)

Optional
prettyprint
boolean
Defaults to false
Set to false to reduce response size (removes whitespace)


false
show_alternative
boolean
Defaults to false
Extend returned values with alternative, black market and digital currency rates


false
Response

200
200

/historical/*.json
get
https://openexchangerates.org/api/historical/:date.json
Get historical exchange rates for any date available from the Open Exchange Rates API, currently going back to 1st January 1999.

Like latest.json, our /historical/ route provides a standard response object containing all the conversion rates for all available symbols/currencies on your requested date, labeled by their international-standard 3-letter ISO currency codes.

The historical rates returned are the last values we published for a given UTC day (up to and including 23:59:59 UTC), except for the current UTC date.

Log in to see full request history
time	status	user agent	
Make a request to see history.
0 Requests This Month

The base property provides the 3-letter currency code to which all the delivered exchange rates are relative. This base currency is also given in the rates object by default (e.g. "USD": 1).

The rates property is an object (hash/dictionary/associative array) containing all the conversion or exchange rates for all of the available (or requested) currencies, labeled by their international-standard 3-letter currency codes. All the values are relative to 1 unit of the requested base currency.

The timestamp property indicates the time (UNIX) that the rates were published. (If you’re using the timestamp value in JavaScript, remember to multiply it by 1000, because JavaScript uses time in milliseconds instead of seconds.)

📘
Additional Parameters
Changing the base currency and requesting specific symbols are currently available for clients on the Developer, Enterprise and Unlimited plans.

🚧
Historical Timezones & Daylight Saving Time
For the sake of consistency, all dates and timestamps in the Open Exchange Rates API refer to the UTC timezone (i.e. GMT+00:00), with no daylight saving applied.

If your server/integration operates in a different timezone (or supports multiple timezones), you should convert your server's local time to the equivalent UTC time before making your request.

🚧
Historical requests for the current day's UTC date
Historical rates are generally the last values we published on a given UTC day (up to and including 23:59:59 UTC), with the exception of the current UTC date.

If you make a request for the current day's historical file, you will receive the most recent rates available for your subscription plan at that moment, as the day is not yet complete (therefore in such cases, the values returned would be the same as you would find via our latest.json endpoint).

This behaviour was added early on in response to client feedback, because the historical endpoint aims to always provide a value for a valid date (even if that date is the same as today's date and therefore no end-of-day values are available).

If you wish to obtain rates for a historical date that is the same as today's date (in UTC), you may prefer to make use of the latest.json endpoint instead, in order to prevent unexpected behaviour.

Basic Code Samples
jQuery
Others...

var date = "2010-10-10";

$.get('https://openexchangerates.org/api/historical/' + date + '.json', {app_id: 'YOUR_APP_ID'}, function(data) {
    console.log("On " + date + ", 1 US Dollar was worth " + data.rates.GBP + " GBP");
});
To request rates for a different base currency, please see Changing Base Currency.

Errors
Please see API Error Messages for a list of possible errors not specified above.

Path Params
date
date
required
Defaults to Required
The requested date in YYYY-MM-DD format (required).

Required
Query Params
app_id
string
Defaults to Required
Your unique App ID (required)

Required
base
string
Defaults to Optional
Change base currency (3-letter code, default: USD)

Optional
symbols
string
Defaults to Optional
Limit results to specific currencies (comma-separated list of 3-letter codes)

Optional
show_alternative
boolean
Defaults to false
Extend returned values with alternative, black market and digital currency rates


false
prettyprint
boolean
Defaults to false
Human-readable response for debugging (response size will be much larger)


false
Responses

200
200


400
400

/currencies.json
get
https://openexchangerates.org/api/currencies.json
Get a JSON list of all currency symbols available from the Open Exchange Rates API, along with their full names, for use in your integration.

This list will always mirror the currencies available in the latest rates (given as their 3-letter codes).

Log in to see full request history
time	status	user agent	
Make a request to see history.
0 Requests This Month

The standard response is a JSON object (e.g. a hash/dictionary/associative array), where each key:value pair represents a currency’s symbol and unit display name (singular and Capitalised).

📘
Requests to currencies.json do not count towards your account usage limit, and App ID authentication is optional for this endpoint.

Basic Code Samples
jQuery
Others...

$.get('https://openexchangerates.org/api/currencies.json', function(data) {
    console.log("Did you know? One unit of VND is known as a '" + data.VND + "'");
});
Alternative, Experimental and Black-market Rates
You can include the list of unofficial, black market and alternative digital currencies via the show_alternative or only_alternative parameters:

HTTP
jQuery

https://openexchangerates.org/api/currencies.json
    ?show_alternative=1
Please note that alternative rates do not always use a three-letter code.

You can find more information on our unofficial, black market and alternative digital currencies here.

Query Params
prettyprint
boolean
Defaults to false

false
show_alternative
boolean
Defaults to false
Include alternative currencies.


false
show_inactive
boolean
Defaults to false
Include historical/inactive currencies


false
Response

200
200

Updated 3 months ago

/historical/*.json
/time-series.json

/time-series.json
get
https://openexchangerates.org/api/time-series.json
Get historical exchange rates for a given time period, where available, using the time series / bulk download API endpoint. Please read all the details before integrating.

Time Series requests are currently available for clients on the Enterprise and Unlimited plans.

Log in to see full request history
time	status	user agent	
Make a request to see history.
0 Requests This Month

🚧
Important
The maximum query period currently allowed is one month. If you need more than one month’s data, please request months sequentially (details below).

With a full list of currencies for each requested day, Time-Series API responses can be very large in size. For better performance, use the symbols parameter to cut down response weight.

The Time Series response format is different from the standard API response as in latest.json. Please familiarise yourself with the standard API response format before using this endpoint.

The start_date is the first day of the time series, and the end_date is the final day (both inclusive).

The base property provides the 3-letter currency code to which all the delivered exchange rates are relative. This base currency is also given in the rates object by default (e.g. "USD": 1).

The rates property is an object of dates, each an object containing the conversion or exchange rates for the specified date, labeled by their international-standard 3-letter currency codes. All the values are relative to 1 unit of the requested base currency.

There is notimestamp property in this response.

Important notes about Time-Series queries
Please note that each day requested counts as one API request (so requesting a full month of data will count as up to 31 ‘hits’).
Where the requested end date is not a valid calendar date, it will be corrected backwards automatically to the nearest valid day. For example, 2012-02-31 will be corrected to 2012-02-29.
To request multiple months sequentially, you can simply request the 1st to the 31st of each month, regardless of month length.
Errors
Please see API Error Messages for a list of possible errors not specified above.

Query Params
app_id
string
Defaults to Required
Your unique App ID

Required
start
date
Defaults to Required
The time series start date in YYYY-MM-DD format

Required
end
date
Defaults to Required
The time series end date in YYYY-MM-DD format (see notes)

Required
symbols
string
Defaults to Recommended
Limit results to specific currencies (comma-separated list of 3-letter codes)

Recommended
base
string
Defaults to Optional
Change base currency (3-letter code, default: USD)

Optional
show_alternative
boolean
Defaults to false
Extend returned values with alternative, black market and digital currency rates


false
prettyprint
boolean
Defaults to false
Human-readable response for debugging (response size will be much larger)


false
Responses

200
200


400
400

Updated 3 months ago

/currencies.json
/convert


/convert
get
https://openexchangerates.org/api/convert/{value}/{from}/{to}
Convert any money value from one currency to another at the latest API rates using the /convert API endpoint.

This feature works differently to other endpoints in our API, using a REST-based approach and an alternate response format.

Currency conversion requests are currently available for clients on the Unlimited plan.

Log in to see full request history
time	status	user agent	
Make a request to see history.
0 Requests This Month

📘
Unlimited Plan Feature
Currency conversion requests are currently available for clients on the Unlimited plan.

Your query is returned in the request object parameter for validation.

The rate and timestamp used to perform the conversion are given in the meta object parameter.

The converted value is given in the response parameter.

Important note about Currency Conversion queries:
The /convert API is offered to save time in integration, but does not imply any difference in accuracy, validity or fitness for purpose from the data that can be obtained with any other API request.

Returned values are used at your own risk, so we strongly recommend validating any returned values before employing them in any system or interface where transactions are processed.

Errors
Please see API Error Messages for a list of possible errors not specified above.

Path Params
value
int32
required
Defaults to null
The value to be converted

from
string
required
Defaults to Required
The base ('from') currency (3-letter code)

Required
to
string
required
Defaults to Required
The target ('to') currency (3-letter code)

Required
Query Params
app_id
string
Defaults to Required
Your unique App ID (required)

Required
prettyprint
boolean
Defaults to false
Human-readable response for debugging


false
Responses

200
200


400
400

Updated 3 months ago

/time-series.json
/ohlc.json


/ohlc.json
get
https://openexchangerates.org/api/ohlc.json
Get historical Open, High Low, Close (OHLC) and Average exchange rates for a given time period, ranging from 1 month to 1 minute, where available. Please read all the details before starting integration.

Values for 'high', 'low' and 'average' are based on all recorded prices we published (up to every 1 second).

OHLC requests are currently available for clients of our VIP Platinum tier.

Log in to see full request history
time	status	user agent	
Make a request to see history.
0 Requests This Month

The following parameters are available for OHLC requests:

app_id (required)

Your Open Exchange Rates App ID
start (required)

A full ISO 8601 formatted timestamp in UTC timezone, with any hour/minute and zero seconds.
Format: "YYYY-MM-DDThh:mm:00Z".
Example: &start=2017-07-17T08:30:00Z.
There are limitations to the start time, depending on the chosen period (see below).
period (required)

The requested time period for the OHLC data.
Allowed periods are: 1m, 5m, 15m, 30m, 1h, 12h, 1d, 1w, and 1mo.
Example: &period=30m.
There are limitations to the period, depending on the chosen 'start' time (see below).
base (optional)

Choose base currency for OHLC prices (default USD).
Example: &base=GBP
symbols (optional)

Limit the number of currencies returned. Recommended to reduce file size.
Example: &symbols=XAU,XAG,XPD,XPT
show_alternative (optional)

Include alternative, digital and black currency prices
Example: &show_alternative=true
prettyprint (optional)

Get a human-readable (whitespace formatted) response. Not recommended for production use.
Example: &prettyprint=true
Limitations
The following restrictions apply to the combination of start_time and period:

start_time must always have zero seconds (i.e. "hh:mm:00") and must be on/after December 19th 2016.
The format for start_time must be ISO-8601 and limited to UTC (timezones are not currently supported) – i.e: "YYYY-MM-DDThh:mm:00Z".
Periods of 1m must not start more than 60 minutes ago (i.e. start_time should be within the past hour).
Periods of 5m must not start more than 24 hours ago, and must be aligned to a whole 5 minute period (e.g. 00, 05, 10, 15 etc.)
Periods of 15m must not start more than 24 hours ago, and must be aligned to a whole 15 minute period (i.e. 00, 15, 30, 45)
Periods of 30m or 60m/1h must not start more than 32 days ago, and must be aligned to a whole 30 minute period (i.e. 00 or 30)
Periods of 12h or 24h/1d must be aligned to whole 30 minute period (i.e. 00, 30)
Periods of 7d/1w must be aligned to the start of a whole calendar day (i.e. YYYY-MM-DDT00:00:00Z)
Periods of 1mo must be aligned to the start of a whole calendar month (i.e. YYYY-MM-01T00:00:00Z)
The requested combination of start_time and period must not produce an end_time that is in the future (i.e. an incomplete period).
🚧
Important
With a full list of currencies, OHLC responses can be very large in size. For better performance, use the symbols parameter to cut down response weight, and set prettyprint=false to remove whitespace.

API Response Format
The OHLC endpoint response format is similar to the standard API response in latest.json. Please familiarise yourself with the standard API response format before using this endpoint.

The start_time is the ISO-8601 formatted timestamp (UTC) of the start of period, corresponding to the 'open' rate.

The end_time is the ISO-8601 formatted timestamp (UTC) of the end of period (inclusive), corresponding to the 'close' rate. (For example, a period of 60m would begin on hh:00:00 and end on hh:59:59).

The base property provides the 3-letter currency code to which all the delivered exchange rates are relative.

The rates object contains an object for each currency in the result set, labelled by its international-standard 3-letter currency code. Each object contains five values:

open – The midpoint rate recorded at start_time
high – The highest midpoint rate recorded at any time during the period
low – The lowest midpoint rate recorded at any time during the period
close – The midpoint rate recorded at end_time
average – The time-weighted average midpoint exchange rate for the entire period
All the values are relative to 1 unit of the requested base currency.

Errors
Please see API Error Messages for a list of possible errors not specified above.

Query Params
app_id
string
Defaults to Required
Your unique App ID.

Required
start_time
date
Defaults to Required
The start time for the requested OHLC period (ISO-8601 format, UTC only). Restrictions apply (please see below).

Required
period
string
Defaults to Required
The requested period (starting on the start_time), e.g. "1m", "30m", "1d". Please see below for supported OHLC periods.

Required
symbols
string
Defaults to Recommended
Limit results to specific currencies (comma-separated list of 3-letter codes)

Recommended
base
string
Defaults to Optional
Change base currency (3-letter code, default: USD)

Optional
prettyprint
boolean
Defaults to false
Human-readable response for debugging (response size will be much larger)


false
Responses

200
200


400
400

Updated 3 months ago

/convert
/usage.json

/usage.json
get
https://openexchangerates.org/api/usage.json
Get basic plan information and usage statistics for an Open Exchange Rates App ID

Log in to see full request history
time	status	user agent	
Make a request to see history.
0 Requests This Month

📘
Requests to usage.info do not count towards your usage volume.
If the App ID you provided is valid, you will receive a JSON response with a status value (containing the HTTP code of the response) and a data object, containing the following attributes:

app_id: The app ID you provided.
status: The current status of this app ID (either 'active' or 'access_restricted')
plan: Plan information for this app ID
name: The name of the current plan
quota: The monthly request allowance (formatted string for display)
update_frequency: The rate at which data refreshes on this plan
features: The supported features of this plan (base, symbols, experimental, time-series, convert)
usage: Usage information for this app ID
requests: Number of requests made since month start
requests_quota: Number of requests allowed each month with this plan
requests_remaining: Number of requests remaining this month
days_elapsed: Number of days since start of month
days_remaining: Number of days remaining until next month's start
daily_average: Average requests per day
NB: If the App ID belongs to an account with unlimited requests, the usage.requests_quota and usage.requests_remaining values will be -1.

📘
Public Endpoint
Because this API endpoint is accessible to anybody with any App ID, no personal or sensitive account data is ever returned.

Basic Code Samples
jQuery
PHP
Others...

$.get('https://openexchangerates.org/api/usage.json', {app_id: 'YOUR_APP_ID'}, function(response) {
    console.log("This Open Exchange Rates app ID has made " + response.data.usage.requests + "hits this month.");
});
Query Params
app_id
string
Defaults to Required
Your unique App ID (required)

Required
prettyprint
boolean
Defaults to true
Set 'false' to minify response


Responses

200
200


401
401

Updated 3 months ago

/ohlc.json
Set Base Currency ('base')

Set Base Currency ('base')
The default base currency of the API is US Dollars (USD), but you can request exchange rates relative to a different base currency, where available, by setting the base parameter in your request.

📘
Changing Base Currency is currently available for clients on the Developer, Enterprise and Unlimited plans.

Any currency can be chosen as a base currency when requesting the latest rates, as well as historical rates and time-series (where available).

The base currency should be requested with its 3-digit ISO code (see our list of available API currencies if in doubt).

Results will be delivered relative to 1 unit of the currency you have requested.

Basic Request & Response*
Append the base query parameter to your API request, along with the required 3-digit ISO currency code or symbol, like so (for Euros):

HTTP
jQuery

https://openexchangerates.org/api/latest.json
    ?app_id=[[app:app_id]]
    &base=EUR
The response format is exactly the same as the standard API response, with all rates in the rates object given relative to 1 standard unit of your requested base currency:

JSON

{
    disclaimer: "https://openexchangerates.org/terms/",
    license: "https://openexchangerates.org/license/",
    "timestamp": 1424127600,
    "base": "EUR",
    "rates": {
        "AED": 4.626447,
        "AFN": 61.002415,
        "ALL": 137.92617,
        /* ... */
    }
}
Basic Code Samples
jQuery
Others...

$.get('https://openexchangerates.org/api/latest.json', {app_id: '[[app:app_id]]', base: 'UGX'}, function(data) {
    console.log("1 Ugandan Shilling equals " + data.rates.JPY + " Japanese Yen");
});
Combining Parameters
Requesting symbols for a specific base currency can be combined with other API parameters, such as requesting specific rates/currencies (symbols) and JSONP callbacks (callback), for example:

HTTP

https://openexchangerates.org/api/historical/2015-02-16.json
    ?app_id=[[app:app_id]]
    &base=CAD
    &symbols=AUD,GBP,EUR
    &callback=someFunctionName
The response will combine your parameters:

JSON

someFunctionName(
{
    disclaimer: "https://openexchangerates.org/terms/",
    license: "https://openexchangerates.org/license/",
    timestamp: 1424127600,
    base: "CAD",
    rates: {
            AUD: 1.032828,
            EUR: 0.706867,
            GBP: 0.522328,
        }
    }
)
Default Base Currency
🚧
The default API base currency is always United States Dollars (USD). It's not currently possible to set another default base for your entire account, so if (for example) you always need rates in GBP, you’ll need to always add &base=GBP to your API requests.

Get Specific Currencies ('symbols')
By default, the API returns rates for all currencies, but if you need to minimise transfer size, you can request a limited subset of exchange rates, where available, by setting the symbols (alias: currencies) parameter in your request.

👍
Requesting specific currency rates is available for all clients, including Free subscribers.

We recommend using this parameter as much as possible to reduce response weight, which can improve performance, especially on mobile devices and low-bandwidth connections.

Symbols (currencies) should be provided as a comma-separated list of standard 3-letter ISO currency codes (see our list of available API currencies if in doubt), in any order.

Basic Request & Response
Append the symbols query parameter to your API request, along with the comma-separated list of 3-digit ISO currency codes or symbols you require (in any order), like so:

HTTP
jQuery

https://openexchangerates.org/api/latest.json
    ?app_id=[[app:app_id]]
    &symbols=GBP,EUR,AED,CAD
The response format is exactly the same as the standard API response, with all rates in the rates object given relative to 1 standard unit of your requested base currency:

JSON

{
    disclaimer: "https://openexchangerates.org/terms/",
    license: "https://openexchangerates.org/license/",
    "timestamp": 1424127600,
    "base": "USD",
    "rates": {
        "AED": 3.67295,
        "CAD": 0.99075,
        "EUR": 0.793903,
        "GBP": 0.62885
    }
}
The response format is the same as the standard API response, with only your requested currencies delivered in the rates object.

Basic Code Samples
jQuery
Others...

$.get('https://openexchangerates.org/api/latest.json', {app_id: '[[app:app_id]]', symbols: 'QAR,RUB,SEK'}, function(data) {
    console.log("1 US Dollar equals " + data.rates.SEK + " Swedish Krona");
});
Combining Parameters
Requesting specific currency symbols can be combined with other API parameters, such as changing the base currency (base) and JSONP callbacks (callback) – for example:

HTTP

https://openexchangerates.org/api/historical/2015-02-16.json
    ?app_id=[[app:app_id]]
    &base=CAD
    &symbols=AUD,GBP,EUR
    &callback=someFunctionName
The response will combine your parameters:

JSON

someFunctionName(
{
    disclaimer: "https://openexchangerates.org/terms/",
    license: "https://openexchangerates.org/license/",
    timestamp: 1424127600,
    base: "CAD",
    rates: {
            AUD: 1.032828,
            EUR: 0.706867,
            GBP: 0.522328,
        }
    }
)
The parameter is also available (and strongly recommended) for Time Series requests.

Bid-Ask Prices ('show_bid_ask')
Request bid, ask and mid prices for all currencies, where available.

We calculate the best available (top of the book) buy and sell prices for the majority of currencies we offer, and provide these instead of a single midpoint rate when you use the show_bid_ask API parameter.

Using this parameter replaces the regular results, so that instead of a midpoint currency value for each returned currency symbol, you will receive an object containing ask, bid, and mid.

Currently available for clients of our VIP Platinum tier. Please contact us if you would like to test out this feature.

Request 'Bid-Ask' Rates
Append the show_bid_ask query parameter to your latest.json, historical/ or spot.json API request, like so:

HTTP
jQuery

https://openexchangerates.org/api/latest.json
    ?app_id=YOUR_APP_ID
    &show_bid_ask=1
The response format is the same as the standard API response, except that instead of a single (midpoint) value for each returned currency symbol, you will now receive an object containing bid, ask and mid for each currency where these are available*:

JSON

{
  "disclaimer": "...",
  "license": "...",
  "timestamp": 1501084644,
  "base": "USD",
  "rates": {
    "AED": {
      "bid": 3.67229,
      "ask": 3.67378,
      "mid": 3.673035
    },
    "AFN": {
      "bid": 68.399726,
      "ask": 68.690659,
      "mid": 68.545193
    },
    /* ... */
}
*For some currencies, we do not currently provide bid and ask prices. In such cases, or if we don't have bid and ask prices available for a given currency or data, we will not return them for that symbol.

You will still receive mid, but bid and ask will not be included.

Basic Code Samples
jQuery
Others...

$.get('https://openexchangerates.org/api/latest.json', {app_id: 'YOUR_APP_ID', show_bid_ank: 1}, function(data) {
    console.log("USD/GBP 'bid' price: " + data.rates.GBP.bid);
    console.log("USD/GBP 'ask' price: " + data.rates.GBP.ask);
});
Combining Parameters
The show_bid_ask parameter can be combined with any other parameters available, such as base and symbols, and historical queries.

Updated 3 months ago

Get Specific Currencies ('symbols')
Alternative Rates ('show_alternative')

Alternative Rates ('show_alternative')
Certain alternative rates aren't suitable for our primary API endpoints, but have been commonly requested by our clients (such as Dicom, Dipro and black market rates for Venezuelan Bolìvar, and a variety of digital currencies).

You may now request latest and historical rates for unofficial, black market and alternative digital currencies by adding a simple API parameter onto your request.

Please contact us if you would like to receive data for an alternative rate or digital currency that we do not currently provide.

You may add data for all supported unofficial, black market and alternative digital currencies by adding the show_alternative query parameter to your API request. The list of supported 'alternative' currencies is here.

Some of these rates are due to be added to the primary API (such as LTC/Litecoin and ETH/Ether), while others will remain available only through the show_alternative API parameter (such as VEF_DIPRO and VEF_DICOM).

📘
Alternative currencies data are available for all clients, and the feature is currently in stable beta. The request and response format may change and currencies may be added and removed (but we'll post an update via our Status Page whenever this happens).

You may contact us with any feedback or questions about this feature and the returned rates at support@openexchangerates.org.

🚧
Future inclusion of ETH and LTC symbols in primary API results
In early 2018, we intend to merge ETH (Ethereum) and LTC (Litecoin) with our 'primary' API results. This means they'll be available to all users, without using the special show_alternative parameter.

If you rely on ETH or LTC data in your integration and you use the show_alternative parameter to fetch them, then you will still receive them after this change, exactly as before and will not need to change your integration.

However, if you use the (now deprecated) only_alternative parameter to fetch LTC or ETH, you will need to modify your integration to use show_alternative instead. After this change, only_alternative will no longer return ETH or LTC. We will send an email update ahead of this change, giving you ample time to make any needed adjustments.

Please contact us if you need any help or support making this change, or have any questions about these rates.

Request Latest 'Alternative' Rates
Append the show_alternative query parameter to your latest.json, historical/ or time-series.json API request, like so:

HTTP
jQuery

https://openexchangerates.org/api/latest.json
    ?app_id=YOUR_APP_ID
    &show_alternative=1
The response format is exactly the same as the standard API response, with additional rates added to the rates object:

JSON

{
    disclaimer: "https://openexchangerates.org/terms/",
    license: "https://openexchangerates.org/license/",
    "timestamp": 1500652863,
    "base": "USD",
    "rates": {
        "AED": 3.673018,
        /* ... */
        "VEF_BLKMKT": 8570.78,
        "VEF_DICOM": 2700,
        "VEF_DIPRO": 10,
        /* ... */
    }
}
Each new symbol can be set as the base currency, if available in the returned data set (default is USD).

NB: Only currencies in the returned list can be set as a base currency.

📘
Alternative rate symbols are not limited to standard international 3-letter ISO codes, and may have fewer or more characters (such as "VEF_BLKMKT").
You can find a full list via the currencies.json route (see below), and our list of supported currencies.

Get List Of Available Alternative Currencies
The show_alternative query option can be added to requests for our currencies.json endpoint, to include alternative rate symbols and full currency names:

HTTP

https://openexchangerates.org/api/currencies.json
    ?show_alternative=1
You can view a list of supported alternative rates here.

Basic Code Samples
jQuery
Others...

$.get('https://openexchangerates.org/api/latest.json', {app_id: 'YOUR_APP_ID', show_alternative: 1}, function(data) {
    console.log("1 USD equals " + data.rates.DOGE + " DogeCoin.");
    console.log("1 USD equals " + data.rates.VEF_BLKMKT + " Venezuelan Bolivar on the black market.");
});
Combining Parameters
Alternative, digital and black-market rates can be combined with all other parameters on the latest.json endpoint:

HTTP

https://openexchangerates.org/api/latest.json
    ?app_id=YOUR_APP_ID
    &base=LTC
    &show_alternative=1
    &symbols=ETH,VEF,VEF_DIPRO,VEF_DICOM,VEF_BLKMKT,BTC,NEM
    &prettyprint=0
Changes to Alternative rates parameter
🚧
What happened to "experimental" rates?
On 31st January 2017, we updated the terminology for this API option from show_experimental to show_alternative, to reflect the fact that these currencies are not generally considered to be experimental. The parameter functions exactly as before.

Although we will continue to support the legacy 'experimental' syntax, we strongly recommend updating your integration to the new parameter name.

🚧
What happened to the "only_alternative" / "only_experimental" parameter?
The only_alternative (and previously, only_experimental) parameters enabled you to see only alternative rates in your API response (excluding the standard list).

This parameter has been deprecated as of July 2017, and may be removed in future. We strongly advise using show_alternative instead, and using the symbols parameter to limit the returned results.

Updated 3 months ago

Bid-Ask Prices ('show_bid_ask')
Minified Response ('prettyprint')

Minified Response ('prettyprint')
All of our API requests can be minified or pretty-printed.

By default, the Open Exchange Rates API returns responses in human-readable (pretty-printed) format, with indentation and line breaks.

If you prefer to minify your API responses to save bandwidth, you can use the prettyprint query parameter with any valid API route.

The data will be returned with unnecessary whitespace removed, but otherwise exactly the same.

Example Query
Request:

HTTP
jQuery

https://openexchangerates.org/api/latest.json
    ?app_id=[[app:app_id]]
    &prettyprint=0
Response:

JSON

{"disclaimer": "[...]","license":"[...]","timestamp":1453623028,"base":"USD","rates":{"AED":3.67289,"AFN":68.580001,"ALL":127.189901,/* ... */}}
📘
Please note: because the default value is 1 ('true'), you need to specify prettyprint=0 to receive minified responses.
Updated 3 months ago

Alternative Rates ('show_alternative')
JSONP Requests ('callback')

JSONP Requests ('callback')
All of our API routes/endpoints support JSONP callbacks.

If you need to receive your API response wrapped inside a JSONP callback function, you can use the callback query parameter with any valid API route. The data will be returned wrapped in the callback function you specify.

The callback value can be any valid JavaScript method name.

The entire JSON API response will be delivered wrapped in the requested callback function.

Example JSONP Query
Request URL:

HTTP

https://openexchangerates.org/api/latest.json
    ?app_id=[[app:app_id]]
    &callback=myCallbackFunction
JSONP Response:

JSON

myCallbackFunction({
    "disclaimer": "[...]",
    "license": "[...]",
    "timestamp": 1346874992,
    "base": "USD",
    "rates": {
        /* ... */
    }
})
Updated 3 months ago

Minified Response ('prettyprint')
ETags / Cache Control
Did this page help you?
Table of Contents

ETags / Cache Control
ETags (“If-None-Match”) provide a simple method of saving bandwidth, by checking whether the rates have been updated since your last request.

If the data have not changed since your previous request, you can fallback to a cached copy, and your API response will be under 0.2kb (instead of the standard 2–4kb response). If the rates have changed, you'll receive the latest data as usual.

It's easier than it sounds - here's a step-by-step guide to prove it!

From the article on Etags:

An ETag ["Entity Tag"] is an opaque identifier assigned by a web server to a specific version of a resource found at a URL. If the resource content at that URL ever changes, a new and different ETag is assigned. Used in this manner ETags are similar to fingerprints, and they can be quickly compared to determine if two versions of a resource are the same or not.
-- Wikipedia

1. Store the latest API response
Each time you make a request to the Open Exchange Rates API, the HTTP response headers will include an ETag and a Date.

The Etag is a unique identifier for the data returned in the response, and the Date is the time at which the data was last modified.

Example:


Date: Thu, 20 Dec 2012 14:48:28 GMT
ETag: "4e6acdd9fea30c21d9bdf1925afbf846"
After receiving your API response, cache the entire response somewhere (e.g. to a file, database or in memory), along with the values for the ETag and Date headers.

2. Add the "If-None-Match" header
Next time you make a request to the same API URL, add the If-None-Match header, with the value set to the ETag you grabbed from the previous request, wrapped in double quotation '"' marks.

You also need to send an If-Modified-Since header, which will be the Date value from the last successful request.

Using the example above, your two request headers would look like this:


If-None-Match: "4e6acdd9fea30c21d9bdf1925afbf846"
If-Modified-Since: Thu, 20 Dec 2012 14:48:28 GMT
3. If not modified, use cached data
If the data have not been updated since your last request, the response status code will be 304 – Not Modified, and no data will be returned.

You can now safely use the cached API response from your previous successful request.

4. If updated, cache the new response
If the rates have changed since your last request, the latest data will returned as usual, along with new ETag and `Date`` headers.

Repeat Step 1.

📘
Please note: Although ETags help reduce bandwidth for your users, all API requests still count towards your monthly request allowance – even if the data has not changed since your last request.
Further Examples
This post on the Facebook Developers blog contains a solid run-down of ETags.
Updated 3 months ago

JSONP Requests ('callback')
API Libraries & Extensions
Did this page help you?

