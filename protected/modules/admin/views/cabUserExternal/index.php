<?php
/* @var $this CabUserExternalController */
/* @var $dataProvider CActiveDataProvider */

$this->breadcrumbs=array(
	'Адміністративна панель'=>array('default/index'),
	'Управління користувачами'=>array('default/id3'),
	'Таблиця «Каталог зовнішніх користувачів порталу»',
);

$this->menu=array(
	array('label'=>'Додати', 'url'=>array('create')),
	array('label'=>'Управляти', 'url'=>array('admin')),
);
?>

<h1>Таблиця «Каталог зовнішніх користувачів порталу»</h1>

<?php $this->widget('zii.widgets.CListView', array(
	'dataProvider'=>$dataProvider,
	'itemView'=>'_view',
)); ?>
