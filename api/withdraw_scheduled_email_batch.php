<?php

/**
 * withdraw_scheduled_email_batch.php - Withdraw (update status to withdraw) the given email batch
 *
 *
 * @client Squirrel Market Place Extension
 * @author ali
 * @since Tue 13 Dec 2023 18:00:00 
 **/

require_once __DIR__ . '/constants.php';
$requestOrigin = $_SERVER['HTTP_ORIGIN'];
if (in_array($requestOrigin, ALLOWED_ORIGINS)) {
    header("Access-Control-Allow-Origin: $requestOrigin");
} else {
    exit;
}

use ZeptoMailExtension\Model\ZeptoEmailBatches;
use ZeptoMailExtension\Model\Connections;
use ZeptoMailExtension\Model\Options;

use Squirrel\Marketplace\ZeptoMailHelper;
use Squirrel\Marketplace\AuthHelper;

const CONTEXT = "Withdraw Email Batch";

require getenv('SQUIRREL_CLIENT_LIB_V2');

if (!isset($_POST['orgid']) || !isset($_POST['batch_id'])) {
    $msg = "Organisation ID or Batch ID not set, please check the extension and try again!";
    sendResponse(false, $msg);
}

$orgId = $_POST["orgid"];
$batchId = $_POST["batch_id"];
$squirrelSc = new SquirrelClient("sqible_demo_crm", false, CONTEXT);
$authHelper = new AuthHelper($squirrelSc, CONTEXT);

$client = Connections::where(["org_id" => $orgId])->first();
if (empty($client)) {
    $msg = "No Connection database entry found for CRM with Organisation ID {$orgId}";
    sendResponse(false, $msg);
}

(new ZeptoEmailBatches)->where('id', $batchId)->update(['status' => BATCH_PROCESSING_STATUSES['withdraw']]);

sendResponse(true, "Email Job has been withdrawn!");

function sendResponse($success, $message)
{
    $response = array(
        "success" => $success,
        "message" => $message
    );
    echo json_encode($response);
    exit;
}
