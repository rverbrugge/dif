function move(s_from, s_to)
{
	var o_from = document.getElementById(s_from);
	var o_to 	= document.getElementById(s_to);
	
	var a_from = new Array();
	var a_to 	= new Array();
	
	for(i=0; i < o_to.options.length; i++)
	{
		var o_option = o_to.options[i];
		a_to.push(new Option(o_option.text, o_option.value));
	}
	
	for(i=0; i < o_from.options.length; i++)
	{
		var o_option = o_from.options[i];
		var o_opt = new Option(o_option.text, o_option.value);
		(o_option.selected) ? (a_to.push(o_opt)) : (a_from.push(o_opt));
	}
	
	fillbox(a_from, o_from);
	fillbox(a_to, o_to);
}

function fillbox(a_option, o_select)
{
	o_select.options.length = 0;
	a_option.sort(boxsort);
	
	for(i=0; i < a_option.length; i++)
	{
		o_select[i] = a_option[i];
	}
}

function boxsort(x,y)
{
	if(x.text < y.text)
	 return -1;
	else 
		return 1;
}

function selectall(s_box) 
{
	var o_obj = document.getElementById(s_box);
	if(!o_obj) return true;
	
  for (i = 0; i < o_obj.options.length; i++) 
  {
    o_obj.options[i].selected = true;
  }
  return true;
}

function syncname(o_dest, s_src, b_strip)
{
	if(!o_dest) return; 
	if(o_dest.value != "") return;

	var o_src = document.tidi.elements[s_src];
	if(!o_src) return;

	var s_value = o_src.value;
	if(b_strip)
	{
		var s_value = s_value.replace(/[\s\W]/g,'');
		var s_value = s_value.toLowerCase();
	}

	o_dest.value = s_value;
}
