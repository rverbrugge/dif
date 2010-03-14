var destination;
var callrpc = new xmlrpc_client("/xmlrpc");

function getTypeList(id,dest)
{
	destination = dest;
	var msg = new xmlrpcmsg("PluginHandler.getTypeList");
	msg.addParam(new xmlrpcval(id, 'int'));
	callrpc.send(msg,0,getTypeResult);

}

function getTagList(id, tree_id, dest, notag)
{
	destination = dest;
	var notaglist = notag;
	var param = {	id : id,
								tree_id : tree_id,
								href_edit_theme_tag : '',
								href_del_theme_tag : ''
								};

	var msg = new xmlrpcmsg("ThemeHandler.getTagList");
	msg.addParam(xmlrpc_encode(param));
	callrpc.send(msg,0,getTagResult);
}


function getTypeResult(data)
{
	var result = xmlrpc_decode(data.value());
	var cbo = document.getElementById(destination);
	if(!cbo) return;

	clearObject(cbo);

	var size = result.length;
	for(i=0; i<size; i++)
	{
		var option = document.createElement("option");
		option.setAttribute('value', result[i]['id']);
		option.appendChild(document.createTextNode(result[i]['name']));
		cbo.appendChild(option);
	}

}

function getTagResult(data)//, state, extra)
{
	var result = xmlrpc_decode(data.value());
	var table = document.getElementById(destination);
	if(!table) return;

	clearObject(table);

	var body = table.getElementsByTagName("tbody")[0];
	var link_edit = document.createElement("a");
	var link_del = document.createElement("a");

	var img_edit = document.createElement("img");
		img_edit.setAttribute('src', '/themes/AdminTheme/htdocs/images/edit.png');
	img_edit.setAttribute('width', 32);
	img_edit.className = "noborder";
	img_edit.setAttribute('height', 32);
	img_edit.setAttribute('alt', 'bewerken');
	
	var img_del = document.createElement("img");
		img_del.setAttribute('src', '/themes/AdminTheme/htdocs/images/editdelete.png');
	img_del.setAttribute('width', 32);
	img_del.className = "noborder";
	img_del.setAttribute('height', 32);
	img_del.setAttribute('alt', 'Tags verwijderen');
	
	link_edit.appendChild(img_edit);
	link_del.appendChild(img_del);

	var size = result.length;
	for(i=0; i<size; i++)
	{
		var item = result[i];
		var edit = link_edit.cloneNode(true);
		edit.setAttribute('href', item['href_edit']);

		var del = link_del.cloneNode(true);
		del.setAttribute('href', item['href_del']);

		var td_link = document.createElement("td");
		var td_name = document.createElement("td");
		var td_user = document.createElement("td");
		if(item['href_del'] != '') td_link.appendChild(del);
		td_link.appendChild(edit);

		td_name.appendChild(document.createTextNode(item['name']));
		td_user.appendChild(document.createTextNode(item['userdefined']));

		var tr = document.createElement("tr");
		tr.appendChild(td_link);
		tr.appendChild(td_name);
		tr.appendChild(td_user);
		body.appendChild(tr);
	}
}

function clearObject(obj)
{
	if(!obj) return;
	if(obj.nodeName == "TABLE")
	{
		var tmp = obj.getElementsByTagName("tbody")[0];
		obj = tmp;
	}

	while(obj.hasChildNodes())
	{
		obj.removeChild(obj.lastChild);
	}
}

function getSelectValue(obj)
{
	var size = obj.options.length;
	for(var i=0; i < size; i++)
	{
		var item = obj.options[i];
		if(item.selected)  return item.value;
	}
}
