<span></span>
<a id="close" href="javascript:removeUserpane()" title="Sluiten"><img src="<?=$htdocs_path;?>/images/fileclose.png" width="22" height="22" alt="Sluiten" /></a>
<h2>Boeking <?=strftime("%e %B %y", strtotime($date));?> om <?=$hour;?>:00</h2>
<?if($users['links'] || $usersearch):?>
<div id="searchform">
<input type="text" size="25" name="searchname" id="searchname" value="<?=$usersearch;?>" />
<input type="button" onclick="<?printf("userSearch('%s',%d,\$F('searchname'))", $date, $hour);?>" value="Zoek" />
</div>
<?endif;?>
<p id="navigation"><?=$users['links'];?></p>
<ul>
<?foreach($users['data'] as $item):?>
	<li>
		<a title="Reserveren" href="javascript:subscribe('<?=$date;?>', <?=$hour;?>, <?=$item['id'];?>, false);"><img src="<?=$htdocs_path;?>/images/subscribe.png" width="16" height="16" alt="Reserveren" /></a>
		<?if($include_vip):?>
		<a title="Reserveren incl. materiaal" href="javascript:subscribe('<?=$date;?>', <?=$hour;?>, <?=$item['id'];?>, true);"><img src="<?=$htdocs_path;?>/images/subscribe_material.png" width="16" height="16" alt="Reserveren incl. materiaal" /></a>
		<?endif;?>
		<?=$item['formatName'];?>
	</li>
<?endforeach;?>
</ul>
<div>
<img src="<?=$htdocs_path;?>/images/subscribe.png" width="16" height="16" alt="Reserveren" /> : Piste
<img src="<?=$htdocs_path;?>/images/subscribe_material.png" width="16" height="16" alt="Reserveren incl. materiaal" /> : Piste incl. materiaal
</div>
