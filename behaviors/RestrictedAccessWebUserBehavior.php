<?php

class RestrictedAccessWebUserBehavior extends CBehavior
{

    public function checkModelOperationAccess()
    {
        return true;
    }

} 