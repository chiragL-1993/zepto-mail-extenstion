<?php

namespace ZeptoMailExtension\Model;

use Carbon\Carbon;

/**
 * Class ZeptoEmailBatches
 * @package ZeptoMailExtension\Model
 *
 *
 * @property int id
 * @property string client
 * @property string module
 * @property string module_records
 * @property string zepto_mail_configurations
 * @property string status
 * @property datetime schedule_at
 * @property string attempts
 * @property Carbon created_at
 * @property Carbon updated_at
 *
 */
class ZeptoEmailBatches extends \SquirrelModel
{

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = "zepto_email_batches";
}
