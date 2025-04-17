<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Page</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>
<body style="height: 100vh;">
    <div class="container" id="error" style="display: none;">
        <div class="alert alert-danger" style="margin-top: 20px;">
            Une erreur s'est produite lors de la lecture de la carte sans contact. Assurez-vous qu'il s'agit d'une carte valide, qu'elle a bien été activée et que rien ne bloque le lien avec la carte.
        </div>
    </div>
    <div class="container" id="error2" style="display: none;">
        <div class="alert alert-danger" style="margin-top: 20px;">
            Cette carte est valide, mais une erreur s'est produite lors de la récupération d'informations sur ce membre. Cet invité pourrait avoir été exclu ou n'a pas été enregistré.
        </div>
    </div>
    <div class="container" id="error3" style="display: none;">
        <div class="alert alert-danger" style="margin-top: 20px;">
            Cette carte est valide, mais le serveur de programmation est actuellement indisponible. Veuillez contacter le personnel administratif immédiatement. Il s'agit d'une urgence.
        </div>
    </div>
    <div id="success" class="container" style="display: none; margin-top: 20px;"></div>
    <div id="wait" style="display: flex; align-items: center; justify-content: center; height: 100%;">
        <p style="text-align: center;"><i>Passez une carte d'anniversaire sans contact sur le dos de l'appareil.</i></p>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        window.onbirthdayid = (id) => {
            document.getElementById("wait").style.display = "none";

            if (id) {
                lookupUserByID(id);
            } else {
                document.getElementById("success").style.display = "none";
                document.getElementById("error").style.display = "";
                document.getElementById("error2").style.display = "none";
                document.getElementById("error3").style.display = "none";
            }
        }

        async function lookupUserByID(id) {
            document.getElementById("wait").style.display = "none";
            document.getElementById("success").style.display = "";
            document.getElementById("error").style.display = "none";
            document.getElementById("error2").style.display = "none";
            document.getElementById("error3").style.display = "none";
            document.getElementById("success").innerHTML = `Récupération des informations...`;

            window.isOnline = (await (await fetch(`/api/isBackendOnline.php?_=${btoa(crypto.getRandomValues(new Uint8Array(8)))}`)).json())['code'] === 0;
            window.data = await (await fetch(`/api/getGuest.php?id=${encodeURIComponent(id)}&_=${btoa(crypto.getRandomValues(new Uint8Array(8)))}`)).json();

            if (!window.isOnline) {
                document.getElementById("wait").style.display = "none";
                document.getElementById("success").style.display = "none";
                document.getElementById("error").style.display = "none";
                document.getElementById("error2").style.display = "none";
                document.getElementById("error3").style.display = "";
                return;
            }

            if (window.data.code === 0) {
                document.getElementById("wait").style.display = "none";
                document.getElementById("success").style.display = "";
                document.getElementById("error").style.display = "none";
                document.getElementById("error2").style.display = "none";
                document.getElementById("error3").style.display = "none";

                window.data = window.data.data;

                document.getElementById("success").innerHTML = `
<div style="display: grid; grid-template-columns: 128px 1fr; grid-gap: 20px;">
    <img alt="Photo" src="/api/getGuestPhoto.php?id=${id}&_=${btoa(crypto.getRandomValues(new Uint8Array(8)))}" style="width: 128px; background-color: rgba(0, 0, 0, .25); aspect-ratio: 245/314;">
    <div style="display: flex; align-items: center;">
        <div>
            <div style="margin-bottom: 10px;"><b>${data.name}</b>${data.annivPlus ? " · + Passe Anniv'Plus" : ""}</div>
            <div><b>Date naiss. :</b> ${new Date(data.birthday).toLocaleDateString('fr-FR', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                })} (${Math.floor((new Date().getTime() - new Date(data.birthday)) / 86400000 / 365.25)} ans)</div>
            <div><b>Activité :</b> ${data.currentActivity ? `${data.currentActivity.name}` : "-"}</div>
            <div><b>Salle :</b> ${data.currentActivity ? `${data.currentActivity.room.length > 0 ? `${data.currentActivity.room.join(", ")}` : `-`}` : "-"}</div>
            <div><b>Quitte l'activité dans :</b> ${data.currentActivity ? `${Math.floor((new Date(data.currentActivity.end).getTime() - new Date().getTime()) / 60000)} min` : "-"}</div>
        </div>
    </div>
</div>
<hr>

<h4 style="margin-top: 20px;">Absences aux activités précédentes</h4>
<div id="member-${id}-events">Chargement...</div>

<h4 style="margin-top: 20px;">Activités inscrites</h4>
<div id="member-${id}-activities">Chargement...</div>

<h4 style="margin-top: 20px;">Contacter cet invité</h4>
<div class="list-group" style="padding-bottom: 50px;">
    <a class="list-group-item list-group-item-action" href="mailto:${data.email}"><b>Courriel :</b> ${data.email}</a>
    <a class="list-group-item list-group-item-action" href="tel:${data.phone}">
        <b>Téléphone :</b> ${data.phone}
        ${!data.sms ? `<div class="alert alert-warning" style="margin-bottom: 0; margin-top: .5rem;"><b>Note :</b> Cet invité n'a pas autorisé la réception d'alertes par SMS.</div>` : ""}
    </a>
    <a class="list-group-item"><b>Ident. invité :</b> ${data._code}</a>
    <a class="list-group-item"><b>Ident. inscr. :</b> ${data._registrationId}</a>
    <a class="list-group-item"><b>Ident. Hyp:</b> ${data._id} (.net: ${data._webId})</a>
</div>
                `;

                try {
                    window.data = await (await fetch(`/api/getAbsences.php?id=${encodeURIComponent(id)}&_=${btoa(crypto.getRandomValues(new Uint8Array(8)))}`)).json();

                    if (window.data.code === 0) {
                        document.getElementById("wait").style.display = "none";
                        document.getElementById("success").style.display = "";
                        document.getElementById("error").style.display = "none";
                        document.getElementById("error2").style.display = "none";
                        document.getElementById("error3").style.display = "none";

                        window.data = window.data.data;
                        if (window.data.length === 0) {
                            document.getElementById("member-" + id + "-events").innerHTML = `Cet invité n'a aucune absence ou retard pour l'instant.`;
                        } else {
                            document.getElementById("member-" + id + "-events").innerHTML = `
<div class="list-group">
    ${data.map(i => `
    <div class="list-group-item ${!i.justified ? "list-group-item-danger" : ""}">
        ${i.type === "delay" ?
            `${!i.justified ? "<b>Retard non justifié</b>" : "Retard"} le ${new Date(i.start ?? i.eventStart).toLocaleDateString('fr-FR', {
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            })} de ${i.eventDuration / 60} minutes en ${i.name}${i.justified ? ` (motif : ${i.reason})` : ""}` :
            `${!i.justified ? "<b>Absence non justifiée</b>" : "Absence"} le ${new Date(i.start ?? i.eventStart).toLocaleDateString('fr-FR', {
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            })} en ${i.name}${i.justified ? ` (motif : ${i.reason})` : ""}`
        }
    </div>
    `).join("")}
</div>
                            `;
                        }
                    } else {
                        document.getElementById("wait").style.display = "none";
                        document.getElementById("success").style.display = "none";
                        document.getElementById("error").style.display = "none";
                        document.getElementById("error2").style.display = "";
                        document.getElementById("error3").style.display = "none";
                    }
                } catch (e) {}

                try {
                    window.data = await (await fetch(`/api/getSchedule.php?id=${encodeURIComponent(id)}&_=${btoa(crypto.getRandomValues(new Uint8Array(8)))}`)).json();

                    if (window.data.code === 0) {
                        document.getElementById("wait").style.display = "none";
                        document.getElementById("success").style.display = "";
                        document.getElementById("error").style.display = "none";
                        document.getElementById("error2").style.display = "none";
                        document.getElementById("error3").style.display = "none";

                        window.data = window.data.data;
                        if (window.data.length === 0) {
                            document.getElementById("member-" + id + "-activities").innerHTML = `Cet invité n'est inscrit à aucune activité pour l'instant.`;
                        } else {
                            document.getElementById("member-" + id + "-activities").innerHTML = `
<div class="list-group">
    ${data.map(i => `
    <div class="list-group-item">
        <code class="text-black">${new Date(i.start).toLocaleDateString('fr-FR', {
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        })}</code> · <b>${i.name}</b> (${(new Date(i.end) - new Date(i.start)) / 60000} min)${i.room.length > 0 ? ` en ${i.room.join(", ")}` : ``}${i.animators.length > 0 ? ` avec ${i.animators.join(", ")}` : ``}
    </div>
    `).join("")}
</div>
                            `;
                        }
                    } else {
                        document.getElementById("wait").style.display = "none";
                        document.getElementById("success").style.display = "none";
                        document.getElementById("error").style.display = "none";
                        document.getElementById("error2").style.display = "";
                        document.getElementById("error3").style.display = "none";
                    }
                } catch (e) {}
            } else {
                document.getElementById("wait").style.display = "none";
                document.getElementById("success").style.display = "none";
                document.getElementById("error").style.display = "none";
                document.getElementById("error2").style.display = "";
                document.getElementById("error3").style.display = "none";
            }
        }
    </script>
</body>
</html>
