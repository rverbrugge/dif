<?if($adminSection):?>
<h1><?=$pageTitle;?></h1>
<em><?=$currentViewName;?></em>
<?endif;?>

<?if(isset($errorMessage)):?><p class="error"><?=$errorMessage;?></p><?endif;?>

<?  /*-----tree edit {{{------*/
if($currentView == ViewManager::TREE_EDIT):?>

<p>
<a href="<?=$href_up;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
</p>

<form action="<?=$urlPath;?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="tag" value="<?=$tag;?>" />

<table id="form">
	<tr><th class="title" colspan="2">Instellingen</th></tr>
	<tr><th>Aantal items</th>	<td><input type="text" name="rows" value="<?=$rows;?>" size="5" /></td></tr>
	<tr><th>Volgorde</th>			<td><select size="1" name="display_order"><?=$cbo_order;?></select></td></tr>
	<tr><th>Naam</th>					<td><input type="text" name="name" value="<?=$name;?>" size="75" /></td></tr>
	<tr><th>Node</th>					<td><?=$ref_tree_id;?></td></tr>
</table>

<input type="submit" value="Save" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>
<?//}}}?>

<?  /*-----overview {{{------*/
else:?>
<h2><?=$name;?></h2>

<?/*----- intro {{{-----*/?>
<?if($display == Attachment::DISP_INTRO):?>

<?foreach($attachment['data'] as $item):?>
<table class="attachmentitem">  
<tr>
	<td>
	  <h2><a href="<?=$item['href_detail'];?>"><?=htmlentities($item['name']);?></a></h2>
		<?if($item['intro']):?><p><?=nl2br(htmlentities($item['intro']));?></p><?endif;?>
	</td>
</tr>
</table>
<?endforeach;?>
<?//}}}?>

<?/*----- brief {{{-----*/?>
<?elseif($display == Attachment::DISP_BRIEF):?>

<ul class="attachmentitem">  
<?foreach($attachment['data'] as $item):?>
	<li><a href="<?=$item['href_detail'];?>"><?=htmlentities($item['name']);?></a></li>
<?endforeach;?>
</ul>

<?endif;?>
<?//}}}?>

<?endif;//}}}?>
