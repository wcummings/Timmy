<?php

$host = $argv[1];

echo "Test 1:\n";
$response = simulateWebhook($host, 'webhook1');
assertEquals(":star: *Will:* 0\\n", trim($response));

function assertEquals($expected, $response) {
    if ($expected !== $response) {
        throw new Exception($expected . " !== " . $response);
    } else {
        echo $expected . ' = ' . $response . "\n";
    }
}

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
