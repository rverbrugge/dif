<?if($breadcrumb):?>
<?foreach($breadcrumb as $item):?>
<a href="<?=$item['path'];?>" title="<?=$item['name'];?>"><?=$item['name'];?></a> &raquo;
<?endforeach;?>
<?=$breadcrumb_last;?>
<?endif;?>
