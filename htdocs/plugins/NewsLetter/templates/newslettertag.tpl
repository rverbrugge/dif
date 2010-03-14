<?if($adminSection):?>
<h1><?=$pageTitle;?></h1>
<em><?=isset($tagName) ? "$tagName $currentViewName" : $currentViewName;?></em>

<?if(isset($errorMessage)):?><p class="error"><?=$errorMessage;?></p><?endif;?>
<?endif;?>

<?  /*-----edit {{{------*/
if($currentView == NewsLetter::VIEW_TAG_EDIT):?>

<h3>Modified Tag: <?=$parent_tag;?></h3>

<p class="options">
<a href="<?=$href_back;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
</p>


<form action="<?=$urlPath;?>" name="editform" method="post" enctype="multipart/form-data">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="tag" value="<?=$tag;?>" />
<input type="hidden" name="nl_id" value="<?=$nl_id;?>" />
<input type="hidden" name="parent_tag" value="<?=$parent_tag;?>" />

<table id="form">
	<tr><th class="title" colspan="2">User defined tags</th></tr>
	<tr>
	<td colspan="2">
		<strong><em>One tag per line, only use characters and numbers</em></strong><br />
		<textarea name="tags" rows="3" cols="75"><?=$tags;?></textarea>
	</td>
	</tr>
	<tr>
		<th colspan="2" class="title">
			Html template to render user defined tags above
		</th>
	</tr>
	<tr> 
		<td colspan="2">
			<p>A php tag has the following form: &lt;?=$foobar;?&gt;<br /><strong><em>Use the file load button to generate php tags.</em></strong></p>
			<textarea id="area1" name="template" rows="20" cols="150"><?=$template;?></textarea>
		</td>
	</tr>
	<tr>
		<th colspan="2" class="title">
			CSS Stylesheet
		</th></tr>
		<tr><td colspan="2"><textarea id="area2" name="stylesheet" rows="20" cols="150"><?=$stylesheet;?></textarea></td>
	</tr>
	<tr><th class="title" colspan="2">Available variables</th></tr>
	<tr>
	<td colspan="2">
			<ul>
			<?foreach($filevars as $key=>$value):?>
			<?if(is_array($value)):?>
				<?foreach($value as $subkey=>$item):?>
					<li><?printf('$%s[%s] = %s', $key, $subkey, $item);?></li>
				<?endforeach;?>
			<?else:?>
			<li><?="\$$key = $value";?></li>
			<?endif;?>
			<?endforeach;?>
			</ul>
	</td>
	</tr>
</table>
<input type="submit" value="Save" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>
<?endif;//}}}?>

<?  /*-----delete {{{------*/
if($currentView == NewsLetter::VIEW_TAG_DELETE):?>

<form action="<?=$urlPath;?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="tag" value="<?=$tag;?>" />
<input type="hidden" name="nl_id" value="<?=$nl_id;?>" />
<input type="hidden" name="parent_tag" value="<?=$parent_tag;?>" />

<p>Weet u zeker dat u de post <strong><?=$formatName;?></strong> wilt delete?</p>

<input type="submit" value="Delete" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>
<?endif;//}}}?>
