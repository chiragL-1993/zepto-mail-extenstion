<?php

namespace ZeptoMailExtension\Model;

use Carbon\Carbon;

/**
 * Class ZeptoEmailRecord
 * @package ZeptoMailExtension\Model
 *
 *
 * @property int id
 * @property string org_id
 * @property string zgid
 * @property string email
 * @property string client_code
 * @property string zepto_mail_api_key
 * @property string zepto_mail_sender_id
 * @property string custom_module_name
 * @property string modules_for_zepto_mail
 * @property string module_field_mappings
 * @property string error_email
 * @property string extra_data
 * @property Carbon refreshed_data
 * @property Carbon created_at
 * @property Carbon updated_at
 *
 */
class Connections extends \SquirrelModel
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = "squirrel_zepto_mail_connections";
}
