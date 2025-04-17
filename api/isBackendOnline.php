<?php

header("Content-Type: application/json");
$data = [
    "code" => 2,
    "data" => null
];

function finish() {
    global $data;
    echo(json_encode($data, JSON_PRETTY_PRINT));
    die;
}

$app = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/includes/app.json"), true);
$client = new SoapClient($app["soap"]["url"], array('login' => $app["soap"]["id"],'password' => $app["soap"]["password"]));

try {
    $id = $client->DatePremierJourBase();
    $data["code"] = 0;
} catch (SoapFault) {
    $data["code"] = 1;
    finish();
}

finish();
