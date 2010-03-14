function parseLinks() 
{
	if (!document.getElementsByTagName) return;
	skip="noext";

	an = document.getElementsByTagName("a");
	size  = an.length;
	for (i=0; i<size; i++) 
	{
		obj = an.item(i);
		//alert(obj.href.indexOf(window.location.hostname));
		if(!obj.href || obj.href.charAt(0) == '/' || obj.href.indexOf(window.location.hostname) > -1) continue;
		if(obj.className.indexOf(skip) > -1 || obj.href.indexOf('mailto:') > -1 || obj.href.indexOf('javascript:') > -1) continue;

		obj.rel = "external"; 
		obj.target= '_blank';
	}
}

Event.observe( window, 'load', function() { parseLinks();} );
