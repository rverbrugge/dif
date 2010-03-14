<?if($adminSection && !isset($pluginProviderRequest)):?>
<h1><?=$pageTitle;?></h1>
<em><?=isset($tagName) ? "$tagName $currentViewName" : $currentViewName;?></em>
<?endif;?>

<?if(isset($errorMessage)):?><p class="error"><?=$errorMessage;?></p><?endif;?>

<?  /*-----tree overview {{{------*/
if($currentView == ViewManager::TREE_OVERVIEW):?>


<p class="options">
<a href="<?=$href_up;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
<a href="<?=$href_new;?>"><img class="noborder" src="<?=$img_new['src'];?>" width="<?=$img_new['width'];?>" height="<?=$img_new['height'];?>" alt="new" title="new" /></a>
<a href="<?=$href_conf;?>"><img class="noborder" src="<?=$img_conf['src'];?>" width="<?=$img_conf['width'];?>" height="<?=$img_conf['height'];?>" alt="configure" title="configure" /></a>
<a href="<?=$href_period;?>"><img class="noborder" src="<?=$img_date['src'];?>" width="<?=$img_date['width'];?>" height="<?=$img_date['height'];?>" alt="block periods" title="block periods" /></a>
<a href="<?=$href_usergroup;?>"><img class="noborder" src="<?=$img_usrgrp['src'];?>" width="<?=$img_usrgrp['width'];?>" height="<?=$img_usrgrp['height'];?>" alt="User groups" title="User groups" /></a>
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


<?if($list['page_numbers']['total'] > 1):?><p class="pagenav"><?=$list['totalItems'];?> items found: <?=$list['links'];?></p><?endif;?>

<table class="overview">
<tr>
<th></th>
<th>Name</th>
<th>Date</th>
<th>Modified</th>
<th>Created</th>
</tr>
<?foreach($list['data'] as $item):?>
<tr<?=!$item['activated'] ? ' class="deactivated"':'';?>>
<td>
	<a href="<?=$item['href_del'];?>"><img class="noborder" src="<?=$img_del['src'];?>" width="<?=$img_del['width'];?>" height="<?=$img_del['height'];?>" alt="delete" title="delete" /></a>
</td>
<td><?="{$item['name']}, {$item['firstname']}";?></td>
<td><?=strftime('%d-%b-%Y', $item['reservation_date'])." {$item['reservation_time']}:00";?></td>
<td><?=strftime('%c', $item['ts']);?></td>
<td><?=strftime('%c', $item['createdate']);?></td>
</tr>
<?endforeach;?>
</table>

<?if($list['page_numbers']['total'] > 1):?><p class="pagenav"><?=$list['totalItems'];?> items found: <?=$list['links'];?></p><?endif;?>
<?endif;//'}}}?>

<?  /*-----tree new and edit {{{------*/
if($currentView == ViewManager::TREE_NEW || $currentView == ViewManager::TREE_EDIT):?>

<p class="options">
<a href="<?=$href_back;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
</p>

<form action="<?=$urlPath;?>" name="myform" method="post">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="tag" value="<?=$tag;?>" />
<input type="hidden" name="id" value="<?=$id;?>" />

<table id="form">
	<tr><th class="title" colspan="2">General</th></tr>
	<tr><th>Name</th>	<td><select size="1" id="usr_id" name="usr_id"><?=$cbo_user;?></select></td></tr>
	<tr><th>Date</th> <td><input type="text" id="datevalue" value="<?=strftime('%Y-%m-%d');?>" size="10" /></td></tr>
	<tr><td colspan="2">
				<div id="times">
				</div>
		</td>
	</tr>
</table>

</form>
<?endif;//}}}?>

<?  /*-----delete {{{------*/
if($currentView == ViewManager::TREE_DELETE):?>

<form action="<?=$urlPath;?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="tag" value="<?=$tag;?>" />
<input type="hidden" name="id" value="<?=$id;?>" />

<p>Weet u zeker dat u het bericht <strong><?=$name;?></strong> wilt delete?</p>

<input type="submit" value="Delete" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>
<?endif;//}}}?>

<?  /*-----overview {{{------*/
if($currentView == ViewManager::OVERVIEW):?>
	
	<div id="dateselect">
	<h2 class="dateheading">Selecteer een datum</h2>

	<input type="text" id="datevalue" value="<?=strftime('%Y-%m-%d');?>" size="10" />
	</div>
	<h2 class="dateheading">Selecteer een tijd</h2>
	<!--p>
	Selecteer het gewenste uur om deze te reserveren. In het bevestigingsscherm kunt u kiezen tussen de volgende opties.
	</p-->

	<div id="times">
	</div>

<?endif;//}}}?>

