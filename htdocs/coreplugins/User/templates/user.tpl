<h1><?=$pageTitle;?></h1>
<em class="subtitle"><?=$currentViewName;?></em>

<?if(isset($errorMessage)):?><p class="error"><?=$errorMessage;?></p><?endif;?>

<?  /*-----admin overview {{{------*/
if($currentView == ViewManager::ADMIN_OVERVIEW):?>
<p class="options">
<a href="<?=$href_up;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
<a href="<?=$href_new;?>"><img class="noborder" src="<?=$img_new['src'];?>" width="<?=$img_new['width'];?>" height="<?=$img_new['height'];?>" alt="new" title="new" /></a>
<a href="<?=$href_import;?>"><img class="noborder" src="<?=$img_import['src'];?>" width="<?=$img_import['width'];?>" height="<?=$img_import['height'];?>" alt="import" title="import" /></a>
<a href="<?=$href_export;?>"><img class="noborder" src="<?=$img_export['src'];?>" width="<?=$img_export['width'];?>" height="<?=$img_export['height'];?>" alt="export" title="export" /></a>
</p>

<div class="search">
<form target="_self" action="<?=$urlPath;?>" method="get">
<?foreach($searchparam as $key=>$value):?>
<input type="hidden" name="<?=$key;?>" value="<?=$value;?>" />
<?endforeach;?>
<input type="text" size="50" name="search" value="<?=array_key_exists('search', $searchcriteria) ? $searchcriteria['search'] : '';?>" />
<input type="submit" value="zoeken" class="formbutton" />
</form>
</div>

<?if($list['page_numbers']['total'] > 1):?><p class="pagenav"><?=$list['totalItems'];?> items: <?=$list['links'];?></p><?endif;?>

<table class="overview">
<tr>
<th></th>
<th>Name</th>
<th>Email</th>
<th>Username</th>
<th>Receive notififations</th>
<th>Login count</th>
<th>Last login</th>
<th>Created</th>
</tr>
<?foreach($list['data'] as $item):?>
<tr>
<td>
	<a href="<?=$item['href_del'];?>"><img class="noborder" src="<?=$img_del['src'];?>" width="<?=$img_del['width'];?>" height="<?=$img_del['height'];?>" alt="delete" title="delete" /></a>
	<a href="<?=$item['href_edit'];?>"><img class="noborder" src="<?=$img_edit['src'];?>" width="<?=$img_edit['width'];?>" height="<?=$img_edit['height'];?>" alt="edit" title="edit" /></a>
</td>
<td><?="{$item['name']} {$item['firstname']}";?></td>
<td><?=$item['email'];?></td>
<td><?=$item['username'];?></td>
<td><?=$item['notify'] ? 'yes' : 'no';?></td>
<td><?=$item['logincount'];?></td>
<td><?=($item['logindate']) ? strftime('%c',$item['logindate']) : '';?></td>
<td><?=strftime('%c', $item['createdate']);?></td>
</tr>
<?endforeach;?>
</table>

<?if($list['page_numbers']['total'] > 1):?><p class="pagenav"><?=$list['totalItems'];?> items: <?=$list['links'];?></p><?endif;?>
<?endif;//}}}?>

<?  /*-----new and edit {{{------*/
if($currentView == ViewManager::ADMIN_NEW || $currentView == ViewManager::ADMIN_EDIT):?>

<p class="options">
<a href="<?=$href_back;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
<a href="javascript:selectall('cbo_grp_used'); document.myform.submit();"><img class="noborder" src="<?=$img_save['src'];?>" width="<?=$img_save['width'];?>" height="<?=$img_save['height'];?>" alt="save" title="save" /></a>
</p>

<form action="<?=$urlPath;?>" name="myform" method="post" enctype="multipart/form-data" onsubmit="selectall('cbo_grp_used');">
<input type="hidden" name="id" value="<?=$id;?>" />

<table id="form">
	<tr><th class="title" colspan="2">Genearl</th></tr>
	<tr><th>Active</th>  	<td><input type="checkbox" name="active" <?=($active)? 'checked ':'';?> class="noborder" /></td></tr>
	<tr><th>Receive notifications</th>  	<td><input type="checkbox" name="notify" <?=($notify)? 'checked ':'';?> class="noborder" /></td></tr>
	<tr><th>First name</th>	<td><input type="text" name="firstname" value="<?=$firstname;?>" size="75" /></td></tr>
	<tr><th>Surname</th>	<td><input type="text" name="name" value="<?=$name;?>" size="75" /></td></tr>
	<tr><th>Address</th>	
		<td>
			<input type="text" name="address" value="<?=$address;?>" size="65" />
			nr <input type="text" name="address_nr" value="<?=$address_nr;?>" size="5" />
	</td></tr>
	<tr><th>Zipcode / city</th>	
		<td>
			<input type="text" name="zipcode" value="<?=$zipcode;?>" size="10" />
			<input type="text" name="city" value="<?=$city;?>" size="40" />
	</td></tr>
	<tr><th>Country</th>	<td><input type="text" name="country" value="<?=$country;?>" size="75" /></td></tr>
	<tr><th>Phone</th>	<td><input type="text" name="phone" value="<?=$phone;?>" size="75" /></td></tr>
	<tr><th>Mobile</th>	<td><input type="text" name="mobile" value="<?=$mobile;?>" size="75" /></td></tr>
	<tr><th>Email</th>	<td><input type="text" name="email" value="<?=$email;?>" size="75" /></td></tr>

	<tr><th class="title" colspan="2">Login information</th></tr>
	<tr><th>Role</th>			<td><select size="1" name="role"><?=$cbo_role;?></select></td></tr>
	<tr><th>Username</th>	<td><input type="text" name="username" value="<?=$username;?>" size="75" /></td></tr>

<?if($currentView == ViewManager::ADMIN_NEW):?>
	<tr><th>Password</th>	<td><input type="text" name="password" value="<?=$password;?>" size="75" /></td></tr>
<?else:?>
	<tr><th>Password</th>	<td><input type="password" name="newpass1" value="" size="75" /></td></tr>
	<tr><th>Confirm password</th>	<td><input type="password" name="newpass2" value="" size="75" /></td></tr>
<?endif;?>

  <tr><th colspan="2" class="title">User groups</th></tr>
	<tr><td colspan="2">
  	<table class="multibox">
		<tr>
			<td>
  		<h3>Available</h3>
  		<select multiple size="13" id="cbo_grp_free" name="grp_free[]"><?=$cbo_grp_free;?></select>
			</td>
  	<td class="nav">
  		<input type="button" class="formbutton" onclick="move('cbo_grp_free','cbo_grp_used')" value="&raquo;" />
  		<input type="button" class="formbutton" onclick="move('cbo_grp_used','cbo_grp_free')" value="&laquo;" />
  	</td>
  	<td>
			<h3>Used</h3>
  		<select multiple size="13" id="cbo_grp_used" name="grp_used[]"><?=$cbo_grp_used;?></select>
		</td></tr>
		</table>
  </td></tr>
</table>

<input type="submit" value="Save" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>
<?endif;//}}}?>

<?  /*-----delete {{{------*/
if($currentView == ViewManager::ADMIN_DELETE):?>

<form action="<?=$urlPath;?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="id" value="<?=$id;?>" />

<p>Are you sure you want to delete <?=$name;?>?</p>

<input type="submit" value="Delete" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>
<?endif;//}}}?>

<?  /*-----import {{{------*/
if($currentView == User::VIEW_IMPORT):?>

<p class="options">
<a href="<?=$href_back;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
</p>


<form action="<?=$urlPath;?>" method="post" enctype="multipart/form-data" onsubmit="selectall('cbo_grp_used');">

<table id="form">
	<tr><th class="title" colspan="2">Import</th></tr>
	<tr><th>Csv File &nbsp; <em class="normal">(<a href="<?=$href_import_template;?>">Voorbeeld</a>)</em>
	</th>	<td><input type="file" name="import_file" value="" size="60" /></td></tr>
  <tr><th colspan="2" class="title">User groups</th></tr>
	<tr><td colspan="2">
  	<table class="multibox">
		<tr>
			<td>
  		<h3>Available</h3>
  		<select multiple size="13" id="cbo_grp_free" name="grp_free[]"><?=$cbo_grp_free;?></select>
			</td>
  	<td class="nav">
  		<input type="button" class="formbutton" onclick="move('cbo_grp_free','cbo_grp_used')" value="&raquo;" />
  		<input type="button" class="formbutton" onclick="move('cbo_grp_used','cbo_grp_free')" value="&laquo;" />
  	</td>
  	<td>
			<h3>Used</h3>
  		<select multiple size="13" id="cbo_grp_used" name="grp_used[]"><?=$cbo_grp_used;?></select>
		</td></tr>
		</table>
  </td></tr>
</table>

<input type="submit" value="Import" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>
<?endif;//}}}?>
