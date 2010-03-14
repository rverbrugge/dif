<?foreach($fields as $item):?>
<?=str_pad($item['name'], $padsize).": {$item['value']}\n";?>
<?endforeach;?>
