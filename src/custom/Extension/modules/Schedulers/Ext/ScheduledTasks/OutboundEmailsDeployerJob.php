<?php

// Enrico Simonetti
// 2018-11-15

use Sugarcrm\Sugarcrm\custom\OutboundEmailsDeployer\OutboundEmailsDeployer;

$job_strings[] = 'class::OutboundEmailsDeployerJob';

class OutboundEmailsDeployerJob implements \RunnableSchedulerJob
{
    protected $job;

    public function setJob(SchedulersJob $job)
    {
        $this->job = $job;
    }

    public function run($data)
    {
        $start_time = microtime(true);

        $oed = new OutboundEmailsDeployer();
        $output = $oed->deployCurrentMapping();

        $messages = PHP_EOL . sprintf(
            translate('LBL_OUTBOUND_EMAILS_DEPLOYER_SUMMARY_MESSAGE_SUCCESSFUL_EXECUTION', 'Administration'),
            round(microtime(true) - $start_time, 2)
        );
        $messages .= PHP_EOL . sprintf(
            translate('LBL_OUTBOUND_EMAILS_DEPLOYER_SUMMARY_MESSAGE_SUCCESSFUL_DELETE', 'Administration'),
            count($output['deleted'])
        );
        $messages .= PHP_EOL . sprintf(
            translate('LBL_OUTBOUND_EMAILS_DEPLOYER_SUMMARY_MESSAGE_NO_CHANGE', 'Administration'),
            count($output['no_changes'])
        );
        $messages .= PHP_EOL . sprintf(
            translate('LBL_OUTBOUND_EMAILS_DEPLOYER_SUMMARY_MESSAGE_SUCCESSFUL_CHANGE', 'Administration'),
            count($output['updated'])
        );

        if (!empty($data)) {
            $decoded_data = json_decode($data);
            if (!empty($decoded_data) && !empty($decoded_data->notify_user_id)) {
                $notification = \BeanFactory::newBean('Notifications');
                $notification->name = translate('LBL_OUTBOUND_EMAILS_DEPLOYER_NOTIFICATION_SUBJECT', 'Administration');
                $notification->assigned_user_id = $decoded_data->notify_user_id;
                $notification->severity = 'Success';
                $notification->description = nl2br($messages);
                $notification->parent_type = 'Users';
                $notification->parent_id = $decoded_data->notify_user_id;
                $notification->save();
        }
        }

        $this->job->succeedJob($messages);
    }
}
