<?php

class AdminController extends Controller
{

    public $defaultAction = 'manageAccounts';

    /**
     * @inheritDoc
     */
    public function filters()
    {
        return array(
            'accessControl',
        );
    }

    /**
     * @inheritDoc
     */
    public function accessRules()
    {
        return array(
            array(
                'allow',
                'roles' => array(Role::SUPER_ADMINISTRATOR),
            ),
            array(
                'deny',
                'users' => array('*'),
            ),
        );
    }

    /**
     * Renders the account management page.
     */
    public function actionManageAccounts()
    {
        $dataProvider = new CActiveDataProvider(
            'Account',
            array(
                'sort' => array(
                    'defaultOrder' => 'create_at DESC',
                ),
                'pagination' => array(
                    'pageSize' => 20
                ),
            )
        );

        $columns = array(
            array(
                'class' => '\AccountLinkColumn',
                'header' => '',
                'labelExpression' => '$data->itemLabel',
                'urlExpression' => 'Yii::app()->controller->createUrl("admin/editAccountPermissions", array("id" => $data["id"]))',
            ),
            array(
                'class' => '\ActivateLinkColumn',
                'labelExpression' => '(int)$data->status === 0 ? Yii::t("account", "Activate") : Yii::t("account", "Deactivate")',
                'urlExpression' => '(int)$data->status === 0 ? Yii::app()->controller->createUrl("admin/activate", array("id" => $data["id"])) : Yii::app()->controller->createUrl("admin/deactivate", array("id" => $data["id"]))',
            ),
            /*
            array(
                'class' => '\TbButtonColumn',
                'viewButtonUrl' => 'Yii::app()->controller->createUrl("view", array("id" => $data->id))',
                'updateButtonUrl' => 'Yii::app()->controller->createUrl("update", array("id" => $data->id))',
                'deleteButtonUrl' => 'Yii::app()->controller->createUrl("delete", array("id" => $data->id))',
            ),
            */
        );

        $this->buildBreadcrumbs(array(
            Yii::t('app', 'Accounts'),
        ));

        $this->render(
            'manageAccounts',
            array(
                'dataProvider' => $dataProvider,
                'columns' => $columns,
            )
        );
    }

    /**
     * Renders the account permissions page.
     * @param int $id the account ID.
     */
    public function actionEditAccountPermissions($id)
    {
        $model = Account::model()->findByPk($id);

        $groups = array_merge(MetaData::projectGroups(), MetaData::topicGroups(), MetaData::skillGroups());
        $groupRoles = MetaData::groupRoles();

        $rawData = array();

        $id = 1;
        foreach ($groups as $groupName => $groupLabel) {
            // TODO fix this, must use stdClass because of the stupid TbToggleColumn
            $row = new stdClass();

            $row->id = $id++;
            $row->accountId = $model->id;
            $row->groupName = $groupName;
            $row->groupLabel = $groupLabel;

            foreach ($groupRoles as $roleName => $roleLabel) {
                $row->$roleName = $model->groupRoleIsActive($groupName, $roleName);
            }

            $rawData[] = $row;
        }

        $dataProvider = new CArrayDataProvider(
            $rawData,
            array(
                'id' => 'permissions',
                'pagination' => false,
            )
        );

        $columns = array();

        $columns[] = array(
            'class' => 'CDataColumn',
            'header' => Yii::t('admin', 'Group name'),
            'name' => 'groupLabel',
        );

        $groupModeratorRoles = MetaData::groupModeratorRoles();
        foreach ($groupRoles as $roleName => $roleLabel) {
            if (Yii::app()->user->checkAccess('GrantGroupAdminPermissions')
                || (isset($groupModeratorRoles[$roleName]) && Yii::app()->user->checkAccess('GrantGroupModeratorPermissions'))
            ) {
                $columns[] = array(
                    'class' => '\GroupRoleToggleColumn',
                    'displayText' => false,
                    'header' => $roleLabel,
                    'name' => $roleName,
                    'toggleAction' => 'admin/toggleRole',
                    'value' => function($data) use ($roleName) {
                            return $data->$roleName;
                        },
                );
            }
        }

        $this->buildBreadcrumbs(array(
            Yii::t('app', 'Accounts') => $this->createUrl('admin/manageAccounts'),
            Yii::t('app', 'Permissions'),
        ));

        $this->render(
            'editAccountPermissions',
            array(
                'columns' => $columns,
                'dataProvider' => $dataProvider,
                'model' => $model,
            )
        );
    }

    /**
     * Toggles a role for a given user.
     * @param integer $id the user ID.
     * @param string $attribute the role name.
     */
    public function actionToggleRole($id, $attribute)
    {
        list ($group, $role) = explode('_', $attribute);

        $attributes = array(
            'account_id' => $id,
            'group_id' => PermissionHelper::groupNameToId($group),
            'role_id' => PermissionHelper::roleNameToId($role),
        );

        if (!PermissionHelper::groupHasAccount($attributes)) {
            PermissionHelper::addAccountToGroup($id, $group, $role);
        } else {
            PermissionHelper::removeAccountFromGroup($id, $group, $role);
        }
    }

    /**
     * @param string $id
     * @return Account
     * @throws CHttpException
     */
    protected function loadModel($id)
    {
        $model = Account::model()->findByPk($id);
        if ($model === null) {
            throw new CHttpException(404, Yii::t('model', 'The requested page does not exist.'));
        }
        return $model;
    }

    /**
     * Activates a given user.
     * @param string $id the user ID.
     */
    public function actionActivate($id)
    {
        $account = $this->loadModel($id);

        $account->status = \nordsoftware\yii_account\models\ar\Account::STATUS_ACTIVATED;
        $account->save(true, array('status'));

        $this->redirect(array($this->defaultAction));
    }

    /**
     * Deactivates a given user.
     * @param string $id the user ID.
     */
    public function actionDeactivate($id)
    {
        $account = $this->loadModel($id);

        $account->status = \nordsoftware\yii_account\models\ar\Account::STATUS_INACTIVE;
        $account->save(true, array('status'));

        $this->redirect(array($this->defaultAction));
    }

    /**
     * Builds and sets the breadcrumbs. (TODO: Move out this copied method from this extension)
     * @param array $items the list of breadcrumb items as label => URL
     * @param array $rootItem override the root breadcrumb item.
     */
    public $breadcrumbs;
    public function buildBreadcrumbs(array $items, array $rootItem = array())
    {
        $breadcrumbs = array();

        !empty($rootItem)
            ? $breadcrumbs[$rootItem[0]] = $rootItem[1] // override breadcrumb root
            : $breadcrumbs['Home'] = Yii::app()->homeUrl;

        // NICE: Yii::app()->breadcrumbRootLabel

        foreach ($items as $label => $url) {
            if (!isset($breadcrumbs[$label])) {
                $breadcrumbs[$label] = $url;
            }
        }

        $this->breadcrumbs = $breadcrumbs;
    }

}
