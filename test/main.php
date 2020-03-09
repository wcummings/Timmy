<?php

$host = $argv[1];

$response = simulateWebhook($host, 'webhook1');
echo $response;

function simulateWebhook($host, $file) {
    $body = file_get_contents('test/files/' . $file . '.json');
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $host . '/timmy/bullshitcard.php');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
    $result = curl_exec($ch);

    // Get response
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $host . '/timmy/test_webhook.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
    $result = curl_exec($ch);

    return $result;
}
?>
