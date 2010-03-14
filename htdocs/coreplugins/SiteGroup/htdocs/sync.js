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

