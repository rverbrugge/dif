<h1><?=$pageTitle;?></h1>
<em class="subtitle"><?=$currentViewName;?></em>

<?if(isset($errorMessage)):?><p class="error"><?=$errorMessage;?></p><?endif;?>

<p class="options">
<a href="<?=$href_up;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
<a href="<?=$href_export;?>"><img class="noborder" src="<?=$img_export['src'];?>" width="<?=$img_export['width'];?>" height="<?=$img_export['height'];?>" alt="export" title="export" /></a>
</p>

<?if(isset($logfile)):?>
<p><?=$logfile;?></p>
<?else:?>
<p>Log file is not available.</p>
<?endif;?>
