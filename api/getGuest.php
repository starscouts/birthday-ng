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

    $data["data"] = [
        "_code" => $client->NumeroINEEtudiant($id),
        "_id" => $id,
        "_webId" => $client->IdentifiantConnexionEtudiant($id),
        "_registrationId" => $client->NumeroDOrdreEtudiant($id),
        "annivPlus" => in_array($app["soap"]["annivPlus"], $client->TDOptionsEtudiantALaDate($id, date('c'))),
        "name" => $client->NomEtudiant($id) . " " . $client->PrenomEtudiant($id),
        "birthday" => $client->DateDeNaissanceEtudiant($id),
        "registration" => $client->DateInscriptionEtudiant($id),
        "email" => $client->EMailEtudiant($id),
        "gender" => $client->SexeEtudiant($id),
        "address" => [
            trim($client->Adresse1Etudiant($id)),
            trim($client->CodePostalEtudiant($id) . " " . $client->VilleEtudiant($id)),
            trim($client->PaysEtudiant($id))
        ],
        "phone" => $client->TelephonePortableEtudiant($id),
        "sms" => $client->AutorisationReceptionSMSEtudiant($id),
        "categories" => $client->NomsTableauDePromotions($client->PromotionsEtudiant($id)),
        "groups" => $client->NomsTableauDeRegroupements($client->RegroupementsEtudiant($id)),
        "currentActivity" => array_map(function ($i) use ($client) {
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
        }, $client->CoursEtudiant($id)), function ($i) {
            return strtotime($i["start"]) - 300 < time() && strtotime($i["end"]) + 60 > time();
        })))[0] ?? null
    ];
    $data["code"] = 0;
} catch (SoapFault) {
    $data["code"] = 1;
    finish();
}

finish();
