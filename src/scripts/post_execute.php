<?php
// post execute actions

// execute repair to deploy sql
global $mod_strings;
require_once('modules/Administration/QuickRepairAndRebuild.php');
$repair = new RepairAndClear();
$repair->repairAndClearAll(array('clearAll'), array($mod_strings['LBL_ALL_MODULES']), true, false);

// enable scheduler
$class = 'class::OutboundEmailsDeployerJob';
$name = 'Outbound Group Email Account Deployer Job';
$interval = '*/30::*::*::*::*';
$status = 'Active';

$sugarQuery = new SugarQuery();
$sugarQuery->from(BeanFactory::getBean('Schedulers'));
$sugarQuery->select(array('id'));
$sugarQuery->where()->equals('job', $class);
$sugarQuery->limit(1);
$record = $sugarQuery->execute();

if (!empty($record) && !empty($record['0'])) {
    $scheduler = BeanFactory::getBean('Schedulers', $record['0']['id']);
} else {
    $scheduler = BeanFactory::newBean('Schedulers');
}

$scheduler->name = $name;
$scheduler->job = $class;
$scheduler->date_time_start = '2000-01-01 00:00:01';
$scheduler->date_time_end = '2100-01-01 00:00:01';
$scheduler->job_interval = $interval;
$scheduler->status = $status;
$scheduler->created_by = $current_user->id;
$scheduler->modified_user_id = $current_user->id;
$scheduler->catch_up = 0;
$scheduler->save();
