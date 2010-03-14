<ul>
<?foreach($schedule['data'] as $item):?>
	<li><a href="javascript:getTimeList('<?=date('Y-m-d', $item['reservation_date']);?>');"><?=strftime('%a %d %b %y', $item['reservation_date']);?> <?=$item['reservation_time'];?>:00 [<?=$item['firstname'];?>]</a></li>
<?endforeach;?>
</ul>
