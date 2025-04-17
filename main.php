<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Anniversaire</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>
<body>
    <div style="display: grid; grid-template-rows: 37px 1fr; position: fixed; inset: 0;">
        <div>
            <button id="btn" style="border-radius: 0; width: 100%;" class="btn btn-primary" onclick="start();">Commencer</button>
            <select onchange="refresh();" style="border-top: none; border-left: none; border-right: none; width: 100%; border-radius: 0; display: none;" id="sel" class="form-select">
                <option value="general">Inspection d'invité</option>
            </select>
        </div>
        <iframe id="frame" style="border: none; width: 100%; height: 100%;"></iframe>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        // General NFC reading library
        if ("NDEFReader" in window) {
            const ndef = new NDEFReader();
            const controller = new AbortController();
            const signal = controller.signal;

            let scanning = false;

            function refresh() {
                document.getElementById("frame").onload = () => {
                    document.getElementById("sel").disabled = false;
                    document.getElementById("frame").onload = null;
                }

                document.getElementById("sel").disabled = true;
                document.getElementById("frame").src = "/pages/" + document.getElementById("sel").value + ".php";
            }

            function start() {
                document.getElementById("sel").disabled = true;
                loop();
                refresh();
            }

            function loop() {
                document.getElementById("btn").style.display = "none";
                document.getElementById("sel").style.display = "block";

                scanning = true;
                ndef.scan({ signal }).then(() => {
                    ndef.onreadingerror = (event) => {
                        console.log(event);
                        if (document.getElementById("frame").contentWindow?.onbirthdayid) document.getElementById("frame").contentWindow?.onbirthdayid(null);
                        setTimeout(() => {
                            if (!scanning) loop();
                        }, 50);
                    };
                    ndef.onreading = (event) => {
                        scanning = false;
                        console.log("Read tag " + event.serialNumber, event);
                        let record = event.message.records.filter(i => i.recordType === "text")[0];

                        if (!record) {
                            if (document.getElementById("frame").contentWindow?.onbirthdayid) document.getElementById("frame").contentWindow?.onbirthdayid(null);

                            setTimeout(() => {
                                if (!scanning) loop();
                            }, 50);
                            return;
                        }

                        let td = new TextDecoder();
                        let data = td.decode(record.data);
                        let id = data.split("\n").filter(i => !i.startsWith("#") && !i.startsWith("//") && !i.startsWith(">") && !i.startsWith("<")).join("\n").trim();

                        if (document.getElementById("frame").contentWindow?.onbirthdayid) document.getElementById("frame").contentWindow?.onbirthdayid(id);
                    };
                }).catch((e) => {
                    console.error(e);
                    if (document.getElementById("frame").contentWindow?.onbirthdayid) document.getElementById("frame").contentWindow?.onbirthdayid(null);
                });
            }
        } else {
            alert("Navigateur ou système non supporté. Veuillez utiliser Chrome 89 ou suivant sur Android et avec HTTPS.");
            document.getElementById("btn").style.display = "none";
            document.getElementById("sel").style.display = "block";
            document.getElementById("sel").disabled = true;
        }
    </script>
</body>
</html>
