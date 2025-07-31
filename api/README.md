# Burst SMS Extension
**Version:** 2

The code in this repo is used to queue and send SMS messages through the Burst SMS API.

### Crons
There are two crons which must be executed on a regular basis. 

| PHP File                | Cron Frequency |
|-------------------------|----------------|
| process_sms_batches.php | Every minute   |
| cancel_sms_batches.php  | Every minute   |
|                         |                |

### Database Tables

| Database Table                    | Description                                                |
|-----------------------------------|------------------------------------------------------------|
| options                           | The Squirrel Options table where API keys are stored       |
| sms_records_v2                    | Holds SMS batches and records to be processed from the CRM |
| sms_batches_v2                    | Scheduled SMS batches                                      |
| squirrel_burst_sms_connections_v2 | Holds the Burst SMS credentials                            |

## Enrol Now Version
This repository is installed to the Enrol Now cloud Squirrel server with crons enabled. For any Enrol Now specific changes, please use a `main-enrolnow` branch.

This repository is copied to the EnrolNow GitLab folder.
