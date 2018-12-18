<?php
// post uninstall actions

// remove scheduler
$class = 'class::OutboundEmailsDeployerJob';

$sugarQuery = new SugarQuery();
$sugarQuery->from(BeanFactory::newBean('Schedulers'));
$sugarQuery->select(array('id'));
$sugarQuery->where()->equals('job', $class);
$sugarQuery->limit(1);
$record = $sugarQuery->execute();

if (!empty($record) && !empty($record['0'])) {
    $scheduler = BeanFactory::retrieveBean('Schedulers', $record['0']['id']);
    $scheduler->mark_deleted($record['0']['id']);
}

// delete all records created by this module
$db = DBManagerFactory::getInstance();
$builder = $db->getConnection()->createQueryBuilder();
$builder->delete('outbound_email');
$builder->where($builder->expr()->isNotNull('parentmailbox_id_c'));
$builder->execute();

$builder = $db->getConnection()->createQueryBuilder();
$builder->delete('teams_outboundemail');
$builder->execute();
