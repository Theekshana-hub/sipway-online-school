<?php
require_once __DIR__ . '/paycorp-config.php';

function paycorpUuid() {
    $d = random_bytes(16);
    $d[6] = chr(ord($d[6]) & 0x0f | 0x40);
    $d[8] = chr(ord($d[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($d), 4));
}

/** Paycorp REST proxy එකට request යවනවා. Fail වුණොත් Exception. */
function paycorpCall($operation, array $requestData) {
    $payload = [
        'version'      => '1.5',
        'msgId'        => paycorpUuid(),
        'operation'    => $operation,
        'requestDate'  => date('Y-m-d\TH:i:s.000'),
        'validateOnly' => false,
        'requestData'  => $requestData,
    ];
    $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $hmac = hash_hmac('sha256', $json, PAYCORP_HMAC_SECRET);

    $ch = curl_init(PAYCORP_ENDPOINT);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $json,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json; charset=UTF-8',
            'HMAC: ' . $hmac,
            'AUTHTOKEN: ' . PAYCORP_AUTH_TOKEN,
        ],
    ]);
    if (PAYCORP_CA_BUNDLE !== '') curl_setopt($ch, CURLOPT_CAINFO, PAYCORP_CA_BUNDLE);
    $res  = curl_exec($ch);
    $err  = curl_error($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($res === false) throw new Exception('Gateway connection failed: ' . $err);
    $data = json_decode($res, true);
    if (!is_array($data)) throw new Exception('Invalid gateway response (HTTP ' . $http . '): ' . mb_substr(strip_tags($res), 0, 300));
    if ($http >= 400 && empty($data['error'])) throw new Exception('HTTP ' . $http . ': ' . mb_substr($res, 0, 300));
    if (!empty($data['error'])) {
        $msg = is_array($data['error']) ? ($data['error']['message'] ?? json_encode($data['error'])) : $data['error'];
        throw new Exception('Gateway error: ' . $msg);
    }
    return $data['responseData'] ?? [];
}

/** Payment page URL එක ගන්නවා. amount = LKR (cents වලට මෙතන convert වෙනවා) */
function paycorpInit($orderNo, $amountLkr, $returnUrl, $comment = '') {
    $cents = (int)round($amountLkr * 100);
    return paycorpCall('PAYMENT_INIT', [
        'clientId'          => PAYCORP_CLIENT_ID,
        'transactionType'   => 'PURCHASE',
        'transactionAmount' => [
            'totalAmount'      => $cents,
            'serviceFeeAmount' => 0,
            'paymentAmount'    => $cents,
            'currency'         => PAYCORP_CURRENCY,
        ],
        'redirect'          => ['returnUrl' => $returnUrl, 'returnMethod' => 'GET'],
        'clientRef'         => $orderNo,
        'comment'           => $comment,
        'tokenize'          => false,
        'useReliability'    => true,
        'extraData'         => new stdClass(),
    ]);
}

function paycorpComplete($reqid) {
    return paycorpCall('PAYMENT_COMPLETE', [
        'clientId' => PAYCORP_CLIENT_ID,
        'reqid'    => $reqid,
    ]);
}