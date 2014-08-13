<?php

class GroupRoleToggleColumn extends TbToggleColumn
{
    protected function initButton()
    {
        parent::initButton();

        $this->button['url'] = 'Yii::app()->controller->createUrl("' . $this->toggleAction . '",array("id"=>$data->accountId, "attribute"=>"{$data->groupName}_' . $this->name . '"))';
    }

    protected function renderDataCellContent($row, $data)
    {
        $this->button['htmlOptions']['id'] = "toggleGroupRole_{$data->groupName}_{$this->name}";

        parent::renderDataCellContent($row, $data); // TODO: Change the autogenerated stub
    }
}