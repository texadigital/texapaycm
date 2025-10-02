@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="h4 mb-4">Notification Preferences</h1>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form id="notification-preferences-form">
        @csrf
        
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Notification Types</h5>
                    </div>
                    <div class="card-body">
                        <div id="preferences-container">
                            <div class="text-center py-4">
                                <div class="spinner-border" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Global Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="email_notifications" name="email_notifications" {{ $user->email_notifications ? 'checked' : '' }}>
                            <label class="form-check-label" for="email_notifications">Email Notifications</label>
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="sms_notifications" name="sms_notifications" {{ $user->sms_notifications ? 'checked' : '' }}>
                            <label class="form-check-label" for="sms_notifications">SMS Notifications</label>
                        </div>
                        <p class="text-muted small">Global settings override individual notification preferences.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">Save Preferences</button>
            <a href="{{ route('notifications.index') }}" class="btn btn-secondary">Back to Notifications</a>
        </div>
    </form>
</div>

<script>
function loadPreferences() {
    fetch('/notifications/preferences')
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('preferences-container');
            container.innerHTML = '';

            // Group notifications by category
            const categories = {
                'Authentication': ['auth.login.success', 'auth.login.failed'],
                'Profile': ['profile.updated', 'security.settings.updated'],
                'KYC Verification': ['kyc.started', 'kyc.completed', 'kyc.failed'],
                'Transfers': ['transfer.quote.created', 'transfer.quote.expired', 'transfer.initiated', 'transfer.payin.success', 'transfer.payin.failed', 'transfer.payout.success', 'transfer.payout.failed', 'transfer.refund.initiated', 'transfer.refund.completed'],
                'Support': ['support.ticket.created', 'support.ticket.replied', 'support.ticket.closed'],
                'Limits': ['limits.warning.daily', 'limits.warning.monthly', 'limits.exceeded']
            };

            Object.keys(categories).forEach(categoryName => {
                const categoryDiv = document.createElement('div');
                categoryDiv.className = 'mb-4';
                categoryDiv.innerHTML = `
                    <h6 class="text-muted mb-3">${categoryName}</h6>
                    <div class="row" id="category-${categoryName.toLowerCase().replace(/\s+/g, '-')}"></div>
                `;
                container.appendChild(categoryDiv);

                const categoryContainer = document.getElementById(`category-${categoryName.toLowerCase().replace(/\s+/g, '-')}`);
                
                categories[categoryName].forEach(type => {
                    if (data.preferences[type]) {
                        const preferenceDiv = document.createElement('div');
                        preferenceDiv.className = 'col-md-6 mb-3';
                        preferenceDiv.innerHTML = `
                            <div class="card">
                                <div class="card-body py-3">
                                    <h6 class="card-title mb-2">${getNotificationTypeName(type)}</h6>
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="${type}_email" name="preferences[${type}][email_enabled]" ${data.preferences[type].email_enabled ? 'checked' : ''}>
                                        <label class="form-check-label" for="${type}_email">Email</label>
                                    </div>
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="${type}_sms" name="preferences[${type}][sms_enabled]" ${data.preferences[type].sms_enabled ? 'checked' : ''}>
                                        <label class="form-check-label" for="${type}_sms">SMS</label>
                                    </div>
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="${type}_push" name="preferences[${type}][push_enabled]" ${data.preferences[type].push_enabled ? 'checked' : ''}>
                                        <label class="form-check-label" for="${type}_push">Push</label>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="${type}_in_app" name="preferences[${type}][in_app_enabled]" ${data.preferences[type].in_app_enabled ? 'checked' : ''}>
                                        <label class="form-check-label" for="${type}_in_app">In-App</label>
                                    </div>
                                </div>
                            </div>
                        `;
                        categoryContainer.appendChild(preferenceDiv);
                    }
                });
            });
        })
        .catch(error => {
            console.error('Error loading preferences:', error);
            document.getElementById('preferences-container').innerHTML = '<div class="text-center py-4"><p class="text-danger">Failed to load preferences</p></div>';
        });
}

function getNotificationTypeName(type) {
    const names = {
        'auth.login.success': 'Successful Login',
        'auth.login.failed': 'Failed Login',
        'profile.updated': 'Profile Updated',
        'security.settings.updated': 'Security Settings Updated',
        'kyc.started': 'KYC Started',
        'kyc.completed': 'KYC Completed',
        'kyc.failed': 'KYC Failed',
        'transfer.quote.created': 'Quote Created',
        'transfer.quote.expired': 'Quote Expired',
        'transfer.initiated': 'Transfer Initiated',
        'transfer.payin.success': 'Payment Received',
        'transfer.payin.failed': 'Payment Failed',
        'transfer.payout.success': 'Transfer Completed',
        'transfer.payout.failed': 'Transfer Failed',
        'transfer.refund.initiated': 'Refund Initiated',
        'transfer.refund.completed': 'Refund Completed',
        'support.ticket.created': 'Support Ticket Created',
        'support.ticket.replied': 'Support Reply',
        'support.ticket.closed': 'Support Ticket Closed',
        'limits.warning.daily': 'Daily Limit Warning',
        'limits.warning.monthly': 'Monthly Limit Warning',
        'limits.exceeded': 'Limit Exceeded'
    };
    return names[type] || type;
}

document.getElementById('notification-preferences-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = {};
    
    // Convert form data to nested object
    for (let [key, value] of formData.entries()) {
        if (key.startsWith('preferences[')) {
            const match = key.match(/preferences\[([^\]]+)\]\[([^\]]+)\]/);
            if (match) {
                const type = match[1];
                const field = match[2];
                if (!data.preferences) data.preferences = {};
                if (!data.preferences[type]) data.preferences[type] = {};
                data.preferences[type][field] = value === 'on';
            }
        } else {
            data[key] = value === 'on';
        }
    }

    fetch('/notifications/preferences', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            const alert = document.createElement('div');
            alert.className = 'alert alert-success alert-dismissible fade show';
            alert.innerHTML = `
                ${data.message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.querySelector('.container').insertBefore(alert, document.querySelector('.container').firstChild);
        }
    })
    .catch(error => {
        console.error('Error saving preferences:', error);
        alert('Failed to save preferences');
    });
});

// Load preferences on page load
document.addEventListener('DOMContentLoaded', function() {
    loadPreferences();
});
</script>
@endsection


