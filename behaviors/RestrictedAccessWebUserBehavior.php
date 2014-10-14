<?php

class RestrictedAccessWebUserBehavior extends CBehavior
{

    public function init()
    {
        parent::init();
        if (!(in_array('RestrictedAccessWebUserTrait', class_uses($this->owner)))) {
            throw new CException('yii-restricted-access is activated but the CWebUser instance does not use the required trait for the access checks to work as expected. Refer to the README for instructions.');
        }

    }

}