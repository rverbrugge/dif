<?if($adminSection):?>
<h1><?=$pageTitle;?></h1>
<em><?=$currentViewName;?></em>
<?endif;?>

<?if(isset($errorMessage)):?><p class="error"><?=$errorMessage;?></p><?endif;?>

<?  /*-----tree new and edit {{{------*/
if($currentView == ViewManager::TREE_NEW || $currentView == ViewManager::TREE_EDIT):?>
	
<p class="options">
<a href="<?=$href_back;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
<a href="javascript:document.myform.submit();"><img class="noborder" src="<?=$img_save['src'];?>" width="<?=$img_save['width'];?>" height="<?=$img_save['height'];?>" alt="save" title="save" /></a>
</p>

<form action="<?=$urlPath;?>" name="myform" method="post" enctype="multipart/form-data">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="tag" value="<?=$tag;?>" />
<input type="hidden" name="rcd_id" value="<?=$rcd_id;?>" />

<?=$tpl_element_item;?>

<input type="submit" value="Save" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>
<?endif;//}}}?>

<?  /*-----record overview {{{------*/
if($currentView == ViewManager::TREE_OVERVIEW):?>

<p class="options">
<a href="<?=$href_up;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
<a href="<?=$href_conf;?>"><img class="noborder" src="<?=$img_conf['src'];?>" width="<?=$img_conf['width'];?>" height="<?=$img_conf['height'];?>" alt="settings" title="settings" /></a>
<a href="<?=$href_elem;?>"><img class="noborder" src="<?=$img_list['src'];?>" width="<?=$img_list['width'];?>" height="<?=$img_list['height'];?>" alt="form elements" title="form elements" /></a>
<a href="<?=$href_new;?>"><img class="noborder" src="<?=$img_new['src'];?>" width="<?=$img_new['width'];?>" height="<?=$img_new['height'];?>" alt="New" title="New" /></a>
<a href="javascript:document.myform.submit();"><img class="noborder" src="<?=$img_del['src'];?>" width="<?=$img_del['width'];?>" height="<?=$img_del['height'];?>" alt="delete selection" title="delete selection" /></a>
<?if($list):?>
<a href="<?=$href_del_all;?>"><img class="noborder" src="<?=$img_block['src'];?>" width="<?=$img_block['width'];?>" height="<?=$img_block['height'];?>" alt="delete all records" title="delete all records" /></a>
<a href="<?=$href_export;?>"><img class="noborder" src="<?=$img_export['src'];?>" width="<?=$img_export['width'];?>" height="<?=$img_export['height'];?>" alt="export selection" title="export selection" /></a>
<?endif;?>
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

<?if($records['page_numbers']['total'] > 1):?><p class="pagenav"><?=$records['totalItems'];?> items found: <?=$records['links'];?></p><?endif;?>

<form action="<?=$urlPath;?>" method="post" name="myform" enctype="multipart/form-data">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="tag" value="<?=$tag;?>" />
<input type="hidden" name="<?=ViewManager::getInstance()->getUrlId();?>" value="<?=ViewManager::TREE_DELETE;?>" />

<table class="overview">
<tr>
<th><a href="javascript:toggleCheckBoxes(document.myform);">toggle selection</a></th>
<?foreach($columns as $item):?>
<th><?=$item;?></th>
<?endforeach;?>
</tr>
<?foreach($list as $record):?>
<tr>
<?foreach($record as $item):?>
<td><?=$item;?></td>
<?endforeach;?>
</tr>
<?endforeach;?>
</table>
</form>

<?if($records['page_numbers']['total'] > 1):?><p class="pagenav"><?=$records['totalItems'];?> items found: <?=$records['links'];?></p><?endif;?>

<?endif;//}}}?>

<?  /*----- record delete {{{------*/
if($currentView == ViewManager::TREE_DELETE):?>

<form action="<?=$urlPath;?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="tag" value="<?=$tag;?>" />
<input type="hidden" name="rcd_id" value="<?=$rcd_id;?>" />

<p>Weet u zeker dat u het record <strong><?=$name;?></strong> wilt delete?</p>

<input type="submit" value="Delete" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_up;?>';" class="formbutton" />

</form>
<?endif;//}}}?>

<?  /*----- record delete all {{{------*/
if($currentView == Form::VIEW_RECORD_DEL_ALL):?>

<form action="<?=$urlPath;?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="tag" value="<?=$tag;?>" />

<p>Are you sure you want to delete <strong>ALL</strong> records?</p>

<input type="submit" value="Delete" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_up;?>';" class="formbutton" />

</form>
<?endif;//}}}?>

<?  /*-----overview {{{------*/
if($currentView == ViewManager::OVERVIEW):?>
	
<?if(isset($formError)):?><p class="error"><?=$formError;?></p><?endif;?>

<form action="<?=$urlPath;?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="tag" value="<?=$tag;?>" />

<?=$tpl_element_item;?>

<input type="submit" value="<?=$settings['caption'];?>" class="formbutton" />

</form>
<?endif;//}}}?>
