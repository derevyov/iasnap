<?php
//$this->breadcrumbs=array(
//    "Головна"=>array("/"),
//    "Админка"=>array("/admin"),
//    $this->module->label => array("/".$this->module->id),
//    $this->label => array("/".$this->module->id."/".$this->id),
//);
$root_img = CHtml::image($this->createUrl("default/getimage",array("image"=>"Home")),"Корень",array("width"=>24,"height"=>24));
$parent_img = CHtml::image($this->createUrl("default/getimage",array("image"=>"Arrow_up")),"До батьків",array("width"=>24,"height"=>24));
$add_img = CHtml::image($this->createUrl("default/getimage",array("image"=>"Plus")),"Зар.таблицу",array("width"=>24,"height"=>24));
$topparentid = (isset($parentid) && $parentid!=NULL)?FFRegistry::model()->findByPk($parentid)->parent:NULL;
$this->menu = array(
        array("label"=>$root_img."Корень","url"=>array($this->id."/index","parentid"=>"")),
        array("label"=>$parent_img."До батьків","url"=>array($this->id."/index","parentid"=>$topparentid)),
        array("label"=>" ", "itemOptions"=>array("style"=>"border-top: double #55b")),
        array("label"=>$add_img."Зар.таблицу","url"=>array($this->id."/registry")),    
        array("label"=>$add_img."Додати нащадка","url"=>array($this->id."/new","parentid"=>$parentid),"visible"=>($parentid!=NULL)),
);

$criteria=new CDbCriteria();
if ($parentid==null) {
    $criteria->addCondition("parent is null");
}
else {
    $criteria->params["parent"] = $parentid;
    $criteria->addCondition("parent = :parent");
}
$dataProvider=new CActiveDataProvider("FFRegistry", array(
        'criteria' => $criteria,
        'pagination' => array(
            'pageSize' => 30,
        )
    )
);
$headlabel = $dataProvider->model->attributeLabels();

$this->widget("zii.widgets.CListView", array(
    'dataProvider'=>$dataProvider,
    'itemView'=>'_view',
    'itemsTagName'=>'tbody',
    'tagName'=>'table',
    'template'=>'<caption>{summary}</caption><thead><tr><th>ID</th><th>'.
    $headlabel["tablename"].'</th><th>'.$headlabel["description"].'</th><th>Дії</th></tr></thead>{items}',
    )
);
if ($this->action->id=="edit") {
    $this->renderPartial("_edit",array("id"=>$id,"parentid"=>$parentid));
}
else if ($this->action->id=="new") {
    $this->renderPartial("_new",array("parentid"=>$parentid,"formregistry"=>$registry));
}
else if ($this->action->id=="fieldnew") {
    $this->renderPartial("_addfield",array("parentid"=>$parentid));
}
else if ($this->action->id=="registry") {
    $this->renderPartial("registry",array("parentid"=>$parentid,"modelregistry"=>$modelregistry));
}

