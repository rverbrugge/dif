<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" 
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="nl" lang="nl">
<head>

<title><?=$pageTitle ? "$siteTitle - $pageTitle" : $siteTitle;?></title>

<meta http-equiv="Content-type" content="text/html; charset=iso-8859-1" />
<meta http-equiv="Content-Language" content="nl" />

<meta name="revisit-after" content="12 days" />
<meta name="robots" content="index,follow" />
<meta name="author" content="Ramses Verbrugge- www.difsystems.nl" />
<meta name="copyright" content="DIF Systems - http://www.difsystems.nl - Copyright (C) 2007-2008. All rights reserved." />
<?=$htmlheaders;?>

</head>
<body>

<div id="site">

<div id="header">
<?if(isset($tpl_header) && $tpl_header):?> 
	<?=$tpl_header;?>
<?else:?>
<img src="<?=$path;?>images/header.jpg" width="755" height="183" alt="<?=$siteTitle;?>" />
<?endif;?>
</div>


<?if(isset($tpl_menu) && $tpl_menu):?> 
	<div id="menu">
	<?=$tpl_menu;?>
	</div>
<?endif;?>

<div id="container">

	<div id="breadcrumb">
	<?=(isset($tpl_breadcrumb) && $tpl_breadcrumb) ? $tpl_breadcrumb : '<!--no breadcrumb-->&nbsp;';?>
	</div>

<table id="main">
<tr>
<td id="mainpane">

	<h1><?=$pageTitle;?></h1>

	<?=(isset($tpl_content)) ? $tpl_content : '<!-- no content specified -->';?>

</td>

<?if(
(isset($tpl_submenu) && $tpl_submenu) ||
(isset($tpl_currmenu) && $tpl_currmenu) ||
(isset($tpl_right) && $tpl_right)
):?> 
<td id="rightpane">

<?if(isset($tpl_submenu) && $tpl_submenu):?> 
<?=$tpl_submenu;?>
<?endif;?>

<?if(isset($tpl_currmenu) && $tpl_currmenu):?> 
<?=$tpl_currmenu;?>
<?endif;?>

<?if(isset($tpl_right) && $tpl_right):?> 
<?=$tpl_right;?>
<?endif;?>
</td>
<?endif;?>

</tr>
</table>

<?if(isset($tpl_footer)):?> 
<div id="footer">
<?=$tpl_footer;?>
</div>
<?endif;?>

</div>
</div>

</body>
</html>
