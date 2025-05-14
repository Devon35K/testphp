<?php

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "SportOfficeDB";

// Connect to database
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    header("Location: ../view/adminView.php?message=" . urlencode("Database connection failed"));
    exit();
}

// Validate and get form data
$requiredFields = ['student_id', 'full_name', 'address', 'email', 'password', 'status'];
foreach ($requiredFields as $field) {
    if (empty($_POST[$field])) {
        header("Location: ../view/adminView.php?message=" . urlencode("Missing required field: $field"));
        exit();
    }
}

// Check if user is authorized to reset password (OTP verified)
if (!isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true) {
    // Redirect to forgot password page with error message
    header("Location: ../view/forgotPassView.php?error=unauthorized");
    exit;
}

// Process password reset
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $email = $_SESSION['reset_otp_email'] ?? '';

    // Validate inputs
    $errors = [];

    if (empty($new_password)) {
        $errors[] = "New password is required";
    } elseif (strlen($new_password) < 8) {
        $errors[] = "Password must be at least 8 characters";
    }

    if ($new_password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }

    if (empty($email)) {
        $errors[] = "Email session expired";
    }

    // If no errors, update password
    if (empty($errors)) {
        // Hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Determine if user is in admin or users table
        $stmt = $conn->prepare("CALL find_user_by_email(?)");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $role = $user['role'];
            $table = ($role === 'admin') ? 'admins' : 'users';

            // Update password in the appropriate table
            $update_stmt = $conn->prepare("UPDATE $table SET password = ? WHERE email = ?");
            $update_stmt->bind_param("ss", $hashed_password, $email);

            if ($update_stmt->execute()) {
                // Clear all session variables
                unset($_SESSION['reset_otp']);
                unset($_SESSION['reset_otp_email']);
                unset($_SESSION['reset_otp_time']);
                unset($_SESSION['otp_verified']);

                // Redirect with success message
                header("Location: ../view/loginView.php?success=password_reset");
                exit;
            } else {
                $errors[] = "Failed to update password: " . $conn->error;
            }
            $update_stmt->close();
        } else {
            $errors[] = "User not found";
        }
        $stmt->close();
    }

    // If we get here, there were errors
    $_SESSION['reset_errors'] = $errors;
    header("Location: forgotPassView.php?error=reset_failed");
    exit;
}

// If not a POST request, redirect to forgot password page
header("Location: forgotPassView.php");
exit;
?>