<?php

$hook_array['after_relationship_delete'][] = array(
    100,
    'Teams after relationship delete hook',
    'custom/logichooks/modules/Teams/afterRelationshipDeleteTeams.php',
    'afterRelationshipDeleteTeams', 
    'callAfterRelationshipDelete'
);
