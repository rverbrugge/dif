<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" 
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>

<title><?=$pageTitle ? "$siteTitle - $pageTitle" : $siteTitle;?></title>

<meta http-equiv="Content-type" content="text/html; charset=iso-8859-1" />
<meta http-equiv="Content-Language" content="nl" />

<!-- prevent caching  -->
<meta http-equiv="Pragma" content="no-cache" />
<meta http-equiv="Cache-Control" content="no-cache, must-revalidate" />
<meta http-equiv="Cache-Control" content="no-cache" />
<meta http-equiv="Cache-Control" content="no-store" />
<meta http-equiv="Expires" content="0" />

<?=$htmlheaders;?>

</head>
<body>

<div id="header">
	<div id="logo"><img src="<?=$img_logo['src'];?>" width="<?=$img_logo['width'];?>" height="<?=$img_logo['height'];?>" alt="DIF Content Management System" /></div>

	<h1><?=$siteTitle;?><em>v<?=DIF_VERSION;?></em>
<?if(isset($loginName) && $loginName):?><strong>[<?=$loginName;?>]</strong><?endif;?>
	
</h1>

	<div id="menu">
	<?=(isset($tpl_menu)) ? $tpl_menu : '<!--no menu-->';?>
	</div>
</div>

<div id="breadcrumb">
<?=(isset($tpl_breadcrumb)) ? $tpl_breadcrumb : '<!--no breadcrumb-->';?>
</div>

<div id="content">
	<?if(isset($submenu) && $submenu):?>
	<div id"submenu">
	<ul>
	<?foreach($submenu as $item):?>
	<li><a href="<?=$item['path'];?>" title="<?=$item['name'];?>"><?=$item['name'];?></a></li>
	<?endforeach;?>
	</ul>
	</div>
	<?endif;?>

	<?=(isset($tpl_content)) ? $tpl_content : '<!-- no content specified -->';?>
</div>

</body>
</html>
