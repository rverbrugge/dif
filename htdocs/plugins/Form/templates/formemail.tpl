<?if(isset($text) && $text):?>
<?=$text;?>
<?else:?>
<?=$subject;?>  

<?=$fields;?> 
<?endif;?>

--------------------------------------------
ip:      <?=$ip;?> 
host:    <?=$host;?> 
client:  <?=$client;?> 
date:    <?=strftime("%c");?> 
