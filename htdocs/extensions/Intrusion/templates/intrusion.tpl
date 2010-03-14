<?if($adminSection):?>
<h1><?=$pageTitle;?></h1>
<em><?=isset($tagName) ? "$tagName $currentViewName" : $currentViewName;?></em>
<?endif;?>

<?if(isset($errorMessage)):?><p class="error"><?=$errorMessage;?></p><?endif;?>

<?  /*-----overview {{{------*/
if($currentView == Intrusion::VIEW_OVERVIEW):?>
<p class="options">
<a href="<?=$href_up;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
<a href="<?=$href_new;?>"><img class="noborder" src="<?=$img_new['src'];?>" width="<?=$img_new['width'];?>" height="<?=$img_new['height'];?>" alt="new" title="new" /></a>
</p>

<div class="search">
<form target="_self" action="<?=$urlPath;?>" method="get">
<?foreach($searchparam as $key=>$value):?>
<input type="hidden" name="<?=$key;?>" value="<?=$value;?>" />
<?endforeach;?>
<input type="text" size="50" name="search" value="<?=array_key_exists('search', $searchcriteria) ? $searchcriteria['search'] : '';?>" />
<input type="submit" value="search" class="formbutton" />
</form>
</div>


<?if($list['page_numbers']['total'] > 1):?><p class="pagenav"><?=$list['totalItems'];?> items gevonden: <?=$list['links'];?></p><?endif;?>

<table class="overview">
<tr>
<th></th>
<th>Ip</th>
<th>State</th>
<th>Permanent</th>
<th>Count</th>
<th>Expiration date</th>
<th>Modified</th>
<th>Created</th>
</tr>
<?foreach($list['data'] as $item):?>
<tr<?=!$item['activated'] ? ' class="deactivated"':'';?>>
<td>
	<a href="<?=$item['href_del'];?>"><img class="noborder" src="<?=$img_del['src'];?>" width="<?=$img_del['width'];?>" height="<?=$img_del['height'];?>" alt="delete" title="delete" /></a>
	<a href="<?=$item['href_edit'];?>"><img class="noborder" src="<?=$img_edit['src'];?>" width="<?=$img_edit['width'];?>" height="<?=$img_edit['height'];?>" alt="edit" title="edit" /></a>
</td>
<td><?=$item['ip'];?></td>
<td><?=$item['state'];?></td>
<td><?=$item['permanent'] ? 'yes' : 'no';?></td>
<td><?=$item['count'];?></td>
<td><?=($item['activated'] && !$item['permanent']) ? strftime('%c', $item['expire']) : '';?></td>
<td><?=strftime('%c', $item['ts']);?></td>
<td><?=strftime('%c', $item['createdate']);?></td>
</tr>
<?endforeach;?>
</table>

<?if($list['page_numbers']['total'] > 1):?><p class="pagenav"><?=$list['totalItems'];?> items found: <?=$list['links'];?></p><?endif;?>

<?endif;//}}}?>

<?  /*-----tree edit {{{------*/
if($currentView == Intrusion::VIEW_NEW || $currentView == Intrusion::VIEW_EDIT ):?>

<p class="options">
<a href="<?=$href_up;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
<a href="javascript:document.myform.submit();"><img class="noborder" src="<?=$img_save['src'];?>" width="<?=$img_save['width'];?>" height="<?=$img_save['height'];?>" alt="save" title="save" /></a>
</p>

<form action="<?=$urlPath;?>" name="myform" method="post" enctype="multipart/form-data">
<input type="hidden" name="ext_id" value="<?=$ext_id;?>" />
<?if($currentView == Intrusion::VIEW_EDIT):?>
<input type="hidden" name="ip" value="<?=$ip;?>" />
<?endif;?>

<table id="form">
	<tr><th class="title" colspan="2">Settings</th></tr>
	<tr><th>Permanent</th>  	<td><input type="checkbox" name="permanent" <?=($permanent)? 'checked ':'';?> class="noborder" /></td></tr>
	<?if($currentView == Intrusion::VIEW_NEW):?>
	<tr><th>Ip address</th>	<td><input type="text" name="ip" value="<?=$ip;?>" size="75" /></td></tr>
	<?else:?>
	<tr><th>Ip address</th>	<td><?=$ip;?></td></tr>
	<?endif;?>
	<tr><th>Attempts</th>	<td><input type="text" name="count" value="<?=$count;?>" size="5" /></td></tr>
</table>

<input type="submit" value="Save" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>
<?endif;//}}}?>

<?  /*----- delete {{{------*/
if($currentView == Intrusion::VIEW_DELETE):?>

<form action="<?=$urlPath;?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="ext_id" value="<?=$ext_id;?>" />
<input type="hidden" name="ip" value="<?=$ip;?>" />

<p>Are you sure you want to delete the intrusion record for host <strong><?=$ip;?></strong>?</p>

<input type="submit" value="Delete" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>
<?endif;//}}}?>
