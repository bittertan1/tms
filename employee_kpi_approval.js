document.addEventListener('DOMContentLoaded', function() {
    // Get CSRF token from server or generate if not exists
    function getCSRFToken() {
        let csrfToken = document.querySelector('meta[name="csrf-token"]');
        return csrfToken ? csrfToken.getAttribute('content') : '';
    }

    // Create approval confirmation modal
    function createApprovalModal(kpiId, employeeName, kpiName) {
        // Remove any existing modals
        const existingModal = document.getElementById('approvalModal');
        if (existingModal) {
            existingModal.remove();
        }

        // Create modal HTML
        const modalHtml = `
            <div id="approvalModal" class="modal-overlay">
                <div class="modal-container">
                    <div class="modal-header">
                        <h2>Confirm KPI Approval</h2>
                        <button class="modal-close">&times;</button>
                    </div>
                    <div class="modal-content">
                        <p>Are you sure you want to approve the KPI for <strong>${employeeName}</strong>?</p>
                        <p>KPI: <em>${kpiName}</em></p>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-cancel">Cancel</button>
                        <button class="btn btn-approve">Approve</button>
                    </div>
                </div>
            </div>
        `;

        // Insert modal into document
        const modalContainer = document.createElement('div');
        modalContainer.innerHTML = modalHtml;
        document.body.appendChild(modalContainer.firstElementChild);

        // Get modal elements
        const modal = document.getElementById('approvalModal');
        const closeBtn = modal.querySelector('.modal-close');
        const cancelBtn = modal.querySelector('.btn-cancel');
        const approveBtn = modal.querySelector('.btn-approve');

        // Close modal functions
        function closeModal() {
            modal.classList.add('modal-hide');
            setTimeout(() => modal.remove(), 300);
        }

        // Event listeners
        closeBtn.addEventListener('click', closeModal);
        cancelBtn.addEventListener('click', closeModal);

        // Approve action
        approveBtn.addEventListener('click', function() {
            // Disable button to prevent multiple clicks
            approveBtn.disabled = true;
            approveBtn.textContent = 'Processing...';

            // Prepare form data
            const formData = new FormData();
            formData.append('id', kpiId);
            formData.append('action', 'approve');
            formData.append('csrf_token', getCSRFToken());

            // Send approval request
            fetch('approve_employee_kpi.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update UI
                    const row = document.querySelector(`tr[data-kpi-id="${kpiId}"]`);
                    if (row) {
                        const statusCell = row.querySelector('.status-badge');
                        statusCell.textContent = 'Approved';
                        statusCell.classList.remove('status-pending');
                        statusCell.classList.add('status-approved');
                    }

                    // Show success message
                    showNotification('success', data.message);
                    
                    // Close modal
                    closeModal();
                } else {
                    // Show error message
                    showNotification('error', data.message);
                }
            })
            .catch(error => {
                console.error('Approval Error:', error);
                showNotification('error', 'An unexpected error occurred');
            })
            .finally(() => {
                // Re-enable button
                approveBtn.disabled = false;
                approveBtn.textContent = 'Approve';
            });
        });

        return modal;
    }

    // Notification function
    function showNotification(type, message) {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;
        document.body.appendChild(notification);

        // Remove notification after 5 seconds
        setTimeout(() => {
            notification.classList.add('notification-hide');
            setTimeout(() => notification.remove(), 300);
        }, 5000);
    }

    // Attach event listeners to pending status badges
    function attachApprovalListeners() {
        const pendingBadges = document.querySelectorAll('.status-badge.status-pending');
        pendingBadges.forEach(badge => {
            badge.addEventListener('click', function() {
                const row = this.closest('tr');
                const kpiId = row.dataset.kpiId;
                const employeeName = row.querySelector('.employee-name').textContent;
                const kpiName = row.querySelector('.kpi-name').textContent;

                createApprovalModal(kpiId, employeeName, kpiName);
            });
        });
    }

    // Initial attachment of listeners
    attachApprovalListeners();

    // Optional: Re-attach listeners if table content is dynamically updated
    // You might want to use a MutationObserver or call this after table updates
});

// Styles for modal and notifications (you can move this to your CSS file)
const styleElement = document.createElement('style');
styleElement.textContent = `
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1000;
        opacity: 1;
        transition: opacity 0.3s ease;
    }

    .modal-overlay.modal-hide {
        opacity: 0;
    }

    .modal-container {
        background: white;
        border-radius: 8px;
        width: 400px;
        max-width: 90%;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px;
        border-bottom: 1px solid #e0e0e0;
    }

    .modal-header h2 {
        margin: 0;
        font-size: 18px;
    }

    .modal-close {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #666;
    }

    .modal-content {
        padding: 20px;
        text-align: center;
    }

    .modal-footer {
        display: flex;
        justify-content: center;
        padding: 15px;
        border-top: 1px solid #e0e0e0;
        gap: 15px;
    }

    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        transition: background-color 0.2s;
    }

    .btn-cancel {
        background-color: #f0f0f0;
        color: #333;
    }

    .btn-approve {
        background-color: #4CAF50;
        color: white;
    }

    .btn:disabled {
        opacity: 0.