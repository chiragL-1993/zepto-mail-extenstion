<?php

use ZeptoMailExtension\Model\Connections;

require_once __DIR__ . '/constants.php';
require getenv('SQUIRREL_CLIENT_LIB_V2');
$sc = new SquirrelClient("sqible_demo_crm");

$sc->log->info("New Zepto Mail Extension Connection to be created. POST: " . print_r($_POST, true));

$create = false;
// Webhook gets triggered if the plugin is re-installed
if (filter_var($_POST["isUpgrade"], FILTER_VALIDATE_BOOLEAN)) {
    $dbRow = Connections::where(["org_id" => $_POST["organizationId"]])->first();
    if ($dbRow) {
        // Fetch and Merge the extra data object to not loose any additional settings
        $existingData = json_decode($dbRow->extra_data);
        $newData = json_decode(json_encode($_POST));
        $mergedData = (object) array_merge((array) $existingData, (array) $newData);
        $newJsonData = json_encode($mergedData);
        $dbRow->update(["extra_data" => $newJsonData]);
        $sc->log->info("Extension upgraded for client " . $dbRow->client_code . ". POST: " . print_r($mergedData, true));
    } else {
        // Could set Create flag here but if this occurs there is something else wrong which needs to be fixed
        $sc->log->critical("Extension has been upgraded but was not in database - this should not occur! POST: " . print_r($_POST, true));
    }
    exit;
} else {
    $create = true;
}

if ($create) {
    try {
        $dbRow = Connections::create([
            "org_id" => $_POST["organizationId"],
            "email" => $_POST["loginUserID"],
            "extra_data" => json_encode($_POST)
        ]);
        // Post to Squirrel
        $url = "https://scripts.squirrelcrmhub.com.au/zoho_scripts/marketplace/zepto-mail-extenstion/api/zepto_mail_extension_install.php";
        $response = postRequest($url, $_POST);
        $sc->log->info('Setting Create Response: ' . json_encode($response));
    } catch (Exception $e) {
        $sc->log->error("Error while inserting row for new Zepto Mail Extension ! POST: " . print_r($_POST, true));
    }
}

function postRequest($url, $body)
{
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_HTTPHEADER => ["Content-Type: multipart/form-data"],
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    return $response;
}
