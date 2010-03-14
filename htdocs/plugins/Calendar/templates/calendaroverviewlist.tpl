<?if($cal['page_numbers']['total'] > 1):?><p class="pagenav"><?=$cal['totalItems'];?> items: <?=$cal['links'];?></p><?endif;?>

<?/*----- grouptype {{{-----*/
$groupCounter = 0;
//}}}?>

<?/*----- full {{{-----*/?>
<?if($settings['display'] == Calendar::DISP_FULL):?>

<?foreach($cal['data'] as $item):?>

<?/*----- heading {{{-----*/
if($settings['group_type'] == Calendar::GRP_YEAR)
{
	$year = date('Y', $item['start']);
	if($groupCounter != $year)
	{
		printf('<h1 class="calendar">%s</h1>', strftime('%Y', $item['start'])); 
		$groupCounter = $year;
	}
}
elseif($settings['group_type'] == Calendar::GRP_MONTH)
{
	$month = date('m', $item['start']);
	if($groupCounter != $month)
	{
		printf('<h1 class="calendar">%s</h1>', strftime('%B %Y', $item['start'])); 
		$groupCounter = $month;
	}
}
//}}}?>

<div class="calitem">  
	<?if($settings['comment']):?>
	<h2><a href="<?=$item['href_detail'];?>"><?=htmlentities($item['name']);?></a></h2>
	<?else:?>
	<h2><?=htmlentities($item['name']);?></h2>
	<?endif;?>
	<p>
	<?= !$item['activated'] && $settings['history_comment'] ? '<em class="history">' : '<em>' ?>
	<?=strftime($settings['date_format'], $item['start']);?> <?=$item['start_time'];?>
	<?=$item['stop'] != $item['start'] ? sprintf(' - %s %s', strftime($settings['date_format'], $item['stop']), $item['stop_time']) : ($item['stop_time'] ? ' - '.$item['stop_time'] : '');?> 
	<?if(!$item['activated'] && $settings['history_comment']):?><span class="hiscomment"><?=$settings['history_comment'];?></span><?endif;?>
	</em></p> 
	
	<?if($item['thumbnail']):?>
	<div class="calimg">
	<a href="<?=$item['href_detail'];?>"><img src="<?=$item['thumbnail']['src'];?>" height="<?=$item['thumbnail']['height'];?>" width="<?=$item['thumbnail']['width'];?>" alt="<?=htmlentities($item['name']);?>" /></a>
	</div>
	<?endif;?>

	<?if($item['intro']):?><p class="newsintro"><?=nl2br(htmlentities($item['intro']));?></p><?endif;?>

	<?=$item['text'];?>

	<?=$item['newsattachment'];?>

	<?=$item['template_newsimage'];?>

	<?if($settings['comment']):?>
	<p><a href="<?=$item['href_detail'];?>"><?=$settings['cap_detail'];?></a></p>
	<?endif;?>

</div>
<div class="calbreak"><hr /></div>
<?endforeach;?>
<?//}}}?>

<?/*----- image {{{-----*/?>
<?elseif($settings['display'] == Calendar::DISP_IMAGE):?>

<?foreach($cal['data'] as $item):?>
<?/*----- heading {{{-----*/
if($settings['group_type'] == Calendar::GRP_YEAR)
{
	$year = date('Y', $item['start']);
	if($groupCounter != $year)
	{
		printf('<h1 class="calendar">%s</h1>', strftime('%Y', $item['start'])); 
		$groupCounter = $year;
	}
}
elseif($settings['group_type'] == Calendar::GRP_MONTH)
{
	$month = date('m', $item['start']);
	if($groupCounter != $month)
	{
		printf('<h1 class="calendar">%s</h1>', strftime('%B %Y', $item['start'])); 
		$groupCounter = $month;
	}
}
//}}}?>

<div class="calitem">  
	<h2><a href="<?=$item['href_detail'];?>"><?=htmlentities($item['name']);?></a></h2>
	<p>
	<?= !$item['activated'] && $settings['history_comment'] ? '<em class="history">' : '<em>' ?>
	<?=strftime($settings['date_format'], $item['start']);?> <?=$item['start_time'];?>
	<?=$item['stop'] != $item['start'] ? sprintf(' - %s %s', strftime($settings['date_format'], $item['stop']), $item['stop_time']) : ($item['stop_time'] ? ' - '.$item['stop_time'] : '');?> 
	<?if(!$item['activated'] && $settings['history_comment']):?><span class="hiscomment"><?=$settings['history_comment'];?></span><?endif;?>
	</em></p>

	<?if($item['thumbnail']):?>
	<div class="calimg">
	<a href="<?=$item['href_detail'];?>"><img src="<?=$item['thumbnail']['src'];?>" height="<?=$item['thumbnail']['height'];?>" width="<?=$item['thumbnail']['width'];?>" alt="<?=htmlentities($item['name']);?>" /></a>
	</div>
	<?endif;?>

</div>
<div class="calbreak"><hr /></div>
<?endforeach;?>
<?//}}}?>

<?/*----- intro {{{-----*/?>
<?elseif($settings['display'] == Calendar::DISP_INTRO):?>

<?foreach($cal['data'] as $item):?>
<?/*----- heading {{{-----*/
if($settings['group_type'] == Calendar::GRP_YEAR)
{
	$year = date('Y', $item['start']);
	if($groupCounter != $year)
	{
		printf('<h1 class="calendar">%s</h1>', strftime('%Y', $item['start'])); 
		$groupCounter = $year;
	}
}
elseif($settings['group_type'] == Calendar::GRP_MONTH)
{
	$month = date('m', $item['start']);
	if($groupCounter != $month)
	{
		printf('<h1 class="calendar">%s</h1>', strftime('%B %Y', $item['start'])); 
		$groupCounter = $month;
	}
}
//}}}?>

<div class="calitem">  
	<h2><a href="<?=$item['href_detail'];?>"><?=htmlentities($item['name']);?></a></h2>
	<p>
	<?= !$item['activated'] && $settings['history_comment'] ? '<em class="history">' : '<em>' ?>
	<?=strftime($settings['date_format'], $item['start']);?> <?=$item['start_time'];?>
	<?=$item['stop'] != $item['start'] ? sprintf(' - %s %s', strftime($settings['date_format'], $item['stop']), $item['stop_time']) : ($item['stop_time'] ? ' - '.$item['stop_time'] : '');?> 
	<?if(!$item['activated'] && $settings['history_comment']):?><span class="hiscomment"><?=$settings['history_comment'];?></span><?endif;?>
	</em></p>

	<?if($item['intro']):?><p><?=nl2br(htmlentities($item['intro']));?></p><?endif;?>
	<p><a href="<?=$item['href_detail'];?>"><?=$settings['cap_detail'];?></a></p>
</div>
<div class="calbreak"><hr /></div>
<?endforeach;?>
<?//}}}?>

<?/*----- intro & image {{{-----*/?>
<?elseif($settings['display'] == Calendar::DISP_INTRO_IMAGE):?>

<?foreach($cal['data'] as $item):?>
<?/*----- heading {{{-----*/
if($settings['group_type'] == Calendar::GRP_YEAR)
{
	$year = date('Y', $item['start']);
	if($groupCounter != $year)
	{
		printf('<h1 class="calendar">%s</h1>', strftime('%Y', $item['start'])); 
		$groupCounter = $year;
	}
}
elseif($settings['group_type'] == Calendar::GRP_MONTH)
{
	$month = date('m', $item['start']);
	if($groupCounter != $month)
	{
		printf('<h1 class="calendar">%s</h1>', strftime('%B %Y', $item['start'])); 
		$groupCounter = $month;
	}
}
//}}}?>

<div class="calitem">  
	<h2><a href="<?=$item['href_detail'];?>"><?=htmlentities($item['name']);?></a></h2>

	<?if($item['thumbnail']):?>
	<div class="calimg">
	<a href="<?=$item['href_detail'];?>"><img src="<?=$item['thumbnail']['src'];?>" height="<?=$item['thumbnail']['height'];?>" width="<?=$item['thumbnail']['width'];?>" alt="<?=htmlentities($item['name']);?>" /></a>
	</div>
	<?endif;?>

	<div class="calmain">
	<?= !$item['activated'] && $settings['history_comment'] ? '<em class="history">' : '<em>' ?>
	<?=strftime($settings['date_format'], $item['start']);?> <?=$item['start_time'];?>
	<?=$item['stop'] != $item['start'] ? sprintf(' - %s %s', strftime($settings['date_format'], $item['stop']), $item['stop_time']) : ($item['stop_time'] ? ' - '.$item['stop_time'] : '');?> 
	<?if(!$item['activated'] && $settings['history_comment']):?><span class="hiscomment"><?=$settings['history_comment'];?></span><?endif;?>
	</em><br />

	<?if($item['intro']):?><?=nl2br(htmlentities($item['intro']));?><?endif;?>
	<p><a href="<?=$item['href_detail'];?>"><?=$settings['cap_detail'];?></a></p>
	</div>
</div>
<div class="calbreak"><hr /></div>
<?endforeach;?>
<?//}}}?>

<?/*----- brief {{{-----*/?>
<?elseif($settings['display'] == Calendar::DISP_BRIEF):?>

<table class="calitem">  
<?foreach($cal['data'] as $item):?>
<?/*----- heading {{{-----*/
if($settings['group_type'] == Calendar::GRP_YEAR)
{
	$year = date('Y', $item['start']);
	if($groupCounter != $year)
	{
		printf('<tr><th colspan="2" class="calendar">%s</th></tr>', strftime('%Y', $item['start']));
		$groupCounter = $year;
	}
}
elseif($settings['group_type'] == Calendar::GRP_MONTH)
{
	$month = date('m', $item['start']);
	if($groupCounter != $month)
	{
		printf('<tr><th colspan="2" class="calendar">%s</th></tr>', strftime('%B %Y', $item['start']));
		$groupCounter = $month;
	}
}
//}}}?>
<?=$item['activated'] ? '<tr>' : '<tr class="disabled">' ?>
	<td class="date">
	<em>
	<?=strftime($settings['date_format'], $item['start']);?>
	<?=$item['stop'] != $item['start'] ? sprintf(' - %s', strftime($settings['date_format'], $item['stop'])) : '';?>
	</em><br />
	</td>
	<td class="main">
		<a href="<?=$item['href_detail'];?>"><?=htmlentities($item['name']);?></a>
		<?if(!$item['activated'] && $settings['history_comment']):?> <span class="history hiscomment"><?=$settings['history_comment'];?></span><?endif;?>
	</td>
</tr>
<?endforeach;?>
</table>

<?endif;?>
<?//}}}?>

<?if($cal['page_numbers']['total'] > 1):?><p class="pagenav"><?=$cal['totalItems'];?> items: <?=$cal['links'];?></p><?endif;?>
