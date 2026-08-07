<?php
$ch = curl_init('https://pay.pesapal.com/v3/api/Auth/RequestToken');
$payload = json_encode(['consumer_key' => 'u+nAfIIT/y0vtZdwd4ypMumdpUUPmyYm', 'consumer_secret' => 'r2hNkSEMzL9P4ByZiltITGsj/3g=']);
$headers = ['Accept: application/json', 'Content-Type: application/json'];
if (false === curl_setopt($ch, CURLOPT_RETURNTRANSFER, true)) { echo 'setopt return failed\n'; }
if (false === curl_setopt($ch, CURLOPT_TIMEOUT, 30)) { echo 'timeout failed\n'; }
if (false === curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false)) { echo 'ssl verify failed\n'; }
if (false === curl_setopt($ch, CURLOPT_POSTFIELDS, $payload)) { echo 'postfields failed\n'; }
if (false === curl_setopt($ch, CURLOPT_HTTPHEADER, $headers)) { echo 'headers failed\n'; }
if (false === curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST')) { echo 'customrequest failed\n'; }
$response = curl_exec($ch);
$info = curl_getinfo($ch);
$error = curl_error($ch);
curl_close($ch);
echo 'HTTP: ' . $info['http_code'] . "\n";
echo 'ERROR: ' . $error . "\n";
echo 'BODY: ' . $response . "\n";
