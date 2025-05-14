<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "SportOfficeDB";

// Connect to database
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    header("Location: forgotPassView.php?message=" . urlencode("Database connection failed"));
    exit();
}




use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

header('Content-Type: application/json');

// Function to generate random OTP
function generateOTP($length = 6) {
    $characters = '0123456789';
    $otp = '';
    for ($i = 0; $i < $length; $i++) {
        $otp .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $otp;
}

// Function to send OTP email
function sendOTPEmail($email, $otp) {
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'tagummabinisportoffice@gmail.com';
        $mail->Password = 'wecx ezju zcin ymmn'; // Use your actual app password
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;

        // Recipients
        $mail->setFrom('tagummabinisportoffice@gmail.com', 'USeP Sports Office');
        $mail->addAddress($email);
        $mail->addReplyTo('tagummabinisportoffice@gmail.com', 'USeP Sports Office');

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset OTP - USeP Sports Office';
        $mail->Body = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
                    .header { background-color: #800000; color: white; padding: 15px; text-align: center; border-radius: 5px 5px 0 0; }
                    .content { padding: 20px; }
                    .otp-code { font-size: 24px; font-weight: bold; text-align: center; margin: 20px 0; padding: 10px; background-color: #f5f5f5; border-radius: 5px; letter-spacing: 5px; }
                    .footer { margin-top: 20px; font-size: 12px; color: #777; text-align: center; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>USeP OSAS-Sports Unit</h2>
                    </div>
                    <div class='content'>
                        <h3>Password Reset Request</h3>
                        <p>You have requested to reset your password. Please use the following OTP code to verify your identity:</p>
                        <div class='otp-code'>$otp</div>
                        <p>This code will expire in 15 minutes. If you did not request this password reset, please ignore this email.</p>
                    </div>
                    <div class='footer'>
                        <p>© " . date('Y') . " USeP OSAS-Sports Unit. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer Error sending OTP: {$mail->ErrorInfo}");
        return false;
    }
}

// Handle AJAX requests
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'send_otp':
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

            if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid email address']);
                exit;
            }

            // Check if email exists in the database
            $stmt = $conn->prepare("CALL find_user_by_email(?)");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 0) {
                echo json_encode(['status' => 'error', 'message' => 'Email not registered']);
                exit;
            }

            // Generate OTP and store in session
            $otp = generateOTP();
            $_SESSION['reset_otp'] = $otp;
            $_SESSION['reset_otp_email'] = $email;
            $_SESSION['reset_otp_time'] = time();

            // Send OTP email
            if (sendOTPEmail($email, $otp)) {
                echo json_encode(['status' => 'success', 'message' => 'OTP sent successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to send OTP email']);
            }
            break;

        case 'verify_otp':
            $entered_otp = trim($_POST['otp']);

            // Check if OTP session exists and is not expired (15 minutes)
            if (!isset($_SESSION['reset_otp']) || !isset($_SESSION['reset_otp_time']) ||
                (time() - $_SESSION['reset_otp_time'] > 900)) {
                echo json_encode(['status' => 'error', 'message' => 'OTP expired or invalid session']);
                exit;
            }

            // Verify OTP
            if ($entered_otp == $_SESSION['reset_otp']) {
                $_SESSION['otp_verified'] = true;
                echo json_encode(['status' => 'success', 'message' => 'OTP verified successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Invalid OTP']);
            }
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>