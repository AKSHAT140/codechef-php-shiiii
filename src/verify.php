<?php
require_once 'functions.php';

$msg = '';
if (isset($_GET['email']) && isset($_GET['code'])) {
    $email = $_GET['email'];
    $code = $_GET['code'];
    if (verifySubscription($email, $code)) {
        $msg = 'Your email has been verified! You will now receive reminders.';
    } else {
        $msg = 'Verification failed. Invalid or expired code.';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Email Verification</title>
</head>
<body>
    <h2 id="verification-heading">Email Verification</h2>
    <p><?=htmlspecialchars($msg)?></p>
</body>
</html>