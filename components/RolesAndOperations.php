<?php

/**
 * Defaults. Override in application
 * and configure to use custom class in RestrictedAccessComponent (TODO)
 * Class RolesAndOperations
 */
class RolesAndOperations
{

    /**
     * Returns the system roles as name => label.
     * @return array
     */
    static public function systemRoles()
    {
        return array(
            Role::DEVELOPER => 'Developer',
            Role::SUPER_ADMINISTRATOR => 'Super Administrator',
            Role::AUTHENTICATED => 'Authenticated',
            Role::GUEST => 'Guest',
        );
    }

    /**
     * Returns the group roles as name => label.
     * @return array
     */
    static public function groupRoles()
    {
        return array_merge(
            self::groupAdminRoles(),
            self::groupModeratorRoles()
        );
    }

    static public function groupAdminRoles()
    {
        return array(
            Role::GROUP_ADMINISTRATOR => 'Group Administrator',
            Role::GROUP_PUBLISHER => 'Group Publisher',
            Role::GROUP_EDITOR => 'Group Editor',
            Role::GROUP_APPROVER => 'Group Approver',
            Role::GROUP_MODERATOR => 'Group Moderator',
        );
    }

    static public function groupModeratorRoles()
    {
        return array(
            Role::GROUP_CONTRIBUTOR => 'Group Contributor',
            Role::GROUP_REVIEWER => 'Group Reviewer',
            Role::GROUP_TRANSLATOR => 'Group Translator',
            Role::GROUP_MEMBER => 'Group Member',
        );
    }

    /**
     * Get a label for a group role by it's title.
     * @param string $title the role title from the database.
     * @return string the role label that can be shown in the UI.
     * @throws CException if a label cannot be found based on title.
     */
    static public function groupRoleTitleToLabel($title)
    {
        $titleToLabelMap = array(
            Role::GROUP_ADMINISTRATOR => 'Administrator',
            Role::GROUP_PUBLISHER => 'Publisher',
            Role::GROUP_EDITOR => 'Editor',
            Role::GROUP_APPROVER => 'Approver',
            Role::GROUP_MODERATOR => 'Moderator',
            Role::GROUP_CONTRIBUTOR => 'Contributor',
            Role::GROUP_REVIEWER => 'Reviewer',
            Role::GROUP_TRANSLATOR => 'Translator',
            Role::GROUP_MEMBER => 'Member',
        );
        if (!isset($titleToLabelMap[$title])) {
            throw new CException(sprintf('No role "%s" can be found.', $title));
        }
        return $titleToLabelMap[$title];
    }

    static public function getAddItemSystemRoleMap()
    {
        $map = array();
        foreach (DataModel::crudModels() as $modelClass => $table) {
            $map["$modelClass.Add"] = array(
                Role::AUTHENTICATED,
            );
        }
        return $map;
    }

    /**
     * @return array
     */
    static public function operationToSystemRolesMap()
    {
        return array_merge(
            self::getAddItemSystemRoleMap(),
            array(
                'Browse' => array(
                    Role::GUEST,
                    Role::AUTHENTICATED,
                ),
                'View' => array(
                    Role::GUEST,
                    Role::AUTHENTICATED,
                ),
                'Add' => array(
                    Role::AUTHENTICATED,
                ),
                'P3media.Import.*' => array(
                    Role::AUTHENTICATED,
                ),
            )
        );
    }

    /**
     * @return array
     */
    static public function operationToGroupRolesMap()
    {
        return array(
            'Edit' => array(
                Role::GROUP_EDITOR,
                Role::GROUP_ADMINISTRATOR,
            ),
            'Translate' => array(
                Role::GROUP_TRANSLATOR,
                Role::GROUP_ADMINISTRATOR,
            ),
            'Preview' => array(
                Role::GROUP_REVIEWER,
                Role::GROUP_ADMINISTRATOR,
            ),
            'PrepareForReview' => array(
                Role::GROUP_CONTRIBUTOR,
                Role::GROUP_EDITOR,
                Role::GROUP_ADMINISTRATOR,
            ),
            'PrepareForPublishing' => array(
                Role::GROUP_CONTRIBUTOR,
                Role::GROUP_EDITOR,
                Role::GROUP_ADMINISTRATOR,
            ),
            'Evaluate' => array(
                Role::GROUP_REVIEWER,
                Role::GROUP_ADMINISTRATOR,
            ),
            'Review' => array(
                Role::GROUP_REVIEWER,
                Role::GROUP_ADMINISTRATOR,
            ),
            'Proofread' => array(
                Role::GROUP_REVIEWER,
                Role::GROUP_ADMINISTRATOR,
            ),
            'Publish' => array(
                Role::GROUP_PUBLISHER,
                Role::GROUP_ADMINISTRATOR,
            ),
            'Clone' => array(
                Role::GROUP_EDITOR,
                Role::GROUP_ADMINISTRATOR,
            ),
            'Approve' => array(
                Role::GROUP_APPROVER,
                Role::GROUP_ADMINISTRATOR,
            ),
            'Remove' => array(
                Role::GROUP_CONTRIBUTOR,
                Role::GROUP_ADMINISTRATOR,
            ),
            'Replace' => array(
                Role::GROUP_MODERATOR,
                Role::GROUP_ADMINISTRATOR,
            ),
            'ChangeGroup' => array_keys(self::groupRoles()),
            'GrantPermission' => array(
                Role::GROUP_MODERATOR,
                Role::GROUP_ADMINISTRATOR,
            ),
            'GrantGroupAdminPermissions' => array(
                Role::GROUP_ADMINISTRATOR,
            ),
            'GrantGroupModeratorPermissions' => array(
                Role::GROUP_MODERATOR,
                Role::GROUP_ADMINISTRATOR,
            ),
        );
    }

} 