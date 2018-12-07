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

        $messages = 'Outbound Emails Deployer Job executed successfully in ' . round(microtime(true) - $start_time, 2) . ' seconds' . PHP_EOL;
        if (!empty($output['errors'])) {
            $messages .= implode(PHP_EOL, $output['errors']);
        }
        if (!empty($output['completed'])) {
            $messages .= implode(PHP_EOL, $output['completed']);
        }

        $this->job->succeedJob($messages);
    }
}
