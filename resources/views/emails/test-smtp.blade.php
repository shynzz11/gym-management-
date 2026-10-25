<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SMTP Test Email</title>
</head>
<body>
    <p>This is a test email from {{ $senderName }}.</p>
    <p>If you received this message, your SMTP configuration is working correctly.</p>
    <p>Sent at: {{ now()->toDateTimeString() }}</p>
</body>
</html>
