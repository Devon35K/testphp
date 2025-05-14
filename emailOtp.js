document.addEventListener('DOMContentLoaded', function() {
    const sendOtpBtn = document.getElementById('send-otp-btn');
    const verifyBtn = document.getElementById('verify-btn');
    const emailInput = document.getElementById('email');
    const otpInput = document.getElementById('otp_inp');
    const otpVerifyDiv = document.querySelector('.otpverify');
    const resetPasswordModal = document.getElementById('resetPasswordModal');
    const resetPasswordForm = document.getElementById('resetPasswordForm');

    // Function to show SweetAlert
    function showAlert(icon, title, text) {
        Swal.fire({
            icon: icon,
            title: title,
            text: text,
            confirmButtonColor: '#800000'
        });
    }

    // Send OTP button click event
    if (sendOtpBtn) {
        sendOtpBtn.addEventListener('click', function() {
            const email = emailInput.value.trim();

            // Basic email validation
            if (!email) {
                showAlert('error', 'Error', 'Please enter an email address');
                return;
            }

            if (!isValidEmail(email)) {
                showAlert('error', 'Error', 'Please enter a valid email address');
                return;
            }

            // Show loading state
            sendOtpBtn.disabled = true;
            sendOtpBtn.textContent = 'Sending...';

            // Send AJAX request to send OTP
            fetch('../controller/emailOTP.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=send_otp&email=' + encodeURIComponent(email)
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showAlert('success', 'Success', 'OTP sent successfully! Please check your email.');
                        otpVerifyDiv.style.display = 'flex';
                        sendOtpBtn.textContent = 'Resend OTP';
                        sendOtpBtn.disabled = false;
                    } else {
                        showAlert('error', 'Error', data.message || 'Failed to send OTP');
                        sendOtpBtn.textContent = 'Send OTP';
                        sendOtpBtn.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('error', 'Error', 'Something went wrong. Please try again.');
                    sendOtpBtn.textContent = 'Send OTP';
                    sendOtpBtn.disabled = false;
                });
        });
    }

    // Verify OTP button click event
    if (verifyBtn) {
        verifyBtn.addEventListener('click', function() {
            const otp = otpInput.value.trim();

            // Basic OTP validation
            if (!otp) {
                showAlert('error', 'Error', 'Please enter the OTP');
                return;
            }

            if (otp.length !== 6 || !/^\d+$/.test(otp)) {
                showAlert('error', 'Error', 'OTP must be 6 digits');
                return;
            }

            // Show loading state
            verifyBtn.disabled = true;
            verifyBtn.textContent = 'Verifying...';

            // Send AJAX request to verify OTP
            fetch('../controller/emailOTP.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=verify_otp&otp=' + encodeURIComponent(otp)
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showAlert('success', 'Success', 'OTP verified successfully!');
                        resetPasswordModal.style.display = 'block';
                    } else {
                        showAlert('error', 'Error', data.message || 'Invalid OTP');
                        verifyBtn.disabled = false;
                        verifyBtn.textContent = 'Verify OTP';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('error', 'Error', 'Something went wrong. Please try again.');
                    verifyBtn.disabled = false;
                    verifyBtn.textContent = 'Verify OTP';
                });
        });
    }

    // Reset password form validation
    if (resetPasswordForm) {
        resetPasswordForm.addEventListener('submit', function(e) {
            const newPassword = resetPasswordForm.querySelector('input[name="new_password"]').value;
            const confirmPassword = resetPasswordForm.querySelector('input[name="confirm_password"]').value;

            if (newPassword.length < 8) {
                e.preventDefault();
                showAlert('error', 'Error', 'Password must be at least 8 characters');
                return;
            }

            if (newPassword !== confirmPassword) {
                e.preventDefault();
                showAlert('error', 'Error', 'Passwords do not match');
                return;
            }

            // Add hidden email field to form
            const emailField = document.createElement('input');
            emailField.type = 'hidden';
            emailField.name = 'email';
            emailField.value = emailInput.value;
            resetPasswordForm.appendChild(emailField);
        });
    }

    // Helper function to validate email
    function isValidEmail(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    }

    // Close modal function
    window.closeResetModal = function() {
        resetPasswordModal.style.display = 'none';
    };
});