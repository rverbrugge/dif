function getName(ref)
{
	var obj = document.editform.elements[ref];
	if(!obj) return;
	return obj.value;
}

function stripName(name)
{
	var name = name.replace(/[\s\W]/g,'');
	return name.toLowerCase();
}

function syncname(dest, name)
{
	if(!dest) return; 
	if(dest.value != "") return;

	dest.value = name;
}

function createTags(dest)
{
	//if(editAreaLoader.getValue(dest)) return; // skip if already data
	var tags = getName('tags');

	var tag = '';
	var list = tags.split(/\r?\n/);
	var retval = '';
	for(var i = 0; i < list.length; i++)
	{
		if(!list[i]) continue; // skip empty lines

		tag = list[i].replace(/:$/,'');
		retval += '<?=isset($'+tag+')?$'+tag+":'';?>\n";
	}
	editAreaLoader.setValue(dest, retval);

}
