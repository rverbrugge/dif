<?if($adminSection):?>
<h1><?=$pageTitle;?></h1>
<em><?=$currentViewName;?></em>
<?endif;?>

<?if(isset($errorMessage)):?><p class="error"><?=$errorMessage;?></p><?endif;?>

<?  /*-----tree edit {{{------*/
if($currentView == ViewManager::TREE_EDIT):?>
<p class="options">
<a href="<?=$href_up;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
<a href="javascript:document.editform.submit();"><img class="noborder" src="<?=$img_save['src'];?>" width="<?=$img_save['width'];?>" height="<?=$img_save['height'];?>" alt="save" title="save" /></a>
</p>


<form action="<?=$urlPath;?>" method="post" name="editform" enctype="multipart/form-data">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="tag" value="<?=$tag;?>" />

<table id="form">
	<tr><th class="title" colspan="2">Settings</th></tr>
	<tr><th>Display style</th>							<td><select size="1" name="display"><?=$cbo_display;?></select></td></tr>
	<tr><th>Group by</th>					<td><select size="1" name="group_type"><?=$cbo_group;?></select></td></tr>
	<tr><th>Items per page</th>				<td><input type="text" name="rows" value="<?=$rows;?>" size="5" /></td></tr>
	<tr><th>Date format </th>								<td><input type="text" name="date_format" value="<?=$date_format;?>" size="15" /> <em>(use <a href="http://nl.php.net/strftime" target="_blank">strftime</a> format)</em></td></tr>
	<tr><th>Caption detail link</th>				<td><input type="text" name="cap_detail" value="<?=$cap_detail;?>" size="75" /></td></tr>
	<tr><th class="title" colspan="2">Archive settings</th></tr>
	<tr><th>Period</th>	
		<td>
			from: <input type="text" class="date" id="start" name="start" value="<?=$start;?>" size="12" />
			till: <input type="text" class="date" id="stop" name="stop" value="<?=$stop;?>" size="12" />
		</td>
	</tr>
	<tr><th>Node</th>	<td><?=$ref_tree_id;?></td></tr>
</table>

<input type="submit" value="Save" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>

<?endif;//}}}?>

<?  /*-----overview {{{------*/
if($currentView == ViewManager::OVERVIEW):?>
	
<?=$tpl_list;?>

<?endif;//}}}?>

<?  /*----- detail {{{------*/
if($currentView == Calendar::VIEW_ARCHIVE):?>
	<p><em>
	<?=strftime($settings['date_format'], $cal['start']);?> <?=$cal['start_time'];?>
	<?=$cal['stop'] != $cal['start'] ? sprintf(' - %s %s', strftime($settings['date_format'], $cal['stop']), $cal['stop_time']) : ($cal['stop_time'] ? ' - '.$cal['stop_time'] : '');?> 
 &nbsp; views: <?=$cal['count'];?></em></p>

	<?if($calsettings['detail_img'] && $cal['thumbnail']):?>
	<div class="calimg">
	<img src="<?=$cal['thumbnail']['src'];?>" height="<?=$cal['thumbnail']['height'];?>" width="<?=$cal['thumbnail']['width'];?>" alt="<?=htmlentities($cal['name']);?>" />
	</div>
	<?endif;?>

	<?if($cal['intro']):?><p class="calintro"><?=nl2br(htmlentities($cal['intro']));?></p><?endif;?>

	<?=$cal['text'];?>

	<br clear="all" />
	<?=$calattachment;?>

	<?=$template_calimage;?>

	<?if(isset($calendarcomment)):?>
	<p><a href="<?=$href_back;?>"><?=$calsettings['cap_back'];?></a></p>
	<?=$calendarcomment;?>
	<?endif;?>


	<p><a href="<?=$href_back;?>"><?=$calsettings['cap_back'];?></a></p>
<?endif;//}}}?>
