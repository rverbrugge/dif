<?foreach($fields['data'] as $item):?>
<?if($item['class'] == "InputHidden" || $item['class'] == "InputLogin"):?>
<?=$item['html'];?>
<?endif;?>
<?endforeach;?>

<table class="form">  
<?foreach($fields['data'] as $item):?>
<?if($item['class'] == "InputHidden" || $item['class'] == "InputLogin") continue;?>
<?if($item['class'] == "InputDescription"):?>
<tr>
	
	<td class="description" colspan="<?=$settings['mandatorysign'] ? 3 : 2;?>">
		<h3><?=$item['name'];?></h3>
		<?if($item['description']) print "<p>".$item['description']."</p>";?>
	</td>
</tr>
<?else:?>
<tr <?=$item['mandatory'] ? 'class="mandatory"' : '';?>>
	<th><?=$item['name'];?><?if($item['description']) print "<p><em>".$item['description']."</em></p>";?></th>
	<td><?=$item['html'];?></td>
	<?if($settings['mandatorysign']):?>
	<td><?=$item['mandatory'] ? $settings['mandatorysign'] : '';?></td>
	<?endif;?>
</tr>
<?endif;?>
<?endforeach;?>
</table>

