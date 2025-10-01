@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 mb-0">Notifications</h1>
        <div>
            <button class="btn btn-outline-primary btn-sm" onclick="markAllAsRead()">
                Mark All as Read
            </button>
            <a href="{{ route('notifications.preferences') }}" class="btn btn-outline-secondary btn-sm">
                Settings
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div id="notifications-container">
        <div class="text-center py-4">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>

    <div id="load-more-container" class="text-center mt-4" style="display: none;">
        <button class="btn btn-outline-primary" onclick="loadMore()">Load More</button>
    </div>
</div>

<script>
let currentPage = 1;
let isLoading = false;

function loadNotifications(page = 1) {
    if (isLoading) return;
    
    isLoading = true;
    const container = document.getElementById('notifications-container');
    
    if (page === 1) {
        container.innerHTML = '<div class="text-center py-4"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';
    }

    fetch(`/notifications?page=${page}&per_page=20`)
        .then(response => response.json())
        .then(data => {
            if (page === 1) {
                container.innerHTML = '';
            }
            
            if (data.notifications.length === 0 && page === 1) {
                container.innerHTML = '<div class="text-center py-5"><p class="text-muted">No notifications yet</p></div>';
                return;
            }

            data.notifications.forEach(notification => {
                const notificationElement = createNotificationElement(notification);
                container.appendChild(notificationElement);
            });

            // Show/hide load more button
            const loadMoreContainer = document.getElementById('load-more-container');
            if (data.pagination.current_page < data.pagination.last_page) {
                loadMoreContainer.style.display = 'block';
            } else {
                loadMoreContainer.style.display = 'none';
            }

            currentPage = page;
        })
        .catch(error => {
            console.error('Error loading notifications:', error);
            if (page === 1) {
                container.innerHTML = '<div class="text-center py-5"><p class="text-danger">Failed to load notifications</p></div>';
            }
        })
        .finally(() => {
            isLoading = false;
        });
}

function createNotificationElement(notification) {
    const div = document.createElement('div');
    div.className = `card mb-3 ${notification.read_at ? '' : 'border-primary'}`;
    div.innerHTML = `
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
                <div class="flex-grow-1">
                    <h6 class="card-title mb-1">${notification.title}</h6>
                    <p class="card-text text-muted mb-2">${notification.message}</p>
                    <small class="text-muted">${new Date(notification.created_at).toLocaleString()}</small>
                </div>
                <div class="ms-3">
                    ${notification.read_at ? '' : '<span class="badge bg-primary">New</span>'}
                    <button class="btn btn-sm btn-outline-secondary ms-2" onclick="markAsRead(${notification.id})">
                        Mark as Read
                    </button>
                </div>
            </div>
        </div>
    `;
    return div;
}

function markAsRead(notificationId) {
    fetch(`/notifications/${notificationId}/read`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload notifications to update UI
            loadNotifications(1);
        }
    })
    .catch(error => {
        console.error('Error marking notification as read:', error);
    });
}

function markAllAsRead() {
    if (confirm('Mark all notifications as read?')) {
        fetch('/notifications/read-all', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadNotifications(1);
            }
        })
        .catch(error => {
            console.error('Error marking all notifications as read:', error);
        });
    }
}

function loadMore() {
    loadNotifications(currentPage + 1);
}

// Load notifications on page load
document.addEventListener('DOMContentLoaded', function() {
    loadNotifications(1);
});
</script>
@endsection
