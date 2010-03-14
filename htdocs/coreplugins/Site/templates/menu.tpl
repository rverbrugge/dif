<p><a href="javascript:d.openAll();">Expand all</a> | <a href="javascript:d.closeAll();">Collapse all</a></p>

<script type="text/javascript">

	d = new dTree('d');

<?foreach($sitemenu as $item):?>
	img = '<?=$item['parent'] == $parentRootId ? $htdocsPath.'img/globe.gif' : ($item['activated'] ? '' : $htdocsPath.'img/trash.gif');?>';

	d.add(<?printf("%d,%d,'%s','%s','','%s',img,img",$item['id'], $item['parent'], $item['name'], $item['href_overview'], $item['activated'] ? '' : 'disabled');?>);
<?endforeach;?>


	document.write(d);

	<?if(isset($root)):?>
	d.closeAll();
	<?else:?>
	d.openTo(<?=$current_node;?>, true, false);
	<?endif;?>

</script>


