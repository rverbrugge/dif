<h1><?=$pageTitle;?></h1>

<?if(isset($errorMessage)):?><p class="error"><?=$errorMessage;?></p><?endif;?>

<p>To get started DIF needs to know some information.</p>
<p>Specify the location of an empty or existing DIF database.<br />
		The location is specified with a DSN or Data Source Name. A DSN is formatted like this: <em>&lt;protocol&gt;://&lt;username&gt;:&lt;password&gt;@&lt;host&gt;/&lt;database&gt;</em><br />
		For example: <em>mysql://foo_user:foo_password@foo/bar</em></p>
<p>Next, choose a system administrator account. Be sure to <strong>change the password</strong>!</p>
<p>If this is a fresh installation, leave the default plugins option checked so the setup loads the site with all plugins available.</p>

<form action="<?=$urlPath;?>" method="post">

<table id="form">
	<tr><th colspan="2" class="title">Settings</th></tr>
	<tr><th>Enable caching</th>		<td><input class="noborder" type="checkbox" name="caching" <?=$caching ? 'checked ' : '';?>/></td></tr>
	<tr><th>Database DSN <br /><em class="normal">Data Source Name</em></th>						
		<td>
			<input type="text" name="dsn" value="<?=$dsn;?>" size="75" /><br />
			<em>format: &lt;protocol&gt;://&lt;username&gt;:&lt;password&gt;@&lt;host&gt;/&lt;database&gt;</em><br />
		</td>
	</tr>
	<tr><th colspan="2" class="title">Systeem Administrator</th></tr>
	<tr><th>Username</th>						<td><input type="text" name="username" value="<?=$username;?>" size="75" /></td></tr>
	<tr><th>Password</th>						<td><input type="password" name="password" value="" size="75" /></td></tr>
	<tr><th>Password confirm</th>	<td><input type="password" name="password1" value="" size="75" /></td></tr>
	<tr><th>
		Restricted access to administration section<br />
		<em class="normal"><strong>CAUTION:</strong>
			This option can deny you from accessing this setting.<br />
			You can also change this setting in the data/conf/system.ini file via ftp. </em>
	</th>						
	<td>
		<input type="text" name="admin_section_ip_allow" value="<?=$admin_section_ip_allow;?>" size="75" /><br />
		<em>format: 192.168.1.1,192.168.1.2</em><br />
	</td>
	</tr>
	<tr><th colspan="2" class="title">Email settings</th></tr>
	<tr><th>Email address</th>						<td><input type="text" name="email_address" value="<?=$email_address;?>" size="75" /></td></tr>
	<tr><th>Mailserver</th>								<td><input type="text" name="email_host" value="<?=$email_host;?>" size="75" /></td></tr>
	<tr><th>Email email_username</th>						<td><input type="text" name="email_username" value="<?=$email_username;?>" size="75" /></td></tr>
	<tr><th>Email password</th>						<td><input type="text" name="email_password" value="<?=$email_password;?>" size="75" /></td></tr>
	<tr><th colspan="2" class="title">Plugins</th></tr>
	<tr><th>Install default plugins</th>		<td><input class="noborder" type="checkbox" name="plugin" <?=$plugin ? 'checked ' : '';?>/></td></tr>
	<tr><th>Install default themes</th>		<td><input class="noborder" type="checkbox" name="theme" <?=$theme ? 'checked ' : '';?>/></td></tr>
	<tr><th>Install default extensions</th>		<td><input class="noborder" type="checkbox" name="extension" <?=$extension ? 'checked ' : '';?>/></td></tr>
</table>

<input type="submit" value="Next" class="formbutton" />

</form>
