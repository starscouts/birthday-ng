<?php

if (!isset($_GET["id"])) return;

date_default_timezone_set("Europe/Paris");
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
    $id = $client->AccederEtudiantParNumeroINE($_GET["id"]);
    $list = array_map(function ($i) use ($client) {
        return [
            "_id" => $i,
            "start" => date('c', strtotime($client->DatePremierJourBase()) + ($client->PlaceCours($i) * 86400)),
            "end" => date('c', strtotime($client->DatePremierJourBase()) + ($client->PlaceCours($i) * 86400) + ($client->DureeCours($i) * 86400)),
            "name" => $client->LibelleMatiere($client->MatiereCours($i)),
            "room" => $client->NomsTableauDeSalles($client->SallesDuCours($i)),
            "animators" => array_map(function ($i) use ($client) {
                return $client->NomEnseignant($i) . " " . $client->PrenomEnseignant($i);
            }, $client->EnseignantsDuCours($i))
        ];
    }, $client->CoursEtudiant($id));

    usort($list, function ($a, $b) {
        return strtotime($a["start"] ?? $a["eventStart"] ?? 0) - strtotime($b["start"] ?? $b["eventStart"] ?? 0);
    });

    $data["data"] = $list;
    $data["code"] = 0;
} catch (SoapFault $e) {
    var_dump($e);
    $data["code"] = 1;
    finish();
}

finish();
