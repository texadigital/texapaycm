Introduction
How to start?
When creating an account with pawaPay, you will first receive access to our sandbox environment. The sandbox environment is completely isolated from our production environment.
You can safely test your integration with pawaPay without real mobile money wallets and real money involved immediately after creating your pawaPay account.
Access to your production account, where live payments can be made, will be granted after completing the onboarding on your sandbox account.
Now that you have an account in our sandbox, let’s look at how to start integrating.
​
Where is the API?
The base URL for the pawaPay Merchant API is different between our sandbox and production environments.
Environment	Base URL
The specific operation can be called by appending the endpoint to the base URL.
The base URLs are different between sandbox and production accounts. Please store them in your application’s environment-specific configuration.
​
Where is the pawaPay Dashboard?
From the pawaPay Dashboard you can:
Set up callback URLs
Generate an API token
See all accepted payments
See financial statements
Invite users
Control access levels
And much more
As our sandbox environment is completely isolated from our production environment, the URLs to access the pawaPay dashboard are different.
Environment	Dashboard URL
​
How to authenticate calls to the Merchant API
The pawaPay Merchant API uses a bearer token for authentication. An authorization header is required for all calls to the pawaPay Merchant API.
The token can be generated from the pawaPay Dashboard. Instructions on how to do that can be found in the pawaPay Dashboard Docs.
Below is an example payout request with curl:

Copy

Ask AI
curl -i -X POST \
-H 'Authorization: Bearer <YOUR_API_TOKEN>' \
-H 'Content-Type: application/json' \
-d '{
"payoutId": "33f30946-881d-40bc-8ca2-94aa4cd467ac",
"amount": "15",
"currency": "ZMW",
"recipient": {
    "type": "MMO",
    "accountDetails": {
        "phoneNumber": "260763456789",
        "provider": "MTN_MOMO_ZMB"
        }
    }
}'
The API tokens are different between sandbox and production accounts.
When moving from sandbox to production a new API token must be generated from the production pawaPay Dashboard.
Please store the API token in your application’s environment specific secure storage.
If you are looking to add a second layer of security, you can implement Signatures to ensure security even if your API token should leak.
​
Setting up callbacks
As the pawaPay API is asynchronous, we recommend implementing a callback handler in your application to find out about the final status of your payments as soon as they are processed.
You can read more about how to do that from the pawaPay Dashboard docs.
When configured, we will send you a callback with the final status of a payment to your configured callback URL. Read more about callbacks.
​
Starting to build the integration
Now you are all set to integrate with the pawaPay Merchant API.
Play in Postman
Test and get a feel for the API using our Postman collection.
Deposits
Find out how to integrate to the API to get paid.
Payouts
Find out how to integrate to send money.
Refunds
Find out how to handle refunds.
Payment page
Find out how to get paid, with an out-of-the-box payment experience.
​
Testing the integration
You can test the pawaPay Merchant API and Dashboard with your sandbox account right after signing up. You do not need to go through the onboarding process for that since no real money is involved.
There are special phone numbers available to simulate successful and different failure cases.
It is not possible to see the authorization flows (i.e. entering the PIN prompt) in sandbox.
Here’s a few helpful resources to get everything you need to start integrating and testing.
Providers
Find all the different providers currently available on pawaPay.
Test phone numbers
Here’s all the test numbers you can use on your sandbox account to test different payment scenarios.
Failure codes
Here’s a summary of all the different payment failure codes you might encounter. They are all of course documented in the API reference as well.
Brand guidelines
Find the brand guidelines on how to use provider logos.

Introduction
Going live
​
Overview
When you are ready to move from the sandbox environment to production, make sure you use the correct base URL and the correct authentication token. These are the only parameters that differ between sandbox and production.
We recommend storing those parameters in your environment-specific configuration.
If you use IP whitelisting, ensure that our IP addresses are whitelisted so we can deliver callbacks to you.
​
Go-live testing
We recommend implementing a feature flag for your integration with pawaPay to enable go-live testing in production, before market launch. This allows you to test the end-to-end flow in production to uncover any environment-specific problems before they impact customers.
To test live payments, you will need the following for each MMO that you use:
A phone and phone number with the specific MMO.
An active mobile money wallet on that phone number.
An available balance on the mobile money wallet to conduct the tests with.
Be ready to enter the PIN code on the phone approximately 1-20 seconds from initiating the payment through our API.

Guides
Deposits
Deposits allow you to request payment from a customer. Funds will be moved from the customer’s mobile money wallet to your account in pawaPay.
In this guide, we’ll go through step by step on how to approach building a high quality deposit flow.
If you haven’t already, check out the following information to set you up for success with this guide.
What you should know
Understand some consideration to take into account when working with mobile money.
How to start
Sort out API tokens and callbacks.
​
Initiating a deposit
Let’s start by hard-coding everything. Throughout this guide we will be making the flow more dynamic and improving the customer experience incrementally.
1
Initiate the deposit

Let’s send this payload to the initiate deposit endpoint.

Copy

Ask AI

    {
        "depositId": "afb57b93-7849-49aa-babb-4c3ccbfe3d79",
        "amount": "100",
        "currency": "RWF",
        "payer": {
            "type": "MMO",
            "accountDetails": {
                "phoneNumber": "250783456789",
                "provider": "MTN_MOMO_RWA"
            }
        }
    }
Let’s see what’s in this payload.
We ask you to generate a UUIDv4 depositId to uniquely identify this deposit. This is so that you always have a reference to the deposit you are initiating, even if you do not receive a response from us due to network errors. This allows you to always reconcile all payments between your system and pawaPay. You should store this depositId in your system before initiating the deposit with pawaPay.
The amount and currency should be relatively self-explanatory. This is the amount that will be collected from the customer. Any fees will be deducted from that amount after the collection has completed.
Then the provider and phoneNumber specify exactly who is paying. Mobile money wallets are identified by the phone number, like bank accounts are by their account number. The provider specifies which mobile money operator this wallet is registered at. For example MTN Ghana or MPesa Kenya.
You will receive a response for the initiation.

Copy

Ask AI
    {
        "depositId": "afb57b93-7849-49aa-babb-4c3ccbfe3d79",
        "status": "ACCEPTED",
        "nextStep": "FINAL_STATUS",
        "created": "2025-05-15T07:38:56Z"
    }
The status shows whether this deposit was ACCEPTED for processing or not. We will go through failure modes later in the guide.
We will also take a look later how nextStep can be used, but for now it’s safe to ignore it.
2
The customer will approve the payment

Now that the deposit has been initiated, the customer will receive a PIN prompt on their phone to authorise the payment.


This step only happens when initiating live payments on your production account.
On your sandbox account, this step is skipped!
3
Get the final status of the payment

Now that the customer has approved the payment, you will receive a callback from us to your configured callback URL.

Copy

Ask AI
    {
        "depositId": "afb57b93-7849-49aa-babb-4c3ccbfe3d79",
        "status": "COMPLETED",
        "amount": "100.00",
        "currency": "RWF",
        "country": "RWA",
        "payer": {
            "type": "MMO",
            "accountDetails": {
                "phoneNumber": "250783456789",
                "provider": "MTN_MOMO_RWA"
            }
        },
        "customerMessage": "DEMO",
        "created": "2025-05-15T07:38:56Z",
        "providerTransactionId": "df0e9405-fb17-42c2-a264-440c239f67ed"
    }
The main thing to focus on here is the status which is COMPLETED indicating that the deposit has been processed successfully and the funds have been collected to your wallet on your pawaPay account.
If you have not configured callbacks, you can always poll the check deposit status endpoint for the final status of the payment.
4
And done!

Congratulations! You have now made your very first deposit with pawaPay. We made a call to pawaPay to initiate the deposit. And we found out the final status of that payment.
But there’s a little more to a high quality integration. In the next sections, we will go into more details on:
How to make this easy to expand to new markets and providers
How to handle failures so that discrepancies between your system and pawaPay don’t happen
How to cover countries with providers that use different authorisations methods for payments
And more…
​
Asking for payment details from the customer
In the first part we harcoded everything. Now let’s take a look at how to ask that information from the customer who is paying.
We are focusing on the use case where you know the amount to be paid already. Later in the guide, we will also cover the case where the customer can choose the amount to pay.
Some providers do not support decimals in amount, so amounts like “100.50” might not be possible.
You can find how providers support decimals in the amount in the Providers section.
1
Showing the providers

The active configuration endpoint has useful and important information including which providers and markets have been configured on your pawaPay account.
In most cases you already know from which country the customer is from. And since we are implementing deposits, we can fetch the configuration for deposits only.

Copy

Ask AI

    {
        "companyName": "Demo",
        "countries": [
            {
                "country": "RWA",
                "prefix": "250",
                "displayName": {
                    "en": "Rwanda",
                    "fr": "Rwanda"
                },
                "providers": [
                    {
                        "provider": "AIRTEL_RWA",
                        "displayName": "Airtel",
                        "currencies": [
                            {
                                "currency": "RWF",
                                "displayName": "R₣",
                                "operationTypes": ...
                            }
                        ]
                    },
                    {
                        "provider": "MTN_MOMO_RWA",
                        "displayName": "MTN",
                        "currencies": [
                            {
                                "currency": "RWF",
                                "displayName": "R₣",
                                "operationTypes": ...
                            }
                        ]
                    }
                ]
            }
        ]
        ...
    }
As you can see, in providers we have a list of all the providers that have been configured on your account for initiating deposits. The displayName contains the name of the provider. You can show those as options to your customer.
To enable new payment providers being automatically added to your deposit flows, we’ve also added the logo of the provider as the logo. This way it is possible to implement the deposit flow dynamically, so that when new providers are enabled on your pawaPay account, they become available for your customers without additional effort.
This might look something like this:


Do not have a default provider. For example, as the first selection in a dropdown. This often gets missed by customers causing payments to fail due to being sent to the wrong provider.
2
Ask for the phone number

In the active configuration endpoint there is already the prefix within the country object. This the country calling code for that country. We recommend showing that to the customer in front of the input box for entering the phone number. This makes it clear that the number they enter should not include the country code.
We have also added flag to the country object to make it look a little nicer.
This might look something like this:


3
Now let's validate the number and predict the provider

To make sure the number entered by the customer is a valid phone number, let’s use the predict provider endpoint.
First, concatenate the prefix to what was entered by the customer as the phone number.
You can now send it to validation. We will handle basic things like whitespace, characters etc. We also make sure the number of digits is correct for the country and handle leading zeros.

Copy

Ask AI

    {
        "phoneNumber": "25007 834-56789a"
    }
If the phone number is not valid, a failure will be returned. You can show the customer a validation error. Otherwise, you will get the following response:

Copy

Ask AI
    {
        "country": "RWA",
        "provider": "MTN_MOMO_RWA",
        "phoneNumber": "250783456789"
    }
The phoneNumber is the sanitized MSISDN format of the phone number that you can use to initiate the deposit. We strongly recommend using this endpoint for validating numbers especially due to some countries not strictly following ITU E.164.
Also, in the response, you will find the provider. This is the predicted provider for this phoneNumber. We recommend preselecting the predicted provider for the customer. In most countries we see very high accuracy for predictions removing another step from the payment experience.
We recommend allowing the customer to override the prediction as the accuracy is not 100%.
You should now have a payment page similar to this:


4
Initiate the deposit

Now all information is either coming from the active configuration endpoint, the customer and your system (the amount to pay). We can call the initiate deposit endpoint with that information.

Copy

Ask AI

    {
        "depositId": "afb57b93-7849-49aa-babb-4c3ccbfe3d79",
        "amount": "100",
        "currency": "RWF",
        "payer": {
            "type": "MMO",
            "accountDetails": {
                "phoneNumber": "250783456789",
                "provider": "MTN_MOMO_RWA"
            }
        }
    }
The response and callback with the final status are the same as in the first part of the guide.
Please keep in mind that not all providers support amounts with decimals. You can find which providers do and which ones don’t from the Providers section.
5
Done again!

We now made a real deposit flow collecting information from different sources and avoiding any hard-coded data. This way, enabling new providers will not cause additional changes for you.
Next, let’s take a look at what to do while we are waiting for the customer to authorise the payment.
​
After initiating the deposit
After you initiate a deposit, the customer will need to authorise it. While there are a couple of different ways providers handle payment authorisation, the most common one involves a PIN prompt popping up on the customers phone. In this step, we will focus on that type of authorisation flow. Later in the guide, we will cover the rest as well.
1
Finding the payment authorisation type

We already used the active configuration endpoint to find the providers configured on your pawaPay account. The type of authorisation used by the provider is also available from that endpoint.

Copy

Ask AI

    {
        "companyName": "Demo",
        "countries": [
            {
                ...
                "providers": [
                    {
                        "provider": "ORANGE_SLE",
                        "displayName": "Airtel",
                        "nameDisplayedToCustomer": "SL LIMITED",
                        "currencies": [
                            {
                                "currency": "SLE",
                                "displayName": "Le",
                                "operationTypes": {
                                    "DEPOSIT": {
                                        ...
                                        "authType": "PROVIDER_AUTH",
                                        "pinPrompt": "MANUAL",
                                        "pinPromptRevivable": true
                                        ...
                                        }
                                    ]
                    }
                ]
            }
        ]
        ...
    }
There are three properties here that we will focus on.
The authType shows the type of authorisation that this provider uses. We are focusing on PROVIDER_AUTH currently. Other authTypes will be covered later in the guide.
The pinPrompt property is only applicable for providers with authType of PROVIDER_AUTH. It indicates whether or not the PIN prompt that is part of the authorisation will pop up on the customers phone automatically (AUTOMATIC) or if they need to take some action for that (MANUAL).
The pinPromptRevivable shows whether it’s possible for the customer to get the PIN prompt to pop up again in case it fails on the first attempt.
Now that we know the specifics of the authorisation flow of the provider, let’s implement the screen to show the customer after having initiated the deposit.
2
Customer experience for AUTOMATIC `pinPrompt`

For pinPrompt value of AUTOMATIC we can show the customer a screen indicating that they should authorise the payment by entering their mobile money PIN on their phone.
On the PIN prompt, there is usually a reference to the payment provider (pawaPay or one of its local subsidiaries). You should show that to the customer as part of the message to assure that it’s a valid payment authorisation request. You can find it from the response as nameDisplayedToCustomer.

Copy

Ask AI

    {
        "companyName": "Demo",
        "countries": [
            {
                ...
                "providers": [
                    {
                        "provider": "ORANGE_SLE",
                        "displayName": "Airtel",
                        "nameDisplayedToCustomer": "SL LIMITED",
                        ...
                    }
                ]
            }
        ]
        ...
    }
Here’s an example of what you might show to the customer while waiting for the callback with the final status of this payment.


3
What if the PIN prompt is revivable?

If pinPromptRevivable is true for the provider, it means that the customer can revive the PIN prompt in case something happens on the first attempt. For example, this might happen when they can’t find their phone before the PIN prompt times out, they happen to get a call at the same time or there is a network issue delivering the PIN prompt.
In this case, we recommend waiting about 10-15 seconds after initiation and showing the customer instructions on how they can revive the PIN prompt to try again. The specific instructions are in the response from active configuration endpoint as ‘pinPromptInstructions’.

Copy

Ask AI
    ...
    "pinPromptInstructions": {
        "channels": [
            {
                "type": "USSD",
                "displayName": {
                    "en": "Did not get a PIN prompt?",
                    "fr": "N’avez-vous pas reçu la demande de code PIN?"
                },
                "instructions": {
                    "en": [
                        {
                            "text": "Dial *115#",
                            "template": "Dial {{shortCode}}",
                            "variables": {
                                "shortCode": "*115#"
                            }
                        },
                        {
                            "text": "Enter PIN",
                            "template": "Enter PIN",
                            "variables": {
                                "shortCode": "*115#"
                            }
                        }
                    ],
                    "fr": [
                        {
                            "text": "Composez *115#",
                            "template": "Composez {{shortCode}}",
                            "variables": {
                                "shortCode": "*115#"
                            }
                        },
                        {
                            "text": "Entrez le code PIN",
                            "template": "Entrez le code PIN",
                            "variables": {
                                "shortCode": "*115#"
                            }
                        }
                    ]
                }
            }
        ]
    },
    ...
The channel for reviving the PIN prompt indicates where the customer is able to revive the PIN prompt. This is predominantly USSD indicating they need to dial a USSD code on their phone to revive the PIN prompt.
The displayName includes a message on the intent of the instructions. You are free to use it or come up with your own message.
The quickLink (if available) includes the href that you can use to predial the USSD shortcode. You should use it as the href of an <a /> tag or button.
This way, when the customer is using the same phone to visit your site that they are paying with, they can press to predial the USSD code.
The instructions include the exact steps they should take to revive the PIN. You can iterate over them to show the instructions for reviving the PIN prompt. You can use the text of each instruction directly or if you want to emphasise key properties, you can use the template and variables. Just surrond the variables of an instruction with the emphasis you need and replace them in the template.
4
Customer experience for MANUAL `pinPrompt`

In case of pinPrompt value of MANUAL, the customer needs to take some action to get to the PIN prompt. While most customers know what to do and they will get an SMS with instructions anyway, we’ve seen improved success rates with clear instructions as part of payment experience.
You can get the instructions to show for this exactly the same way as for PIN prompt revival.

Copy

Ask AI
    "pinPromptInstructions": {
        "channels": [
            {
                "type": "USSD",
                "displayName": {
                    "en": "Follow the instructions to authorise the payment",
                    "fr": "Veuillez suivre les instructions afin d’autoriser le paiement."
                },
                "instructions": {
                    "en": [
                        {
                            "text": "Dial *555*6#",
                            "template": "Dial {{shortCode}}",
                            "variables": {
                                "shortCode": "*555*6#"
                            }
                        },
                        {
                            "text": "Enter PIN",
                            "template": "Enter PIN",
                            "variables": {
                                "shortCode": "*555*6#"
                            }
                        }
                    ],
                    "fr": [
                        {
                            "text": "Composez *555*6#",
                            "template": "Composez {{shortCode}}",
                            "variables": {
                                "shortCode": "*555*6#"
                            }
                        },
                        {
                            "text": "Entrez le code PIN",
                            "template": "Entrez le code PIN",
                            "variables": {
                                "shortCode": "*555*6#"
                            }
                        }
                    ]
                }
            }
        ]
    },
5
And done!

Now we have made sure that the customer always knows what they need to do to authorise a payment.
Next let’s take a look now how to handle failures.
​
Avoiding failures on initiation
Payment initiation can fail for various reasons. Let’s see how we can best handle those.
1
Handling provider downtime

Providers may have downtime. We monitor providers performance and availability 24/7. For your operational and support teams, we have a status page. From there they can subscribe to get updates in email or slack for all the providers they are interested in.
To avoid failed payment attempts, we’ve also exposed this information over API from both the provider availability and active configuration endpoints. This way you can be up front with the customer before they attempt to pay.

Copy

Ask AI

    {
        "companyName": "Demo",
        "countries": [
            {
                "country": "BEN",
                "displayName": {
                    "fr": "Bénin",
                    "en": "Benin"
                },
                "prefix": "229",
                "providers": [
                    {
                        "provider": "MOOV_BEN",
                        "nameDisplayedToCustomer": "PAWAPAY",
                        "currencies": [
                            {
                                "currency": "XOF",
                                "displayName": "CFA",
                                "operationTypes": {
                                    "DEPOSIT": {
                                        ...
                                        "status": "OPERATIONAL",
                                        ...
                                    }
                                }
                            }
                        ]
                    },
                    {
                        "provider": "MTN_MOMO_BEN",
                        "nameDisplayedToCustomer": "PAWAPAY",
                        "currencies": [
                            {
                                "currency": "XOF",
                                "displayName": "CFA",
                                "operationTypes": {
                                    "DEPOSIT": {
                                        ...
                                        "status": "CLOSED",
                                        ...
                                    }
                                }
                            }
                        ]
                    }
                ]
            }
        ]
        ...
    }
Based on whether the status of the specific provider is OPERATIONAL or CLOSED you can inform the customer up front that this provider is currently not available and they can attempt again later.
2
Handling decimals in amount and transaction limits

Every time you initiate a deposit you should confirm that the status in the response is ACCEPTED.
If the status is REJECTED the failureReason will contain both the failureCode and failureMessage indicating what has happened. Most of those failures are avoidable if handled beforehand.
The failureMessage from pawaPay API is meant for you and your support and operations teams. You are free to decide what message to show to the customer.

Copy

Ask AI
    //Incorrect amount of decimals in amount
    {
        "depositId": "f4401bd2-1568-4140-bf2d-eb77d2b2b639",
        "status": "REJECTED",
        "failureReason": {
            "failureCode": "INVALID_AMOUNT",
            "failureMessage": "The provider MOOV_BEN only supports up to '2' decimal places in amount."
        }
    }

    //Amount larger than transaction limits allow
    {
        "depositId": "f4401bd2-1568-4140-bf2d-eb77d2b2b639",
        "status": "REJECTED",
        "failureReason": {
            "failureCode": "AMOUNT_OUT_OF_BOUNDS",
            "failureMessage": "The amount needs to be more than '100' and less than '2000000' for provider 'MOOV_BEN'."
        }
    }

    //Invalid currency
    {
        "depositId": "f4401bd2-1568-4140-bf2d-eb77d2b2b639",
        "status": "REJECTED",
        "failureReason": {
            "failureCode": "INVALID_CURRENCY",
            "failureMessage": "The currency 'USD' is not supported with provider 'MOOV_BEN'. Please consult API docs for supported currencies."
        }
    }
In the above example, where the provider does not support decimal places in amount, you need to preempt that by rounding the amounts. Depending on your use case, you might do that preemptively by adjusting local pricing or dynamically before payment.
Second thing to consider is that customers mobile money wallets have limits on how big the payments can be. Customers are able to get those limits increased on their mobile money wallets by contacting their provider and going through extended KYC. By default, we have set the transaction limits on your pawaPay account based on the most common wallet limits.
Regarding currencies, the only country available through pawaPay that supports multiple currencies is DRC. These are exposed in the active configuration endpoint as an array of currencies.
You can access both the decimal places supported and transaction limits from the active configuration endpoint.

Copy

Ask AI

    {
        "companyName": "Demo",
        "countries": [
            {
                "country": "BEN",
                "displayName": {
                    "fr": "Bénin",
                    "en": "Benin"
                },
                "prefix": "229",
                "providers": [
                    {
                        "provider": "MOOV_BEN",
                        "nameDisplayedToCustomer": "PAWAPAY",
                        "currencies": [
                            {
                                "currency": "XOF",
                                "displayName": "CFA",
                                "operationTypes": {
                                    "DEPOSIT": {
                                        ...
                                        "decimalsInAmount": "NONE",
                                        "minAmount": "100",
                                        "maxAmount": "2000000",
                                        ...
                                    }
                                }
                            }
                        ]
                    },
                    {
                        "provider": "MTN_MOMO_BEN",
                        "nameDisplayedToCustomer": "PAWAPAY",
                        "currencies": [
                            {
                                "currency": "XOF",
                                "displayName": "CFA",
                                "operationTypes": {
                                    "DEPOSIT": {
                                        ...
                                        "decimalsInAmount": "NONE",
                                        "minAmount": "100",
                                        "maxAmount": "2000000",
                                        ...
                                    }
                                }
                            }
                        ]
                    }
                ]
            }
        ]
        ...
    }
You can use those to ensure inputs to initiate deposit are cleaned before initiation and rejections don’t happen.
3
Ensure the right phone number format

The deposit endpoint expects the phoneNumber in the MSISDN format.
You can use the predict provider endpoint to clean up the input you receive from the customer. Make sure the input starts with the country code.

Copy

Ask AI

    {
        "phoneNumber": "25007 834-56789a"
    }
If the phone number is not valid, a failure will be returned. You can show the customer a validation error.

Copy

Ask AI
    {
        "country": "RWA",
        "provider": "MTN_MOMO_RWA",
        "phoneNumber": "250783456789"
    }
The phoneNumber in the response will be in the correct format to initiate the deposit.
4
One initiation per depositId

All payments initiated with pawaPay are idempotent based on their ID. You must always generate a new UUIDv4 for the depositId. When you attempt to use the same depositId more than once the payment will be rejected.

Copy

Ask AI
    {
        "depositId": "f4401bd2-1568-4140-bf2d-eb77d2b2b639",
        "status": "DUPLICATE_IGNORED"
    }
5
Handling provider availability

We already discussed how to show to the customer which payment methods are currently not available due to provider downtime. There is usually some time between when you fetch and show this information to the customer and when they initiate the payment. We still need to ensure accurate information is shown in case the provider goes down during that time.
If a payment is initiated during provider downtime, it will be rejected.

Copy

Ask AI
    {
        "depositId": "f4401bd2-1568-4140-bf2d-eb77d2b2b639",
        "status": "REJECTED",
        "failureReason": {
            "failureCode": "PROVIDER_TEMPORARILY_UNAVAILABLE",
            "failureMessage": "The provider 'MTN_MOMO_BEN' is currently not able to process payments. Please consult our status page for downtime information on all providers. Programmatic access is also available, please consult our API docs."
        }
    }
Many customers will have mobile money wallets with different providers. It’s reasonable to ask them to pay with another provider or to attempt again later.
6
Handling HTTP 500 with failureCode UNKNOWN_ERROR

The UNKNOWN_ERROR failureCode indicates that something unexpected has gone wrong when processing the payment. There is no status in the response in this case.
It is not safe to assume the initiation has failed for this deposit. You should verify the status of the deposit using the check deposit status endpoint. Only if the deposit is NOT_FOUND should it be considered FAILED.
We will take a look later in the guide, how to ensure consistency of payment statuses between your system and pawaPay.
7
And done!

We have now handled different failures that might happen during initiation.
Next, let’s take a look at payment failures that can happen during processing.
​
Handling processing failures
1
Handling failures during processing

As the pawaPay API is asynchronous, you will get a deposit callback with the final status of the deposit. If the status of the deposit is FAILED you can find further information about the failure from failureReason. It includes the failureCode and the failureMessage indicating what has gone wrong.
The failureMessage from pawaPay API is meant for you and your support and operations teams. You are free to decide what message to show to the customer.
Find all the failure codes and implement handling as you choose.
Operation specific processing failures are also documented in the API reference:
Deposit callback
Payout callback
Refund callback
We recommend allowing easy retries for customers by taking the customer back to the payment information collection screen and showing the failure reason on that page. This way they can quickly try again.
We have standardised the numerous different failure codes and scenarios with all the different providers.
The specificity of the failure codes varies by provider. The UNSPECIFIED_FAILURE code indicates that the provider indicated a failure with the payment, but did not provide any more specifics on the reason of the failure.
In case there is a general failure, the UNKNOWN_ERROR failureCode will be returned.
2
And done!

We have now also taken care of failures that can happen during payment processing. This way the customer knows what has happened and can take appropriate action to try again.
Now let’s take a look at some markets where the payment authorisation flow is a little different than usual.
​
Ensuring consistency
When working with financial APIs there are some considerations to take to ensure that you never think a payment is failed, when it is actually successful or vice versa. It is essential to keep systems in sync on the statuses of payments.
Let’s take a look at some considerations and pseudocode to ensure consistency.
1
Defensive status handling

All statuses should be checked defensively without assumptions.

Copy

Ask AI
    if( status == "COMPLETED" ) {
        myInvoice.setPaymentStatus(COMPLETED);
    } else if ( status == "FAILED" ) {
        myInvoice.setPaymentStatus(FAILED);
    } else if  ( status == "PROCESSING") {
        handleRedirectionAuth();
    } else {
        //It is unclear what might have failed. Escalate for further investigation.
        myInvoice.setPaymentStatus(NEEDS_ATTENTION);
    }
2
Handling network errors and system crashes

The key reason we require you to provide a depositId for each payment is to ensure that you can always ask us what is the status of a payment, even if you never get a response from us.
You should always store this depositId in your system before initiating a deposit.

Copy

Ask AI
    var depositId = new UUIDv4();

    //Let's store the depositId we will use to ensure we always have it available even if something dramatic happens
    myInvoice.setExternalPaymentId(depositId).save();
    myInvoice.setPaymentStatus(PENDING);

    try {
        var initiationResponse = pawaPay.initiateDeposit(depositId, ...)
    } catch (InterruptedException e) {
        var checkResult = pawaPay.checkDepositStatus(depositId);

        if ( result.status == "FOUND" ) {
            //The payment reached pawaPay. Check the status of it from the response.
        } else if ( result.status == "NOT_FOUND" ) {
            //The payment did not reach pawaPay. Safe to mark it as failed.
            myInvoice.setPaymentStatus(FAILED);
        } else {
            //Unable to determine the status. Leave the payment as pending.
            //We will create a status recheck cycle later for such cases.

            //In case of a system crash, we should also leave the payment in pending status to be handled in the status recheck cycle.
        }
    }
The important thing to notice here is that we only mark a payment as FAILED when there is a clear indication of it’s failure. We use the check deposit status endpoint when in doubt whether the payment was ACCEPTED by pawaPay.
3
Implementing an automated reconciliation cycle

Implementing the considerations listed above avoids almost all discrepancies of payment statuses between your system and pawaPay. When using callbacks to receive the final statuses of payments, issues like network connectivity, system downtime, and configuration errors might cause the callback not to be received by your system. To avoid keeping your customers waiting, we strongly recommend implementing a status recheck cycle.
This might look something like the following.

Copy

Ask AI
    //Run the job every few minutes.

    var pendingInvoices = invoices.getAllPendingForLongerThan15Minutes();

    for ( invoice in pendingInvoices ) {
        var checkResult = pawaPay.checkDepositStatus(invoice.getExternalPaymentId);

        if ( checkResult.status == "FOUND" ) {
            //Determine if the payment is in a final status and handle accordingly
            handleInvoiceStatus(checkResult.data);
        } else if (checkResult.status == "NOT_FOUND" ) {
            //The payment has never reached pawaPay. Can be failed safely.
            invoice.setPaymentStatus(FAILED);
        } else {
            //Something must have gone wrong. Leave for next cycle.
        }
    }
Having followed the rest of the guide, with this simple reconciliation cycle, you should not have any inconsistencies between your system and pawaPay. Having these checks automated will take a load off your operations and support teams as well.
​
Other payment authorisation flows
Overwhelmingly providers use a PIN prompt to authorise payments. There are some markets that need support for alternative authorisation flows:
Burkina Faso
Ivory Coast
Senegal
If you are not looking to go live in these markets, you can skip this section of the guide.
In this case, we recommend filtering out all providers that do not have authType of PROVIDER_AUTH from your calls to active configuration.
Let’s implement support for these authorisation flows as well to ensure that all providers can be used.
​
Preauthorised flows
1
Let's add support for preauthorised payments

Currently, the only provider using preauthorised payments is Orange in Burkina Faso. Preauthorisation means that the customer needs to authorise the payment before initiation the payment.
For Orange in Burkina Faso the customer needs to generate an OTP through Oranges USSD menu. Let’s make sure they know how to do that by showing them step-by-step instructions for that. Let’s get those instructions from the active configuration endpoint.

Copy

Ask AI
    "authTokenInstructions": {
        "channels": [
            {
                "type": "USSD",
                "displayName": {
                    "en": "Preauthorization instructions",
                    "fr": "Instructions de préautorisation"
                },
                "instructions": {
                    "en": [
                        {
                            "text": "Dial *144*4*6#",
                            "template": "Dial {{shortCode}}",
                            "variables": {
                                "shortCode": "*144*4*6#"
                            }
                        },
                        {
                            "text": "Enter amount",
                            "template": "Enter amount",
                            "variables": {
                                "shortCode": "*144*4*6#"
                            }
                        },
                        {
                            "text": "Enter PIN",
                            "template": "Enter PIN",
                            "variables": {
                                "shortCode": "*144*4*6#"
                            }
                        },
                        {
                            "text": "Use OTP",
                            "template": "Use OTP",
                            "variables": {
                                "shortCode": "*144*4*6#"
                            }
                        }
                    ],
                    "fr": [
                        {
                            "text": "Composez *144*4*6#",
                            "template": "Composez {{shortCode}}",
                            "variables": {
                                "shortCode": "*144*4*6#"
                            }
                        },
                        {
                            "text": "Entrez le montant",
                            "template": "Entrez le montant",
                            "variables": {
                                "shortCode": "*144*4*6#"
                            }
                        },
                        {
                            "text": "Entrez le code PIN",
                            "template": "Entrez le code PIN",
                            "variables": {
                                "shortCode": "*144*4*6#"
                            }
                        },
                        {
                            "text": "Entrez le code à usage unique reçu par SMS",
                            "template": "Entrez le code à usage unique reçu par SMS",
                            "variables": {
                                "shortCode": "*144*4*6#"
                            }
                        }
                    ]
                }
            }
        ]
    },
The channel for reviving the PIN prompt indicates where the customer is able to generate the OTP. For Orange in Burkina Faso, this is USSD indicating they need to dial a USSD code on their phone to revive the PIN prompt.
The displayName includes a message on the intent of the instructions. You are free to use it or come up with your own message.
The quickLink includes the href that you can use to predial the USSD shortcode. You should use it as the href of an tag or button. This way, when the customer is using the same phone to visit your site that they are paying with, they can click to predial the USSD code.
The instructions include the exact steps they should take to generate the OTP. You can iterate over them to show the instructions to generate the OTP. You can use the text of each instruction directly or if you want to emphasise key properties, you can use the template and variables. Just surrond the variables of an instruction with the emphasis you need and replace them in the template.
You also need to provide the customer a place to provide the preauthentication OTP. The customer would then follow the instructions on their phone to generate the OTP.
2
Dial into the USSD menu of the provider



3
Enter their mobile money PIN to generate the OTP



4
Get the OTP



5
Provide it to you



6
You must then include this OTP as the preAuthorisationCode into the request to initiate deposit.

Copy

Ask AI
    {
        "depositId": "3bd57454-fc43-49ad-9949-54e03f173c85",
        "payer": {
            "type": "MMO",
            "accountDetails": {
                "phoneNumber": "22673394446",
                "provider": "ORANGE_BFA"
            }
        },
        "preAuthorisationCode": "367025",
        "amount": "100",
        "currency": "XOF"
    }
You can now just tell the customer that the payment is processing and wait for a callback from pawaPay with the final status of the payment.
7
And done!

We can now get paid by customers using Orange in Burkina Faso.
Now let’s look at redirection based authorisation flows.
​
Redirection based flows
Some providers use redirection based authorisation. This is slightly more involved as you will need to redirect the customer to a URL where they can authorise the payment. This is used by Wave in both Senegal and Ivory Coast.
Let’s look at how that might look like for a customer using Wave in Senegal.
1
Allow the customer to initiate the payment



2
Initiate deposit

You can now initiate the deposit. After the authorisation is completed, the customer will be forwarded to…
successfulUrl if the payment completed successfully.
failedUrl if the payment failed to complete.

Copy

Ask AI
    {
        "depositId": "3bd57454-fc43-49ad-9949-54e03f173c85",
        "amount": "99",
        "currency": "XOF",
        "successfulUrl": "https://merchant.com/successfulUrl",
        "failedUrl": "https://merchant.com/failedUrl",
        "payer": {
            "type": "MMO",
            "accountDetails": {
            "phoneNumber": "221773456789",
            "provider": "WAVE_SEN"
            }
        }
    }
You will receive a response for this with information on what to do next.

Copy

Ask AI
    {
        "depositId": "3bd57454-fc43-49ad-9949-54e03f173c85",
        "status": "ACCEPTED",
        "nextStep": "GET_AUTH_URL",
        "created": "2025-05-15T07:38:56Z"
    }
The nextStep property indicates that the next thing to do is to retrieve the authorizationUrl to forward the customer to.
3
Get the authorizationUrl

If you have configured callbacks, you will receive a callback with the authorizationUrl.

Copy

Ask AI
    {
        "depositId": "241cc3c4-74b8-4fbd-921d-340cf648e2a3",
        "status": "PROCESSING",
        "nextStep": "REDIRECT_TO_AUTH_URL",
        "amount": "99.00",
        "currency": "XOF",
        "country": "SEN",
        "payer": {
            "type": "MMO",
            "accountDetails": {
                "phoneNumber": "221773456789",
                "provider": "WAVE_SEN"
            }
        },
        "customerMessage": "DEMO",
        "created": "2025-05-15T07:38:56Z",
        "successfulUrl": "https://merchant.com/successfulUrl",
        "failedUrl": "https://merchant.com/failedUrl",
        "authorizationUrl": "https://provider.com/authorizationUrl"
    }
If you do not have callbacks configured, you can always poll check deposit status. The response will keep returning GET_AUTH_URL as the value of nextStep until the provider makes the authorizationUrl available. Then the response will include the authorizationUrl and the value of nextStep will have changed to REDIRECT_TO_AUTH_URL.

Copy

Ask AI
    {
        "depositId": "241cc3c4-74b8-4fbd-921d-340cf648e2a3",
        "status": "PROCESSING",
        "nextStep": "REDIRECT_TO_AUTH_URL",
        "amount": "99.00",
        "currency": "XOF",
        "country": "SEN",
        "payer": {
            "type": "MMO",
            "accountDetails": {
                "phoneNumber": "221773456789",
                "provider": "WAVE_SEN"
            }
        },
        "customerMessage": "DEMO",
        "created": "2025-05-15T07:38:56Z",
        "successfulUrl": "https://merchant.com/successfulUrl",
        "failedUrl": "https://merchant.com/failedUrl",
        "authorizationUrl": "https://provider.com/authorizationUrl"
    }
This is usually very fast, but since the provider needs to return it, polling should be implemented.
4
Forward the customer to authorise the payment

You can now use the authorizationUrl that you retrieved in the previous step to forward the customer to it. If they are on desktop, they will see a QR code that they can scan with their phone that has the Wave app installed.


5
The customer can then authorise the payment

If the customer is using your site from the phone that has Wave app installed, they will be immmediately redirected to the app to confirm the payment.


6
Once that's done...

…they will see a confirmation screen and then depending on the result of the payment, be forwarded back to either the successfulUrl or failedUrl.


7
Confirm the payment status

We will of course send you a callback with the final status of the payment, as usual.
We do recommend validating the status of the deposit on your successfulUrl or failedUrl from the check deposit status endpoint.
8
And done!

You have now built the payment experience allowing customers to pay you regardless of which provider they are using.
​
Customer chooses the amount
For some use cases, the customer should decide how much they are paying.
Let’s take a look at how to support that.
1
Decimals support

Not all providers support decimals in amount - amounts like 100.50 will fail to process. This information is available for each provider in active configuration endpoint.

Copy

Ask AI

    {
        "companyName": "Demo",
        "countries": [
            {
                "country": "BEN",
                "displayName": {
                    "fr": "Bénin",
                    "en": "Benin"
                },
                "prefix": "229",
                "providers": [
                    {
                        "provider": "MOOV_BEN",
                        "nameDisplayedToCustomer": "PAWAPAY",
                        "currencies": [
                            {
                                "currency": "XOF",
                                "displayName": "CFA",
                                "operationTypes": {
                                    "DEPOSIT": {
                                        ...
                                        "decimalsInAmount": "NONE",
                                        ...
                                    }
                                }
                            }
                        ]
                    },
                    {
                        "provider": "MTN_MOMO_BEN",
                        "nameDisplayedToCustomer": "PAWAPAY",
                        "currencies": [
                            {
                                "currency": "XOF",
                                "displayName": "CFA",
                                "operationTypes": {
                                    "DEPOSIT": {
                                        ...
                                        "decimalsInAmount": "NONE",
                                        ...
                                    }
                                }
                            }
                        ]
                    }
                ]
            }
        ]
        ...
    }
The possible values are NONE and TWO_PLACES. It’s easy to provide dynamic validation now that is appropriate for the provider.
2
Transaction limits

Customers’ mobile money wallets have limits on how big the payments can be. Customers are able to get those limits increased on their mobile money wallets by contacting their provider and going through extended KYC. By default, we have set the transaction limits on your pawaPay account based on the most common wallet limits.
The limits for the provider will be available from the active configuration endpoint.

Copy

Ask AI

    {
        "companyName": "Demo",
        "countries": [
            {
                "country": "BEN",
                "displayName": {
                    "fr": "Bénin",
                    "en": "Benin"
                },
                "prefix": "229",
                "providers": [
                    {
                        "provider": "MOOV_BEN",
                        "nameDisplayedToCustomer": "PAWAPAY",
                        "currencies": [
                            {
                                "currency": "XOF",
                                "displayName": "CFA",
                                "operationTypes": {
                                    "DEPOSIT": {
                                        ...
                                        "minAmount": "100",
                                        "maxAmount": "2000000",
                                        ...
                                    }
                                }
                            }
                        ]
                    },
                    {
                        "provider": "MTN_MOMO_BEN",
                        "nameDisplayedToCustomer": "PAWAPAY",
                        "currencies": [
                            {
                                "currency": "XOF",
                                "displayName": "CFA",
                                "operationTypes": {
                                    "DEPOSIT": {
                                        ...
                                        "minAmount": "100",
                                        "maxAmount": "2000000",
                                        ...
                                    }
                                }
                            }
                        ]
                    }
                ]
            }
        ]
        ...
    }
It’s easy to provide dynamic validation ensuring the amount is between minAmount and maxAmount.
3
And done!

Now customers can choose the amount to pay without making multiple attempts.
​
Payments in reconciliation
When using pawaPay, you might find that a payment status is IN_RECONCILIATION. This means that there was a problem determining the correct final status of a payment. When using pawaPay all payments are reconciled by default and automatically - we validate all final statuses to ensure there are no discrepancies.
When encountering payments that are IN_RECONCILIATION you do not need to take any action. The payment has already been sent to our automatic reconciliation engine and it’s final status will be determined soon. The reconciliation time varies by provider. Payments that turn out to be successful are reconciled faster.
​
What to do next?
We’ve made everything easy to test in our sandbox environment before going live.
Test different failure scenarios
We have different phone numbers that you can use to test various failure scenarios on your sandbox account.
Review failure codes
Make sure all the failure codes are handled.
Add another layer of security
To ensure your funds are safe even if your API token should leak, you can always implement signatures for financial calls to add another layer of security.
And when you are ready to go live
Have a look at what to consider to make sure everything goes well.
Was this page helpful?


Yes

No
Postman
Payouts
website
discord
linkedin

Guides
Payouts
Payouts allow you to send money to a customer. Funds will be moved from your country wallet in pawaPay to the customers mobile money wallet.
In this guide, we’ll walk through how to build a high-quality payout flow step by step.
If you haven’t already, check out the following information to set you up for success with this guide.
What you should know
Understand some considerations to take into account when working with mobile money.
How to start
Sort out API tokens and callbacks.
​
Initiating a payout
Let’s start by hardcoding everything. Throughout this guide we will be taking care of more and more details to make sure everything is accurate.
1
Initiate the payout

Let’s send this payload to the initiate payout endpoint.

Copy

Ask AI

    {
        "payoutId": "afb57b93-7849-49aa-babb-4c3ccbfe3d79",
        "amount": "100",
        "currency": "RWF",
        "recipient": {
            "type": "MMO",
            "accountDetails": {
                "phoneNumber": "250783456789",
                "provider": "MTN_MOMO_RWA"
            }
        }
    }
Let’s see what’s in this payload.
We ask you to generate a UUIDv4 payoutId to uniquely identify this payout. This is so that you always have a reference to the payout you are initiating, even if you do not receive a response from us due to network errors. This allows you to always reconcile all payments between your system and pawaPay. You should store this payoutId in your system before initiating the payout with pawaPay.
The amount and currency should be relatively self-explanatory. This is the amount that will be disbursed to the customer. Any fees will be deducted from your pawaPay wallet balance on successful completion of the payout. Principal amount (the amount specified in amount) will be reserved from your pawaPay wallet. If the payout fails, the funds will be returned to your pawaPay wallet immediately.
Then the provider and phoneNumber specify who is paying exactly. Mobile money wallets are identified by the phone number, like bank accounts are by their IBAN. The provider specifies which mobile money operator this wallet is registered at. For example MTN Ghana or MPesa Kenya.
You will receive a response for the initiation.

Copy

Ask AI
    {
        "payoutId": "afb57b93-7849-49aa-babb-4c3ccbfe3d79",
        "status": "ACCEPTED",
        "created": "2025-05-15T07:38:56Z"
    }
The status shows whether this payout was ACCEPTED for processing or not. We will go through failure modes later in the guide.
2
The payout will be processed

Payouts do not require any authorisation or other action from the customer to receive it. The payout is usually processed within a few seconds. The customer will get an SMS receipt informing them that they have received a payout.
3
Get the final status of the payment

You will receive a callback from us to your configured callback URL.

Copy

Ask AI
    {
        "payoutId": "afb57b93-7849-49aa-babb-4c3ccbfe3d79",
        "status": "COMPLETED",
        "amount": "100.00",
        "currency": "RWF",
        "country": "RWA",
        "recipient": {
            "type": "MMO",
            "accountDetails": {
                "phoneNumber": "250783456789",
                "provider": "MTN_MOMO_RWA"
            }
        },
        "customerMessage": "DEMO",
        "created": "2025-05-15T07:38:56Z",
        "providerTransactionId": "df0e9405-fb17-42c2-a264-440c239f67ed"
    }
The main thing to focus on here is the status which is COMPLETED indicating that the payout has been processed successfully.
If you have not configured callbacks, you can always poll the check payout status endpoint for the final status of the payment.
4
And done!

Congratulations! You have now made your very first payout with pawaPay. We made a call to pawaPay to initiate the payout. And we found out the final status of that payment.
But there’s a little more to a high quality integration. In the next sections, we will go into more details on:
How to make this easy to maintain
How to handle failures so that discrepancies between your system and pawaPay don’t happen
And much more…
​
Getting the phone number from the customer
To make a payout to a customer, we need to have their valid phone number and need to know the provider they are using. We suggest validating that information when you are receiving that information whether that is during account signup or a separate process.
1
Asking for the phone number

In the active configuration endpoint there is already the prefix within the country object. This is the country calling code for that country. We recommend showing that in front of the input box for entering the phone number. This makes it clear that the number entered should not include the country code.
We have also added flag to the country object to make it look a little nicer.
The providers that have been configured on your pawaPay account for payouts are in the providers property. It includes both the provider code to use in calls to initiate a payout and the displayName for it. To enable new providers being automatically added to your payout flows, we’ve also added the logo of the provider as the logo. This way it is possible to implement the payout flow dynamically, so that when new providers are enabled on your pawaPay account, they become available for your customers without additional effort.

Copy

Ask AI

    {
        "companyName": "Demo",
        "countries": [
            {
                "country": "RWA",
                "prefix": "250",
                "displayName": {
                    "en": "Rwanda",
                    "fr": "Rwanda"
                },
                "providers": [
                    {
                        "provider": "AIRTEL_RWA",
                        "displayName": "Airtel",
                        "currencies": [
                            {
                                "currency": "RWF",
                                "displayName": "R₣",
                                "operationTypes": ...
                            }
                        ]
                    },
                    {
                        "provider": "MTN_MOMO_RWA",
                        "displayName": "MTN",
                        "currencies": [
                            {
                                "currency": "RWF",
                                "displayName": "R₣",
                                "operationTypes": ...
                            }
                        ]
                    }
                ]
            }
        ]
        ...
    }
You can use the above information to collect the phone number together with the provider.
Do not have a default provider. For example, as the first selection in a dropdown. This often gets missed by customers causing payments to fail due to being sent to the wrong provider.
2
Validating the phone number

To make sure the phone number is a valid phone number, let’s use the predict provider endpoint.
First, concatenate the prefix to what was entered as the phone number.
You can now send it to validation. We will handle basic things like whitespace, characters etc. We also make sure the number of digits is correct for the country and handle leading zeros.

Copy

Ask AI

    {
        "phoneNumber": "25007 834-56789a"
    }
If the phone number is not valid, a failure will be returned. You can show the customer a validation error. Otherwise, you will get the following response:

Copy

Ask AI
    {
        "country": "RWA",
        "provider": "MTN_MOMO_RWA",
        "phoneNumber": "250783456789"
    }
The phoneNumber is the sanitized MSISDN format of the phone number that you can use to initiate the payout. We strongly recommend using this endpoint for validating numbers especially due to some countries not strictly following ITU E.164.
Also, in the response, you will find the provider. This is the predicted provider for this phoneNumber. We recommend preselecting the predicted provider for the customer. In most countries we see very high accuracy for predictions removing another step from the payment experience.
We recommend making it possible to override the prediction as the accuracy is not 100%.
3
Initiate the payout

Now all information is either coming from the active configuration endpoint, the customer and your system (the amount to pay). We can call the initiate payout endpoint with that information.

Copy

Ask AI

    {
        "payoutId": "afb57b93-7849-49aa-babb-4c3ccbfe3d79",
        "amount": "100",
        "currency": "RWF",
        "payer": {
            "type": "MMO",
            "accountDetails": {
                "phoneNumber": "250783456789",
                "provider": "MTN_MOMO_RWA"
            }
        }
    }
The response and callback with the final status are the same as in the first part of the guide.
4
Done again!

We now made a real payout flow that collects information from different sources and avoids any hardcoded data. This way, enabling new providers will not cause additional changes for you.
Now let’s handle the amounts correctly.
​
Handling amounts
Depending on your use case, the amount will either be chosen by the customer or determined by you. As the support for decimals in amount differs by provider and transaction limits apply, let’s take a look at how to handle that.
1
Decimals support

Not all providers support decimals in amount - amounts like 100.50 will fail to process. This information is available for each provider in active configuration endpoint.

Copy

Ask AI

    {
        "companyName": "Demo",
        "countries": [
            {
                "country": "BEN",
                "displayName": {
                    "fr": "Bénin",
                    "en": "Benin"
                },
                "prefix": "229",
                "providers": [
                    {
                        "provider": "MOOV_BEN",
                        "nameDisplayedToCustomer": "PAWAPAY",
                        "currencies": [
                            {
                                "currency": "XOF",
                                "displayName": "CFA",
                                "operationTypes": {
                                    "PAYOUT": {
                                        ...
                                        "decimalsInAmount": "NONE",
                                        ...
                                    }
                                }
                            }
                        ]
                    },
                    {
                        "provider": "MTN_MOMO_BEN",
                        "nameDisplayedToCustomer": "PAWAPAY",
                        "currencies": [
                            {
                                "currency": "XOF",
                                "displayName": "CFA",
                                "operationTypes": {
                                    "PAYOUT": {
                                        ...
                                        "decimalsInAmount": "NONE",
                                        ...
                                    }
                                }
                            }
                        ]
                    }
                ]
            }
        ]
        ...
    }
The possible values are NONE and TWO_PLACES. It’s easy to provide dynamic validation now that is appropriate for the provider. If the amount is determined by your system, you can apply rounding rules based on the above.
If you are preparing the payout amounts beforehand and do not need to do it dynamically, this information is also documented in the providers section.
2
Transaction limits

Customers’ mobile money wallets have limits on how big the payments can be. Customers are able to get those limits increased on their mobile money wallets by contacting their provider and going through extended KYC. By default, we have set the transaction limits on your pawaPay account based on the most common wallet limits.
The limits for the provider will be available from the active configuration endpoint.

Copy

Ask AI

    {
        "companyName": "Demo",
        "countries": [
            {
                "country": "BEN",
                "displayName": {
                    "fr": "Bénin",
                    "en": "Benin"
                },
                "prefix": "229",
                "providers": [
                    {
                        "provider": "MOOV_BEN",
                        "nameDisplayedToCustomer": "PAWAPAY",
                        "currencies": [
                            {
                                "currency": "XOF",
                                "displayName": "CFA",
                                "operationTypes": {
                                    "PAYOUT": {
                                        ...
                                        "minAmount": "100",
                                        "maxAmount": "2000000",
                                        ...
                                    }
                                }
                            }
                        ]
                    },
                    {
                        "provider": "MTN_MOMO_BEN",
                        "nameDisplayedToCustomer": "PAWAPAY",
                        "currencies": [
                            {
                                "currency": "XOF",
                                "displayName": "CFA",
                                "operationTypes": {
                                    "PAYOUT": {
                                        ...
                                        "minAmount": "100",
                                        "maxAmount": "2000000",
                                        ...
                                    }
                                }
                            }
                        ]
                    }
                ]
            }
        ]
        ...
    }
It’s easy to provide validation ensuring the amount is between minAmount and maxAmount.
3
And done!

You now have validation that amount is correct for the payout.
Let’s take a look at enqueued payouts next.
​
Handling enqueued payouts and provider availability
Providers might have downtime in processing payouts. Let’s take a look at how to best handle cases when payout processing may be delayed or temporarily unavailable.
1
Checking if the provider is operational

Providers may have downtime. We monitor providers performance and availability 24/7. For your operational and support teams, we have a status page. From there they can subscribe to get updates in email or slack for all the providers they are interested in.
To avoid the customer getting failed or delayed payouts, we’ve also exposed this information over API from the both the provider availability and active configuration endpoints. This way you can be up front with the customer that their payout might take more time to be delivered.
Let’s use the provider availability endpoint here as it’s more common for payout use cases. We are filtering it to the specific country and operationType with the query parameters.

Copy

Ask AI

    [
        {
            "country": "BEN",
            "providers": [
                {
                    "provider": "MOOV_BEN",
                    "operationTypes": {
                        "PAYOUT": "OPERATIONAL"
                    }
                },
                {
                    "provider": "MTN_MOMO_BEN",
                    "operationTypes": {
                        "PAYOUT": "DELAYED"
                    }
                }
            ]
        }
    ]
The values you might see are:
OPERATIONAL - the provider is available and processing payouts normally.
DELAYED - the provider is having downtime and all payout requests are being enqueued in pawaPay.
CLOSED - the provider is having downtime and pawaPay is rejecting all payout requests.
Based on the above information you can inform the customer that their payout will be delayed (DELAYED) or is not possible at the moment and they can try again later (CLOSED).
If your payouts are not initiated by customers, you can validate the status of the provider and delay initiating payouts while the provider is unavailable (CLOSED).
2
Handling delayed processing

We rarely close payouts processing. Mostly we switch off processing, but enqueue your payout requests to be processed when the provider is operational again.
All payouts initiated when the status of the providers payouts is DELAYED will be ACCEPTED on initiation. They will then be moved to the ENQUEUED status. Our 24/7 payment operations team will be monitoring the provider for when their payout service becomes operational again. When that happens, the payouts will get processed as usual.
To find out whether a payout is enqueued, you can check payout status.

Copy

Ask AI

    {
        "status": "FOUND",
        "data": {
            "payoutId": "37b250e0-3075-42c8-92a9-cad55b3d86c8",
            "status": "ENQUEUED",
            "amount": "100.00",
            "currency": "XOF",
            "country": "BEN",
            "recipient": {
                "type": "MMO",
                "accountDetails": {
                    "phoneNumber": "22951345789",
                    "provider": "MTN_MOMO_BEN"
                }
            },
            "customerMessage": "DEMO",
            "created": "2025-05-28T06:28:29Z",
        }
    }
You do not need to take any action to leave the payout to be processed once the provider is operational again.
If you want to cancel the payout while it’s enqueued, you use the cancel enqueued payout endpoint.

Copy

Ask AI

    RESPONSE:
    {
        "payoutId": "37b250e0-3075-42c8-92a9-cad55b3d86c8",
        "status": "ACCEPTED"
    }
The request is asynchronous. The payout must be in ENQUEUED status. If the payout is already in PROCESSING status, the request will be rejected.
You will receive a callback with a FAILED status when cancellation has been completed.
​
Handling failures on initiation
Having implemented the above suggestions payout initiations should never be rejected. Let’s take a look at a couple of edge cases to make sure everything is handled.
1
Handling HTTP 500 with failureCode UNKNOWN_ERROR

The UNKNOWN_ERROR failureCode indicates that something unexpected has gone wrong when processing the payment. There is no status in the response in this case.
It is not safe to assume the initiation has failed for this payout. You should verify the status of the payout using the check payout status endpoint. Only if the payout is NOT_FOUND should it be considered FAILED.
We will take a look later in the guide, how to ensure consistency of payment statuses between your system and pawaPay.
2
And done!

Initiating payouts should be handled now.
Next, let’s take a look at payment failures that can happen during processing.
​
Handling processing failures
1
Handling failures during processing

As the pawaPay API is asynchronous, you will get a payout callback with the final status of the payout. If the status of the payout is FAILED you can find further information about the failure from failureReason. It includes the failureCode and the failureMessage indicating what has gone wrong.
The failureMessage from pawaPay API is meant for you and your support and operations teams. You are free to decide what message to show to the customer.
Find all the failure codes and implement handling as you choose.
We have standardised the numerous different failure codes and scenarios with all the different providers.
The quality of the failure codes varies by provider. The UNSPECIFIED_FAILURE code indicates that the provider indicated a failure with the payment, but did not provide any more specifics on the reason of the failure.
In case there is a general failure, the UNKNOWN_ERROR failureCode would be returned.
2
And done!

We have now also taken care of failures that can happen during payment processing. This way the customer knows what has happened and can take appropriate action to try again.
Now let’s take a look at how to ensure consistency of statuses between you and pawaPay.
​
Ensuring consistency
When working with financial APIs there are some considerations to take to ensure that you never think a payment is failed, when it is actually successful or vice versa. It is essential to keep systems in sync on the statuses of payments.
Let’s take a look at some considerations and pseudocode to ensure consistency.
1
Defensive status handling

All statuses should be checked defensively without assumptions.

Copy

Ask AI
    if( status == "COMPLETED" ) {
        myOrder.setPaymentStatus(COMPLETED);
    } else if ( status == "FAILED" ) {
        myOrder.setPaymentStatus(FAILED);
    } else if  ( status == "PROCESSING") {
        waitForProcessingToComplete();
    } else if( status == "ENQUEUED" ) {
        determineIfShouldBeCancelled();
    } else {
        //It is unclear what might have failed. Escalate for further investigation.
        myOrder.setPaymentStatus(NEEDS_ATTENTION);
    }
2
Handling network errors and system crashes

The key reason we require you to provide a payoutId for each payment is to ensure that you can always ask us what is the status of a payment, even if you never get a response from us.
You should always store this payoutId in your system before initiating a payout.

Copy

Ask AI
    var payoutId = new UUIDv4();

    //Let's store the payoutId we will use to ensure we always have it available even if something dramatic happens
    myOrder.setExternalPaymentId(payoutId).save();
    myOrder.setPaymentStatus(PENDING);

    try {
        var initiationResponse = pawaPay.initiatePayout(payoutId, ...)
    } catch (InterruptedException e) {
        var checkResult = pawaPay.checkPayoutStatus(payoutId);

        if ( result.status == "FOUND" ) {
            //The payment reached pawaPay. Check the status of it from the response.
        } else if ( result.status == "NOT_FOUND" ) {
            //The payment did not reach pawaPay. Safe to mark it as failed.
            myOrder.setPaymentStatus(FAILED);
        } else {
            //Unable to determine the status. Leave the payment as pending.
            //We will create a status recheck cycle later for such cases.

            //In case of a system crash, we should also leave the payment in pending status to be handled in the status recheck cycle.
        }
    }
The important thing to notice here is that we only mark a payment as FAILED when there is a clear indication of its failure. We use the check payout status endpoint when in doubt whether the payment was ACCEPTED by pawaPay.
3
Implementing an automated reconciliation cycle

Implementing the considerations listed above avoids almost all discrepancies of payment statuses between your system and pawaPay. When using callbacks to receive the final statuses of payments, issues like network connectivity, system downtime, and configuration errors might cause the callback not to be received by your system. To avoid keeping your customers waiting, we strongly recommend implementing a status recheck cycle.
This might look something like the following.

Copy

Ask AI
    //Run the job every few minutes.

    var pendingOrders = orders.getAllPendingForLongerThan15Minutes();

    for ( order in pendingOrders ) {
        var checkResult = pawaPay.checkPayoutStatus(order.getExternalPaymentId);

        if ( checkResult.status == "FOUND" ) {
            //Determine if the payment is in a final status and handle accordingly
            handleOrderStatus(checkResult.data);
        } else if (checkResult.status == "NOT_FOUND" ) {
            //The payment has never reached pawaPay. Can be failed safely.
            invoice.setPaymentStatus(FAILED);
        } else {
            //Something must have gone wrong. Leave for next cycle.
        }
    }
Having followed the rest of the guide, with this simple reconciliation cycle, you should not have any inconsistencies between your system and pawaPay. Having these checks automated will take a load off your operations and support teams as well.
​
Payments in reconciliation
When using pawaPay, you might find that a payment status is IN_RECONCILIATION. This means that there was a problem determining the correct final status of a payment. When using pawaPay all payments are reconciled by default and automatically - we validate all final statuses to ensure there are no discrepancies.
When encountering payments that are IN_RECONCILIATION you do not need to take any action. The payment has already been sent to our automatic reconciliation engine and it’s final status will be determined soon. The reconciliation time varies by provider. Payments that turn out to be successful are reconciled faster.
​
What to do next?
We’ve made everything easy to test in our sandbox environment before going live.
Test different failure scenarios
We have different phone numbers that you can use to test various failure scenarios on your sandbox account.
Review failure codes
Make sure all the failure codes are handled.
Add another layer of security
To ensure your funds are safe even if your API token should leak, you can always implement signatures for financial calls to add another layer of security.
And when you are ready to go live
Have a look at what to consider to make sure everything goes well.
Was this page helpful?


Yes

No
Deposits
Refunds

Guides
Refunds
Refunds allow you to return successfully collected funds back to the customer. The funds will be moved from your wallet in pawaPay to the customer’s mobile money wallet.
In this guide, we’ll go through step by step on how to refund a deposit.
If you haven’t already, check out the following information to set you up for success with this guide.
What you should know
Understand some considerations to take into account when working with mobile money.
How to start
Sort out API tokens and callbacks.
​
Initiating a refund
Let’s start by hard-coding everything.
1
Initiate the refund

Let’s send this payload to the initiate refund endpoint.

Copy

Ask AI

    {
        "refundId": "f02b543c-541c-4f21-bbea-20d2d56063d6",
        "depositId": "afb57b93-7849-49aa-babb-4c3ccbfe3d79"
    }
We ask you to generate a UUIDv4 refundId to uniquely identify this refund. This is so that you always have a reference to the refund you are initiating, even if you do not receive a response from us due to network errors. This allows you to always reconcile all payments between your system and pawaPay. You should store this refundId in your system before initiating the refund with pawaPay.
The depositId refers to the deposit you are looking to refund.
You will receive a response for the initiation.

Copy

Ask AI
    {
        "payoutId": "f02b543c-541c-4f21-bbea-20d2d56063d6",
        "status": "ACCEPTED",
        "created": "2025-05-15T07:38:56Z"
    }
The status shows whether this payout was ACCEPTED for processing or not. We will go through failure modes later in the guide.
2
The refund will be processed

Refunds do not require any authorisation or other action from the customer to receive it. The refund is usually processed within a few seconds. The customer will get an SMS receipt informing them that they have received a refund.
3
Get the final status of the payment

You will receive a callback from us to your configured callback URL.

Copy

Ask AI
    {
        "refundId": "64d4a574-5204-4700-bf75-acce936b8648",
        "status": "COMPLETED",
        "amount": "100.00",
        "currency": "RWF",
        "country": "RWA",
        "recipient": {
            "type": "MMO",
            "accountDetails": {
                "phoneNumber": "260973456789",
                "provider": "MTN_MOMO_RWA"
            }
        },
        "customerMessage": "Google One",
        "created": "2025-05-15T07:38:56Z"
    }
The main thing to focus on here is the status which is COMPLETED indicating that the refund has been processed successfully.
If you have not configured callbacks, you can always poll the check refund status endpoint for the final status of the payment.
4
And done!

Congratulations! You have now made your very first refund with pawaPay. We made a call to pawaPay to initiate the refund. And we found out the final status of that refund. The customer has been refunded the full amount of their deposit.
Next let’s take a look at partial refunds.
​
Partial refund
Sometimes only a part of the original payment should get refunded. Let’s take a look at how to do that.
1
Partial refund

Let’s send this payload to the initiate a partial refund endpoint.

Copy

Ask AI

    {
        "refundId": "f7232951-ab27-4175-bb63-6e15a9516df6",
        "depositId": "afb57b93-7849-49aa-babb-4c3ccbfe3d79",
        "amount": "30",
        "currency": "RWF"
    }
We are now specifying the amount and the currency of how much should be refunded. Otherwise everything stays the same. The request should be ACCEPTED and you will receive a callback once it’s processed.
2
Multiple refunds

You can initiate multiple partial refunds as long as the total amount does not exceed the amount of the original deposit.
If the total amount of all refunds exceeds the amount of the original deposit, the refund will be REJECTED.

Copy

Ask AI
    {
        "refundId": "bacec0ff-7e5b-4db7-bd2d-b49a9c6603e3",
        "status": "REJECTED",
        "rejectionReason": {
            "rejectionCode": "AMOUNT_TOO_LARGE",
            "rejectionMessage": "Amount should not be greater than 100"
        }
    }
If a full refund (no amount specified) is initiated after a successfully processed partial refund, the remaining amount will be refunded.
For example:
Original deposit: 100 RWF
Partial refund: 20 RWF
Partial refund: 20 RWF
Full refund (amount not specified on initiation): 100 - 20 - 20 = 60 RWF
3
And done!

We’ve now done partial refunds as well.
Let’s take a look at other considerations to make sure everything works smoothly.
​
Other considerations
1
One refund at a time

For a single deposit, only one refund can be initiated at a time. If there is a refund that is PROCESSING, initiating a new refund will be rejected.

Copy

Ask AI
    {
        "refundId": "4dca0890-7da2-4337-b540-b883f43b877c",
        "status": "REJECTED",
        "rejectionReason": {
            "rejectionCode":"REFUND_IN_PROGRESS",
            "rejectionMessage": "Another refund transaction is already in progress"
        }
    }
2
Maximum amount

Once the full amount of the original deposit has been refunded, refunds for that deposit will be rejected.

Copy

Ask AI
    {
        "refundId": "200e0f76-0a49-442e-8403-c58c45d22948",
        "status": "REJECTED",
        "rejectionReason": {
            "rejectionCode":"DEPOSIT_ALREADY_REFUNDED",
            "rejectionMessage": "Requested deposit has been already refunded"
        }
    }
3
Decimals in amount

Not all providers support decimals in amounts. You can find which providers support them in the providers section.
To dynamically support rounding the amount to refund, this information is exposed in active-configuration for each provider.

Copy

Ask AI

    {
        "companyName": "DEMO",
        "countries": [
            {
                "country": "RWA",
                ...
                "providers": [
                    {
                        "provider": "AIRTEL_RWA",
                        ...
                        "currencies": [
                            {
                                "currency": "RWF",
                                ...
                                "operationTypes": {
                                    "REFUND": {
                                        ...
                                        "decimalsInAmount": "NONE",
                                        ...
                                    }
                                }
                            }
                        ]
                    },
                    {
                        "provider": "MTN_MOMO_RWA",
                        ...
                        "currencies": [
                            {
                                "currency": "RWF",
                                ...
                                "operationTypes": {
                                    "REFUND": {
                                        ...
                                        "decimalsInAmount": "NONE",
                                        ....
                                    }
                                }
                            }
                        ]
                    }
                ]
            }
        ],
        ...
    }
The decimalsInAmount property shows whether the specific provider supports decimals in the amount when doing refunds. The possible values for it are TWO_PLACES and NONE. You can dynamically round the amount if decimals are not supported (NONE).
​
Handling enqueued refunds and provider availability
Providers might have downtime in processing refunds. Let’s take a look at how to best handle cases when refund processing may be delayed or temporarily unavailable.
1
Checking if the provider is operational

Providers may have downtime. We monitor providers performance and availability 24/7. For your operational and support teams, we have a status page. From there they can subscribe to get updates in email or slack for all the providers they are insterested in.
To avoid the customer getting failed or delayed payouts, we’ve also exposed this information over API from the both the provider availability and active configuration endpoints. This way you can be up front with the customer that their refund might take more time to be delivered.
Let’s use the provider availability endpoint here as it’s more common for refund use cases. We are filtering it to the specific country and operationType with the query parameters.

Copy

Ask AI

    [
        {
            "country": "BEN",
            "providers": [
                {
                    "provider": "MOOV_BEN",
                    "operationTypes": {
                        "REFUND": "OPERATIONAL"
                    }
                },
                {
                    "provider": "MTN_MOMO_BEN",
                    "operationTypes": {
                        "REFUND": "DELAYED"
                    }
                }
            ]
        }
    ]
The values you might see are:
OPERATIONAL - the provider is available and processing refunds normally.
DELAYED - the provider is having downtime and all refund requests are being enqueued in pawaPay.
CLOSED - the provider is having downtime and pawaPay is rejecting all refund requests.
Based on the above information you can inform the customer that their refund will be delayed (DELAYED) or is not possible at the moment and they can try again later (CLOSED).
You can validate the status of the provider and delay initiating refunds while the provider is unavailable (CLOSED).
2
Handling delayed processing

We rarely close refunds processing. Mostly we switch off processing, but enqueue your refund requests to be processed when the provider is operational again.
All refunds initiated when the status of the providers refunds is DELAYED will be ACCEPTED on initiation. They will then be moved to the ENQUEUED status. Our 24/7 payment operations team will be monitoring the provider for when their refunds service becomes operational again. When that happens, the refunds will get processed as usual.
To find out whether a refund is enqueued, you can check refund status.

Copy

Ask AI

    {
        "status": "FOUND",
        "data": {
            "refundId": "37b250e0-3075-42c8-92a9-cad55b3d86c8",
            "status": "ENQUEUED",
            "amount": "100.00",
            "currency": "XOF",
            "country": "BEN",
            "recipient": {
                "type": "MMO",
                "accountDetails": {
                    "phoneNumber": "22951345789",
                    "provider": "MTN_MOMO_BEN"
                }
            },
            "customerMessage": "DEMO",
            "created": "2025-05-28T06:28:29Z",
        }
    }
You do not need to take any action to leave the payout for processing once the provider is operational again.
​
Handling failures on initiation
Having implemented the above suggestions, refund initiations should never be rejected. Let’s take a look at a couple of edge cases to make sure everything is handled.
1
Handling HTTP 500 with failureCode UNKNOWN_ERROR

The UNKNOWN_ERROR failureCode indicates that something unexpected has gone wrong when processing the payment. There is no status in the response in this case.
It is not safe to assume the initiation has failed for this refund. You should verify the status of the refund using the check refund status endpoint. Only if the refund is NOT_FOUND should it be considered FAILED.
We will take a look later in the guide, how to ensure consistency of payment statuses between your system and pawaPay.
2
And done!

Initiating payouts should be handled now.
Next, let’s take a look at payment failures that can happen during processing.
​
Handling processing failures
1
Handling failures during processing

As the pawaPay API is asynchronous, you will get a refund callback with the final status of the refund. If the status of the refund is FAILED you can find further information about the failure from failureReason. It includes the failureCode and the failureMessage indicating what has gone wrong.
The failureMessage from pawaPay API is meant for you and your support and operations teams. You are free to decide what message to show to the customer.
Find all the failure codes and implement handling as you choose.
We have standardised the numerous different failure codes and scenarios with all the different providers.
The quality of the failure codes varies by provider. The UNSPECIFIED_FAILURE code indicates that the provider indicated a failure with the payment, but did not provide any specifics on the reason of the failure.
In case there is a general failure, the UNKNOWN_ERROR failureCode would be returned.
2
And done!

We have now also taken care of failures that can happen during payment processing. This way the customer knows what has happened and can take appropriate action to try again.
Now let’s take a look at how to ensure consistency of statuses between you and pawaPay.
​
Ensuring consistency
When working with financial APIs there are some considerations to take to ensure that you never think a payment has failed, when it is actually successful or vice versa. It is essential to keep systems in sync on the statuses of payments.
Let’s take a look at some considerations and pseudocode to ensure consistency.
1
Defensive status handling

All statuses should be checked defensively without assumptions.

Copy

Ask AI
    if( status == "COMPLETED" ) {
        myOrder.setRefundStatus(COMPLETED);
    } else if ( status == "FAILED" ) {
        myOrder.setRefundStatus(FAILED);
    } else if  ( status == "PROCESSING") {
        waitForProcessingToComplete();
    } else if( status == "ENQUEUED" ) {
        determineIfShouldBeCancelled();
    } else {
        //It is unclear what might have failed. Escalate for further investigation.
        myOrder.setRefundStatus(NEEDS_ATTENTION);
    }
2
Handling network errors and system crashes

The key reason we require you to provide a refundId for each payment is to ensure that you can always ask us what is the status of a payment, even if you never get a response from us.
You should always store this refundId in your system before initiating a refund.

Copy

Ask AI
    var refundId = new UUIDv4();

    //Let's store the refundId we will use to ensure we always have it available even if something dramatic happens
    myOrder.setExternalRefundId(refundId).save();
    myOrder.setRefundStatus(PENDING);

    try {
        var initiationResponse = pawaPay.initiateRefund(refundId, ...)
    } catch (InterruptedException e) {
        var checkResult = pawaPay.checkRefundStatus(payoutId);

        if ( result.status == "FOUND" ) {
            //The payment reached pawaPay. Check the status of it from the response.
        } else if ( result.status == "NOT_FOUND" ) {
            //The payment did not reach pawaPay. Safe to mark it as failed.
            myOrder.setRefundStatus(FAILED);
        } else {
            //Unable to determine the status. Leave the payment as pending.
            //We will create a status recheck cycle later for such cases.

            //In case of a system crash, we should also leave the payment in pending status to be handled in the status recheck cycle.
        }
    }
The important thing to notice here is that we only mark a payment as FAILED when there is a clear indication of its failure. We use the check refund status endpoint when in doubt whether the payment was ACCEPTED by pawaPay.
3
Implementing an automated reconciliation cycle

Implementing the considerations listed above avoids almost all discrepancies of payment statuses between your system and pawaPay. When using callbacks to receive the final statuses of payments, issues like network connectivity, system downtime, and configuration errors might cause the callback not to be received by your system. To avoid keeping your customers waiting, we strongly recommend implementing a status recheck cycle.
This might look something like the following.

Copy

Ask AI
    //Run the job every few minutes.

    var pendingRefunds = orders.getAllRefundsPendingForLongerThan15Minutes();

    for ( refund in pendingRefunds ) {
        var checkResult = pawaPay.checkRefundStatus(refund.getExternalRefundId);

        if ( checkResult.status == "FOUND" ) {
            //Determine if the payment is in a final status and handle accordingly
            handleRefundStatus(checkResult.data);
        } else if (checkResult.status == "NOT_FOUND" ) {
            //The payment has never reached pawaPay. Can be failed safely.
            invoice.setRefundStatus(FAILED);
        } else {
            //Something must have gone wrong. Leave for next cycle.
        }
    }
Having followed the rest of the guide, with this simple reconciliation cycle, you should not have any inconsistencies between your system and pawaPay. Having these checks automated will take a load off your operations and support teams as well.
​
Payments in reconciliation
When using pawaPay, you might find that a payment status is IN_RECONCILIATION. This means that there was a problem determining the correct final status of a payment. When using pawaPay all payments are reconciled by default and automatically - we validate all final statuses to ensure there are no discrepancies.
When encountering payments that are IN_RECONCILIATION you do not need to take any action. The payment has already been sent to our automatic reconciliation engine and it’s final status will be determined soon. The reconciliation time varies by provider. Payments that turn out to be successful are reconciled faster.
​
What to do next?
We’ve made everything easy to test in our sandbox environment before going live.
Test different failure scenarios
We have different phone numbers that you can use to test various failure scenarios on your sandbox account.
Review failure codes
Make sure all the failure codes are handled.
Add another layer of security
To ensure your funds are safe even if your API token should leak, you can always implement signatures for financial calls to add another layer of security.
And when you are ready to go live
Have a look at what to consider to make sure everything goes well.

Resources
Providers
Providers in our Merchant API refer to the specific Mobile Money Operators (MMOs) that are available through our platform. They are a mandatory part of the accountDetails for both Deposits and Payouts. This ensures the payment is routed to the correct provider.
You can always check which providers are available on your account from the active configuration endpoint.
You can use the predict provider endpoint to predict the provider for a phone number (MSISDN).
The number of decimal places that can be specified for the amount of the payment varies between different providers. You can use the active configuration endpoint to dynamically fetch whether decimals are supported by the provider.
You can find the list of all available providers, their currency, authorisation type and supported decimal places below.
Benin

Burkina Faso

Cameroon

MMO	Provider	Country	Currency	Authorisation	Decimals in amount
MTN	MTN_MOMO_CMR	CMR	XAF	PROVIDER_AUTH	Not supported
Orange	ORANGE_CMR	CMR	XAF	PROVIDER_AUTH	Not supported
Côte d'Ivoire (Ivory Coast)

Democratic Republic of the Congo (DRC)

Gabon

Ghana

Kenya

Malawi

Mozambique

Nigeria

Republic of the Congo

Rwanda

Senegal

Sierra Leone

Tanzania

Uganda

Zambia

Resources
Sandbox test numbers
​
Overview
As part of using the pawaPay platform, you will have access to our sandbox environment. Your sandbox account will have access to all the providers available on our platform. This allows you to safely test your integration without using actual mobile money wallets and real funds. In the sandbox, we provide special phone numbers (MSISDNs) that allow you to simulate both successful and unsuccessful transactions, helping you verify how your integration handles various payment outcomes.
​
Sandbox test phone numbers (MSISDNs)
These phone numbers can only be used in sandbox, not in production.
The payment process is faster than in production as the customer does not explicitly authorise the payment.
Benin

Burkina Faso

Cameroon

MTN (MTN_MOMO_CMR)
Orange (ORANGE_CMR)
Operation	MSISDN	Status	failureCode
Deposit	237653456019	FAILED	PAYER_LIMIT_REACHED
237653456029	FAILED	PAYER_NOT_FOUND
237653456039	FAILED	PAYMENT_NOT_APPROVED
237653456069	FAILED	UNSPECIFIED_FAILURE
237653456129	SUBMITTED	-
237653456789	COMPLETED	-
Payout	237653456089	FAILED	RECIPIENT_NOT_FOUND
237653456119	FAILED	UNSPECIFIED_FAILURE
237653456129	SUBMITTED	-
237653456789	COMPLETED	-
Côte d'Ivoire (Ivory Coast)

Democratic Republic of the Congo (DRC)

Gabon

Ghana

Kenya

Malawi

Nigeria

Republic of the Congo

Rwanda

Senegal

Sierra Leone

Tanzania

Uganda

Zambia

Was this page helpful?


Yes

No
Providers
Failure codes
website
discord
linkedin

Resources
Failure codes
​
Technical failure codes
Technical failures should not happen during live operation and are intended to provide clear information during the development of the integration.
Failure Code	HTTP status	Operation	Description
NO_AUTHENTICATION	401	All	We did not find the API token in the request headers.
AUTHENTICATION_ERROR	403	All	The API token specified for authentication is not valid or missing. Please refer to Authentication.
AUTHORISATION_ERROR	403	All	The API token specified for authentication is now authorized for the API call.
HTTP_SIGNATURE_ERROR	403	All	You have enabled signatures for financial calls, but we are unable to verify the signature from the request.
INVALID_INPUT	400	All	We were unable to parse the payload of the request.
MISSING_PARAMETER	400	All	A mandatory property is missing from the body of the request. The ‘failureMessage’ contains more information. Consult the API reference.
UNSUPPORTED_PARAMETER	400	All	A parameter was found in the body of the request that is not supported by the endpoint. The ‘failureMessage’ contains more information. Consult the API reference.
INVALID_PARAMETER	400	All	The value of a parameter in the body of the request was invalid. The ‘failureMessage’ contains more information. Consult the API reference.
AMOUNT_OUT_OF_BOUNDS	200	All	The amount is outside of the transaction limits for this provider and operation. Check active configuration for effective limits.
INVALID_AMOUNT	200	All	The number of decimal places specified in the amount is not supported by the provider. Check Providers for their support for decimals.
INVALID_PHONE_NUMBER	200	All	The phone number specified is not in the MSISDN format.
INVALID_CURRENCY	200	All	The currency is not supported by the provider.
INVALID_PROVIDER	200	All	The provider is not valid for the request.
DUPLICATE_METADATA_FIELD	400	All	You have multiple instances of the same metadta included in the request.
DEPOSITS_NOT_ALLOWED	403	Deposits	Deposits have not been enabled for the specified ‘provider’ on your pawaPay account.
PAYOUTS_NOT_ALLOWED	403	Payouts	Payouts have not been enabled for the specified ‘provider’ on your pawaPay account.
REFUNDS_NOT_ALLOWED	403	Refunds	Refunds have not been enabled for the specified ‘provider’ on your pawaPay account.
PROVIDER_TEMPORARILY_UNAVAILABLE	All	The provider specified is currently experiencing an outage and processing of payments has been temporarily halted. Please refer to our Status Page for live information about MMO availability.	
UNKNOWN_ERROR	500	All	An unknown error has occurred while processing this payment. Please check the status of this payment over API before considering failed.
​
Transaction failure codes
Transaction failures are expected to happen during live operation and are intended to provide clear information as to why a particular payment was not successful.
Failure Code	HTTP status	Operation	Description
PAYMENT_NOT_APPROVED	Deposits	The customer did not authorize the payment. Example: Customer did not enter their PIN in time.	
INSUFFICIENT_BALANCE	Deposits	The customer does not have enough funds to perform the deposit.	
PAYMENT_IN_PROGRESS	Deposits	The customer is initiating a transaction while an unfinished transaction is still pending. Note: Some providers only allow a single transaction to be processed at any given time. When the customer does not enter the PIN to authorize a payment in time, it might take up to 10 minutes for them to be able to initiate a new transaction.	
PAYER_NOT_FOUND	Deposits	The phone number (MSISDN) does not belong to the provider.	
RECIPIENT_NOT_FOUND	Payouts	The phone number (MSISDN) does not belong to the provider.	
MANUALLY_CANCELLED	Payouts/Refunds	The payout request was enqueued and subsequently failed manually from the pawaPay Dashboard or through the Cancel Enqueued Payout endpoint.	
PAWAPAY_WALLET_OUT_OF_FUNDS	Payouts/Refunds	The balance of your pawaPay wallet does not have the funds to initiate this payout.	
DEPOSIT_ALREADY_REFUNDED	Refunds	The deposit you are refunding has already been refunded in full.	
AMOUNT_TOO_LARGE	Refunds	The amount you are attempting to refund is larger than what is left to be refunded for this deposit including previous partial refunds.	
//REFUND_IN_PROGRESS	Refunds	There is already a refund being processed for this deposit. Only one refund can be processed for a single deposit at a time.	
WALLET_LIMIT_REACHED	All	The customer has reached a limit on their wallet. Example: Customer is only allowed to transfer a maximum of 1 000 000 per week.	
UNSPECIFIED_FAILURE	All	The provider did not give any information about the reason for this failure.	
UNKNOWN_ERROR	All	An unknown error has occurred while processing this payment. Please check the status of this payment over API before considering failed.	
Was this page helpful?


Yes

No
Sandbox test numbers
Provider brand guidelines
website
discord
linkedin


Resources
Signatures in API
​
Signatures
The pawaPay API is secured by the API token as explained in Authentication.
To add a second layer of security, you can optionally sign your financial requests to us - deposit, payout and refund requests.
In this case, pawaPay will only accept financial requests that have been signed by you. To utilize this additional capability, you should provide your public key in the pawaPay Dashboard and enable this feature.
Read how to do that from the pawaPay Dashboard Docs. This ensures that even if your API token leaks, only you can initiate financial requests with pawaPay.
If configured, pawaPay will also send callbacks to your callback URLs with the final status of your payment.
Your network team can whitelist the pawaPay platform IP addresses for these callback URLs.
Additionally, you can also enable pawaPay to sign those callbacks. You can then validate the signature that is included in the header of the callback to ensure that callbacks are in fact coming from pawaPay and have not been tampered with.
​
Signatures in financial requests
Financial requests are requests sent to the pawaPay Merchant API to move funds. These include deposits, payouts, bulk payouts and refunds.
The implementation of signatures in pawaPay is based on the standard described in RFC-9421.
When creating the financial request to send to the pawaPay Merchant API, you should create a Content-Digest, sign the request and add Signature and Signature-Input headers.
You can find sample node code for signing your requests from Github.
​
Hash the request body
For generating the Content-Digest you can use either SHA-256 or SHA-512 algorithm. The Content-Digest should be created from the request body. Having the request body hashed and available as a header allows verification that the content of the request has not been tampered with.

Copy

Ask AI
Content-Digest: sha-512=:mXRb9GJnfR/lyXOVfa27Wg+QrRgX3DVhXpQwjxbWoG3BgX7ZHmXLpvQb4il2kxgLjWmj6oSdwDdn5rUAJVYnUw==:

{ 
    "depositId": "d6df5c10-bd43-408c-b622-f10f9eaa568b",
    "amount": "15",
    "currency": "ZMW",
    "payer": {
        "type": "MMO",
        "address": {
            "provider": "MTN_MOMO_ZMB",
            "phoneNumber": "260763456789"
        }
    }
}
You can read more about it here.
​
Create the signature base
For creating the content that will be signed, you need to create a signature base. This should include all details of the request that should be verifiable. We recommend including at least the following Derived Components.
@method
@authority
@path
Also the following headers should be included into the request and the signature base.
Signature-Date
Content-Digest
Content-Type
Let’s take the following example request to initiate a deposit.

Copy

Ask AI
Authorization: Bearer <YOUR_API_TOKEN>
Content-Type: application/json; charset=UTF-8
Accept-Encoding: gzip, x-gzip, deflate
Content-Digest: sha-512=:mXRb9GJnfR/lyXOVfa27Wg+QrRgX3DVhXpQwjxbWoG3BgX7ZHmXLpvQb4il2kxgLjWmj6oSdwDdn5rUAJVYnUw==:
Signature-Date: 2024-05-02T15:36:45.058799Z1714653405;expires=1714653465
Accept-Signature: rsa-pss-sha512,ecdsa-p256-sha256,rsa-v1_5-sha256,ecdsa-p384-sha384
Accept-Digest: sha-256,sha-512
{
    "depositId": "d6df5c10-bd43-408c-b622-f10f9eaa568b",
    "amount": "15",
    "currency": "ZMW",
    "payer": {
        "type": "MMO",
        "accountDetails": {
            "provider": "MTN_MOMO_ZMB",
            "phoneNumber": "260763456789"
        }
    }
}
The signature base for the above request would be the following.

Copy

Ask AI
"@method": POST
"@authority": localhost:8080
"@path": /deposits
"signature-date": 2024-05-02T15:36:45.058799Z
"content-digest": sha-512=:mXRb9GJnfR/lyXOVfa27Wg+QrRgX3DVhXpQwjxbWoG3BgX7ZHmXLpvQb4il2kxgLjWmj6oSdwDdn5rUAJVYnUw==:
"content-type": application/json; charset=UTF-8
"@signature-params": ("@method" "@authority" "@path" "signature-date" "content-digest" "content-type");alg="ecdsa-p256-sha256";keyid="CUSTOMER_TEST_KEY";created=1714653405;expires=1714653465
You can read more about creating the signature base here.
​
Create the signature
You can use your private key now to sign the signature base. You can use one of the following algorithms:
RSASSA-PSS Using SHA-512
RSASSA-PKCS1-v1_5 Using SHA-256
ECDSA Using Curve P-256 DSS and SHA-256
ECDSA Using Curve P-384 DSS and SHA-384
You can read more about creating the signature here.
​
Include Signature and Signature-Input headers
Having generated the signature, you should include it into the Signature header of the request. You also need to create the Signature-Input header which outlines the parameters and their order that were used to generate the Signature as well as metadata about the signature. The metadata should include:
The used algorithm (alg)
The date the signature was created (created)
The expiration date of the keypair (expires)
The id of the key (keyid)
This allows pawaPay to validate the basis for the signature against your public key. Read more about it here.
The final request that can be sent to pawaPay would look as follows.

Copy

Ask AI
Authorization: Bearer <YOUR_API_TOKEN>
Content-Type: application/json; charset=UTF-8
Accept-Encoding: gzip, x-gzip, deflate
Content-Digest: sha-512=:mXRb9GJnfR/lyXOVfa27Wg+QrRgX3DVhXpQwjxbWoG3BgX7ZHmXLpvQb4il2kxgLjWmj6oSdwDdn5rUAJVYnUw==:
Signature-Date: 2024-05-02T15:36:45.058799Z
Signature: sig-pp=:MEQCIHoWKI71ADMmqwtwW48CHgfbDWdVItVMNlXTFJjoxmEDAiBTY30Le4wQd3RXqvmYubVwrxuP7Tz1SeZcnsNdHqjJDg==:
Signature-Input: sig-pp=("@method" "@authority" "@path" "signature-date" "content-digest" "content-type");alg="ecdsa-p256-sha256";keyid="CUSTOMER_TEST_KEY";created=1714653405;expires=1714653465
Accept-Signature: rsa-pss-sha512,ecdsa-p256-sha256,rsa-v1_5-sha256,ecdsa-p384-sha384
Accept-Digest: sha-256,sha-512
{
    "depositId": "d6df5c10-bd43-408c-b622-f10f9eaa568b",
    "amount": "15",
    "currency": "ZMW",
    "payer": {
        "type": "MMO",
        "accountDetails": {
            "provider": "MTN_MOMO_ZMB",
            "phoneNumber": "260763456789"
        }
    }
}
The pawaPay API would respond by accepting the payment for processing with the following response (headers irrelevant for signatures are omitted).

Copy

Ask AI
Content-Digest: sha-512=:NkvHr2fjqMoKW6nxA6V6jeQXhZyKVAcYdOv6Rmpa2cMn7yZmYDFrPzj/1LiAvOmJkCEdfsS5Bn9N/uZL8nCLZQ==:
Signature-Date: 2024-05-02T15:36:46.084331Z
Signature: sig-pp=:MEUCIFPakg6tQqN33NueVBPCKK4/GJ7BmHqux2yNQqWOEfmRAiEA43SOGd4JvlX2DWuh1oe0nP+/J8POSfr24SwXw2aRHRs=:
Signature-Input: sig-pp=("@status" "signature-date" "content-digest");alg="ecdsa-p256-sha256";keyid="HTTP_EC_P256_KEY:1";created=1714653406;expires=1714653466

{"depositId":"d6df5c10-bd43-408c-b622-f10f9eaa568b","status":"ACCEPTED","created":"2024-05-02T12:36:46Z"}
Do not forget to enable signed financial calls and upload your public key in the pawaPay Dashboard. Learn how to do that from the pawaPay Dashboard Docs.
​
Make the request
You can now send this request to pawaPay Merchant API to initiate a deposit, payout, bulk payout and refund.
​
Signatures in callbacks
When receiving callbacks from pawaPay they will include the following headers.
Signature
Signature-Input
Signature-Date
Content-Type
Content-Digest
You can verify that the request has not been tampered with and is coming from pawaPay.
Here is an example callback for a deposit.

Copy

Ask AI
Content-Type: application/json; charset=UTF-8
Accept-Encoding: gzip, x-gzip, deflate
Content-Digest: sha-512=:0ki7QBS/0MA424uwOq3k5HnJnL5SRkPjit12m0YMpd4JgWiMvm9+yNT3FunkpDaTSsKhTkliQwJlRw9bgsos9w==:
Signature-Date: 2024-05-02T16:45:51.131905Z
Signature: sig-pp=:MEQCIHFvGCUgyxmmowMufO4Yk20pBs3JHRax81si2QZVi9ByAiBPpg1WBhQjZ6fmi3a/gKcWiQ73Qm9Ol35On3c4K/flew==:
Signature-Input: sig-pp=("@method" "@authority" "@path" "signature-date" "content-digest" "content-type");alg="ecdsa-p256-sha256";keyid="CUSTOMER_TEST_KEY";created=1714657551;expires=1714657611
Accept-Signature: rsa-pss-sha512,ecdsa-p256-sha256,rsa-v1_5-sha256,ecdsa-p384-sha384
Accept-Digest: sha-256,sha-512
{
    "depositId": "4985d482-454d-4ebc-abc9-ad525eef21b6",
    "status": "COMPLETED",
    "requestedAmount": "15",
    "currency": "ZMW",
    "country": "ZMB",
    "payer": {
        "type": "MMO",
        "accountDetails": {
            "provider": "MTN_MOMO_ZMB",
            "phoneNumber": "260763456789"
        }
    },
    "customerMessage": "Signed deposit",
    "created": "2024-05-02T16:45:51.120601Z",
    "amount": "15",
    "providerTransactionId": "ABC123"
}
​
Validate content integrity
Create a hash of the request body using the algorithm specified in the Content-Digest header. Comparing the generated value to the value in Content-Digest ensures the body of the request has not been tampered with.
​
Validate the signature
Based on the parameters in Signature-Input, generate the signature base for the request. You can read more about it here.
Based on the previous example, the signature base would be the following.

Copy

Ask AI
"@method": POST
"@authority": localhost:8080
"@path": /callback
"signature-date": 2024-05-02T16:45:51.131905Z
"content-digest": sha-512=:0ki7QBS/0MA424uwOq3k5HnJnL5SRkPjit12m0YMpd4JgWiMvm9+yNT3FunkpDaTSsKhTkliQwJlRw9bgsos9w==:
"content-type": application/json; charset=UTF-8
"@signature-params": ("@method" "@authority" "@path" "signature-date" "content-digest" "content-type");alg="ecdsa-p256-sha256";keyid="CUSTOMER_TEST_KEY";created=1714657551;expires=1714657611
You can retrieve the public key to verify the signature from the Public Keys endpoint. Using the retrieved public key, the generated signature base and the signature, you can now verify that the the content (as specified by the Signature-Input) was in fact signed by pawaPay and therefore originates from pawaPay.
Do not forget to enable signed callbacks in the pawaPay Dashboard. Learn how to do that from the pawaPay Dashboard Docs.
Was this page helpful?


Yes

No


pawaPay Merchant API home pagelight logo

v2

Search/ask from AI (MIGHT NOT BE ACCURATE)
⌘K
Sandbox login
Create account

API docs
API Reference
Dashboard Docs
Run in Postman
Support Discord
Introduction
Welcome
What's mobile money?
What you should know
How to start?
Going live
Guides
Postman
Deposits
Payouts
Refunds
Payment Page
Resources
Upgrading from V1
Providers
Sandbox test numbers
Failure codes
Provider brand guidelines
Signatures in API
On this page
Initiating a refund
Partial refund
Other considerations
Handling enqueued refunds and provider availability
Handling failures on initiation
Handling processing failures
Ensuring consistency
Payments in reconciliation
What to do next?
Guides
Refunds
Refunds allow you to return successfully collected funds back to the customer. The funds will be moved from your wallet in pawaPay to the customer’s mobile money wallet.
In this guide, we’ll go through step by step on how to refund a deposit.
If you haven’t already, check out the following information to set you up for success with this guide.
What you should know
Understand some considerations to take into account when working with mobile money.
How to start
Sort out API tokens and callbacks.
​
Initiating a refund
Let’s start by hard-coding everything.
1
Initiate the refund

Let’s send this payload to the initiate refund endpoint.

Copy

Ask AI
    POST https://api.sandbox.pawapay.io/v2/refunds

    {
        "refundId": "f02b543c-541c-4f21-bbea-20d2d56063d6",
        "depositId": "afb57b93-7849-49aa-babb-4c3ccbfe3d79"
    }
We ask you to generate a UUIDv4 refundId to uniquely identify this refund. This is so that you always have a reference to the refund you are initiating, even if you do not receive a response from us due to network errors. This allows you to always reconcile all payments between your system and pawaPay. You should store this refundId in your system before initiating the refund with pawaPay.
The depositId refers to the deposit you are looking to refund.
You will receive a response for the initiation.

Copy

Ask AI
    {
        "payoutId": "f02b543c-541c-4f21-bbea-20d2d56063d6",
        "status": "ACCEPTED",
        "created": "2025-05-15T07:38:56Z"
    }
The status shows whether this payout was ACCEPTED for processing or not. We will go through failure modes later in the guide.
2
The refund will be processed

Refunds do not require any authorisation or other action from the customer to receive it. The refund is usually processed within a few seconds. The customer will get an SMS receipt informing them that they have received a refund.
3
Get the final status of the payment

You will receive a callback from us to your configured callback URL.

Copy

Ask AI
    {
        "refundId": "64d4a574-5204-4700-bf75-acce936b8648",
        "status": "COMPLETED",
        "amount": "100.00",
        "currency": "RWF",
        "country": "RWA",
        "recipient": {
            "type": "MMO",
            "accountDetails": {
                "phoneNumber": "260973456789",
                "provider": "MTN_MOMO_RWA"
            }
        },
        "customerMessage": "Google One",
        "created": "2025-05-15T07:38:56Z"
    }
The main thing to focus on here is the status which is COMPLETED indicating that the refund has been processed successfully.
If you have not configured callbacks, you can always poll the check refund status endpoint for the final status of the payment.
4
And done!

Congratulations! You have now made your very first refund with pawaPay. We made a call to pawaPay to initiate the refund. And we found out the final status of that refund. The customer has been refunded the full amount of their deposit.
Next let’s take a look at partial refunds.
​
Partial refund
Sometimes only a part of the original payment should get refunded. Let’s take a look at how to do that.
1
Partial refund

Let’s send this payload to the initiate a partial refund endpoint.

Copy

Ask AI
    POST https://api.sandbox.pawapay.io/v2/refunds

    {
        "refundId": "f7232951-ab27-4175-bb63-6e15a9516df6",
        "depositId": "afb57b93-7849-49aa-babb-4c3ccbfe3d79",
        "amount": "30",
        "currency": "RWF"
    }
We are now specifying the amount and the currency of how much should be refunded. Otherwise everything stays the same. The request should be ACCEPTED and you will receive a callback once it’s processed.
2
Multiple refunds

You can initiate multiple partial refunds as long as the total amount does not exceed the amount of the original deposit.
If the total amount of all refunds exceeds the amount of the original deposit, the refund will be REJECTED.

Copy

Ask AI
    {
        "refundId": "bacec0ff-7e5b-4db7-bd2d-b49a9c6603e3",
        "status": "REJECTED",
        "rejectionReason": {
            "rejectionCode": "AMOUNT_TOO_LARGE",
            "rejectionMessage": "Amount should not be greater than 100"
        }
    }
If a full refund (no amount specified) is initiated after a successfully processed partial refund, the remaining amount will be refunded.
For example:
Original deposit: 100 RWF
Partial refund: 20 RWF
Partial refund: 20 RWF
Full refund (amount not specified on initiation): 100 - 20 - 20 = 60 RWF
3
And done!

We’ve now done partial refunds as well.
Let’s take a look at other considerations to make sure everything works smoothly.
​
Other considerations
1
One refund at a time

For a single deposit, only one refund can be initiated at a time. If there is a refund that is PROCESSING, initiating a new refund will be rejected.

Copy

Ask AI
    {
        "refundId": "4dca0890-7da2-4337-b540-b883f43b877c",
        "status": "REJECTED",
        "rejectionReason": {
            "rejectionCode":"REFUND_IN_PROGRESS",
            "rejectionMessage": "Another refund transaction is already in progress"
        }
    }
2
Maximum amount

Once the full amount of the original deposit has been refunded, refunds for that deposit will be rejected.

Copy

Ask AI
    {
        "refundId": "200e0f76-0a49-442e-8403-c58c45d22948",
        "status": "REJECTED",
        "rejectionReason": {
            "rejectionCode":"DEPOSIT_ALREADY_REFUNDED",
            "rejectionMessage": "Requested deposit has been already refunded"
        }
    }
3
Decimals in amount

Not all providers support decimals in amounts. You can find which providers support them in the providers section.
To dynamically support rounding the amount to refund, this information is exposed in active-configuration for each provider.

Copy

Ask AI
    GET https://api.sandbox.pawapay.io/v2/active-conf?country=RWA&operationType=REFUND

    {
        "companyName": "DEMO",
        "countries": [
            {
                "country": "RWA",
                ...
                "providers": [
                    {
                        "provider": "AIRTEL_RWA",
                        ...
                        "currencies": [
                            {
                                "currency": "RWF",
                                ...
                                "operationTypes": {
                                    "REFUND": {
                                        ...
                                        "decimalsInAmount": "NONE",
                                        ...
                                    }
                                }
                            }
                        ]
                    },
                    {
                        "provider": "MTN_MOMO_RWA",
                        ...
                        "currencies": [
                            {
                                "currency": "RWF",
                                ...
                                "operationTypes": {
                                    "REFUND": {
                                        ...
                                        "decimalsInAmount": "NONE",
                                        ....
                                    }
                                }
                            }
                        ]
                    }
                ]
            }
        ],
        ...
    }
The decimalsInAmount property shows whether the specific provider supports decimals in the amount when doing refunds. The possible values for it are TWO_PLACES and NONE. You can dynamically round the amount if decimals are not supported (NONE).
​
Handling enqueued refunds and provider availability
Providers might have downtime in processing refunds. Let’s take a look at how to best handle cases when refund processing may be delayed or temporarily unavailable.
1
Checking if the provider is operational

Providers may have downtime. We monitor providers performance and availability 24/7. For your operational and support teams, we have a status page. From there they can subscribe to get updates in email or slack for all the providers they are insterested in.
To avoid the customer getting failed or delayed payouts, we’ve also exposed this information over API from the both the provider availability and active configuration endpoints. This way you can be up front with the customer that their refund might take more time to be delivered.
Let’s use the provider availability endpoint here as it’s more common for refund use cases. We are filtering it to the specific country and operationType with the query parameters.

Copy

Ask AI
    GET https://api.sandbox.pawapay.io//v2/availability?country=BEN&operationType=REFUND

    [
        {
            "country": "BEN",
            "providers": [
                {
                    "provider": "MOOV_BEN",
                    "operationTypes": {
                        "REFUND": "OPERATIONAL"
                    }
                },
                {
                    "provider": "MTN_MOMO_BEN",
                    "operationTypes": {
                        "REFUND": "DELAYED"
                    }
                }
            ]
        }
    ]
The values you might see are:
OPERATIONAL - the provider is available and processing refunds normally.
DELAYED - the provider is having downtime and all refund requests are being enqueued in pawaPay.
CLOSED - the provider is having downtime and pawaPay is rejecting all refund requests.
Based on the above information you can inform the customer that their refund will be delayed (DELAYED) or is not possible at the moment and they can try again later (CLOSED).
You can validate the status of the provider and delay initiating refunds while the provider is unavailable (CLOSED).
2
Handling delayed processing

We rarely close refunds processing. Mostly we switch off processing, but enqueue your refund requests to be processed when the provider is operational again.
All refunds initiated when the status of the providers refunds is DELAYED will be ACCEPTED on initiation. They will then be moved to the ENQUEUED status. Our 24/7 payment operations team will be monitoring the provider for when their refunds service becomes operational again. When that happens, the refunds will get processed as usual.
To find out whether a refund is enqueued, you can check refund status.

Copy

Ask AI
    GET https://api.sandbox.pawapay.io/v2/payouts/37b250e0-3075-42c8-92a9-cad55b3d86c8

    {
        "status": "FOUND",
        "data": {
            "refundId": "37b250e0-3075-42c8-92a9-cad55b3d86c8",
            "status": "ENQUEUED",
            "amount": "100.00",
            "currency": "XOF",
            "country": "BEN",
            "recipient": {
                "type": "MMO",
                "accountDetails": {
                    "phoneNumber": "22951345789",
                    "provider": "MTN_MOMO_BEN"
                }
            },
            "customerMessage": "DEMO",
            "created": "2025-05-28T06:28:29Z",
        }
    }
You do not need to take any action to leave the payout for processing once the provider is operational again.
​
Handling failures on initiation
Having implemented the above suggestions, refund initiations should never be rejected. Let’s take a look at a couple of edge cases to make sure everything is handled.
1
Handling HTTP 500 with failureCode UNKNOWN_ERROR

The UNKNOWN_ERROR failureCode indicates that something unexpected has gone wrong when processing the payment. There is no status in the response in this case.
It is not safe to assume the initiation has failed for this refund. You should verify the status of the refund using the check refund status endpoint. Only if the refund is NOT_FOUND should it be considered FAILED.
We will take a look later in the guide, how to ensure consistency of payment statuses between your system and pawaPay.
2
And done!

Initiating payouts should be handled now.
Next, let’s take a look at payment failures that can happen during processing.
​
Handling processing failures
1
Handling failures during processing

As the pawaPay API is asynchronous, you will get a refund callback with the final status of the refund. If the status of the refund is FAILED you can find further information about the failure from failureReason. It includes the failureCode and the failureMessage indicating what has gone wrong.
The failureMessage from pawaPay API is meant for you and your support and operations teams. You are free to decide what message to show to the customer.
Find all the failure codes and implement handling as you choose.
We have standardised the numerous different failure codes and scenarios with all the different providers.
The quality of the failure codes varies by provider. The UNSPECIFIED_FAILURE code indicates that the provider indicated a failure with the payment, but did not provide any specifics on the reason of the failure.
In case there is a general failure, the UNKNOWN_ERROR failureCode would be returned.
2
And done!

We have now also taken care of failures that can happen during payment processing. This way the customer knows what has happened and can take appropriate action to try again.
Now let’s take a look at how to ensure consistency of statuses between you and pawaPay.
​
Ensuring consistency
When working with financial APIs there are some considerations to take to ensure that you never think a payment has failed, when it is actually successful or vice versa. It is essential to keep systems in sync on the statuses of payments.
Let’s take a look at some considerations and pseudocode to ensure consistency.
1
Defensive status handling

All statuses should be checked defensively without assumptions.

Copy

Ask AI
    if( status == "COMPLETED" ) {
        myOrder.setRefundStatus(COMPLETED);
    } else if ( status == "FAILED" ) {
        myOrder.setRefundStatus(FAILED);
    } else if  ( status == "PROCESSING") {
        waitForProcessingToComplete();
    } else if( status == "ENQUEUED" ) {
        determineIfShouldBeCancelled();
    } else {
        //It is unclear what might have failed. Escalate for further investigation.
        myOrder.setRefundStatus(NEEDS_ATTENTION);
    }
2
Handling network errors and system crashes

The key reason we require you to provide a refundId for each payment is to ensure that you can always ask us what is the status of a payment, even if you never get a response from us.
You should always store this refundId in your system before initiating a refund.

Copy

Ask AI
    var refundId = new UUIDv4();

    //Let's store the refundId we will use to ensure we always have it available even if something dramatic happens
    myOrder.setExternalRefundId(refundId).save();
    myOrder.setRefundStatus(PENDING);

    try {
        var initiationResponse = pawaPay.initiateRefund(refundId, ...)
    } catch (InterruptedException e) {
        var checkResult = pawaPay.checkRefundStatus(payoutId);

        if ( result.status == "FOUND" ) {
            //The payment reached pawaPay. Check the status of it from the response.
        } else if ( result.status == "NOT_FOUND" ) {
            //The payment did not reach pawaPay. Safe to mark it as failed.
            myOrder.setRefundStatus(FAILED);
        } else {
            //Unable to determine the status. Leave the payment as pending.
            //We will create a status recheck cycle later for such cases.

            //In case of a system crash, we should also leave the payment in pending status to be handled in the status recheck cycle.
        }
    }
The important thing to notice here is that we only mark a payment as FAILED when there is a clear indication of its failure. We use the check refund status endpoint when in doubt whether the payment was ACCEPTED by pawaPay.
3
Implementing an automated reconciliation cycle

Implementing the considerations listed above avoids almost all discrepancies of payment statuses between your system and pawaPay. When using callbacks to receive the final statuses of payments, issues like network connectivity, system downtime, and configuration errors might cause the callback not to be received by your system. To avoid keeping your customers waiting, we strongly recommend implementing a status recheck cycle.
This might look something like the following.

Copy

Ask AI
    //Run the job every few minutes.

    var pendingRefunds = orders.getAllRefundsPendingForLongerThan15Minutes();

    for ( refund in pendingRefunds ) {
        var checkResult = pawaPay.checkRefundStatus(refund.getExternalRefundId);

        if ( checkResult.status == "FOUND" ) {
            //Determine if the payment is in a final status and handle accordingly
            handleRefundStatus(checkResult.data);
        } else if (checkResult.status == "NOT_FOUND" ) {
            //The payment has never reached pawaPay. Can be failed safely.
            invoice.setRefundStatus(FAILED);
        } else {
            //Something must have gone wrong. Leave for next cycle.
        }
    }
Having followed the rest of the guide, with this simple reconciliation cycle, you should not have any inconsistencies between your system and pawaPay. Having these checks automated will take a load off your operations and support teams as well.
​
Payments in reconciliation
When using pawaPay, you might find that a payment status is IN_RECONCILIATION. This means that there was a problem determining the correct final status of a payment. When using pawaPay all payments are reconciled by default and automatically - we validate all final statuses to ensure there are no discrepancies.
When encountering payments that are IN_RECONCILIATION you do not need to take any action. The payment has already been sent to our automatic reconciliation engine and it’s final status will be determined soon. The reconciliation time varies by provider. Payments that turn out to be successful are reconciled faster.
​
What to do next?
We’ve made everything easy to test in our sandbox environment before going live.
Test different failure scenarios
We have different phone numbers that you can use to test various failure scenarios on your sandbox account.
Review failure codes
Make sure all the failure codes are handled.
Add another layer of security
To ensure your funds are safe even if your API token should leak, you can always implement signatures for financial calls to add another layer of security.
And when you are ready to go live
Have a look at what to consider to make sure everything goes well.
Was this page helpful?


Yes

No
Payouts
Payment Page
Ask a question...

website
discord
linkedin
Powered by Mintlify
Refunds - pawaPay Merchant API



pawaPay Merchant API home pagelight logo

v2

Search/ask from AI (MIGHT NOT BE ACCURATE)
⌘K
Sandbox login
Create account

API docs
API Reference
Dashboard Docs
Run in Postman
Support Discord
Deposits
POST
Initiate deposit
GET
Check deposit status
POST
Resend deposit callback
Deposit callback
Payouts
POST
Initiate payout
GET
Check payout status
POST
Resend payout callback
POST
Cancel enqueued payout
POST
Initiate bulk payouts
Payout callback
Refunds
POST
Initiate refund
GET
Check refund status
POST
Resend refund callback
Refund callback
Remittances
POST
Initiate remittance
GET
Check remittance status
POST
Resend remittance callback
POST
Cancel enqueued remittance
Remittance callback
Payment page
POST
Deposit via Payment Page
Wallet balances
GET
Wallet balances
Toolkit
GET
Active Configuration
GET
Provider Availability
POST
Predict Provider
GET
Public Keys
Initiate refund


Copy

Ask AI
curl --request POST \
  --url https://api.sandbox.pawapay.io/v2/refunds \
  --header 'Authorization: Bearer <token>' \
  --header 'Content-Type: application/json' \
  --data '{
  "refundId": "f4401bd2-1568-4140-bf2d-eb77d2b2b639",
  "depositId": "f4401bd2-1568-4140-bf2d-eb77d2b2b639",
  "amount": "15",
  "currency": "ZMW",
  "metadata": [
    {
      "orderId": "ORD-123456789"
    },
    {
      "customerId": "customer@email.com",
      "isPII": true
    }
  ]
}'



Copy

Ask AI
{
  "refundId": "f4401bd2-1568-4140-bf2d-eb77d2b2b639",
  "status": "ACCEPTED",
  "created": "2020-10-19T11:17:01Z"
}
Refunds
Initiate refund
POST

https://api.sandbox.pawapay.io
/
v2
/
refunds

Try it
The refund endpoint allows you to pay a customer.
Check the guide!
Follow the step-by-step guide on how to handle refunds.
This API call is idempotent, which means it is safe to submit a request with the same refundId multiple times.
Duplicate requests with the same refundId will be ignored with the DUPLICATE_IGNORED status in the response.
Since the request can be rejected, you must check the status code in the response for each submitted request. The failureReason in the response will contain information about the reason of the rejection.
Each request can get one of the statuses on initiation:
Status	Callback	Description
ACCEPTED	Yes	The refund has been accepted by pawaPay for processing.
REJECTED	No	The refund has been rejected. See rejectionReason for details.
DUPLICATE_IGNORED	No	The refund has been ignored as a duplicate of an already accepted refund. Duplication logic relies upon refundId.
​
How to find out the final status of this refund?
As the pawaPay Merchant API is an asynchronous API, you can find out the final status of the ACCEPTED refund by either:
Waiting for a callback
If you have configured callbacks, the callback with the final status of the refund will be delivered to your callback URL.
Checking the status
Or poll the Check Rrefund Status endpoint.
Headers related to signatures must only be included if you have enabled “Only accept signed requests”. Read more about it from the pawaPay Dashboard documentation.
Authorizations
​
Authorization
stringheaderrequired
See Authentication.

Headers
​
Content-Digest
string<string>
SHA-256 or SHA-512 hash of the request body.

​
Signature
string<string>
Signature of the request according to RFC-9421.

​
Signature-Input
string<string>
Signature input according to RFC-9421.

​
Accept-Signature
string<string>
Expected signature algorithm of the response according to RFC-9421.

​
Accept-Digest
string<string>
Expected digest algorithm of the response according to RFC-9421.

Body
application/json
​
refundId
string<uuid>required
A UUIDv4 based unique ID for this payment.
We require you to provide the unique ID for all initiated payments to ensure you can always reconcile all payments.
Please store this ID in your system before initiating the payment with pawaPay.

Required string length: 36
Example:
"f4401bd2-1568-4140-bf2d-eb77d2b2b639"

​
depositId
string<uuid>required
The depositId of the deposit to be refunded.

Required string length: 36
Example:
"f4401bd2-1568-4140-bf2d-eb77d2b2b639"

​
amount
stringrequired
The amount of the payment.

Amount must follow below requirements or the request will be rejected:

Not all providers support decimals. Find which ones do from providers or dynamically using active configuration endpoint.
Transaction limits apply. Find them from the Active Configuration endpoint.
Leading zeroes are not permitted except where the value is less than 1. For any value less than one, one and only one leading zero must be supplied.
Required string length: 1 - 23
Example:
"15"

​
currency
stringrequired
The currency in which the amount is specified.

Format must be the ISO 4217 three character currency code in upper case. Read more from Wikipedia.

Find the supported currencies for the provider.

The active configuration endpoint has all the providers configured for your account together with the supported currencies.

Example:
"ZMW"

​
metadata
object[]
A list of metadata that you can attach to the payment for providing additional context about the payment.
For example, adding the channel from which the payment was initated, product ID or anything else that might help your operations team.

Metadata will be included in:

In the dashboard on payment details pages
Financial statements as JSON object
Callbacks
Metadata can be used when searching in the pawaPay Dashboard.
Full value of the metadata field must be used for searches.

Metadata will not be visible to the customer that is involved in this payment.

Up to 10 metadata fields can be attached to a payment.

Show child attributes

Example:
[
  { "orderId": "ORD-123456789" },
  {
    "customerId": "customer@email.com",
    "isPII": true
  }
]
Response

200

application/json
Request has been accepted for processing by pawaPay

​
refundId
string<uuid>required
The unique ID for this payment in pawaPay as specified by you during initiation.

Required string length: 36
Example:
"f4401bd2-1568-4140-bf2d-eb77d2b2b639"

​
status
enum<string>required
Possible refund initiation statuses:

ACCEPTED - The refund has been accepted by pawaPay for processing.
REJECTED - The refund has been rejected by pawaPay. See failureReason for details.
DUPLICATE_IGNORED - The refund has been ignored as a duplicate of an already accepted refund. Duplication logic relies upon refundId.
Available options: ACCEPTED, REJECTED, DUPLICATE_IGNORED 
​
created
string<date-time>
The timestamp of when the payment was created in the pawaPay platform. Format defined by 'date-time' in RFC3339 section 5.6 from IETF

Example:
"2020-02-21T17:32:29Z"

​
failureReason
object
Show child attributes

Was this page helpful?


Yes

No
Payout callback
Check refund status
Ask a question...

website
discord
linkedin
Powered by Mintlify
Initiate refund - pawaPay Merchant API





Guides
Refunds
Refunds allow you to return successfully collected funds back to the customer. The funds will be moved from your wallet in pawaPay to the customer’s mobile money wallet.
In this guide, we’ll go through step by step on how to refund a deposit.
If you haven’t already, check out the following information to set you up for success with this guide.
What you should know
Understand some considerations to take into account when working with mobile money.
How to start
Sort out API tokens and callbacks.
​
Initiating a refund
Let’s start by hard-coding everything.
1
Initiate the refund

Let’s send this payload to the initiate refund endpoint.

Copy

Ask AI
    POST https://api.sandbox.pawapay.io/v2/refunds

    {
        "refundId": "f02b543c-541c-4f21-bbea-20d2d56063d6",
        "depositId": "afb57b93-7849-49aa-babb-4c3ccbfe3d79"
    }
We ask you to generate a UUIDv4 refundId to uniquely identify this refund. This is so that you always have a reference to the refund you are initiating, even if you do not receive a response from us due to network errors. This allows you to always reconcile all payments between your system and pawaPay. You should store this refundId in your system before initiating the refund with pawaPay.
The depositId refers to the deposit you are looking to refund.
You will receive a response for the initiation.

Copy

Ask AI
    {
        "payoutId": "f02b543c-541c-4f21-bbea-20d2d56063d6",
        "status": "ACCEPTED",
        "created": "2025-05-15T07:38:56Z"
    }
The status shows whether this payout was ACCEPTED for processing or not. We will go through failure modes later in the guide.
2
The refund will be processed

Refunds do not require any authorisation or other action from the customer to receive it. The refund is usually processed within a few seconds. The customer will get an SMS receipt informing them that they have received a refund.
3
Get the final status of the payment

You will receive a callback from us to your configured callback URL.

Copy

Ask AI
    {
        "refundId": "64d4a574-5204-4700-bf75-acce936b8648",
        "status": "COMPLETED",
        "amount": "100.00",
        "currency": "RWF",
        "country": "RWA",
        "recipient": {
            "type": "MMO",
            "accountDetails": {
                "phoneNumber": "260973456789",
                "provider": "MTN_MOMO_RWA"
            }
        },
        "customerMessage": "Google One",
        "created": "2025-05-15T07:38:56Z"
    }
The main thing to focus on here is the status which is COMPLETED indicating that the refund has been processed successfully.
If you have not configured callbacks, you can always poll the check refund status endpoint for the final status of the payment.
4
And done!

Congratulations! You have now made your very first refund with pawaPay. We made a call to pawaPay to initiate the refund. And we found out the final status of that refund. The customer has been refunded the full amount of their deposit.
Next let’s take a look at partial refunds.
​
Partial refund
Sometimes only a part of the original payment should get refunded. Let’s take a look at how to do that.
1
Partial refund

Let’s send this payload to the initiate a partial refund endpoint.

Copy

Ask AI
    POST https://api.sandbox.pawapay.io/v2/refunds

    {
        "refundId": "f7232951-ab27-4175-bb63-6e15a9516df6",
        "depositId": "afb57b93-7849-49aa-babb-4c3ccbfe3d79",
        "amount": "30",
        "currency": "RWF"
    }
We are now specifying the amount and the currency of how much should be refunded. Otherwise everything stays the same. The request should be ACCEPTED and you will receive a callback once it’s processed.
2
Multiple refunds

You can initiate multiple partial refunds as long as the total amount does not exceed the amount of the original deposit.
If the total amount of all refunds exceeds the amount of the original deposit, the refund will be REJECTED.

Copy

Ask AI
    {
        "refundId": "bacec0ff-7e5b-4db7-bd2d-b49a9c6603e3",
        "status": "REJECTED",
        "rejectionReason": {
            "rejectionCode": "AMOUNT_TOO_LARGE",
            "rejectionMessage": "Amount should not be greater than 100"
        }
    }
If a full refund (no amount specified) is initiated after a successfully processed partial refund, the remaining amount will be refunded.
For example:
Original deposit: 100 RWF
Partial refund: 20 RWF
Partial refund: 20 RWF
Full refund (amount not specified on initiation): 100 - 20 - 20 = 60 RWF
3
And done!

We’ve now done partial refunds as well.
Let’s take a look at other considerations to make sure everything works smoothly.
​
Other considerations
1
One refund at a time

For a single deposit, only one refund can be initiated at a time. If there is a refund that is PROCESSING, initiating a new refund will be rejected.

Copy

Ask AI
    {
        "refundId": "4dca0890-7da2-4337-b540-b883f43b877c",
        "status": "REJECTED",
        "rejectionReason": {
            "rejectionCode":"REFUND_IN_PROGRESS",
            "rejectionMessage": "Another refund transaction is already in progress"
        }
    }
2
Maximum amount

Once the full amount of the original deposit has been refunded, refunds for that deposit will be rejected.

Copy

Ask AI
    {
        "refundId": "200e0f76-0a49-442e-8403-c58c45d22948",
        "status": "REJECTED",
        "rejectionReason": {
            "rejectionCode":"DEPOSIT_ALREADY_REFUNDED",
            "rejectionMessage": "Requested deposit has been already refunded"
        }
    }
3
Decimals in amount

Not all providers support decimals in amounts. You can find which providers support them in the providers section.
To dynamically support rounding the amount to refund, this information is exposed in active-configuration for each provider.

Copy

Ask AI
    GET https://api.sandbox.pawapay.io/v2/active-conf?country=RWA&operationType=REFUND

    {
        "companyName": "DEMO",
        "countries": [
            {
                "country": "RWA",
                ...
                "providers": [
                    {
                        "provider": "AIRTEL_RWA",
                        ...
                        "currencies": [
                            {
                                "currency": "RWF",
                                ...
                                "operationTypes": {
                                    "REFUND": {
                                        ...
                                        "decimalsInAmount": "NONE",
                                        ...
                                    }
                                }
                            }
                        ]
                    },
                    {
                        "provider": "MTN_MOMO_RWA",
                        ...
                        "currencies": [
                            {
                                "currency": "RWF",
                                ...
                                "operationTypes": {
                                    "REFUND": {
                                        ...
                                        "decimalsInAmount": "NONE",
                                        ....
                                    }
                                }
                            }
                        ]
                    }
                ]
            }
        ],
        ...
    }
The decimalsInAmount property shows whether the specific provider supports decimals in the amount when doing refunds. The possible values for it are TWO_PLACES and NONE. You can dynamically round the amount if decimals are not supported (NONE).
​
Handling enqueued refunds and provider availability
Providers might have downtime in processing refunds. Let’s take a look at how to best handle cases when refund processing may be delayed or temporarily unavailable.
1
Checking if the provider is operational

Providers may have downtime. We monitor providers performance and availability 24/7. For your operational and support teams, we have a status page. From there they can subscribe to get updates in email or slack for all the providers they are insterested in.
To avoid the customer getting failed or delayed payouts, we’ve also exposed this information over API from the both the provider availability and active configuration endpoints. This way you can be up front with the customer that their refund might take more time to be delivered.
Let’s use the provider availability endpoint here as it’s more common for refund use cases. We are filtering it to the specific country and operationType with the query parameters.

Copy

Ask AI
    GET https://api.sandbox.pawapay.io//v2/availability?country=BEN&operationType=REFUND

    [
        {
            "country": "BEN",
            "providers": [
                {
                    "provider": "MOOV_BEN",
                    "operationTypes": {
                        "REFUND": "OPERATIONAL"
                    }
                },
                {
                    "provider": "MTN_MOMO_BEN",
                    "operationTypes": {
                        "REFUND": "DELAYED"
                    }
                }
            ]
        }
    ]
The values you might see are:
OPERATIONAL - the provider is available and processing refunds normally.
DELAYED - the provider is having downtime and all refund requests are being enqueued in pawaPay.
CLOSED - the provider is having downtime and pawaPay is rejecting all refund requests.
Based on the above information you can inform the customer that their refund will be delayed (DELAYED) or is not possible at the moment and they can try again later (CLOSED).
You can validate the status of the provider and delay initiating refunds while the provider is unavailable (CLOSED).
2
Handling delayed processing

We rarely close refunds processing. Mostly we switch off processing, but enqueue your refund requests to be processed when the provider is operational again.
All refunds initiated when the status of the providers refunds is DELAYED will be ACCEPTED on initiation. They will then be moved to the ENQUEUED status. Our 24/7 payment operations team will be monitoring the provider for when their refunds service becomes operational again. When that happens, the refunds will get processed as usual.
To find out whether a refund is enqueued, you can check refund status.

Copy

Ask AI
    GET https://api.sandbox.pawapay.io/v2/payouts/37b250e0-3075-42c8-92a9-cad55b3d86c8

    {
        "status": "FOUND",
        "data": {
            "refundId": "37b250e0-3075-42c8-92a9-cad55b3d86c8",
            "status": "ENQUEUED",
            "amount": "100.00",
            "currency": "XOF",
            "country": "BEN",
            "recipient": {
                "type": "MMO",
                "accountDetails": {
                    "phoneNumber": "22951345789",
                    "provider": "MTN_MOMO_BEN"
                }
            },
            "customerMessage": "DEMO",
            "created": "2025-05-28T06:28:29Z",
        }
    }
You do not need to take any action to leave the payout for processing once the provider is operational again.
​
Handling failures on initiation
Having implemented the above suggestions, refund initiations should never be rejected. Let’s take a look at a couple of edge cases to make sure everything is handled.
1
Handling HTTP 500 with failureCode UNKNOWN_ERROR

The UNKNOWN_ERROR failureCode indicates that something unexpected has gone wrong when processing the payment. There is no status in the response in this case.
It is not safe to assume the initiation has failed for this refund. You should verify the status of the refund using the check refund status endpoint. Only if the refund is NOT_FOUND should it be considered FAILED.
We will take a look later in the guide, how to ensure consistency of payment statuses between your system and pawaPay.
2
And done!

Initiating payouts should be handled now.
Next, let’s take a look at payment failures that can happen during processing.
​
Handling processing failures
1
Handling failures during processing

As the pawaPay API is asynchronous, you will get a refund callback with the final status of the refund. If the status of the refund is FAILED you can find further information about the failure from failureReason. It includes the failureCode and the failureMessage indicating what has gone wrong.
The failureMessage from pawaPay API is meant for you and your support and operations teams. You are free to decide what message to show to the customer.
Find all the failure codes and implement handling as you choose.
We have standardised the numerous different failure codes and scenarios with all the different providers.
The quality of the failure codes varies by provider. The UNSPECIFIED_FAILURE code indicates that the provider indicated a failure with the payment, but did not provide any specifics on the reason of the failure.
In case there is a general failure, the UNKNOWN_ERROR failureCode would be returned.
2
And done!

We have now also taken care of failures that can happen during payment processing. This way the customer knows what has happened and can take appropriate action to try again.
Now let’s take a look at how to ensure consistency of statuses between you and pawaPay.
​
Ensuring consistency
When working with financial APIs there are some considerations to take to ensure that you never think a payment has failed, when it is actually successful or vice versa. It is essential to keep systems in sync on the statuses of payments.
Let’s take a look at some considerations and pseudocode to ensure consistency.
1
Defensive status handling

All statuses should be checked defensively without assumptions.

Copy

Ask AI
    if( status == "COMPLETED" ) {
        myOrder.setRefundStatus(COMPLETED);
    } else if ( status == "FAILED" ) {
        myOrder.setRefundStatus(FAILED);
    } else if  ( status == "PROCESSING") {
        waitForProcessingToComplete();
    } else if( status == "ENQUEUED" ) {
        determineIfShouldBeCancelled();
    } else {
        //It is unclear what might have failed. Escalate for further investigation.
        myOrder.setRefundStatus(NEEDS_ATTENTION);
    }
2
Handling network errors and system crashes

The key reason we require you to provide a refundId for each payment is to ensure that you can always ask us what is the status of a payment, even if you never get a response from us.
You should always store this refundId in your system before initiating a refund.

Copy

Ask AI
    var refundId = new UUIDv4();

    //Let's store the refundId we will use to ensure we always have it available even if something dramatic happens
    myOrder.setExternalRefundId(refundId).save();
    myOrder.setRefundStatus(PENDING);

    try {
        var initiationResponse = pawaPay.initiateRefund(refundId, ...)
    } catch (InterruptedException e) {
        var checkResult = pawaPay.checkRefundStatus(payoutId);

        if ( result.status == "FOUND" ) {
            //The payment reached pawaPay. Check the status of it from the response.
        } else if ( result.status == "NOT_FOUND" ) {
            //The payment did not reach pawaPay. Safe to mark it as failed.
            myOrder.setRefundStatus(FAILED);
        } else {
            //Unable to determine the status. Leave the payment as pending.
            //We will create a status recheck cycle later for such cases.

            //In case of a system crash, we should also leave the payment in pending status to be handled in the status recheck cycle.
        }
    }
The important thing to notice here is that we only mark a payment as FAILED when there is a clear indication of its failure. We use the check refund status endpoint when in doubt whether the payment was ACCEPTED by pawaPay.
3
Implementing an automated reconciliation cycle

Implementing the considerations listed above avoids almost all discrepancies of payment statuses between your system and pawaPay. When using callbacks to receive the final statuses of payments, issues like network connectivity, system downtime, and configuration errors might cause the callback not to be received by your system. To avoid keeping your customers waiting, we strongly recommend implementing a status recheck cycle.
This might look something like the following.

Copy

Ask AI
    //Run the job every few minutes.

    var pendingRefunds = orders.getAllRefundsPendingForLongerThan15Minutes();

    for ( refund in pendingRefunds ) {
        var checkResult = pawaPay.checkRefundStatus(refund.getExternalRefundId);

        if ( checkResult.status == "FOUND" ) {
            //Determine if the payment is in a final status and handle accordingly
            handleRefundStatus(checkResult.data);
        } else if (checkResult.status == "NOT_FOUND" ) {
            //The payment has never reached pawaPay. Can be failed safely.
            invoice.setRefundStatus(FAILED);
        } else {
            //Something must have gone wrong. Leave for next cycle.
        }
    }
Having followed the rest of the guide, with this simple reconciliation cycle, you should not have any inconsistencies between your system and pawaPay. Having these checks automated will take a load off your operations and support teams as well.
​
Payments in reconciliation
When using pawaPay, you might find that a payment status is IN_RECONCILIATION. This means that there was a problem determining the correct final status of a payment. When using pawaPay all payments are reconciled by default and automatically - we validate all final statuses to ensure there are no discrepancies.
When encountering payments that are IN_RECONCILIATION you do not need to take any action. The payment has already been sent to our automatic reconciliation engine and it’s final status will be determined soon. The reconciliation time varies by provider. Payments that turn out to be successful are reconciled faster.
​
What to do next?
We’ve made everything easy to test in our sandbox environment before going live.
Test different failure scenarios
We have different phone numbers that you can use to test various failure scenarios on your sandbox account.
Review failure codes
Make sure all the failure codes are handled.
Add another layer of security
To ensure your funds are safe even if your API token should leak, you can always implement signatures for financial calls to add another layer of security.
And when you are ready to go live
Have a look at what to consider to make sure everything goes well.