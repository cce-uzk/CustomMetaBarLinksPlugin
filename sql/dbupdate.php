<#1>
<?php
global $DIC;
$db = $DIC->database();
if (!$db->tableExists('cmbl_links'))
{
    $fields = array(
        'link_id' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => true
        ),
        'title' => array(
            'type' => 'text',
            'length' => 50,
            'fixed' => false,
            'notnull' => true
        ),
        'external_link' => array(
            'type' => 'text',
            'length' => 100,
            'fixed' => false,
            'notnull' => true
        ),
        'is_active' => array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => false
        ),
        'icon_id' => array(
            'type' => 'text',
            'length' => 250,
            'fixed' => false
        ),
        'roles_ids' => array(
            'type' => 'text',
            'length' => 1024,
            'fixed' => false
        )
    );

    $db->createTable("cmbl_links", $fields);
    $db->addPrimaryKey("cmbl_links", array("link_id"));
    $db->createSequence("cmbl_links");
}
?>
