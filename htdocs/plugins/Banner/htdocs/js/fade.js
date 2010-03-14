var resizeDuration = 1;

// -----------------------------------------------------------------------------------

//
//	Additional methods for Element added by SU, Couloir
//	- further additions by Lokesh Dhakar (huddletogether.com)
//
Object.extend(Element, {
	getWidth: function(element) {
	   	element = $(element);
	   	return element.offsetWidth; 
	},
	setWidth: function(element,w) {
	   	element = $(element);
    	element.style.width = w +"px";
	},
	setHeight: function(element,h) {
   		element = $(element);
    	element.style.height = h +"px";
	},
	setTop: function(element,t) {
	   	element = $(element);
    	element.style.top = t +"px";
	},
	setLeft: function(element,l) {
	   	element = $(element);
    	element.style.left = l +"px";
	},
	setSrc: function(element,src) {
    	element = $(element);
    	element.src = src; 
	},
	setName: function(element,name) {
    	element = $(element);
    	element.alt = name; 
	},
	setHref: function(element,href) {
    	element = $(element);
    	element.href = href; 
	},
	setInnerHTML: function(element,content) {
		element = $(element);
		element.innerHTML = content;
	}
});

	//
	//	changeImage()
	//	Hide most elements and preload image in preparation for resizing image container.
	//
function blendImage(image, name, url, tag) 
{	
		var imgtag = 'bnrimg'+tag;
		var divtag = 'bnr'+tag;

		$(divtag).style.backgroundImage = "url(" + $(imgtag).src + ")"; 

		Element.hide(imgtag);
		var imgPreloader = new Image();
		
		// once image is preloaded, resize image container
		imgPreloader.onload=function(){
			showSwapImage(image, url, tag);
			Element.setName(name);
			imgPreloader.onload=function(){};	//	clear onLoad, IE behaves irratically with animated gifs otherwise 
		}
		imgPreloader.src = image['src'];

		//document.getElementById(divtag).style.backgroundImage = "url(" + document.getElementById(imgtag).src + ")"; 

}
	
function swapImage(image, name, url, tag) 
{	
		var imgtag = 'bnrimg'+tag;
		var divtag = 'bnr'+tag;

		document.getElementById(divtag).style.backgroundImage = "none";

		var imgPreloader = new Image();
		
		// once image is preloaded, resize image container
		imgPreloader.onload=function(){
			new Effect.Fade(imgtag, {duration: resizeDuration, from:1.0, to:0.0, queue: { position: 'front', scope: 'banner', limit: 2 }, afterFinish: function(){ showSwapImage(image, url, tag);}});
			Element.setName(name);
			imgPreloader.onload=function(){};	//	clear onLoad, IE behaves irratically with animated gifs otherwise 
		}
		imgPreloader.src = image['src'];

}
	
function showSwapImage(image, url, tag)
{
		var imgtag = 'bnrimg'+tag;
		var linktag = 'bnrhref'+tag;
		
		Element.setSrc(imgtag, image['src']);
		Element.setWidth(imgtag, image['width']);
		Element.setHeight(imgtag, image['height']);

		if(document.getElementById(linktag))
			Element.setHref(linktag, url ? url : '#');

		Effect.Appear(imgtag, { duration: resizeDuration, from:0.0, to:1.0, queue: { position: 'front', scope: 'banner', limit: 2}  });
}

function showImage(image, name, url, tag)
{
		var imgtag = 'bnrimg'+tag;
		var linktag = 'bnrhref'+tag;

		Element.show(imgtag);

		Element.setSrc(imgtag, image['src']);
		Element.setName(imgtag, name);
		Element.setWidth(imgtag, image['width']);
		Element.setHeight(imgtag, image['height']);

		if(document.getElementById(linktag))
			Element.setHref(linktag, url ? url : '#');
}

