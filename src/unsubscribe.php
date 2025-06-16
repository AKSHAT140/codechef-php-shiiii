<?php
require_once 'functions.php';

$msg = '';
if (isset($_GET['email'])) {
    $email = $_GET['email'];
    if (unsubscribeEmail($email)) {
        $msg = 'You have been unsubscribed successfully.';
    } else {
        $msg = 'Unsubscription failed or email not found.';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Unsubscribe</title>
</head>
<body>
    <h2 id="unsubscription-heading">Unsubscribe from Task Updates</h2>
    <p><?=htmlspecialchars($msg)?></p>
</body>
</html>
