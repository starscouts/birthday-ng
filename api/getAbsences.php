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
    $ret = [];

    $pre = [
        ...array_map(function ($i) use ($client) {
            return [
                "type" => "absence",
                "_id" => $i,
                "start" => $client->DateHeureDebutAbsenceEtudiant($i),
                "end" => $client->DateHeureFinAbsenceEtudiant($i),
                "duration" => null,
                "justified" => $client->AbsenceEtudiantEstJustifiee($i),
                "reason" => $client->MotifAbsenceEtudiant($i),
                "activities" => array_map(function ($i) use ($client) {
                    return [
                        "_id" => $i,
                        "start" => date('c', strtotime($client->DatePremierJourBase()) + ($client->PlaceCours($i) * 86400)),
                        "end" => date('c', strtotime($client->DatePremierJourBase()) + ($client->PlaceCours($i) * 86400) + ($client->DureeCours($i) * 86400)),
                        "name" => $client->LibelleMatiere($client->MatiereCours($i)),
                        "room" => $client->NomsTableauDeSalles($client->SallesDuCours($i))
                    ];
                }, $client->CoursManquesAbsenceEtudiant($i))
            ];
        }, $client->AbsencesEtudiantEntre2Dates($id, $client->DatePremierJourBase(), date('c'))),
        ...array_map(function ($i) use ($client, $id) {
            $s = strtotime(substr($client->DateHeureRetardEtudiant($i), 0, -1));

            return [
                "type" => "delay",
                "_id" => $i,
                "start" => null,
                "end" => null,
                "duration" => round($client->DureeRetardEtudiant($i) * 86400),
                "justified" => $client->RetardEtudiantEstJustifie($i),
                "reason" => $client->MotifRetardEtudiant($i),
                "activities" => array_map(function ($i) use ($client) {
                    $id = $i["_id"];

                    return [
                        ...$i,
                        "name" => $client->LibelleMatiere($client->MatiereCours($id)),
                        "room" => $client->NomsTableauDeSalles($client->SallesDuCours($id))
                    ];
                }, array_values(array_filter(array_map(function ($i) use ($client) {
                    return [
                        "start" => date('c', strtotime($client->DatePremierJourBase()) + ($client->PlaceCours($i) * 86400)),
                        "end" => date('c', strtotime($client->DatePremierJourBase()) + ($client->PlaceCours($i) * 86400) + ($client->DureeCours($i) * 86400)),
                        "_id" => $i
                    ];
                }, $client->CoursEtudiantEntre2Dates($id, $client->DatePremierJourBase(), date('c'))), function ($i) use ($s) {
                    return strtotime($i["start"]) === $s;
                })))
            ];
        }, $client->RetardsEtudiantEntre2Dates($id, $client->DatePremierJourBase(), date('c'))),
    ];

    foreach ($pre as $item) {
        foreach ($item["activities"] as $activity) {
            $i = [
                "_id" => $item["_id"],
                "_activityId" => $activity["_id"],
                "type" => $item["type"],
                "eventStart" => $item["start"],
                "eventEnd" => $item["end"],
                "eventDuration" => $item["duration"],
                "justified" => $item["justified"],
                "reason" => $item["reason"],
                "start" => $activity["start"],
                "end" => $activity["end"],
                "name" => $activity["name"],
                "room" => $activity["room"]
            ];

            $ret[] = $i;
        }
    }

    usort($ret, function ($a, $b) {
        return strtotime($b["start"] ?? $b["eventStart"] ?? 0) - strtotime($a["start"] ?? $a["eventStart"] ?? 0);
    });

    $data["data"] = $ret;
    $data["code"] = 0;
} catch (SoapFault $e) {
    var_dump($e);
    $data["code"] = 1;
    finish();
}

finish();
