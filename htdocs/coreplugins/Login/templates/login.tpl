<h1><?=$pageTitle;?></h1>

<?if(isset($errorMessage)):?><p class="error"><?=$errorMessage;?></p><?endif;?>

<form action="<?=$urlPath;?>" method="post">
<input type="hidden" name="referer" value="<?=$referer;?>" />

<div id="login">
<table id="form">
	<tr><th>Username</th>	<td><input type="text" name="username" value="" size="30" /></td></tr>
	<tr><th>Password</th>	<td><input type="password" name="password" value="" size="30" /></td></tr>
<?if(!$dbExists):?><tr><td colspan="2"><em>Default: username: system, password: manager</em></td></tr><?endif;?>
</table>

<input type="submit" value="Login" class="formbutton" />
</div>

</form>
