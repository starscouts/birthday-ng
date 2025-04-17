<?php

if (!isset($_GET["id"])) return;

$app = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/includes/app.json"), true);
$client = new SoapClient($app["soap"]["url"], array('login' => $app["soap"]["id"],'password' => $app["soap"]["password"]));

try {
    $id = $client->AccederEtudiantParNumeroINE($_GET["id"]);
    $data = $client->PhotoEtudiant($id);

    header("Content-Type: image/jpeg");
    header("Content-Length: " . strlen($data));

    die($data);
} catch (SoapFault) {
    die;
}
