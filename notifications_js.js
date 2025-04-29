// notifications.js
document.addEventListener('DOMContentLoaded', function() {
    // Notification bell icon
    const notificationBell = document.querySelector('.notification i');
    
    // Create notification counter
    const notificationCounter = document.createElement('span');
    notificationCounter.classList.add('notification-counter');
    
    // Create notifications dropdown
    const dropdownContainer = document.createElement('div');
    dropdownContainer.id = 'notifications-dropdown';
    dropdownContainer.classList.add('notifications-dropdown');
    
    // Check and update notifications
    function checkNotifications() {
        fetch('get_notifications.php', {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update notification count
                const unreadCount = data.unread_count;
                
                // Update bell icon
                updateNotificationBell(unreadCount);
                
                // Render notifications dropdown
                renderNotificationsDropdown(data.notifications);
            }
        })
        .catch(error => {
            console.error('Notification check failed:', error);
        });
    }

    // Update notification bell icon
    function updateNotificationBell(unreadCount) {
        // Remove existing counter
        const existingCounter = notificationBell.querySelector('.notification-counter');
        if (existingCounter) {
            notificationBell.removeChild(existingCounter);
        }
        
        // Add counter if unread notifications exist
        if (unreadCount > 0) {
            notificationCounter.textContent = unreadCount;
            notificationBell.appendChild(notificationCounter);
            
            // Animate bell for new notifications
            notificationBell.classList.add('notification-pulse');
        } else {
            notificationBell.classList.remove('notification-pulse');
        }
    }

    // Render notifications dropdown
    function renderNotificationsDropdown(notifications) {
        // Clear existing dropdown
        dropdownContainer.innerHTML = '';
        
        // Create dropdown header
        const dropdownHeader = document.createElement('div');
        dropdownHeader.classList.add('notifications-dropdown-header');
        dropdownHeader.innerHTML = `
            <h3>Notifications</h3>
            <a href="notifications.php" class="see-all">See All</a>
        `;
        dropdownContainer.appendChild(dropdownHeader);
        
        // Create notifications list
        const notificationsList = document.createElement('div');
        notificationsList.classList.add('notifications-list');
        
        // Handle empty state
        if (notifications.length === 0) {
            const emptyState = document.createElement('div');
            emptyState.classList.add('notifications-empty');
            emptyState.innerHTML = `
                <i class="fas fa-bell-slash"></i>
                <p>No new notifications</p>
            `;
            notificationsList.appendChild(emptyState);
        } else {
            // Render notifications
            notifications.forEach(notification => {
                const notificationItem = document.createElement('div');
                notificationItem.classList.add('notification-item');
                notificationItem.dataset.notificationId = notification.id;
                
                notificationItem.innerHTML = `
                    <div class="notification-icon">
                        <i class="${notification.icon}"></i>
                    </div>
                    <div class="notification-content">
                        <p class="notification-message">${notification.message}</p>
                        <span class="notification-time">${notification.formatted_time}</span>
                    </div>
                `;
                
                // Add click event to mark as read
                notificationItem.addEventListener('click', () => markNotificationAsRead(notification.id));
                
                notificationsList.appendChild(notificationItem);
            });
        }
        
        dropdownContainer.appendChild(notificationsList);
        
        // Add dropdown to body if not already added
        if (!document.body.contains(dropdownContainer)) {
            document.body.appendChild(dropdownContainer);
        }
        
        // Position dropdown relative to bell icon
        positionDropdown();
    }

    // Position dropdown relative to bell icon
    function positionDropdown() {
        if (!notificationBell) return;
        
        const bellRect = notificationBell.getBoundingClientRect();
        dropdownContainer.style.position = 'fixed';
        dropdownContainer.style.top = `${bellRect.bottom + 10}px`;
        dropdownContainer.style.right = `${window.innerWidth - bellRect.right}px`;
    }

    // Mark notification as read
    function markNotificationAsRead(notificationId) {
        const formData = new FormData();
        formData.append('action', 'mark_read');
        formData.append('notification_id', notificationId);

        fetch('notifications.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove notification from dropdown
                const notificationItem = dropdownContainer.querySelector(`[data-notification-id="${notificationId}"]`);
                if (notificationItem) {
                    notificationItem.remove();
                }
                
                // Recheck notifications to update counter
                checkNotifications();
            }
        })
        .catch(error => {
            console.error('Error marking notification as read:', error);
        });
    }

    // Toggle dropdown visibility
    function toggleNotificationsDropdown() {
        dropdownContainer.classList.toggle('notifications-dropdown-visible');
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        if (!notificationBell.contains(event.target) && 
            !dropdownContainer.contains(event.target)) {
            dropdownContainer.classList.remove('notifications-dropdown-visible');
        }
    });

    // Add click event to notification bell
    if (notificationBell) {
        notificationBell.addEventListener('click', toggleNotificationsDropdown);
    }

    // Initial check and setup periodic updates
    checkNotifications();
    setInterval(checkNotifications, 60000); // Check every minute

    // Add styles dynamically
    const styleElement = document.createElement('style');
    styleElement.textContent = `
        .notification-counter {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: red;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 10px;
            min-width: 16px;
            text-align: center;
        }

        .notification-pulse {
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        .notifications-dropdown {
            position: fixed;
            width: 300px;
            max-height: 400px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            overflow-y: auto;
            display: none;
            z-index: 1000;
        }

        .notifications-dropdown-visible {
            display: block;
        }

        .notifications-dropdown-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
        }

        .notifications-dropdown-header h3 {
            margin: 0;
            font-size: 16px;
        }

        .notifications-dropdown-header .see-all {
            color: #4285f4;
            text-decoration: none;
            font-size: 14px;
        }

        .notifications-list {
            max-height: 350px;
            overflow-y: auto;
        }

        .notification-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .notification-item:hover {
            background-color: #f5f5f5;
        }

        .notification-icon {
            margin-right: 15px;
            color: #4285f4;
            font-size: 24px;
        }

        .notification-content {
            flex-grow: 1;
        }

        .notification-message {
            margin: 0 0 5px 0;
            font-size: 14px;
            color: #333;
        }

        .notification-time {
            font-size: 12px;
            color: #888;
        }

        .notifications-empty {
            text-align: center;
            padding: 30px;
            color: #888;
        }

        .notifications-empty i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #ccc;
        }
    `;
    document.head.appendChild(styleElement);
});
