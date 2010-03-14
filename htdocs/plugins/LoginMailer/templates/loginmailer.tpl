<?if($adminSection):?>
<h1><?=$pageTitle;?></h1>
<em><?=isset($tagName) ? "$tagName $currentViewName" : $currentViewName;?></em>
<?endif;?>

<?if(isset($errorMessage)):?><p class="error"><?=$errorMessage;?></p><?endif;?>

<?  /*-----conf edit {{{------*/
if($currentView == ViewManager::CONF_EDIT):?>
<p class="options">
<a href="<?=$href_up;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
</p>


<p>No configuration options available.</p>

<?//}}}?>

<?  /*-----tree edit {{{------*/
elseif($currentView == ViewManager::TREE_EDIT):?>

<p class="options">
<a href="<?=$href_up;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
</p>

<form action="<?=$urlPath;?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="tag" value="<?=$tag;?>" />

<table id="form">
	<tr><th class="title" colspan="2">Form settings</th></tr>
	<tr><th>Intro</th>	<td><textarea name="intro" rows="3" cols="75"><?=$intro;?></textarea></td></tr>
	<tr><th>Location after submit</th>	<td><select size="1" name="ref_tree_id"><?=$cbo_tree_id;?></select></td></tr>
	<tr><th>Save button</th>	<td><input type="text" name="submit" value="<?=$submit;?>" size="75" /></td></tr>
	<tr><th class="title" colspan="2">Email settings</th></tr>
	<tr><th>Subject</th>	<td><input type="text" name="subject" value="<?=$subject;?>" size="75" /></td></tr>
	<tr><th colspan="2" class="title">Email content</th></tr>
	<tr><td colspan="2">
			<textarea id="area1" name="content" rows="20" cols="100"><?=$content;?></textarea><br />
			You can use PHP tags to insert user specific information.<br /> 
			A php tag has the following form: <strong>&lt;?=$foobar;?&gt;</strong><br />
			Available tags are:<br />
			<?=htmlentities('<?=$name;?>');?><br />
			<?=htmlentities('<?=$firstname;?>');?><br />
			<?=htmlentities('<?=$formatName;?>');?><br />
			<?=htmlentities('<?=$email;?>');?><br />
			<?=htmlentities('<?=$username;?>');?><br />
			<?=htmlentities('<?=$password_url;?>');?><br />

			<pre>CAUTION: You need to include the <strong>$password_url</strong> variable to enable the user to change his password!</pre>
		</td>
	</tr>
	<tr><th class="title" colspan="2">Save settings</th></tr>
	<tr><th>Location after succes</th>	<td><select size="1" name="fin_tree_id"><?=$cbo_fin_tree_id;?></select></td></tr>
	<tr><th>Save button</th>	<td><input type="text" name="fin_submit" value="<?=$fin_submit;?>" size="75" /></td></tr>
</table>

<input type="submit" value="Save" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>
<?//}}}?>

<?  /*----- edit {{{------*/
elseif($currentView == LoginMailer::VIEW_ACTIVATE):?>

<?if(isset($formError)):?><p class="error"><?=$formError;?></p><?endif;?>

<p>Changing password for <strong><?=$userinfo['formatName'];?></strong></p>

<form action="<?=$urlPath;?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="tag" value="<?=$tag;?>" />
<input type="hidden" name="key" value="<?=$request_key;?>" />

<table>
	<tr><th>Username</th>	<td><strong><?=$userinfo['username'];?></strong></td></tr>
	<tr><th>Password</th>	<td><input type="password" name="newpass1" value="" size="50" /></td></tr>
	<tr><th>Confirm</th>	<td><input type="password" name="newpass2" value="" size="50" /></td></tr>
</table>

<input type="submit" value="<?=$settings['fin_submit'];?>" class="formbutton" />

</form>
<?//}}}?>

<?  /*-----overview {{{------*/
//if($currentView == ViewManager::OVERVIEW):
else:?>

<?if(isset($formError)):?><p class="error"><?=$formError;?></p><?endif;?>

<?=$settings['intro'] ? "<p>".nl2br($settings['intro'])."</p>": '';?>

<form action="<?=$urlPath;?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="tag" value="<?=$tag;?>" />

<table>
	<tr><th>E-mail</th>	<td><input type="text" name="email" value="" size="50" /></td></tr>
</table>

<input type="submit" value="<?=$settings['submit'];?>" class="formbutton" />

</form>
<?endif;//}}}?>

