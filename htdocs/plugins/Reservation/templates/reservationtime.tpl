<table>
<tr><th>Tijd</th><th>Reserveringen van <?=strftime('%A %d %B %Y', $timestamp);?></th></tr>
<?foreach($schedule as $item):?>
<tr>
	<td class="time">
	<?if($item['url']):?>
	<a title="Reserveren" href="javascript:<?=$item['url'];?>"><?=$item['id'];?>:00</a>
	<?else:?>
	<?=$item['id'];?>:00
	<?endif;?>
	</td>
	<td>
	<?if($item['users']):?>
	<ul>
	<?foreach($item['users'] as $user):?>
	<li>
	<?if($user['url']):?>
	<a title="Reservering annuleren" href="javascript:<?=$user['url'];?>"><img src="<?=$htdocs_path;?>images/delete.png" width="16" height="16" title="Reservering annuleren" alt="Reservering annuleren" /></a>
	<a title="Reservering annuleren" href="javascript:<?=$user['url'];?>"><?printf(($user['ownGroup'] && $user['vip']) ? '%s, %s (incl. materiaal)' : '%s, %s', $user['firstname'], $user['name']);?></a>
	<?else:?>
	<?printf(($user['ownGroup'] && $user['vip']) ? '%s, %s (incl. materiaal)' : '%s, %s', $user['firstname'], $user['name']);?>
	<?endif;?>
	</li>
	<?endforeach;?>
	</ul>
	<?endif;?>
	</td>
</tr>
<?endforeach;?>
</table>
