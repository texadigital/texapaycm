@extends('layouts.app')

@section('content')
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #0b1426 0%, #1a1f3a 100%);
            color: #e2e8f0;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(51, 65, 85, 0.3);
            padding: 32px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        .header {
            text-align: center;
            margin-bottom: 32px;
        }

        .header h1 {
            font-size: 28px;
            font-weight: 600;
            color: #f8fafc;
            margin-bottom: 8px;
        }

        .header p {
            color: #94a3b8;
            font-size: 16px;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            display: block;
            font-weight: 500;
            color: #cbd5e1;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-input {
            width: 100%;
            padding: 16px;
            background: rgba(30, 41, 59, 0.5);
            border: 2px solid rgba(51, 65, 85, 0.5);
            border-radius: 12px;
            color: #f8fafc;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-input.error {
            border-color: #ef4444;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
        }

        .bank-selector {
            position: relative;
        }

        .bank-dropdown {
            width: 100%;
            padding: 16px;
            background: rgba(30, 41, 59, 0.5);
            border: 2px solid rgba(51, 65, 85, 0.5);
            border-radius: 12px;
            color: #f8fafc;
            font-size: 16px;
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 12px center;
            background-repeat: no-repeat;
            background-size: 16px;
            padding-right: 48px;
        }

        .bank-dropdown:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .bank-dropdown option {
            background: #1e293b;
            color: #f8fafc;
            padding: 12px;
        }

        .error-message {
            color: #ef4444;
            font-size: 14px;
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .success-message {
            color: #10b981;
            font-size: 14px;
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn {
            width: 100%;
            padding: 16px 24px;
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 32px;
        }

        .btn:hover:not(:disabled) {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(59, 130, 246, 0.4);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .btn-secondary {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            margin-top: 16px;
        }

        .btn-secondary:hover:not(:disabled) {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            box-shadow: 0 10px 25px -5px rgba(16, 185, 129, 0.4);
        }

        .loading-spinner {
            width: 20px;
            height: 20px;
            border: 2px solid transparent;
            border-top: 2px solid currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .verified-info {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: 12px;
            padding: 20px;
            margin-top: 24px;
        }

        .verified-info h3 {
            color: #10b981;
            font-size: 18px;
            margin-bottom: 12px;
        }

        .verified-info .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .verified-info .info-label {
            color: #94a3b8;
        }

        .verified-info .info-value {
            color: #f8fafc;
            font-weight: 500;
        }

        .alert {
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }

        .hint {
            font-size: 14px;
            color: #64748b;
            margin-top: 6px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #64748b;
            text-decoration: none;
            margin-bottom: 24px;
            transition: color 0.3s ease;
        }

        .back-link:hover {
            color: #3b82f6;
        }

        @media (max-width: 640px) {
            .container {
                margin: 10px;
                padding: 24px;
            }
            
            .header h1 {
                font-size: 24px;
            }
        }
    </style>
    <div class="container">
        <a href="{{ route('dashboard') }}" class="back-link">
            ← Back to Dashboard
        </a>

        <div class="header">
            <h1>Transfer to Bank (Nigeria)</h1>
            <p>Enter recipient bank details and verify account name</p>
        </div>

        @if(session('transfer.error'))
            <div class="alert alert-error">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                {{ session('transfer.error') }}
            </div>
        @endif

        <form id="bankForm" method="POST" action="{{ route('transfer.bank.verify') }}">
            @csrf
            
            <div class="form-group">
                <label for="account_number" class="form-label">Account Number</label>
                <input 
                    type="text" 
                    id="account_number" 
                    name="account_number" 
                    class="form-input {{ $errors->has('account_number') ? 'error' : '' }}"
                    placeholder="Enter 10-digit account number"
                    value="{{ old('account_number', $account_number) }}"
                    maxlength="10"
                    pattern="[0-9]{10}"
                    required
                >
                <div class="hint">Enter a valid 10-digit Nigerian bank account number</div>
                @error('account_number')
                    <div class="error-message">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div class="form-group">
                <label for="bank_code" class="form-label">Select Bank</label>
                <div class="bank-selector">
                    <select 
                        id="bank_code" 
                        name="bank_code" 
                        class="bank-dropdown {{ $errors->has('bank_code') ? 'error' : '' }}"
                        required
                    >
                        <option value="">Choose a bank...</option>
                        <!-- Banks will be loaded here -->
                    </select>
                </div>
                <div class="hint">Select the bank where the account is held</div>
                @error('bank_code')
                    <div class="error-message">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <button type="submit" class="btn" id="verifyBtn">
                <span class="btn-text">Verify Account Name</span>
                <div class="loading-spinner" id="loadingSpinner" style="display: none;"></div>
            </button>
        </form>

        @if($account_name)
            <div class="verified-info">
                <h3>✓ Account Verified Successfully</h3>
                <div class="info-row">
                    <span class="info-label">Account Name:</span>
                    <span class="info-value">{{ $account_name }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Bank:</span>
                    <span class="info-value">{{ $bank_name }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Account Number:</span>
                    <span class="info-value">{{ $account_number }}</span>
                </div>
                
                <a href="{{ route('transfer.quote') }}" class="btn btn-secondary">
                    Continue to Amount & Quote →
                </a>
            </div>
        @endif
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const bankSelect = document.getElementById('bank_code');
            const form = document.getElementById('bankForm');
            const verifyBtn = document.getElementById('verifyBtn');
            const btnText = verifyBtn.querySelector('.btn-text');
            const loadingSpinner = document.getElementById('loadingSpinner');
            const accountInput = document.getElementById('account_number');

            // Load banks on page load
            loadBanks();

            // Form submission
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                if (!validateForm()) {
                    return;
                }

                // Show loading state
                verifyBtn.disabled = true;
                btnText.style.display = 'none';
                loadingSpinner.style.display = 'block';

                // Submit form
                form.submit();
            });

            // Account number input formatting
            accountInput.addEventListener('input', function(e) {
                // Only allow numbers
                this.value = this.value.replace(/\D/g, '');
                
                // Limit to 10 digits
                if (this.value.length > 10) {
                    this.value = this.value.slice(0, 10);
                }
            });

            function validateForm() {
                let isValid = true;
                
                // Clear previous errors
                clearErrors();

                // Validate account number
                const accountNumber = accountInput.value.trim();
                if (!/^\d{10}$/.test(accountNumber)) {
                    showError(accountInput, 'Please enter a valid 10-digit account number');
                    isValid = false;
                }

                // Validate bank selection
                if (!bankSelect.value) {
                    showError(bankSelect, 'Please select a bank');
                    isValid = false;
                }

                return isValid;
            }

            function showError(input, message) {
                input.classList.add('error');
                
                // Remove existing error message
                const existingError = input.parentNode.querySelector('.error-message');
                if (existingError) {
                    existingError.remove();
                }

                // Add new error message
                const errorDiv = document.createElement('div');
                errorDiv.className = 'error-message';
                errorDiv.innerHTML = `
                    <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    ${message}
                `;
                input.parentNode.appendChild(errorDiv);
            }

            function clearErrors() {
                // Remove error classes
                document.querySelectorAll('.form-input, .bank-dropdown').forEach(input => {
                    input.classList.remove('error');
                });

                // Remove error messages
                document.querySelectorAll('.error-message').forEach(error => {
                    if (!error.closest('.alert')) { // Don't remove alert errors
                        error.remove();
                    }
                });
            }

            async function loadBanks() {
                try {
                    const response = await fetch('/api/banks', {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    const data = await response.json();
                    const banks = data.banks || [];

                    // Clear existing options except the first one
                    bankSelect.innerHTML = '<option value="">Choose a bank...</option>';

                    // Add banks to select
                    banks.forEach(bank => {
                        const option = document.createElement('option');
                        option.value = bank.bankCode || '';
                        option.textContent = bank.name || bank.bankCode || 'Unknown Bank';
                        
                        // Pre-select if this was the previously selected bank
                        if (bank.bankCode === '{{ old("bank_code", $bank_code) }}') {
                            option.selected = true;
                        }
                        
                        bankSelect.appendChild(option);
                    });

                    console.log(`Loaded ${banks.length} banks successfully`);

                } catch (error) {
                    console.error('Failed to load banks:', error);
                    
                    // Add error option
                    bankSelect.innerHTML = `
                        <option value="">Failed to load banks - please refresh</option>
                    `;
                }
            }
        });
    </script>
@endsection
