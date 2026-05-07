document.addEventListener('DOMContentLoaded', function() {
    console.log('Sidebar user info script loaded');
    
    // Find the info button
    const infoButton = document.querySelector('.sidebar-profile .profile-info-icon');
    
    if (!infoButton) {
        console.log('Info button not found, creating one...');
        const sidebarProfile = document.querySelector('.sidebar-profile');
        if (sidebarProfile) {
            const newInfoButton = document.createElement('button');
            newInfoButton.className = 'profile-info-icon';
            newInfoButton.setAttribute('type', 'button');
            newInfoButton.innerHTML = '<i class="ph ph-info"></i>';
            newInfoButton.style.cursor = 'pointer';
            sidebarProfile.appendChild(newInfoButton);
        }
    }
    
    // Re-select the button
    const infoBtn = document.querySelector('.sidebar-profile .profile-info-icon');
    
    if (!infoBtn) {
        console.error('Still cannot find info button');
        return;
    }
    
    console.log('Info button found:', infoBtn);
    
    // Create modal overlay
    const modalOverlay = document.createElement('div');
    modalOverlay.className = 'user-info-modal-overlay';
    
    // Create modal container
    const modalContainer = document.createElement('div');
    modalContainer.className = 'user-info-modal';
    
    modalContainer.innerHTML = `
        <div class="user-info-modal-header">
            <h3>
                <i class="ph ph-user-circle"></i>
                User Information
            </h3>
            <button class="user-info-modal-close">&times;</button>
        </div>
        <div class="user-info-modal-body">
            <div class="user-avatar-section">
                <div class="user-avatar-circle">
                    <i class="ph ph-user-circle"></i>
                </div>
                <h4 class="user-name">Admin User</h4>
                <span class="user-role-badge">Administrator</span>
            </div>
            
            <div class="user-details-section">
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="ph ph-envelope"></i>
                    </div>
                    <div class="detail-info">
                        <label>Email Address</label>
                        <p>admin@waranauniversity.edu</p>
                    </div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="ph ph-briefcase"></i>
                    </div>
                    <div class="detail-info">
                        <label>Role</label>
                        <p>Administrator</p>
                    </div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="ph ph-calendar"></i>
                    </div>
                    <div class="detail-info">
                        <label>Last Login</label>
                        <p>Today, 10:30 AM</p>
                    </div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="ph ph-identification-card"></i>
                    </div>
                    <div class="detail-info">
                        <label>User ID</label>
                        <p>ADM-2024-001</p>
                    </div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="ph ph-phone"></i>
                    </div>
                    <div class="detail-info">
                        <label>Phone Number</label>
                        <p>+91 98765 43210</p>
                    </div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="ph ph-buildings"></i>
                    </div>
                    <div class="detail-info">
                        <label>Department</label>
                        <p>University Administration</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="user-info-modal-footer">
            <button class="modal-btn modal-btn-primary" id="viewFullProfileBtn">
                <i class="ph ph-user"></i>
                View Full Profile
            </button>
            <button class="modal-btn modal-btn-secondary" id="closeModalBtn">
                Close
            </button>
        </div>
    `;
    
    modalOverlay.appendChild(modalContainer);
    document.body.appendChild(modalOverlay);
    
    // Add CSS styles
    const style = document.createElement('style');
    style.textContent = `
        /* Modal Overlay */
        .user-info-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            z-index: 9999;
            display: none;
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
        
        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        /* Modal Container */
        .user-info-modal {
            background: var(--white, #FFFFFF);
            border-radius: 16px;
            width: 90%;
            max-width: 450px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 35px rgba(58, 35, 89, 0.2);
            animation: slideUp 0.3s ease;
            position: relative;
        }
        
        /* Modal Header */
        .user-info-modal-header {
            background: linear-gradient(135deg, var(--primary-purple, #3A2359), var(--secondary-purple, #5A3A82));
            color: white;
            padding: 20px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 16px 16px 0 0;
        }
        
        .user-info-modal-header h3 {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-info-modal-header h3 i {
            font-size: 1.3rem;
        }
        
        .user-info-modal-close {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }
        
        .user-info-modal-close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }
        
        /* Modal Body */
        .user-info-modal-body {
            padding: 24px;
        }
        
        /* Avatar Section */
        .user-avatar-section {
            text-align: center;
            margin-bottom: 24px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--light-purple, #F4F0F9);
        }
        
        .user-avatar-circle {
            width: 80px;
            height: 80px;
            background: var(--light-purple, #F4F0F9);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            border: 3px solid var(--primary-purple, #3A2359);
        }
        
        .user-avatar-circle i {
            font-size: 3rem;
            color: var(--primary-purple, #3A2359);
        }
        
        .user-name {
            margin: 0 0 5px 0;
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--text-dark, #2D1A42);
        }
        
        .user-role-badge {
            display: inline-block;
            background: linear-gradient(135deg, var(--primary-purple, #3A2359), var(--secondary-purple, #5A3A82));
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        /* Details Section */
        .user-details-section {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        
        .detail-item {
            display: flex;
            gap: 15px;
            align-items: flex-start;
            padding: 12px;
            background: var(--light-purple, #F4F0F9);
            border-radius: 12px;
            transition: all 0.2s ease;
        }
        
        .detail-item:hover {
            transform: translateX(5px);
            background: #E9E3F5;
        }
        
        .detail-icon {
            width: 40px;
            height: 40px;
            background: white;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .detail-icon i {
            font-size: 1.2rem;
            color: var(--primary-purple, #3A2359);
        }
        
        .detail-info {
            flex: 1;
        }
        
        .detail-info label {
            display: block;
            font-size: 0.7rem;
            font-weight: 500;
            color: var(--text-light, #6B5B7D);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        
        .detail-info p {
            margin: 0;
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text-dark, #2D1A42);
        }
        
        /* Modal Footer */
        .user-info-modal-footer {
            padding: 20px 24px;
            background: var(--light-purple, #F4F0F9);
            border-radius: 0 0 16px 16px;
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }
        
        .modal-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            font-family: inherit;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .modal-btn-primary {
            background: var(--primary-purple, #3A2359);
            color: white;
        }
        
        .modal-btn-primary:hover {
            background: var(--secondary-purple, #5A3A82);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(58, 35, 89, 0.2);
        }
        
        .modal-btn-secondary {
            background: white;
            color: var(--text-dark, #2D1A42);
            border: 1px solid #E5E0EA;
        }
        
        .modal-btn-secondary:hover {
            background: var(--light-purple, #F4F0F9);
            transform: translateY(-2px);
        }
        
        .modal-btn:active {
            transform: translateY(0);
        }
        
        /* Scrollbar Styling */
        .user-info-modal::-webkit-scrollbar {
            width: 8px;
        }
        
        .user-info-modal::-webkit-scrollbar-track {
            background: var(--light-purple, #F4F0F9);
            border-radius: 10px;
        }
        
        .user-info-modal::-webkit-scrollbar-thumb {
            background: var(--secondary-purple, #5A3A82);
            border-radius: 10px;
        }
        
        /* Responsive */
        @media (max-width: 576px) {
            .user-info-modal {
                width: 95%;
                max-width: 95%;
            }
            
            .user-info-modal-header {
                padding: 15px 20px;
            }
            
            .user-info-modal-body {
                padding: 20px;
            }
            
            .detail-item {
                padding: 10px;
            }
            
            .user-info-modal-footer {
                padding: 15px 20px;
                flex-direction: column;
            }
            
            .modal-btn {
                justify-content: center;
            }
        }
    `;
    document.head.appendChild(style);
    
    // Function to open modal
    function openModal() {
        console.log('Opening modal...');
        modalOverlay.style.display = 'flex';
        document.body.style.overflow = 'hidden'; // Prevent background scrolling
    }
    
    // Function to close modal
    function closeModal() {
        console.log('Closing modal...');
        modalOverlay.style.display = 'none';
        document.body.style.overflow = ''; // Restore scrolling
    }
    
    // Add click event to info button
    if (infoBtn) {
        infoBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Info button clicked');
            openModal();
        });
    }
    
    // Close modal when clicking close button
    const closeBtn = modalContainer.querySelector('.user-info-modal-close');
    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }
    
    // Close modal when clicking secondary close button
    const closeModalBtn = document.getElementById('closeModalBtn');
    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', closeModal);
    }
    
    // Close modal when clicking overlay
    modalOverlay.addEventListener('click', function(e) {
        if (e.target === modalOverlay) {
            closeModal();
        }
    });
    
    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modalOverlay.style.display === 'flex') {
            closeModal();
        }
    });
    
    // Handle view profile button
    const viewProfileBtn = document.getElementById('viewFullProfileBtn');
    if (viewProfileBtn) {
        viewProfileBtn.addEventListener('click', function() {
            alert('Redirecting to full profile page...');
            // Uncomment to redirect
            // window.location.href = 'profile.php';
            closeModal();
        });
    }
    
    console.log('Modal setup complete');
});