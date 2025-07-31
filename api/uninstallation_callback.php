<?php

use ZeptoMailExtension\Model\Connections;

require getenv('SQUIRREL_CLIENT_LIB_V2');
$sc = new SquirrelClient("sqible_demo_crm");

$sc->log->info("Zepto Mail Extension  Connection to be uninstalled. POST: " . print_r($_POST, true));

try {
    $dbRow = Connections::where([
        "org_id" => $_POST["organizationId"],
    ]);
    if ($dbRow) {
        $dbRow->delete();
    } else {
        $sc->log->critical("Organisation with ID " . $_POST["organizationId"] . " not found in database. Should not occur.");
    }
} catch (Exception $e) {
    $sc->log->error("Error while deleting row for Zepto Mail Extension connection. " . $e->getMessage());
}
