<?if($voted):?>

<?/*----- horizontal {{{-----*/?>
<?if($settings['display'] == Poll::DISP_HORIZONTAL):?>

<table>
<?foreach($pollitem['data'] as $item):?>
<tr>
	<th><?=htmlentities($item['name']);?> </th><td class="votes"> [<?=$item['votes'];?>]</td>
</tr>
<tr>
	<td class="scorecont" colspan="2">
		<div class="score" style="width:<?=round((($settings['width']-8)/100)*$item['percentage']);?>px !important;"> &nbsp;</div>
		<div class="scorecard" style="width:<?=$settings['width']-8?>px !important"><?=round($item['percentage']);?> %</div>
	</td>
</tr>
<?endforeach;?>
</table>
<?//}}}?>

<?/*----- vertical {{{-----*/?>
<?elseif($settings['display'] == Poll::DISP_INTRO):?>

<?foreach($poll['data'] as $item):?>
<table class="poll_item">  
<tr>
	<td class="poll_main">
	  <em><?=date('d-m-Y', $item['online']);?></em>
	  <h2><?=htmlentities($item['name']);?></h2>
		<?if($item['vertical']):?><p><?=nl2br(htmlentities($item['vertical']));?></p><?endif;?>
		<a href="<?=$item['href_detail'];?>"><?=$settings['cap_detail'];?></a>
	</td>
</tr>
</table>
<?endforeach;?>
<?//}}}?>

<?/*----- circle {{{-----*/?>
<?elseif($settings['display'] == Poll::DISP_CIRCLE):?>

<?foreach($poll['data'] as $item):?>
<table class="poll_item">  
<tr>
	<td><em><?=date('d-m-Y', $item['online']);?></em></td>
	<td><a href="<?=$item['href_detail'];?>"><?=htmlentities($item['name']);?></a></td>
</tr>
</table>
<?endforeach;?>

<?endif;?>
<?//}}}?>

<?else:?>
<?foreach($pollitem['data'] as $item):?>
	<a href="javascript:vote(<?=$item['id'];?>);"><?=htmlentities($item['name']);?></a>
<?endforeach;?>
<?endif;?>

