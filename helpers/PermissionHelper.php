<?php

/**
 * Static helper class for managing permissions in Gapminder.
 */
class PermissionHelper
{

    const VISIBILITY_VISIBLE = 'visible';
    const VISIBILITY_HIDDEN = null;

    /**
     * @var array runtime cache for roles.
     */
    private static $_roles = array();

    /**
     * @var array runtime cache for groups.
     */
    private static $_groups = array();

    /**
     * Adds an account to a specific group.
     *
     * @param int $accountId
     * @param string $group
     * @param string $role
     *
     * @return bool
     */
    static public function addAccountToGroup($accountId, $group, $role)
    {
        $attributes = array(
            'account_id' => $accountId,
            'group_id' => self::groupNameToId($group),
            'role_id' => self::roleNameToId($role),
        );

        if (self::groupHasAccount($attributes)) {
            return false; // already in group
        }

        Yii::log(
            sprintf('Adding account #%d to group "%s" with role "%s".', $accountId, $group, $role),
            CLogger::LEVEL_TRACE,
            'permissions'
        );

        $model = new GroupHasAccount();
        $model->attributes = $attributes;
        if (!$model->save()) {
            throw new SaveException($model);
        }

        return true;
    }

    /**
     * Removes an account from a specific group.
     *
     * @param int $accountId
     * @param string $group
     * @param string $role
     *
     * @return bool
     */
    static public function removeAccountFromGroup($accountId, $group, $role)
    {
        $attributes = array(
            'account_id' => $accountId,
            'group_id' => self::groupNameToId($group),
            'role_id' => self::roleNameToId($role),
        );

        if (!self::groupHasAccount($attributes)) {
            return false; // not in group
        }

        Yii::log(
            sprintf('Removing account #%d from group "%s" with role "%s".', $accountId, $group, $role),
            CLogger::LEVEL_TRACE,
            'permissions'
        );

        $model = GroupHasAccount::model()->findByAttributes($attributes);
        $model->delete();

        return true;
    }

    /**
     * Returns whether a row with the given attributes exist.
     *
     * @param array $attributes
     *
     * @return bool
     */
    static public function groupHasAccount(array $attributes)
    {
        return GroupHasAccount::model()->findByAttributes($attributes) !== null;
    }

    /**
     * @param $accountId
     *
     * @return GroupHasAccount[]
     */
    static public function getGroupHasAccountsForAccount($accountId)
    {
        $criteria = new CDbCriteria();
        $criteria->addCondition('account_id = :accountId');
        $criteria->params[':accountId'] = $accountId;

        return GroupHasAccount::model()->findAll($criteria);
    }

    /**
     * Returns whether the given account has a specific role in a group.
     *
     * @param int $accountId
     * @param string $group
     * @param string $role
     *
     * @return bool
     */
    static public function hasRole($accountId, $group, $role)
    {
        return self::groupHasAccount(
            array(
                'account_id' => $accountId,
                'group_id' => self::groupNameToId($group),
                'role_id' => self::roleNameToId($role),
            )
        );
    }

    /**
     * Returns a map of all roles (id => title) that the account belongs to, regardless of group.
     *
     * @param int $accountId the account model id.
     *
     * @return array the role map.
     */
    static public function getRolesForAccount($accountId)
    {
        $result = array();
        $roles = self::getRoles();
        $groupHasAccounts = self::getGroupHasAccountsForAccount($accountId);
        foreach ($groupHasAccounts as $groupHasAccount) {
            if (isset($roles[$groupHasAccount->role_id]) && !isset($result[$groupHasAccount->role_id])) {
                $result[$groupHasAccount->role_id] = $roles[$groupHasAccount->role_id];
            }
        }
        return $result;
    }

    /**
     * Adds a node to a specific group.
     *
     * @param int $nodeId
     * @param string $groupId
     *
     * @return bool
     */
    static public function addNodeToGroup($nodeId, $groupId)
    {
        $attributes = array(
            'node_id' => $nodeId,
            'group_id' => self::groupNameToId($groupId),
        );

        if (self::nodeHasGroup($attributes)) {
            return false; // already in group
        }

        Yii::log(sprintf('Adding node #%d to group "%s".', $nodeId, $groupId), CLogger::LEVEL_TRACE, 'permissions');

        $model = new NodeHasGroup();
        $model->attributes = $attributes;
        if (!$model->save()) {
            throw new SaveException($model);
        }

        return true;
    }

    /**
     * Removes a node from a specific group.
     *
     * @param int $nodeId
     * @param string $group
     *
     * @return bool
     */
    static public function removeNodeFromGroup($nodeId, $group)
    {
        $attributes = array(
            'node_id' => $nodeId,
            'group_id' => self::groupNameToId($group),
        );

        if (!self::nodeHasGroup($attributes)) {
            return false; // not in group
        }

        Yii::log(sprintf('Removing node #%d from group "%s".', $nodeId, $group), CLogger::LEVEL_TRACE, 'permissions');

        $model = NodeHasGroup::model()->findByAttributes($attributes);
        $model->delete();

        return true;
    }

    /**
     * Returns whether a row with the given attributes exist.
     *
     * @param array $attributes
     *
     * @return bool
     */
    static public function nodeHasGroup(array $attributes)
    {
        return NodeHasGroup::model()->findByAttributes($attributes) !== null;
    }

    /**
     * Converts a role name to its id.
     *
     * @param string $name
     *
     * @return int
     */
    static public function roleNameToId($name)
    {
        $return = array_search($name, self::getRoles());
        if (!$return) {
            throw new CException("No role with name $name");
        }
        return $return;
    }

    /**
     * Converts a role id to its name.
     *
     * @param integer $id
     *
     * @return string
     */
    static public function roleIdToName($id)
    {
        $roles = self::getRoles();
        if (!isset($roles[$id])) {
            throw new CException("No role with id $id");
        }
        return $roles[$id];
    }

    /**
     * Converts a group name to its id.
     *
     * @param string $name
     *
     * @return int
     */
    static public function groupNameToId($name)
    {
        $return = array_search($name, self::getGroups());
        if ($return === false) {
            throw new CException("No group with name $name");
        }
        return $return;
    }

    /**
     * Converts a group id to its name.
     *
     * @param integer $id
     *
     * @return string
     */
    static public function groupIdToName($id)
    {
        $groups = self::getGroups();
        if (!isset($groups[$id])) {
            throw new CException("No group with id $id");
        }
        return $groups[$id];
    }

    /**
     * Return a map over all the roles (name => id).
     *
     * @return array
     */
    static public function getRoles()
    {
        if (empty(self::$_roles)) {
            $roles = array();
            $model = Role::model();
            if (in_array("RestrictedAccessBehavior", array_keys($model->behaviors()))) {
                $model = $model->unrestricted();
            }
            foreach ($model->findAll() as $model) {
                $roles[$model->id] = $model->title;
            }
            self::$_roles = $roles;
        }

        return self::$_roles;
    }

    /**
     * Return a map over all the groups (name => id).
     *
     * @return array
     */
    static public function getGroups()
    {
        if (empty(self::$_groups)) {
            $groups = array();
            $model = Group::model();
            if (in_array("RestrictedAccessBehavior", array_keys($model->behaviors()))) {
                $model = $model->unrestricted();
            }
            foreach ($model->findAll() as $model) {
                $groups[$model->id] = $model->title;
            }
            self::$_groups = $groups;
        }

        return self::$_groups;
    }
}
