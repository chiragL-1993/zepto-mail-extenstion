<?php
/*
 * Webhook
 * When the Zepto Mail in installed in a Enrolnow Child account, create a subform row in the Opportunity
 *
 * @client  Squirrel (In-house)
 * @author  Ali
 * @since   2024-02-23
 */

require getenv('SQUIRREL_CLIENT_LIB_V2');
$sc  = new SquirrelClient('sqible_demo_crm');
$oppId = '1124013000161570001';
$serviceId = '1124013000161570025';

// Receive API request
$postData = $_POST;

if (isset($_POST["companyName"]) && $_POST["companyName"]) {

    $emailData = json_encode($postData);
    $subject = 'Zepto Mail Extension Install Callback';
    $response = $sc->sendTemplateEmail(
        'general-wide',
        'ali@squirrel.biz',
        $subject,
        ['body' => $emailData]
    );

    // Extract POST body
    $orgId = $postData["organizationId"];
    $companyName = $postData["companyName"];
    $installTime = $postData["installTime"];
    $superUser = $postData["superUserID"];
    $installBy = $postData["loginUserID"];

    $description = "Organisation Name: $companyName\n\n";
    $description .= "Organisation ID: $orgId\n";
    $description .= "Super User Email: $superUser\n";
    $description .= "Installed By: $installBy\n";
    $description .= "Installed At: $installTime";

    //Get Opp Record
    try {
        $opportunity = $sc->zoho->getRecord("Deals", $oppId);
    } catch (Exception $e) {
        $sc->log->error("Could not retrieve ZCRM Deal '$oppId' for Enrolnow extension callback: " . $e->getMessage());
        exit;
    }

    $quotes = $opportunity->getFieldValue("Subform_2");
    $quotes[] = array(
        "Service" => $serviceId,
        "Resource" => $serviceId,
        "Task_Description" => $description,
    );

    // Update In Zoho
    try {
        $res = $sc->zoho->updateRecord("Deals", $oppId, ["Subform_2" => $quotes]);
        $sc->log->info("Successfully updated ZCRM Deal '$oppId' for Enrolnow extension callback.");
    } catch (Exception $e) {
        $sc->log->error("Error while updating ZCRM Deal '$oppId' for Enrolnow extension callback. " . $e->getMessage());
    }
}
