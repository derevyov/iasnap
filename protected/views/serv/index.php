<?php
/* @var $this ServController */

//$this->breadcrumbs=array(
//	'Serv',
//);
if (isset($_GET['servid'])) {
$rows=GenServCatClass::model()->getIdService($_GET['servid']);
//GenServices::model()->find()->name
if (!empty($rows)){
?>
<h3>Послуги:</h3>
<font size=3 color="black">
<div id="poslugy">
<?
}
else 
{
echo "Нажаль послуг за обраною категорією ще нема.";
}
?><ol><table><?
foreach($rows as $row) {
    
     if (GenServices::model()->findByPk($row)->is_online=='так') {$status='<div id="isonline">online</div>';} else {$status='';}
    
       
   echo '<tr><td>'.$status.'</td><td><li><a href='.Yii::app()->baseUrl.'/index.php/service?class='.$_GET['class'].'&&param='.$row.'&&servid='.$_GET['servid'].'>'.GenServices::model()->findByPk($row)->name.'</a></li></td></tr>';       
       
       
         }
        
?></table></ol><?


//echo 
 
 //$rows=GenServices::model()->getService();
         
        

?></div>
</font>

<?} else {echo "Оберіть категорію";}?>

