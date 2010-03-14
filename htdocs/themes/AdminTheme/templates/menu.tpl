<?if($menu):?>
<ul>
<?foreach($menu as $item):?>
<li><a <?=($item['selected']) ? 'class="selected"' : '';?> href="<?=$item['path'];?>" title="<?=$item['name'];?>"><?=$item['name'];?></a></li>
<?endforeach;?>
</ul>
<?endif;?>
