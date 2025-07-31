<?php

/**
 * get_scheduled_email_batches.php - Get all the scheduled batches for the client
 *
 *
 * @client Squirrel Market Place Extension
 * @author ali
 * @since Tue 13 Dec 2023 12:00:00 
 **/

require_once __DIR__ . '/constants.php';
/*$requestOrigin = $_SERVER['HTTP_ORIGIN'];
if (in_array($requestOrigin, ALLOWED_ORIGINS)) {
    header("Access-Control-Allow-Origin: $requestOrigin");
} else {
    exit;
}*/

use Carbon\Carbon;

use ZeptoMailExtension\Model\ZeptoEmailRecords;
use ZeptoMailExtension\Model\ZeptoEmailBatches;
use ZeptoMailExtension\Model\Connections;
use ZeptoMailExtension\Model\Options;

use Squirrel\Marketplace\ZeptoMailHelper;
use Squirrel\Marketplace\AuthHelper;

const CONTEXT = "Scheduled Email Batches";

require getenv('SQUIRREL_CLIENT_LIB_V2');

if (!isset($_GET['orgid'])) {
    $msg = "Organisation ID not set, please check the extension and try again!";
    sendResponse(false, $msg);
}

$orgId = $_GET["orgid"];
$squirrelSc = new SquirrelClient("sqible_demo_crm", false, CONTEXT);
$authHelper = new AuthHelper($squirrelSc, CONTEXT);

$client = Connections::where(["org_id" => $orgId])->first();
if (empty($client)) {
    $msg = "No Connection database entry found for CRM with Organisation ID {$orgId}";
    sendResponse(false, $msg);
}

$zeptoMailApiKey = $client->zepto_mail_api_key;

$zeptoMailHelper = new ZeptoMailHelper($squirrelSc, $zeptoMailApiKey);

$currentDateTime = Carbon::now('UTC')->addMinutes(15)->toDateTimeString();

$batches = ZeptoEmailBatches::where('status', BATCH_PROCESSING_STATUSES['scheduled'])
    ->where('client', $client->client_code)
    ->where('schedule_at', '>', $currentDateTime)
    ->get();

$scheduledBatches = [];
foreach ($batches as $batch) {
    $emailConfiguration = json_decode($batch->zepto_mail_configurations);
    $moduleRecords = (array)json_decode($batch->module_records);
    $timezone = $emailConfiguration->timezone;
    $scheduleDateTime = $zeptoMailHelper->convertUTCDateToTZ($batch->schedule_at, $timezone, 'Y-m-d H:i:s', 'd/m/Y h:i A');

    $scheduledBatches[] = [
        'id' => $batch->id,
        'scheduledTime' => $scheduleDateTime,
        'module' => $batch->module,
        'campaign_name' => $emailConfiguration->campaign_name,
        'recipients' => count($moduleRecords),
        'emailText' => $emailConfiguration->email_body
    ];
}

sendResponse(true, $scheduledBatches);

function sendResponse($success, $message)
{
    $response = array(
        "success" => $success,
        "message" => $message
    );
    echo json_encode($response);
    exit;
}
