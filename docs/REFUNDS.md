# Refund System Documentation

This document outlines the automatic refund system implemented in the application.

## Overview

The system is designed to automatically initiate refunds when a payout fails after a successful payin. This ensures that users are not left without their money if there's an issue with the payout process.

## How It Works

1. When a payout fails, the system checks if the payin was successful.
2. If the payin was successful, the system initiates a refund to the original payment method.
3. The refund status is tracked in the `transfers` table with the following fields:
   - `refund_id` - The unique identifier for the refund
   - `refund_status` - The current status of the refund
   - `refund_attempted_at` - When the refund was first attempted
   - `refund_completed_at` - When the refund was completed
   - `refund_response` - The full response from the payment provider
   - `refund_error` - Any error messages related to the refund

## Scheduled Processing

A scheduled job runs every 5 minutes to process pending refunds. This job:

1. Finds all transfers that need refund processing
2. Checks the status of any in-progress refunds
3. Initiates new refunds for eligible transfers
4. Updates the transfer records with the latest status

## Manual Commands

### Process Pending Refunds

```bash
php artisan refunds:process-pending
```

### Test Refund

To manually test refunding a specific transfer:

```bash
php artisan test:refund {transfer_id} [--force]
```

Options:
- `--force`: Skip the eligibility check and attempt refund anyway

### Check Refund Status

To check the status of a refund:

```bash
php artisan test:refund:status {refund_id}
```

## Configuration

Add these environment variables to your `.env` file:

```
PAWAPAY_API_KEY=your_api_key_here
PAWAPAY_SANDBOX=true
```

## Troubleshooting

### Common Issues

1. **Refund not being triggered**
   - Check that the transfer has `payin_status = 'success'` and `payout_status = 'failed'`
   - Verify that `refund_id` is not already set
   - Check the logs for any errors

2. **Refund stuck in PENDING**
   - The system will automatically check the status of pending refunds
   - You can manually check the status using the `test:refund:status` command

3. **Refund failed**
   - Check the `refund_error` field for details
   - Verify that the payment method supports refunds
  
## Logs

Refund-related logs can be found in:
- `storage/logs/laravel.log`
- `storage/logs/refund-processor.log` (scheduled job output)
