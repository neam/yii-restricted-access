<?php

$config['import'][] = 'vendor.neam.yii-restricted-access.behaviors.*';
$config['import'][] = 'vendor.neam.yii-restricted-access.helpers.*';
$config['import'][] = 'vendor.neam.yii-restricted-access.modules.restrictedAccess.widgets.*';
$config['modules']['restrictedAccess'] = array(
    'class' => 'vendor.neam.yii-restricted-access.modules.restrictedAccess.RestrictedAccessModule',
);
