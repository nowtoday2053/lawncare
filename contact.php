<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = $_POST['name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $service = $_POST['service'] ?? '';
    $message = $_POST['message'] ?? '';
    
    // Validate required fields
    if (empty($name) || empty($phone) || empty($address) || empty($service)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }
    
    // Your Twilio credentials (replace with your actual credentials)
    $twilioSid = 'YOUR_TWILIO_SID';
    $twilioToken = 'YOUR_TWILIO_TOKEN';
    $twilioNumber = '+1234567890'; // Your Twilio phone number
    $yourPhoneNumber = '+1234567890'; // Your business phone number
    
    // Create SMS message
    $smsMessage = "🌱 NEW LAWN CARE LEAD!\n\n";
    $smsMessage .= "Name: {$name}\n";
    $smsMessage .= "Phone: {$phone}\n";
    $smsMessage .= "Address: {$address}\n";
    $smsMessage .= "Service: {$service}\n";
    $smsMessage .= "Message: {$message}\n\n";
    $smsMessage .= "💰 URGENT: Contact within 5 minutes for best conversion!";
    
    // Send SMS via Twilio
    $url = "https://api.twilio.com/2010-04-01/Accounts/{$twilioSid}/Messages.json";
    
    $data = [
        'From' => $twilioNumber,
        'To' => $yourPhoneNumber,
        'Body' => $smsMessage
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, "{$twilioSid}:{$twilioToken}");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Also save to database (optional)
    try {
        $pdo = new PDO('sqlite:leads.db');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create table if it doesn't exist
        $pdo->exec("CREATE TABLE IF NOT EXISTS leads (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            phone TEXT NOT NULL,
            address TEXT NOT NULL,
            service TEXT NOT NULL,
            message TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Insert lead
        $stmt = $pdo->prepare("INSERT INTO leads (name, phone, address, service, message) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $phone, $address, $service, $message]);
    } catch (PDOException $e) {
        // Log error but don't fail the request
        error_log("Database error: " . $e->getMessage());
    }
    
    if ($httpCode === 201) {
        echo json_encode([
            'success' => true,
            'message' => "Thanks {$name}! We'll text you a quote at {$phone} within 5 minutes!"
        ]);
    } else {
        // Fallback: send email instead
        $to = 'your-email@zenlawncare.com';
        $subject = '🌱 NEW LAWN CARE LEAD - ' . $name;
        $emailMessage = "New lead from website:\n\n";
        $emailMessage .= "Name: {$name}\n";
        $emailMessage .= "Phone: {$phone}\n";
        $emailMessage .= "Address: {$address}\n";
        $emailMessage .= "Service: {$service}\n";
        $emailMessage .= "Message: {$message}\n";
        
        mail($to, $subject, $emailMessage);
        
        echo json_encode([
            'success' => true,
            'message' => "Thanks {$name}! We'll contact you within 5 minutes!"
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?> 