<?php

trait RestrictedAccessWebUserTrait
{

    /**
     * @var Account
     */
    protected $_accountModel;

    /**
     * @return array
     */
    public function getSystemRoles()
    {
        $systemRoles = array();
        if ($this->owner->isGuest) {
            $systemRoles[] = Role::GUEST;
        } else {
            $systemRoles[] = Role::AUTHENTICATED;
        }
        return $systemRoles;
    }

    /**
     * Checks access using all different types of access checks.
     * @param string $operation
     * @param array $params
     * @param bool $allowCaching
     * @return bool
     */
    public function checkAccess($operation, $params = array(), $allowCaching = true)
    {
        // TODO Implement caching?

        Yii::log("checkAccess - operation: $operation", CLogger::LEVEL_INFO);

        // Auto-grant access to admins
        Yii::log('isAdmin?', CLogger::LEVEL_INFO);
        if ($this->isAdmin()) {
            Yii::log('isAdmin true', CLogger::LEVEL_INFO);
            return true;
        }

        Yii::log("checkSystemRoleBasedAccess - operation: $operation", CLogger::LEVEL_INFO);
        if ($this->checkSystemRoleBasedAccess($operation)) {
            Yii::log("checkSystemRoleBasedAccess true - operation: $operation", CLogger::LEVEL_INFO);
            return true;
        }

        Yii::log("checkGroupRoleBasedAccess - operation: $operation", CLogger::LEVEL_INFO);
        if ($this->checkGroupRoleBasedAccess($operation)) {
            Yii::log("checkGroupRoleBasedAccess true - operation: $operation", CLogger::LEVEL_INFO);
            return true;
        }

        Yii::log("no access - operation: $operation", CLogger::LEVEL_INFO);

        return false;
    }

    /**
     * Checks access using system-based roles.
     * @param string $operation
     * @return bool
     */
    public function checkSystemRoleBasedAccess($operation)
    {
        $operationRoleMap = RolesAndOperations::operationToSystemRolesMap();
        foreach ($this->getSystemRoles() as $role) {
            if (isset($operationRoleMap[$operation]) && in_array($role, $operationRoleMap[$operation])) {
                return true;
            }
        }

        return false;

    }

    /**
     * Checks access using group-based roles.
     * @param string $operation
     * @return bool
     */
    public function checkGroupRoleBasedAccess($operation)
    {
        $operationRoleMap = RolesAndOperations::operationToGroupRolesMap();
        foreach ($this->getGroupRoles() as $role) {
            if (isset($operationRoleMap[$operation]) && in_array($role, $operationRoleMap[$operation])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param CActiveRecord $model
     * @param string $operation
     * @param array $params
     * @return bool
     * @throws CException
     */
    public function checkModelOperationAccess(CActiveRecord $model, $operation, $params = array())
    {
        Yii::log("checkModelOperationAccess - operation: $operation", CLogger::LEVEL_INFO);

        // owner-based
        if (!empty($model->owner_id) && $model->owner_id === Yii::app()->user->id) {
            return true;
        }

        return $this->checkAccess($operation, $params);
    }

    /**
     * Loads an Account model.
     * @return CActiveRecord|null
     */
    /*
    public function loadAccount()
    {
        if ($this->owner->isGuest) {
            return null;
        }

        return Account::model()->findByPk($this->id);
    }
    */

    /**
     * Returns the group based roles for the logged in user.
     *
     * The format is the following:
     *
     * array(
     *   'GapminderInternal' => array(
     *     'Contributor',
     *     'Editor',
     *   ),
     *   'Translators' => array(
     *      'Translator',
     *   ),
     * )
     *
     * @return array
     */
    public function getGroupRolesTree()
    {
        $tree = array('All' => array());

        if (!$this->owner->isGuest) {
            $groups = PermissionHelper::getGroups();
            $roles = PermissionHelper::getRoles();

            foreach (PermissionHelper::getGroupHasAccountsForAccount($this->id) as $gha) {
                $groupName = $groups[$gha->group_id];

                if (!isset($tree[$groupName])) {
                    $tree[$groupName] = array();
                }

                $tree[$groupName][] = $roles[$gha->role_id];
            }
        } else {
            $tree['All'][] = Role::GUEST;
        }

        return $tree;
    }

    /**
     * @return array
     */
    public function getGroups()
    {
        $assigned = $this->getAssignedGroupsAndRoles();
        return $assigned['groups'];
    }

    /**
     * Returns the languages the user is able to translate into.
     * @return array
     */
    public function getTranslatableLanguages()
    {
        return !$this->owner->isGuest
            ? $this->getModel()->profile->getTranslatableLanguages()
            : array();
    }

    /**
     * Checks if the user is able to translate into the given language.
     * @param string $language
     * @return boolean
     */
    public function canTranslateInto($language)
    {
        return array_key_exists($language, $this->getTranslatableLanguages());
    }

    /**
     * Checks if the user is an admin.
     * @return bool
     */
    public function isAdmin()
    {
        if ($this->owner->isGuest) {
            return false;
        }

        $account = Account::model()->findByPk($this->id);

        if (empty($account)) {
            throw new CException("No account found with id {$this->id}");
        }

        return (int) $account->superuser === 1;
    }

    /**
     * Checks if the user is a group administrator.
     * @return bool
     */
    public function isGroupAdmin()
    {
        return $this->hasRole(Role::GROUP_ADMINISTRATOR);
    }

    /**
     * Checks if the user is a translator.
     * @return bool
     */
    public function getIsTranslator()
    {
        return $this->hasRole(Role::GROUP_TRANSLATOR);
    }

    /**
     * Checks if the user is a translator.
     * @return bool
     */
    public function getIsEditor()
    {
        return $this->hasRole(Role::GROUP_EDITOR);
    }

    /**
     * Checks if the user is a reviewer.
     * @return bool
     */
    public function getIsReviewer()
    {
        return $this->hasRole(Role::GROUP_REVIEWER);
    }

    /**
     * Checks if the user has the given role.
     * @param string $roleName (use role name constants, e.g. Role::GROUP_TRANSLATOR).
     * @return bool
     */
    public function hasRole($roleName)
    {
        if (!$this->owner->isGuest) {
            $attributes = array(
                'account_id' => $this->id,
                'role_id' => PermissionHelper::roleNameToId($roleName),
            );

            return PermissionHelper::groupHasAccount($attributes);
        } else {
            return false;
        }
    }

    /**
     * Returns (or sets and returns if not set) the user account model from the runtime cache.
     * @return Account
     */
    public function getModel()
    {
        if (!$this->_accountModel instanceof Account) {
            $this->_accountModel = Account::model()->findByPk($this->id);
        }

        return $this->_accountModel;
    }

    /**
     * Returns the user's full name.
     * @return string
     */
    public function getFullName()
    {
        return $this->getModel()->profile->getFullName();
    }

    /**
     * @return CActiveRecord|null
     */
    public function getGroupRoles()
    {
        $assigned = $this->getAssignedGroupsAndRoles();
        return $assigned['roles'];
    }

    /**
     * @return array
     */
    protected function getAssignedGroupsAndRoles()
    {
        $assigned = array('groups' => array(), 'roles' => array());

        $groups = PermissionHelper::getGroups();
        $roles = PermissionHelper::getRoles();

        foreach (PermissionHelper::getGroupHasAccountsForAccount($this->id) as $gha) {
            if (!isset($assigned['groups'][$gha->group_id])) {
                $assigned['groups'][$gha->group_id] = $groups[$gha->group_id];
            }
            if (!isset($assigned['roles'][$gha->role_id])) {
                $assigned['roles'][$gha->role_id] = $roles[$gha->role_id];
            }
        }

        return $assigned;
    }

    /**
     * Checks if the logged in user belongs to a certain group (with a certain role, if applicable).
     * @param string $group
     * @param string|null $role
     * @return boolean
     */
    public function belongsToGroup($group, $role = null)
    {
        $attributes = array(
            'account_id' => $this->id,
            'group_id' => PermissionHelper::groupNameToId($group),
        );

        if ($role !== null) {
            $attributes['role_id'] = PermissionHelper::roleNameToId($role);
        }

        return PermissionHelper::groupHasAccount($attributes);
    }

}