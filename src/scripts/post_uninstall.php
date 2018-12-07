<?php
// post uninstall actions

// remove scheduler
$class = 'class::OutboundEmailsDeployerJob';

$sugarQuery = new SugarQuery();
$sugarQuery->from(BeanFactory::getBean('Schedulers'));
$sugarQuery->select(array('id'));
$sugarQuery->where()->equals('job', $class);
$sugarQuery->limit(1);
$record = $sugarQuery->execute();

if (!empty($record) && !empty($record['0'])) {
    $scheduler = BeanFactory::getBean('Schedulers', $record['0']['id']);
    $scheduler->mark_deleted($record['0']['id']);
}
