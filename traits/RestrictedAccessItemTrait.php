<?php

trait RestrictedAccessItemTrait
{

    public function beforeRead()
    {
        // todo: use corr accessRestricted from behavior -> tests

        $tableAlias = $this->getTableAlias(false, false);

        $publicCriteria = new CDbCriteria();
        $publicCriteria->join = "LEFT JOIN `node_has_group` AS `nhg_public` ON (`$tableAlias`.`node_id` = `nhg_public`.`node_id` AND `nhg_public`.`group_id` = :current_project_group_id AND `nhg_public`.`visibility` = :visibility)";
        $publicCriteria->addCondition("(`nhg_public`.`id` IS NOT NULL)");
        $publicCriteria->params = array(
            ":current_project_group_id" => PermissionHelper::groupNameToId(Group::GAPMINDER_ORG), // TODO: Base on current domain
            ":visibility" => NodeHasGroup::VISIBILITY_VISIBLE,
        );

        // Console applications will always use the "public" criteria as we cannot check the user permissions due to
        // there never being a user and the CWebUser always tries to initialize a session when accessed.
        if (Yii::app() instanceof CConsoleApplication || PHP_SAPI == 'cli') {
            return $publicCriteria;
        }

        $user = Yii::app()->user;

        if ($user->isAdmin()) {

            // All items
            return true;

        } elseif ($user->isGuest) {

            // Only public items
            return $publicCriteria;

        } else {

            $criteria = new CDbCriteria();

            $criteria->distinct = true;

            $criteria->params[':account_id'] = $user->id;

            // Public items ...
            $criteria->mergeWith($publicCriteria, 'OR');

            // ... and own items
            $criteria->addCondition("`$tableAlias`.`owner_id` = :account_id", "OR");

            // ... and items within groups that the user is a member of
            $criteria->join .= "\n" . "LEFT JOIN (`node_has_group` AS `nhg` INNER JOIN `group_has_account` AS `gha` ON (`gha`.`group_id` = `nhg`.`group_id` AND `gha`.`account_id` = :account_id)) ON (`$tableAlias`.`node_id` = `nhg`.`node_id`) ";
            $criteria->addCondition("`nhg`.id IS NOT NULL AND (`nhg`.`visibility` = 'visible' OR `nhg`.`visibility` IS NULL)", "OR");

            return $criteria;
        }
    }
}