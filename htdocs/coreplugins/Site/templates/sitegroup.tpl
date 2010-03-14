<h3>Websites</h3>

<ul id="sitegroup">
<?foreach($sitegroup['data'] as $item):?>
<li>
	<a <?if($item['selected']) print('class="selected"');?> href="<?=$item['href_detail'];?>"><?="{$item['name']} ({$item['language']})";?></a>
</li>
<?endforeach;?>
</ul>

<p class="center"><a href="<?=$href_sitegroup;?>">configure websites</a></p>
