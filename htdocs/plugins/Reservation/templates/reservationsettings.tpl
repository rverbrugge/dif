<h1><?=$pageTitle;?></h1>
<em><?=$currentViewName;?></em>

<?if(isset($errorMessage)):?><p class="error"><?=$errorMessage;?></p><?endif;?>

<?  /*-----conf edit {{{------*/
if($currentView == Reservation::VIEW_CONFIG):?>
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
	<tr><th>Caption subscribe link</th>					<td><input type="text" name="cap_subscribe" value="<?=$cap_subscribe;?>" size="50" /></td></tr>
	<tr><th>Caption unsubscribe link</th>				<td><input type="text" name="cap_unsubscribe" value="<?=$cap_unsubscribe;?>" size="50" /></td></tr>
	<tr><th>Maximum subscriptions per user</th>	<td><input type="text" name="max_subscribe" value="<?=$max_subscribe;?>" size="5" /></td></tr>
	<tr><th>Maximum subscriptions per period</th>	<td><input type="text" name="slots" value="<?=$slots;?>" size="5" /></td></tr>
	<tr><th>Maximum VIP subscriptions per period</th>	<td><input type="text" name="vip_slots" value="<?=$vip_slots;?>" size="5" /></td></tr>
	<tr><th>VIP user group</th>												<td><select size="1" name="vip_grp_id"><?=$cbo_vip_grp;?></select></td></tr>
	<tr><th class="title" colspan="2">Opening hours</th></tr>
	<?foreach($schedule_times as $item):?>
	<tr><th><?=$item['desc'];?></th>				
		<td>
		from <input type="text" name="schedule_times[<?=$item['id'];?>][start]" value="<?=$item['start'];?>" size="6" /> &nbsp;till &nbsp;
		<input type="text" name="schedule_times[<?=$item['id'];?>][stop]" value="<?=$item['stop'];?>" size="6" />
		</td>
	</tr>
	<?endforeach;?>
</table>

<input type="submit" value="Save" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>

<?endif;//}}}?>
