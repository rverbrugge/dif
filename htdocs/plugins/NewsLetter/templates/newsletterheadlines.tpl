<?if($adminSection):?>
<h1><?=$pageTitle;?></h1>
<em><?=$currentViewName;?></em>
<?endif;?>

<?if(isset($errorMessage)):?><p class="error"><?=$errorMessage;?></p><?endif;?>

<?  /*-----tree edit {{{------*/
if($currentView == ViewManager::TREE_EDIT):?>

<p class="options">
<a href="<?=$href_up;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
</p>

<form action="<?=$urlPath;?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="tag" value="<?=$tag;?>" />

<table id="form">
	<tr><th class="title" colspan="2">Settings</th></tr>
	<tr><th>Amount of items</th>	<td><input type="text" name="rows" value="<?=$rows;?>" size="5" /></td></tr>
	<tr><th>Display style</th>			<td><select size="1" name="display"><?=$cbo_display;?></select></td></tr>
	<tr><th>Caption detail link</th>				<td><input type="text" name="cap_detail" value="<?=$cap_detail;?>" size="75" /></td></tr>
	<tr><th>Title</th>					<td><input type="text" name="name" value="<?=$name;?>" size="75" /></td></tr>
	<tr><th>Nodes</th>					<td><?=$ref_tree_id;?></td></tr>
</table>

<input type="submit" value="Save" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>
<?endif;//}}}?>

<?  /*-----overview {{{------*/
if($currentView == ViewManager::OVERVIEW):?>
<?if($settings['name']):?><h1><?=$settings['name'];?></h1><?endif;?>

<?/*----- image & full {{{-----*/?>
<?if($settings['display'] == NewsLetter::DISP_IMAGE || $settings['display'] == NewsLetter::DISP_FULL):?>

<?foreach($news['data'] as $item):?>
<div class="newsitem">  
	<h2><a href="<?=$item['href_detail'];?>"><?=htmlentities($item['name']);?></a></h2>

	<?if($item['thumbnail']):?>
	<div class="newsimg">
	<a href="<?=$item['href_detail'];?>"><img src="<?=$item['thumbnail']['src'];?>" height="<?=$item['thumbnail']['height'];?>" width="<?=$item['thumbnail']['width'];?>" alt="<?=htmlentities($item['name']);?>" /></a>
	</div>
	<?endif;?>

	<div class="newsmain">
	<em><?=strftime('%d-%B-%Y', $item['online']);?></em><br />
	<?if($item['intro']):?><?=nl2br(htmlentities($item['intro']));?><?endif;?>
	<p><a href="<?=$item['href_detail'];?>"><?=$settings['cap_detail'];?></a></p>
	</div>
</div>
<div class="newsbreak"><hr /></div>
<?endforeach;?>
<?//}}}?>

<?/*----- intro {{{-----*/?>
<?elseif($settings['display'] == NewsLetter::DISP_INTRO):?>

<?foreach($news['data'] as $item):?>
<div class="newsitem">
	<h2><?=htmlentities($item['name']);?></h2>
	<em><?=strftime('%d-%B-%Y', $item['online']);?></em>
	<?if($item['intro']):?><p><?=nl2br(htmlentities($item['intro']));?></p><?endif;?>
	<a href="<?=$item['href_detail'];?>"><?=$settings['cap_detail'];?></a>
</div>
<?endforeach;?>
<?//}}}?>

<?/*----- brief {{{-----*/?>
<?elseif($settings['display'] == NewsLetter::DISP_BRIEF):?>

<ul>
<?foreach($news['data'] as $item):?>
<li>
	<a href="<?=$item['href_detail'];?>"><?=htmlentities($item['name']);?></a>
</li>
<?endforeach;?>
</ul>

<?endif;?>
<?//}}}?>

<?endif;//}}}?>
