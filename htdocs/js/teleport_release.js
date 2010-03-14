;HOST = self.location.href.replace(/(\/\/[^\/]*)\/.*$/,"$1");

HOST_PATH = location.href.replace(/\/[^\/]*$/,"")+ "/";

CWD = location.href.replace(/^(.*\/)[^\/]*$/,"$1")+ "/";

PACKAGED = true;

DEBUG = true;

DEBUG_TYPE = "Memory";

DEBUG_FILTER = "!teleport";

WARNINGS = false;

IS_KONQUEROR = navigator.userAgent.toLowerCase().indexOf("konqueror")!= - 1;

IS_SAFARI = navigator.userAgent.toLowerCase().indexOf("safari")!= - 1 || navigator.userAgent.toLowerCase().indexOf("konqueror")!= - 1;

IS_SAFARI_OLD = false;


if(IS_SAFARI){
	var matches = navigator.userAgent.match(/AppleWebKit\/(\d+)/);
	
	if(matches)IS_SAFARI_OLD = parseInt(matches[1])< 420;
};

IS_OPERA = navigator.userAgent.toLowerCase().indexOf("opera")!= - 1;

IS_GECKO = ! IS_SAFARI && navigator.userAgent.toLowerCase().indexOf("gecko")!= - 1;

IS_IE = document.all && ! IS_OPERA;

IS_IE50 = IS_IE && navigator.userAgent.toLowerCase().indexOf("5.0")!= - 1;

IS_IE55 = IS_IE && navigator.userAgent.toLowerCase().indexOf("5.5")!= - 1;

IS_IE6 = IS_IE && navigator.userAgent.toLowerCase().indexOf("6.")!= - 1;

IS_IE7 = IS_IE && navigator.userAgent.toLowerCase().indexOf("7.")!= - 1;

;

MAX_JAV_RETRIES = IS_OPERA ? 0:3;

HAS_DESKRUN = HAS_WEBRUN = false;

TAGNAME = IS_IE ? "baseName":"localName";


try{
	ISROOT = ! window.opener || ! window.opener.Kernel}

catch(e){
	ISROOT = true};

function include(sourceFile,doBase){
	setStatus("including js file: " + sourceFile);
	
	if(IS_SAFARI){
		document.write("<script src='" +(doBase ? BASEPATH + sourceFile:sourceFile)+ "' defer='true'></script>");
	}
	else{
		var head = $("head")[0];
		var elScript = document.createElement("script");
		elScript.defer = true;
		elScript.src = doBase ? BASEPATH + sourceFile:sourceFile;
		head.appendChild(elScript);
	}

};


if(IS_OPERA){
	var $ = function(tag,doc,prefix,force){
		if(! prefix)return(doc || document).getElementsByTagName(tag);
		return(doc || document).getElementsByTagName(prefix + ":" + tag);
	}

}

else var $ = function(tag,doc,prefix,force){
	return(doc || document).getElementsByTagName((prefix &&(force || IS_GECKO)? prefix + ":":"")+ tag);
};

var $j = function(xmlNode,tag){
	if(IS_IE){
		if(xmlNode.style)return xmlNode.getElementsByTagName(tag);
		else{
			xmlNode.ownerDocument.setProperty("SelectionNamespaces","xmlns:j='http://www.javeline.net/j'");
			return xmlNode.selectNodes("//" + tag + "|//j:" + tag)}
	}
	else return xmlNode.getElementsByTagNameNS("http://www.javeline.net/j",tag);
};

function setStatus(str){
};

Init ={
	queue:[],cond:{
		combined:[]}
	,done:{
	}

,add:function(func,o){
		if(this.inited)func.call(o);
		else 
		if(func)this.queue.push([func,o]);
	}
	,addConditional:function(func,o,strObj){
		if(typeof strObj != "string"){
			if(this.checkCombined(strObj))return func.call(o);
			this.cond.combined.push([func,o,strObj]);
		}
		else 
		if(self[strObj])func.call(o);
		else{
			if(! this.cond[strObj])this.cond[strObj]=[];
			this.cond[strObj].push([func,o]);
			this.checkAllCombined();
		}
	}
	,checkAllCombined:function(){
		for(var i = 0;i < this.cond.combined.length;i ++){
			if(! this.cond.combined[i])continue;
			
			if(this.checkCombined(this.cond.combined[i][2])){
				this.cond.combined[i][0].call(this.cond.combined[i][1]);
				this.cond.combined[i]= null;
			}
		}
	}
	,checkCombined:function(arr){
		for(var i = 0;i < arr.length;i ++){
			if(! this.done[arr[i]])return false;
		};
		return true;
	}
	,run:function(strObj){
		this.inited = true;
		this.done[strObj]= true;
		this.checkAllCombined();
		var data = strObj ? this.cond[strObj]:this.queue;
		
		if(! data)return;
		
		for(var i = 0;i < data.length;i ++)data[i][0].call(data[i][1]);
	}

}
;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;VERSION = 0x000900;

NOGUI_NODE = 101;

GUI_NODE = 102;

KERNEL_MODULE = 103;

MF_NODE = 104;


if(! self.DEBUG)DEBUG = false;

HTTP_GET_VARS ={
}
,vars = location.href.split(/[\?\&\=]/);


for(var k = 1;k < vars.length;k += 2)HTTP_GET_VARS[vars[k]]= vars[k + 1]|| "";

Array.prototype.dataType = "array";

Number.prototype.dataType = "number";

Date.prototype.dataType = "date";

Boolean.prototype.dataType = "boolean";

String.prototype.dataType = "string";

RegExp.prototype.dataType = "regexp";

Function.prototype.dataType = "function";

Kernel ={
	toString:function(){
		return "[Javeline (Kernel)]";
	}
	,all:[],getElement:function(parent,nr){
		var nodes = parent.childNodes;
		
		for(var j = 0,i = 0;i < nodes.length;i ++){
			if(nodes[i].nodeType != 1)continue;
			
			if(j ++ == nr)return nodes[i];
		}
	}
	,inherit:function(classRef){
		classRef.call(this);
	}
	,makeClass:function(o){
		o.inherit = this.inherit;
		o.inherit(Class);
	}
	,isFalse:function(c){
		return c === false;
	}
	,isNot:function(c){
		return(! c && typeof c != "string" && c !== 0 ||(typeof c == "number" && ! isFinite(c)));
	}
	,setReference:function(name,o,global){
		if(self[name]&& self[name].hasFeature)return;
		return(self[name]= o);
	}
	,getRules:function(node){
		var rules ={
		};
		
		for(var w = node.firstChild;w;w = w.nextSibling){
			if(w.nodeType != 1)continue;
			else{
				if(! rules[w[TAGNAME]])rules[w[TAGNAME]]=[];
				rules[w[TAGNAME]].push(w);
			}
		};
		return rules;
	}
	,destroy:function(exclude){
		this.Popup.destroy();
		
		for(var i = 0;i < this.all.length;i ++)
		if(this.all[i]!= exclude && this.all[i].destroy)this.all[i].destroy();
	}

};


try{
	Kernel.root = ! window.opener || ! window.opener.Kernel;
}

catch(e){
	Kernel.root = false};

Init.run('Kernel');

function removeParts(str){
	q = str.replace(/^\s*function\s*\w*\s*\([^\)]*\)\s*\{/,"");
	q = q.replace(/\}\s*$/,"");
	return q;
};

function importClass(ref,strip,win){
	if(! ref)throw new Error(1018,Kernel.formErrorString(1018,null,"importing class","Could not load reference. Reference is null"));
	
	if(! IS_IE)return ref();
	
	if(! strip)return(win.execScript ? win.execScript(ref.toString()):eval(ref.toString()));
	var q = removeParts(ref.toString());
	return win.execScript ? win.execScript(q):eval(q);
}
;__HTTP_SUCCESS__ = 1;

__HTTP_TIMEOUT__ = 2;

__HTTP_ERROR__ = 3;

__RPC_SUCCESS__ = 1;

__RPC_TIMEOUT__ = 2;

__RPC_ERROR__ = 3;

Kernel.TelePort ={
	modules:new Array(),named:{
	}

,register:function(obj){
		var id = false,data ={
			name:obj.SmartBindingHook[0],args:obj.SmartBindingHook[1],obj:obj};
		this.named[obj.SmartBindingHook[0]]= data;
		return this.modules.push(data)- 1;
	}
	,getModules:function(){
		return this.modules;
	}
	,getModuleByName:function(defname){
		return this.named[defname]}
	,hasLoadRule:function(xmlNode){
		for(mod in this.named){
			if(! this.named[mod]|| ! this.named[mod].args)continue;
			
			if(xmlNode.getAttribute(this.named[mod].name)){
				this.lastRuleFound = this.named[mod];
				return true;
			}
		};
		this.lastRuleFound ={
		};
		return false;
	}
	,removeLoadRule:function(xmlNode){
		if(! this.hasLoadRule(xmlNode));
		
		try{
			xmlNode.removeAttribute(xmlNode.getAttributeNode(this.lastRuleFound.name));
			xmlNode.removeAttribute(xmlNode.getAttributeNode(this.lastRuleFound.args));
		}
		catch(e){
		}

}
	,Init:function(){
		this.inited = true;
		var comdef = document.documentElement.getElementsByTagName("head")[0].getElementsByTagName(IS_IE ? "teleport":"j:teleport")[0];
		
		if(! comdef && document.documentElement.getElementsByTagNameNS)comdef = document.documentElement.getElementsByTagNameNS("http://javeline.nl/j","j:teleport")[0];
		
		if(! comdef){
			this.isInited = true;
			return issueWarning(1006,"Could not find Javeline TelePort Definition")};
		
		if(comdef.getAttribute("src")){
			new HTTP().getXML(HOST_PATH + comdef.getAttribute("src"),function(xmlNode,state,extra){if(state != __RPC_SUCCESS__){
					if(extra.retries < MAX_RETRIES)return HTTP.retry(extra.id);
					else throw new Error(1021,Kernel.formErrrorString(1021,null,"Application","Could not load Javeline TelePort Definition:\n\n" + extra.message));
				};
				Kernel.TelePort.xml = xmlNode;
				Kernel.TelePort.isInited = true;
			}
			,true);
		}
		else{
			var xmlNode = comdef.firstChild ? XMLDatabase.getDataIsland(comdef.firstChild):null;
			Kernel.TelePort.xml = xmlNode;
			Kernel.TelePort.isInited = true;
		}
	}
	,load:function(xml){
		if(xml)this.xml = xml;
		
		if(! this.xml)return;
		var nodes = this.xml.childNodes;
		
		if(! nodes.length)return;
		
		for(var i = 0;i < nodes.length;i ++)this.initComm(nodes[i]);
		this.loaded = true;
		
		if(this.onload)this.onload();
	}
	,initComm:function(x){
		if(x.nodeType != 1)return;
		
		if(x[TAGNAME]== "Socket"){
			var o = new Socket();
			Kernel.setReference(x.getAttribute("id"),o);
			o.load(x);
		}
		else 
		if(x[TAGNAME]== "Poll")Kernel.setReference(x.getAttribute("id"),new Poll().load(x));
		else Kernel.setReference(x.getAttribute("id"),new CommBaseClass(x));
	}
	,callMethodFromNode:function(xmlCommNode,xmlNode,receive,multicall,userdata,arg){
		var commRule = xmlCommNode.getAttribute(this.lastRuleFound.name);
		
		if(! commRule){
			if(xmlCommNode.getAttribute("src")){
				var commRule = new HTTP().instantiate(xmlCommNode);
				xmlCommNode.setAttribute("http",commRule);
				xmlCommNode.removeAttributeNode(xmlCommNode.getAttributeNode("src"));
			};
			
			if(! commRule){
				if(! this.hasLoadRule(xmlCommNode))throw new Error(1022,Kernel.formErrorString(1022,null,"TelePort load from xmlNode","Could not load method from node :" +(xmlRPCNode ? xmlRPCNode.xml:"")));
				commRule = xmlCommNode.getAttribute(this.lastRuleFound.name);
			}
		};
		var q = commRule.split(";");
		var obj = eval(q[0]);
		var method = q[1];
		
		if(! arg && this.lastRuleFound.args){
			arg = xmlCommNode.getAttribute(this.lastRuleFound.args);
			arg = arg ? arg.split(";"):[];
		};
		
		if(multicall)obj.force_multicall = true;
		
		if(arg)arg = this.processArguments(arg,xmlNode,xmlCommNode);
		
		if(userdata)obj[method].userdata = userdata;
		
		if(! obj.multicall)obj.callbacks[method]= receive;
		var data = obj.call(method,arg ? obj.fArgs(arg,obj.names[method],(obj.vartype != "cgi" && obj.vexport == "cgi")):null);
		
		if(multicall){
			obj.force_multicall = false;
			return obj;
		};
		
		if(data && ! obj.multicall && ! obj[method].async)return receive(data);
	}
	,processArguments:function(arg,xmlNode,xmlCommNode){
		for(var i = 0;i < arg.length;i ++){
			if(typeof arg[i]== "object")continue;
			
			if(typeof arg[i]== "string"){
				if(arg[i].match(/^xpath\:(.*)$/)){
					var o = xmlNode.selectSingleNode(RegExp.$1);
					
					if(! o)arg[i]= "";
					else 
					if(o.nodeType >= 2 && o.nodeType <= 4)arg[i]= o.nodeValue;
					else arg[i]= o.serialize ? o.serialize():o.xml;
				}
				else 
				if(arg[i].match(/^method\:(.*)$/)){
					arg[i]= self[RegExp.$1](xmlNode,xmlCommNode);
				}
				else 
				if(arg[i].match(/^eval\:(.*)$/)){
					arg[i]= eval(RegExp.$1);
				}
				else 
				if(arg[i].match(/^\((.*)\)$/)){
					arg[i]= this.processArguments(RegExp.$1.split(","),xmlNode,xmlCommNode);
				}
				else arg[i]= arg[i]|| "";
			}
			else arg[i]= arg[i]|| "";
		};
		return arg;
	}

};

function CommBaseClass(xml){
	this.xml = xml;
	this.uniqueId = Kernel.all.push(this)- 1;
	Kernel.makeClass(this);
	this.toString = function(){
		return "[Javeline TelePort Component : " +(this.name || "")+ " (" + this.type + ")]";
	};
	
	if(this.xml){
		this.name = xml.getAttribute("id");
		this.type = xml[TAGNAME];
		
		if(! self[this.type])throw new Error(1023,Kernel.formErrorString(1023,null,"TelePort baseclass","Could not find Javeline TelePort Component '" + this.type + "'"));
		this.inherit(self[this.type]);
		
		if(this.useHTTP){
			if(! self.HTTP)throw new Error(1024,Kernel.formErrorString(1024,null,"Teleport baseclass","Could not find Javeline TelePort HTTP Component"));
			this.inherit(HTTP);
		};
		
		if(this.xml.getAttribute("protocol")){
			if(! self[this.xml.getAttribute("protocol")])throw new Error(1025,Kernel.formErrorString(1025,null,"Teleport baseclass","Could not find Javeline TelePort RPC Component '" + this.xml.getAttribute("protocol")+ "'"));
			this.inherit(self[this.xml.getAttribute("protocol")]);
		}
	};
	
	if(this.xml)this.load(this.xml);
};

Init.run('TelePort');
;;__JMLNODE__ = 1 << 15;

__FORMELEMENT__ = 1 << 6;
;Application ={
	xml:null,xmlForms:null,Init:function(jml){
		this.xml = jml;
		
		if(! this.xml)return;
		this.preLoadNonRef(jml);
		
		for(var i = 0;i < IncludeStack.length;i ++)
		if(IncludeStack[i].nodeType)this.preLoadNonRef(IncludeStack[i]);
		this.preLoadRef(jml);
		
		for(var i = 0;i < IncludeStack.length;i ++)
		if(IncludeStack[i].nodeType)this.preLoadRef(IncludeStack[i]);
		
		if(this.oninit)this.oninit();
		this.inited = true;
	}
	,preLoadNonRef:function(xmlNode){
		if(IS_IE){
			if(xmlNode.style)return;
			xmlNode.ownerDocument.setProperty("SelectionNamespaces","xmlns:j='http://www.javeline.net/j'");
		};
		var nodes = xmlNode.selectNodes("//teleport|//j:teleport|//presentation|//j:presentation|//settings|//j:settings|//skin|//j:skin|//bindings[@id]|//actions[@id]|//dragdrop[@id]|//j:bindings[@id]|//j:actions[@id]|//j:dragdrop[@id]");
		this.preLoadNodes(nodes);
	}
	,preLoadRef:function(xmlNode){
		var nodes = xmlNode.selectNodes("//style|//j:style|//model[@id]|//smartbinding[@id]|//j:smartbinding[@id]|//j:model[@id]");
		this.preLoadNodes(nodes);
	}
	,preLoadNodes:function(nodes){
		for(var i = 0;i < nodes.length;i ++){
			if(this.handler[nodes[i][TAGNAME]]){
				this.handler[nodes[i][TAGNAME]](nodes[i]);
			};
			
			if(nodes[i][TAGNAME]!= "presentation" && nodes[i].parentNode)nodes[i].parentNode.removeChild(nodes[i]);
		}
	}
	,loadIncludeFile:function(filename){
	}

,handler:{
		"teleport":function(q){
			Kernel.TelePort.load(q);
		}
		,"settings":function(q,jmlParent){
			SKIN_PATH = q.getAttribute("skin-path")|| "Skins/";
			
			if(! self.DEBUG)DEBUG = q.getAttribute("debug")== "true";
			
			if(q.getAttribute("debug-type"))DEBUG_TYPE = q.getAttribute("debug-type");
			DEBUG_FILTER = q.getAttribute("debug-teleport")== "true" ? "":"!teleport";
			
			if(q.getAttribute("enable-rightclick")!= "true")document.oncontextmenu = function(){
				return false;
			};
			
			if(q.getAttribute("allow-select")!= "true")document.onselectstart = function(){
				return false};
			Application.autoDisableActions = q.getAttribute("auto-disable-actions")== "true";
			Application.autoDisable = q.getAttribute("auto-disable")!= "false";
			Application.disableF5 = q.getAttribute("disable-f5")== "true";
		}
	}
	,processDatabinding:function(){
		if(! this.loaded){
			if(this.onload)this.onload();
			me.moveNext();
			this.loaded = true;
		};
		this.sbInit ={
		};
	}

};

Init.run('Application');
;;;;;function Class(){
	this.__jmlLoaders =[];
	this.__addJmlLoader = function(func){
		if(! this.__jmlLoaders)func.call(this,this.jml);
		else this.__jmlLoaders.push(func);
	};
	this.__jmlDestroyers =[];
	this.__addJmlDestroyer = function(func){
		this.__jmlDestroyers.push(func);
	};
	this.__regbase = 0;
	this.hasFeature = function(test){
		return this.__regbase & test};
	var events_stack ={
	};
	this.dispatchEvent = function(eventName){
		if(this.disabled)return false;
		var result,arr = events_stack[eventName];
		
		if((! arr || ! arr.length)&& ! this[eventName])return;
		
		for(var args =[],i = 1;i < arguments.length;i ++)args.push(arguments[i]);
		
		if(this[eventName])result = this[eventName].apply(this,args);
		
		if(! arr)return result;
		
		for(var retValue,i = 0;i < arr.length;i ++){
			retValue = arr[i].apply(this,args);
			
			if(retValue != undefined)result = retValue;
		};
		return result;
	};
	this.addEventListener = function(eventName,func){
		if(! events_stack[eventName])events_stack[eventName]=[];
		events_stack[eventName].pushUnique(func);
	};
	this.removeEventListener = function(eventName,func){
		if(events_stack[eventName])events_stack[eventName].remove(func);
	};
	this.hasEventListener = function(eventName){
		return events_stack[eventName]&& events_stack[eventName].length > 0;
	};
	this.destroy = function(){
		if(this.__destroy)this.__destroy();
		
		for(var i = this.__jmlDestroyers.length - 1;i >= 0;i --)this.__jmlDestroyers[i].call(this);
		this.__jmlDestroyers = undefined;
		
		if(this.oExt && ! this.oExt.isNative)this.oExt.host = null;
		
		if(this.oInt && ! this.oExt.isNative)this.oInt.host = null;
	}

}
;;;;;;function extend(dest){
	for(var i = 1;i < arguments.length;i ++){
		var src = arguments[i];
		
		for(var prop in src)dest[prop]= src[prop];
	};
	return dest;
};

function serialize(data){
	return new JSON().getSerialized("test",data)};

function unserialize(str){
	eval("var data = " + strResult);
	return data.params;
}
;;;;;;;;;function runIE(){
	var hasIE7Security = hasIESecurity = false;
	
	if(IS_IE7)
	try{
		new XLMHttpRequest()}
	catch(e){
		hasIE7Security = true};
	
	try{
		new ActiveXObject("microsoft.XMLHTTP")}
	catch(e){
		hasIESecurity = true};
	function fixIESecurity(){
		__CONTENT_IFRAME};
	
	if(hasIESecurity)importClass(fixIESecurity,true,self);
	Kernel.getObject = hasIESecurity ? function(type,message,no_error,isDataIsland){
		if(type == "HTTP"){
			return new XMLHttpRequest();
		}
		else 
		if(type == "XMLDOM"){
			xmlParser = getDOMParser(message,no_error);
			return xmlParser;
		}
	}
	:function(type,message,no_error,isDataIsland){
		if(type == "HTTP"){
			return new ActiveXObject("microsoft.XMLHTTP");
		}
		else 
		if(type == "XMLDOM"){
			xmlParser = new ActiveXObject("microsoft.XMLDOM");
			xmlParser.setProperty("SelectionLanguage","XPath");
			
			if(message){
				if(IS_IE50)message = message.replace(/\] \]/g,"] ]").replace(/^<\?[^>]*\?>/,"");
				xmlParser.loadXML(message);
			};
			
			if(! no_error)this.xmlParseError(xmlParser);
			return xmlParser;
		}
	};
	Kernel.xmlParseError = function(xml){
		var xmlParseError = xml.parseError;
		
		if(xmlParseError != 0){
			throw new Error(1050,Kernel.formErrorString(1050,null,"XML Parse error on line " + xmlParseError.line,xmlParseError.reason + "Source Text:\n" + xmlParseError.srcText.replace(/\t/gi," ")));
		};
		return xml;
	};
	function extendXmlDb(){
	};
	Init.addConditional(extendXmlDb,self,'_XMLDatabase');
	
	if(! hasIESecurity)Init.run('XMLDatabase');
};

function runSafari(){
	setTimeoutSafari = setTimeout;
	lookupSafariCall =[];
	setTimeout = function(call,time){
		if(typeof call == "string")return setTimeoutSafari(call,time);
		return setTimeoutSafari("lookupSafariCall[" +(lookupSafariCall.push(call)- 1)+ "]()",time);
	};
	
	if(IS_SAFARI_OLD){
		HTMLHtmlElement = document.createElement("html").constructor;
		Node = HTMLElement ={
		};
		HTMLElement.prototype = HTMLHtmlElement.__proto__.__proto__;
		HTMLDocument = Document = document.constructor;
		var x = new DOMParser();
		XMLDocument = x.constructor;
		Element = x.parseFromString("<Single />","text/xml").documentElement.constructor;
		x = null;
	};
	Document.prototype.serialize = Node.prototype.serialize = XMLDocument.prototype.serialize = function(){
		return(new XMLSerializer()).serializeToString(this);
	};
	HTMLDocument.prototype.selectNodes = XMLDocument.prototype.selectNodes = function(sExpr,contextNode){
		return XPath.selectNodes(sExpr,contextNode || this);
	};
	Element.prototype.selectNodes = function(sExpr,contextNode){
		return XPath.selectNodes(sExpr,contextNode || this);
	};
	HTMLDocument.prototype.selectSingleNode = XMLDocument.prototype.selectSingleNode = function(sExpr,contextNode){
		return XPath.selectNodes(sExpr,contextNode || this)[0];
	};
	Element.prototype.selectSingleNode = function(sExpr,contextNode){
		return XPath.selectNodes(sExpr,contextNode || this)[0];
	};
	importClass(runNonIe,true,self);
	importClass(runXpath,true,self);
	importClass(runXslt,true,self);
};

function runGecko(){
	importClass(runNonIe,true,self);
	DocumentFragment.prototype.getElementById = function(id){
		return this.childNodes.length ? this.childNodes[0].ownerDocument.getElementById(id):null;
	};
	XMLDocument.prototype.__defineGetter__("xml",function(){return(new XMLSerializer()).serializeToString(this);
	}

);
	XMLDocument.prototype.__defineSetter__("xml",function(){throw new Error(1042,Kernel.formErrorString(1042,null,"XML serializer","Invalid assignment on read-only property 'xml'."));
	}

);
	Node.prototype.__defineGetter__("xml",function(){if(this.nodeType == 3 || this.nodeType == 4 || this.nodeType == 2)return this.nodeValue;
		return(new XMLSerializer()).serializeToString(this);
	}

);
	Element.prototype.__defineGetter__("xml",function(){return(new XMLSerializer()).serializeToString(this);
	}

);
	HTMLDocument.prototype.selectNodes = XMLDocument.prototype.selectNodes = function(sExpr,contextNode){
		var oResult = this.evaluate(sExpr,(contextNode ? contextNode:this),this.createNSResolver(this.documentElement),XPathResult.ORDERED_NODE_SNAPSHOT_TYPE,null);
		var nodeList = new Array(oResult.snapshotLength);
		nodeList.expr = sExpr;
		
		for(i = 0;i < nodeList.length;i ++)nodeList[i]= oResult.snapshotItem(i);
		return nodeList;
	};
	Element.prototype.selectNodes = function(sExpr){
		var doc = this.ownerDocument;
		
		if(doc.selectNodes)return doc.selectNodes(sExpr,this);
		else throw new Error(1047,Kernel.formErrorString(1047,null,"xPath selection","Method selectNodes is only supported by XML Nodes"));
	};
	HTMLDocument.prototype.selectSingleNode = XMLDocument.prototype.selectSingleNode = function(sExpr,contextNode){
		var nodeList = this.selectNodes(sExpr + "[1]",contextNode ? contextNode:null);
		return nodeList.length > 0 ? nodeList[0]:null;
	};
	Element.prototype.selectSingleNode = function(sExpr){
		var doc = this.ownerDocument;
		
		if(doc.selectSingleNode)return doc.selectSingleNode(sExpr,this);
		else throw new Error(1048,Kernel.formErrorString(1048,null,"XPath Selection","Method selectSingleNode is only supported by XML Nodes. \nInfo : " + e));
	};
	function Error(nr,msg){
		this.message = msg;
		this.nr = nr;
	}

};

function runOpera(){
	setTimeoutOpera = setTimeout;
	lookupOperaCall =[];
	setTimeout = function(call,time){
		if(typeof call == "string")return setTimeoutOpera(call,time);
		return setTimeoutOpera("lookupOperaCall[" +(lookupOperaCall.push(call)- 1)+ "]()",time);
	};
	var x = new DOMParser();
	XMLDocument = DOMParser.constructor;
	x = null;
	Node.prototype.serialize = XMLDocument.prototype.serialize = Element.prototype.serialize = function(){
		return(new XMLSerializer()).serializeToString(this);
	};
	Document.prototype.selectNodes = XMLDocument.prototype.selectNodes = HTMLDocument.prototype.selectNodes = function(sExpr,contextNode){
		var oResult = this.evaluate(sExpr,(contextNode ? contextNode:this),this.createNSResolver(this.documentElement),XPathResult.ORDERED_NODE_SNAPSHOT_TYPE,null);
		var nodeList = new Array(oResult.snapshotLength);
		nodeList.expr = sExpr;
		
		for(i = 0;i < nodeList.length;i ++)nodeList[i]= oResult.snapshotItem(i);
		return nodeList;
	};
	Element.prototype.selectNodes = function(sExpr){
		var doc = this.ownerDocument;
		
		if(! doc.selectSingleNode){
			doc.selectSingleNode = HTMLDocument.prototype.selectSingleNode;
			doc.selectNodes = HTMLDocument.prototype.selectNodes;
		};
		
		if(doc.selectNodes)return doc.selectNodes(sExpr,this);
		else throw new Error(1047,Kernel.formErrorString(1047,null,"XPath Selection","Method selectNodes is only supported by XML Nodes"));
	};
	Document.prototype.selectSingleNode = XMLDocument.prototype.selectSingleNode = HTMLDocument.prototype.selectSingleNode = function(sExpr,contextNode){
		var nodeList = this.selectNodes(sExpr + "[1]",contextNode ? contextNode:null);
		return nodeList.length > 0 ? nodeList[0]:null;
	};
	Element.prototype.selectSingleNode = function(sExpr){
		var doc = this.ownerDocument;
		
		if(! doc.selectSingleNode){
			doc.selectSingleNode = HTMLDocument.prototype.selectSingleNode;
			doc.selectNodes = HTMLDocument.prototype.selectNodes;
		};
		
		if(doc.selectSingleNode)return doc.selectSingleNode(sExpr,this);
		else throw new Error(1048,Kernel.formErrorString(1048,null,"XPath Selection","Method selectSingleNode is only supported by XML Nodes. \nInfo : " + e));
	};
	importClass(runNonIe,true,self);
};

function runNonIe(){
	HTMLDocument.prototype.setProperty = XMLDocument.prototype.setProperty = function(x,y){
	};
	Kernel.getObject = function(type,message,no_error){
		if(type == "HTTP"){
			return new XMLHttpRequest();
		}
		else 
		if(type == "XMLDOM"){
			xmlParser = new DOMParser();
			
			if(message)xmlParser = xmlParser.parseFromString(message,"text/xml");
			return xmlParser;
		}
	};
	Kernel.xmlParseError = function(xml){
		if(xml.documentElement.tagName == "parsererror"){
			var str = xml.documentElement.firstChild.nodeValue.split("\n");
			var linenr = str[2].match(/\w+ (\d+)/)[1];
			var message = str[0].replace(/\w+ \w+ \w+: (.*)/,"$1");
			var srcText = xml.documentElement.lastChild.firstChild.nodeValue.split("\n")[0];
			throw new Error(1050,Kernel.formErrorString(1050,null,"XML Parse Error on line " + linenr,message + "\nSource Text : " + srcText.replace(/\t/gi," ")));
		};
		return xml;
	};
	Init.add(function(){var nodes = document.getElementsByTagName("form");
		
		for(var i = 0;i < nodes.length;i ++)nodes[i].removeNode();
		var nodes = document.getElementsByTagName("xml");
		
		for(var i = 0;i < nodes.length;i ++)nodes[i].removeNode();
		nodes = null;
	}

);
	MAXMSG = 3;
	ERROR_COUNT = 0;
	
	if(document.body)document.body.focus = function(){
	};
	Document.prototype.elementFromPoint = function(x,y){
		this.addEventListener("mousemove",this.elementFromPoint__handler,false);
		var event = this.createEvent("MouseEvents");
		var box = this.getBoxObjectFor(this.documentElement);
		var screenDelta ={
			x:box.screenX,y:box.screenY};
		event.initMouseEvent("mousemove",true,false,this.defaultView,0,x + screenDelta.x,y + screenDelta.y,x,y,false,false,false,false,0,null);
		this.dispatchEvent(event);
		this.removeEventListener("mousemove",this.elementFromPoint__handler,false);
		return this.elementFromPoint__target;
	};
	Document.prototype.elementFromPoint__handler = function(event){
		this.elementFromPoint__target = event.explicitOriginalTarget;
		
		if(this.elementFromPoint__target.nodeType == Node.TEXT_NODE)this.elementFromPoint__target = this.elementFromPoint__target.parentNode;
		
		if(this.elementFromPoint__target.nodeName.toUpperCase()== "HTML" && this.documentElement.nodeName.toUpperCase()== "HTML")this.elementFromPoint__target = this.getElementsByTagName("BODY").item(0);
		event.preventDefault();
		event.stopPropagation();
	};
	Document.prototype.elementFromPoint__target = null;
	Init.run('XMLDatabase');
};

function runXpath(){
	XPath ={
		cache:{
		}
	,getChildNode:function(htmlNode,tagName,info,count,num,sResult){
			var numfound = 0,result = null,data = info[count];
			var nodes = htmlNode.childNodes;
			
			if(! nodes)return;
			
			for(var i = 0;i < nodes.length;i ++){
				if(tagName &&(nodes[i].style ? nodes[i].tagName.toLowerCase():nodes[i].tagName)!= tagName)continue;
				
				if(data)data[0](nodes[i],data[1],info,count + 1,numfound ++,sResult);
				else sResult.push(nodes[i]);
			}
		}
		,doQuery:function(htmlNode,qData,info,count,num,sResult){
			var result = null,data = info[count];
			var query = qData[0];
			var returnResult = qData[1];
			var qResult = eval(query);
			
			if(returnResult)return sResult.push(qResult);
			
			if(! qResult)return;
			
			if(data)data[0](htmlNode,data[1],info,count + 1,0,sResult);
			else sResult.push(htmlNode);
		}
		,getTextNode:function(htmlNode,empty,info,count,num,sResult){
			var result = null,data = info[count];
			var nodes = htmlNode.childNodes;
			
			for(var i = 0;i < nodes.length;i ++){
				if(nodes[i].nodeType != 3 && nodes[i].nodeType != 4)continue;
				
				if(data)data[0](nodes[i],data[1],info,count + 1,i,sResult);
				else sResult.push(nodes[i]);
			}
		}
		,getAnyNode:function(htmlNode,empty,info,count,num,sResult){
			var result = null,data = info[count];
			var sel =[],nodes = htmlNode.childNodes;
			
			for(var i = 0;i < nodes.length;i ++){
				if(data)data[0](nodes[i],data[1],info,count + 1,i,sResult);
				else sResult.push(nodes[i]);
			}
		}
		,getAttributeNode:function(htmlNode,attrName,info,count,num,sResult){
			if(! htmlNode || htmlNode.nodeType != 1)return;
			var result = null,data = info[count];
			var value = htmlNode.getAttributeNode(attrName);
			
			if(data)data[0](value,data[1],info,count + 1,0,sResult);
			else 
			if(value)sResult.push(value);
		}
		,getAllNodes:function(htmlNode,x,info,count,num,sResult){
			var result = null,data = info[count];
			var tagName = x[0];
			var inclSelf = x[1];
			var prefix = x[2];
			
			if(inclSelf &&(htmlNode.tagName == tagName || tagName == "*")){
				if(data)data[0](htmlNode,data[1],info,count + 1,0,sResult);
				else sResult.push(htmlNode);
			};
			var nodes = $(tagName,htmlNode,prefix);
			
			for(var i = 0;i < nodes.length;i ++){
				if(data)data[0](nodes[i],data[1],info,count + 1,i,sResult);
				else sResult.push(nodes[i]);
			}
		}
		,getParentNode:function(htmlNode,empty,info,count,num,sResult){
			var result = null,data = info[count];
			var node = htmlNode.parentNode;
			
			if(data)data[0](node,data[1],info,count + 1,0,sResult);
			else 
			if(node)sResult.push(node);
		}
		,getPrecedingSibling:function(htmlNode,tagName,info,count,num,sResult){
			var result = null,data = info[count];
			var node = htmlNode.previousSibling;
			
			while(node){
				if(tagName != "NODE()" && node.tagName != tagName){
					node = node.previousSibling;
					continue;
				};
				
				if(data)data[0](node,data[1],info,count + 1,0,sResult);
				else 
				if(node)sResult.push(node);
			}
		}
		,getFollowingSibling:function(htmlNode,tagName,info,count,num,sResult){
			var result = null,data = info[count];
			var node = htmlNode.nextSibling;
			
			while(node){
				if(tagName != "NODE()" && node.tagName != tagName){
					node = node.nextSibling;
					continue;
				};
				
				if(data)data[0](node,data[1],info,count + 1,0,sResult);
				else 
				if(node)sResult.push(node);
			}
		}
		,multiXpaths:function(contextNode,list,info,count,num,sResult){
			for(var i = 0;i < list.length;i ++){
				var info = list[i][0];
				var rootNode =(info[3]? contextNode.ownerDocument.documentElement:contextNode);
				info[0](rootNode,info[1],list[i],1,0,sResult);
			};
			sResult.makeUnique();
		}
		,compile:function(sExpr){
			sExpr = sExpr.replace(/\[(\d+)\]/g,"/##$1");
			sExpr = sExpr.replace(/\|\|(\d+)\|\|\d+/g,"##$1");
			sExpr = sExpr.replace(/\.\|\|\d+/g,".");
			sExpr = sExpr.replace(/\[([^\]]*)\]/g,"/##$1");
			
			if(sExpr == ".")return ".";
			sExpr = sExpr.replace(/\/\//g,"descendant::");
			return this.processXpath(sExpr);
		}
		,processXpath:function(sExpr){
			var results = new Array();
			sExpr = sExpr.replace(/('[^']*)\|([^']*')/g,"$1_@_$2");
			sExpr = sExpr.split("\|");
			
			for(var i = 0;i < sExpr.length;i ++)sExpr[i]= sExpr[i].replace(/('[^']*)\_\@\_([^']*')/g,"$1|$2");
			
			if(sExpr.length == 1)sExpr = sExpr[0];
			else{
				for(var i = 0;i < sExpr.length;i ++)sExpr[i]= this.processXpath(sExpr[i]);
				results.push([this.multiXpaths,sExpr]);
				return results;
			};
			var isAbsolute = sExpr.match(/^\/[^\/]/);
			var sections = sExpr.split("/");
			
			for(var i = 0;i < sections.length;i ++){
				if(sections[i]== "." || sections[i]== "")continue;
				else 
				if(sections[i].match(/^[\w-_\.]+(?:\:[\w-_\.]+){0,1}$/))results.push([this.getChildNode,sections[i]]);
				else 
				if(sections[i].match(/^\#\#(\d+)$/))results.push([this.doQuery,["num+1 == " + parseInt(RegExp.$1)]]);
				else 
				if(sections[i].match(/^\#\#(.*)$/)){
					var query = RegExp.$1;
					var m =[query.match(/\(/g),query.match(/\)/g)];
					
					if(m[0]|| m[1]){
						while(! m[0]&& m[1]|| m[0]&& ! m[1]|| m[0].length != m[1].length){
							query += sections[++ i];
						}
					};
					results.push([this.doQuery,[this.compileQuery(query)]]);
				}
				else 
				if(sections[i]== "*")results.push([this.getChildNode,null]);
				else 
				if(sections[i].substr(0,2)== "[]")results.push([this.getAllNodes,["*",false]]);
				else 
				if(sections[i].match(/descendant-or-self::node\(\)$/))results.push([this.getAllNodes,["*",true]]);
				else 
				if(sections[i].match(/descendant-or-self::([^\:]*)(?:\:(.*)){0,1}$/))results.push([this.getAllNodes,[RegExp.$2 || RegExp.$1,true,RegExp.$1]]);
				else 
				if(sections[i].match(/descendant::([^\:]*)(?:\:(.*)){0,1}$/))results.push([this.getAllNodes,[RegExp.$2 || RegExp.$1,false,RegExp.$1]]);
				else 
				if(sections[i].match(/^\@(.*)$/))results.push([this.getAttributeNode,RegExp.$1]);
				else 
				if(sections[i]== "text()")results.push([this.getTextNode,null]);
				else 
				if(sections[i]== "node()")results.push([this.getAnyNode,null]);
				else 
				if(sections[i]== "..")results.push([this.getParentNode,null]);
				else 
				if(sections[i].match(/following-sibling::(.*)$/))results.push([this.getFollowingSibling,RegExp.$1.toUpperCase()]);
				else 
				if(sections[i].match(/preceding-sibling::(.*)$/))results.push([this.getPrecedingSibling,RegExp.$1.toUpperCase()]);
				else 
				if(sections[i].match(/self::(.*)$/))results.push([this.doQuery,["XPath.doXpathFunc('local-name', htmlNode) == '" + RegExp.$1 + "'"]]);
				else{
					var query = sections[i];
					var m =[query.match(/\(/g),query.match(/\)/g)];
					
					if(m[0]|| m[1]){
						while(! m[0]&& m[1]|| m[0]&& ! m[1]|| m[0].length != m[1].length){
							query += "/" + sections[++ i];
							m =[query.match(/\(/g),query.match(/\)/g)];
						}
					};
					results.push([this.doQuery,[this.compileQuery(query),true]])}
			};
			results[0][3]= isAbsolute;
			return results;
		}
		,compileQuery:function(code){
			var c = new CodeCompilation(code);
			return c.compile();
		}
		,doXpathFunc:function(type,arg1,arg2,arg3){
			switch(type){
				case "not":return ! arg1;
				case "position()":return num == arg1;
				case "format-number":return new String(Math.round(parseFloat(arg1)* 100)/ 100).replace(/(\.\d?\d?)$/,function(m1){return m1.pad(3,"0",PAD_RIGHT)}
			);
				;
				case "floor":return Math.floor(arg1);
				case "ceiling":return Math.ceil(arg1);
				case "starts-with":return arg1 ? arg1.substr(0,arg2.length)== arg2:false;
				case "string-length":return arg1 ? arg1.length:0;
				case "count":return arg1 ? arg1.length:0;
				case "last":return arg1 ? arg1[arg1.length - 1]:null;
				case "local-name":return arg1 ? arg1.tagName:"";
				case "substring":return arg1 && arg2 ? arg1.substring(arg2,arg3 || 0):"";
				case "contains":return arg1 && arg2 ? arg1.indexOf(arg2)> - 1:false;
				case "concat":
				for(var str = "",i = 1;i < arguments.length;i ++)str += arguments[i];
				return str;
			}
		}
		,selectNodeExtended:function(sExpr,contextNode){
			var sResult = this.selectNodes(sExpr,contextNode);
			
			if(sResult.length == 0)return null;
			
			if(sResult.length == 1){
				sResult = sResult[0];
				
				if(sResult.nodeType == 1)return sResult.firstChild ? sResult.firstChild.nodeValue:"";
				
				if(sResult.nodeType > 1 || sResult.nodeType < 5)return sResult.nodeValue;
			};
			return sResult;
		}
		,selectNodes:function(sExpr,contextNode){
			if(! this.cache[sExpr])this.cache[sExpr]= this.compile(sExpr);
			
			if(typeof this.cache[sExpr]== "string" && this.cache[sExpr]== ".")return[contextNode];
			var info = this.cache[sExpr][0];
			var rootNode =(info[3]&& ! contextNode.nodeType == 9 ? contextNode.ownerDocument.documentElement:contextNode);
			var sResult =[];
			info[0](rootNode,info[1],this.cache[sExpr],1,0,sResult);
			return sResult;
		}
	};
	function CodeCompilation(code){
		this.data ={
			F:[],S:[],I:[],X:[]};
		this.compile = function(){
			code = code.replace(/ or /g," || ");
			code = code.replace(/ and /g," && ");
			code = code.replace(/!=/g,"{}");
			code = code.replace(/=/g,"==");
			code = code.replace(/\{\}/g,"!=");
			this.tokenize();
			this.insert();
			return code;
		};
		this.tokenize = function(){
			var data = this.data.F;
			code = code.replace(/(format-number|contains|substring|local-name|last|node|position|round|starts-with|string|string-length|sum|floor|ceiling|concat|count|not)\s*\(/g,function(d,match){return(data.push(match)- 1)+ "F_";
			}
		);
			var data = this.data.S;
			code = code.replace(/'([^']*)'/g,function(d,match){return(data.push(match)- 1)+ "S_";
			}
		);
			code = code.replace(/"([^"]*)"/g,function(d,match){return(data.push(match)- 1)+ "S_";
			}
		);
			var data = this.data.X;
			code = code.replace(/(^|\W|\_)([\@\.\/A-Za-z][\.\@\/\w]*(?:\(\)){0,1})/g,function(d,m1,m2){return m1 +(data.push(m2)- 1)+ "X_";
			}
		);
			code = code.replace(/(\.[\.\@\/\w]*)/g,function(d,m1,m2){return(data.push(m1)- 1)+ "X_";
			}
		);
			var data = this.data.I;
			code = code.replace(/(\d+)(\W)/g,function(d,m1,m2){return(data.push(m1)- 1)+ "I_" + m2;
			}
		);
		};
		this.insert = function(){
			var data = this.data;
			code = code.replace(/(\d+)([FISX])_/g,function(d,nr,type){var value = data[type][nr];
				
				if(type == "F"){
					return "XPath.doXpathFunc('" + value + "', ";
				}
				else 
				if(type == "S"){
					return "'" + value + "'";
				}
				else 
				if(type == "I"){
					return value;
				}
				else 
				if(type == "X"){
					return "XPath.selectNodeExtended('" + value.replace(/'/g,"\\'")+ "', htmlNode)";
				}
			}
		);
		}
	}

};

function runXslt(){
	function XSLTProcessor(){
		this.templates ={
		};
		this.p ={
			"value-of":function(context,xslNode,childStack,result){
				var xmlNode = XPath.selectNodes(xslNode.getAttribute("select"),context)[0];
				
				if(! xmlNode)value = "";
				else 
				if(xmlNode.nodeType == 1)value = xmlNode.firstChild ? xmlNode.firstChild.nodeValue:"";
				else value = typeof xmlNode == "object" ? xmlNode.nodeValue:xmlNode;
				result.appendChild(this.xmlDoc.createTextNode(value));
			}
			,"copy-of":function(context,xslNode,childStack,result){
				var xmlNode = XPath.selectNodes(xslNode.getAttribute("select"),context)[0];
				
				if(xmlNode)result.appendChild(IS_SAFARI ? result.ownerDocument.importNode(xmlNode,true):xmlNode.cloneNode(true));
			}
			,"if":function(context,xslNode,childStack,result){
				if(XPath.selectNodes(xslNode.getAttribute("test"),context)[0]){
					this.parseChildren(context,xslNode,childStack,result);
				}
			}
			,"for-each":function(context,xslNode,childStack,result){
				var nodes = XPath.selectNodes(xslNode.getAttribute("select"),context);
				
				for(var i = 0;i < nodes.length;i ++){
					this.parseChildren(nodes[i],xslNode,childStack,result);
				}
			}
			,"choose":function(context,xslNode,childStack,result){
				var nodes = xslNode.childNodes;
				
				for(var i = 0;i < nodes.length;i ++){
					if(! nodes[i].tagName)continue;
					
					if(nodes[i][TAGNAME]== "otherwise" || nodes[i][TAGNAME]== "when" && XPath.selectNodes(nodes[i].getAttribute("test"),context)[0])return this.parseChildren(context,nodes[i],childStack[i][2],result);
				}
			}
			,"apply-templates":function(context,xslNode,childStack,result){
				var t = this.templates[xslNode.getAttribute("select")];
				this.parseChildren(context,t[0],t[1],result);
			}
			,"when":function(){
			}
		,"otherwise":function(){
			}
		,"copy-clone":function(context,xslNode,childStack,result){
				result = result.appendChild(xslNode.cloneNode(false));
				
				if(result.nodeType == 1){
					for(var i = 0;i < result.attributes.length;i ++){
						var blah = result.attributes[i].nodeValue;
						result.attributes[i].nodeValue = result.attributes[i].nodeValue.replace(/\{([^\}]+)\}/g,function(m,xpath){var xmlNode = XPath.selectNodes(xpath,context)[0];
							
							if(! xmlNode)value = "";
							else 
							if(xmlNode.nodeType == 1)value = xmlNode.firstChild ? xmlNode.firstChild.nodeValue:"";
							else value = typeof xmlNode == "object" ? xmlNode.nodeValue:xmlNode;
							return value;
						}
					);
						result.attributes[i].nodeValue;
					}
				};
				this.parseChildren(context,xslNode,childStack,result);
			}
		};
		this.parseChildren = function(context,xslNode,childStack,result){
			if(! childStack)return;
			
			for(var i = 0;i < childStack.length;i ++){
				childStack[i][0].call(this,context,childStack[i][1],childStack[i][2],result);
			}
		};
		this.compile = function(xslNode){
			var nodes = xslNode.childNodes;
			
			for(var stack =[],i = 0;i < nodes.length;i ++){
				if(nodes[i][TAGNAME]== "template"){
					this.templates[nodes[i].getAttribute("match")|| nodes[i].getAttribute("name")]=[nodes[i],this.compile(nodes[i])];
				}
				else 
				if(this.p[nodes[i][TAGNAME]]){
					stack.push([this.p[nodes[i][TAGNAME]],nodes[i],this.compile(nodes[i])]);
				}
				else{
					stack.push([this.p["copy-clone"],nodes[i],this.compile(nodes[i])]);
				}
			};
			return stack;
		};
		this.importStylesheet = function(xslDoc){
			this.xslDoc = xslDoc.nodeType == 9 ? xslDoc.documentElement:xslDoc;
			xslStack = this.compile(xslDoc);
			var t = this.templates["/"]? "/":false;
			
			if(! t)
			for(t in this.templates)
			if(typeof this.templates[t]== "array")break;
			this.xslStack =[[this.p["apply-templates"],{
				getAttribute:function(){
					return t}
			}
		]]};
		this.transformToFragment = function(doc,newDoc){
			this.xmlDoc = Kernel.getObject("XMLDOM","<xsltresult></xsltresult>");
			var docfrag = this.xmlDoc.createDocumentFragment();
			var result = this.parseChildren(doc.nodeType == 9 ? doc.documentElement:doc,this.xslDoc,this.xslStack,docfrag);
			return docfrag;
		}
	}

};


if(IS_IE)importClass(runIE,true,self);


if(IS_SAFARI)importClass(runSafari,true,self);


if(IS_OPERA)importClass(runOpera,true,self);


if(IS_GECKO || ! IS_IE && ! IS_SAFARI && ! IS_OPERA)importClass(runGecko,true,self);
;__ALIGNMENT__ = 1 << 12;
;__ANCHORING__ = 1 << 13;
;__CACHE__ = 1 << 2;
;__DATABINDING__ = 1 << 1;
;__DEFERREDUPDATE__ = 1 << 3;
;__DELAYEDRENDER__ = 1 << 11;__DRAGDROP__ = 1 << 5;
;__EDITMODE__ = 1 << 15;

__MULTILANG__ = 1 << 16;
;__FORMELEMENT__ = 1 << 6;
;__JMLDOM__ = 1 << 14;
;__MULTIBINDING__ = 1 << 7;
;__MULTISELECT__ = 1 << 8;
;__PRESENTATION__ = 1 << 9;
;__RENAME__ = 1 << 10;
;;;;;;;;;;;;;;function HTTP(){
	this.queue =[];
	this.callbacks ={
	};
	this.cache ={
	};
	this.timeout = 10000;
	
	if(! this.uniqueId)this.uniqueId = Kernel.all.push(this)- 1;
	this.SmartBindingHook =["http","variables"];
	Kernel.TelePort.register(this);
	
	if(! this.toString){
		this.toString = function(){
			return "[Javeline TelePort Component : (HTTP)]";
		}
	};
	this.loadCache = function(name){
		var strResult = this.get(CWD + name + ".txt");
		
		if(! strResult)return false;
		eval("var data = " + strResult);
		this.cache = data.params;
		return true;
	};
	this.getXML = function(url,receive,async,userdata,nocache){
		return this.get(url,receive,async,userdata,nocache,"",true);
	};
	this.getString = function(url,receive,async,userdata,nocache){
		return this.get(url,receive,async,userdata,nocache,"");
	};
	this.get = function(url,receive,async,userdata,nocache,data,useXML,id,autoroute,useXSLT,caching){
		if(IS_OPERA)async = true;
		
		if(IS_SAFARI)url = htmlentitiesdecode(url);
		
		if(Kernel.isNot(id)){
			var http = Kernel.getObject("HTTP");
			id = this.queue.push([http,receive,null,null,userdata,null,[url,async,data,nocache,useXSLT,caching],useXML,0])- 1;
		}
		else{
			var http = this.queue[id][0];
			http.abort();
		};
		
		if(async){
			if(IS_IE50){
				this.queue[id][3]= new Date();
				var tpModule = this;
				this.queue[id][2]= function(){
					var dt = new Date(new Date().getTime()- tpModule.queue[id][3].getTime());
					var diff = parseInt(dt.getSeconds()* 1000 + dt.getMilliseconds());
					
					if(diff > tpModule.timeout){
						tpModule.dotimeout(id);
						return};
					
					if(tpModule.queue[id][0].readyState == 4){
						tpModule.queue[id][0].onreadystatechange = function(){
						};
						tpModule.receive(id);
					}
				};
				this.queue[id][5]= setInterval(function(){tpModule.queue[id][2]()}
				,20);
			}
			else{
				var tpModule = this;
				http.onreadystatechange = function(){
					if(! tpModule.queue[id]|| http.readyState != 4)return;
					tpModule.receive(id);
				}
			}
		};
		
		if(! autoroute)autoroute = this.shouldAutoroute;
		var srv = autoroute ? this.routeServer:url;
		
		try{
			http.open(this.protocol || "GET",srv +(nocache ?(srv.match(/\?/)? "&":"?")+ Math.random():""),async);
			http.setRequestHeader("User-Agent","Javeline TelePort 1.0.0");
			http.setRequestHeader("Content-type",this.contentType ||(this.useXML || useXML ? "text/xml":"text/plain"));
			
			if(autoroute){
				http.setRequestHeader("X-Route-Request",url);
				http.setRequestHeader("X-Proxy-Request",url);
				http.setRequestHeader("X-Compress-Response","gzip");
			}
		}
		catch(e){
			if(this.autoroute && ! autoroute){
				if(! Kernel.isNot(id)){
					clearInterval(this.queue[id][5]);
				};
				this.shouldAutoroute = true;
				return this.get(url,receive,async,userdata,nocache,data,useXML,id,true,useXSLT);
			};
			var noClear = receive ? receive(null,__RPC_ERROR__,{userdata:userdata,http:http,url:url,tpModule:this,id:id,message:"Permission denied accessing remote resource: " + url}
		):false;
			
			if(! noClear)this.clearQueueItem(id);
			return;
		};
		
		if(this.__HeaderHook)this.__HeaderHook(http);
		
		try{
			http.send(data);
		}
		catch(e){
			Kernel.debugMsg("<strong>File or Resource not available " + arguments[0]+ "</strong><hr />","teleport");
			var noClear = receive ? receive(null,__RPC_ERROR__,{userdata:userdata,http:http,url:url,tpModule:this,id:id,message:"---- Javeline Error ----\nMessage : File or Resource not available: " + arguments[0]}
		):false;
			
			if(! noClear)this.clearQueueItem(id);
			return;
		};
		
		if(! async)return this.receive(id);
	};
	this.receive = function(id){
		if(! this.queue[id])return false;
		clearInterval(this.queue[id][5]);
		var data,message;
		var http = this.queue[id][0];
		
		try{
			if(http.status){
			}
	}
		catch(e){
			return setTimeout('Kernel.lookup(' + this.uniqueId + ').receive(' + id + ')',10);
		};
		var callback = this.queue[id][1];
		var useXML = this.queue[id][7];
		var userdata = this.queue[id][4];
		var retries = this.queue[id][8];
		var a = this.queue[id][6];
		var from_url = a[0];
		var useXSLT = a[4];
		
		try{
			var msg = "";
			
			if(http.status != 200 && http.status != 0)throw new Error(0,"HTTP error [" + id + "]:" + http.status + "\n" + http.responseText);
			
			if(useXML || this.useXML){
				if(http.responseText.replace(/^[\s\n\r]+|[\s\n\r]+$/g,"")== "")throw new Error("Empty Document");
				msg = "Received invalid XML\n\n";
				var xmlDoc = http.responseXML && http.responseXML.documentElement ? Kernel.xmlParseError(http.responseXML):Kernel.getObject("XMLDOM",http.responseText);
				
				if(IS_IE)xmlDoc.setProperty("SelectionLanguage","XPath");
				var xmlNode = xmlDoc.documentElement;
			};
			var data = useXML || this.useXML ? xmlNode:http.responseText;
			
			if(this.isRPC){
				msg = "RPC result did not validate: ";
				message = this.checkErrors(data,http);
				data = this.unserialize(message);
			};
			
			if(useXML && useXSLT){
				var xmlNode = data;
				this.getXML(useXSLT,function(data,state,extra){if(state != __HTTP_SUCCESS__){
						if(state == __HTTP_TIMEOUT__ && extra.retries < MAX_JAV_RETRIES)return extra.tpModule.retry(extra.id);
						else{
							extra.userdata.message = "Could not load XSLT from external resource :\n\n" + extra.message;
							extra.userdata.callback(data,state,extra.userdata);
						}
					};
					var result = xmlNode.transformNode(data);
					var noClear = extra.userdata.callback ? extra.userdata.callback([result,xmlNode],__RPC_SUCCESS__,extra.userdata):false;
					
					if(! noClear)extra.tpModule.queue[id]= null;
				}
				,true,{callback:callback,userdata:userdata,http:http,url:from_url,tpModule:this,id:id,retries:retries}
			);
				return;
			}
		}
		catch(e){
			var noClear = callback ? callback(data,__RPC_ERROR__,{userdata:userdata,http:http,url:from_url,tpModule:this,id:id,message:msg + e.message,retries:retries}
		):false;
			
			if(! noClear){
				http.abort();
				this.clearQueueItem(id);
			};
			return;
		};
		var noClear = callback ? callback(data,__RPC_SUCCESS__,{userdata:userdata,http:http,url:from_url,tpModule:this,id:id,retries:retries}
	):false;
		
		if(! noClear)this.clearQueueItem(id);
		return data;
	};
	this.dotimeout = function(id){
		if(! this.queue[id])return false;
		clearInterval(this.queue[id][5]);
		var http = this.queue[id][0];
		
		try{
			if(http.status){
			}
	}
		catch(e){
			return setTimeout('HTTP.dotimeout(' + id + ')',10);
		};
		var callback = this.queue[id][1];
		var useXML = this.queue[id][7];
		var userdata = this.queue[id][4];
		http.abort();
		var noClear = callback ? callback(null,__RPC_TIMEOUT__,{userdata:userdata,http:http,url:this.queue[id][6][0],tpModule:this,id:id,message:"HTTP Call timed out",retries:this.queue[id][8]}
	):false;
		
		if(! noClear)this.clearQueueItem(id);
	};
	this.clearQueueItem = function(id){
		if(IS_IE50)clearInterval(this.queue[id][5]);
		this.queue[id]= null;
		delete this.queue[id];
	};
	this.retry = function(id){
		if(! this.queue[id])return false;
		clearInterval(this.queue[id][5]);
		var q = this.queue[id];
		var a = q[6];
		q[8]++;
		this.get(a[0],q[1],a[1],q[4],a[3],a[2],q[7],id,null,null,a[5]);
		return true;
	};
	this.cancel = function(id){
		if(! this.queue[id])return false;
		this.queue[id][0].abort();
	};
	
	if(! this.load){
		this.load = function(x){
			var receive = x.getAttribute("receive");
			
			for(var i = 0;i < x.childNodes[i].length;i ++){
				var useXML = x.childNodes[i].getAttribute("type")== "XML";
				var url = x.childNodes[i].getAttribute("url");
				var receive = x.childNodes[i].getAttribute("receive")|| receive;
				var async = x.childNodes[i].getAttribute("async")!= "false";
				this[x.childNodes[i].getAttribute("name")]= function(data,userdata){
					return this.get(url,self[receive],async,userdata,false,data,useXML);
				}
			}
		};
		this.instantiate = function(x){
			var url = x.getAttribute("src");
			var useXSLT = x.getAttribute("xslt");
			var async = x.getAttribute("async")!= "false";
			this.getURL = function(data,userdata){
				return this.get(url,this.callbacks.getURL,async,userdata,false,data,true,null,null,useXSLT);
			};
			var name = "http" + Math.round(Math.random()* 100000);
			Kernel.setReference(name,this);
			return name + ";getURL";
		};
		this.call = function(method,args){
			this[method].call(this,args);
		}
	}

};

Init.run('HTTP');
;Init.run('XMLDatabase');
;;;;;;function REST(){
	this.supportMulticall = false;
	this.protocol = "GET";
	this.vartype = "cgi";
	this.isXML = true;
	this.namedArguments = true;
	this.SmartBindingHook =["rpc","arguments"];
	Kernel.TelePort.register(this);
	
	if(! this.uniqueId){
		Kernel.makeClass(this);
		this.inherit(CommBaseClass);
		this.inherit(HTTP);
		this.inherit(RPC);
	};
	this.unserialize = function(str){
		return str;
	};
	this.serialize = function(functionName,args){
		for(var vars =[],i = 0;i < args.length;i ++)vars.push(encodeURI(args[i][0])+ "=" + encodeURI(args[i][1]|| ""));
		
		if(! this.BaseURL)this.BaseURL = this.URL;
		var nUrl = this.urls[functionName]? this.urls[functionName]:this.BaseURL;
		this.URL = nUrl +(nUrl.match(/\?/)? "&":"?")+ vars.join("&");
		return "";
	};
	this.checkErrors = function(data,http){
		return data;
	};
	this.__load = function(x){
		if(x.getAttribute("method-name")){
			var mName = x.getAttribute("method-name");
			var nodes = x.childNodes;
			
			for(var i = 0;i < nodes.length;i ++){
				var y = nodes[i];
				var v = y.insertBefore(x.ownerDocument.createElement("variable"),y.firstChild);
				v.setAttribute("name",mName);
				v.setAttribute("value",y.getAttribute("name"));
			}
		}
	}

}
;;;function RPC(){
	if(! this.supportMulticall)this.multicall = false;
	this.stack ={
	};
	this.globals ={
	};
	this.names ={
	};
	this.urls ={
	};
	this.isRPC = true;
	this.useHTTP = true;
	this.TelePortModule = true;
	this.routeServer = HOST + "/cgi-bin/rpcproxy.cgi";
	this.autoroute = false;
	this.namedArguments = false;
	this.addMethod = function(name,receive,names,async,vexport,is_global,global_name,global_lookup,caching){
		if(is_global)this.callbacks[name]= new Function('data','status','extra','Kernel.lookup(' + this.uniqueId + ').setGlobalVar("' + global_name + '"' + ', data, extra.http, "' + global_lookup + '", "' + receive + '", extra, status)');
		else 
		if(receive)this.callbacks[name]= receive;
		this.setName(name,names);
		
		if(vexport)this.vexport = vexport;
		this[name]= new Function('return this.call("' + name + '"' + ', this.fArgs(arguments, this.names["' + name + '"], ' +(this.vartype != "cgi" && this.vexport == "cgi")+ '));');
		this[name].async = async;
		this[name].caching = caching;
		return true;
	};
	this.setName = function(name,names){
		this.names[name]= names;
	};
	this.setCallback = function(name,func){
		this.callbacks[name]= func;
	};
	this.fArgs = function(a,nodes,no_globals){
		var args =[];
		
		if(! no_globals)
		for(var i = 0;i < this.globals.length;i ++)args.push([this.globals[i][0],this.globals[i][1]]);
		
		if(nodes && nodes.length){
			for(var value,j = 0,i = 0;i < nodes.length;i ++){
				if(nodes[i].getAttribute("value"))value = nodes[i].getAttribute("value");
				else 
				if(nodes[i].getAttribute("method"))value = self[nodes[i].getAttribute("method")](args);
				else 
				if(! Kernel.isNot(a[j]))value = a[j ++];
				else value = nodes[i].getAttribute("default");
				value = nodes[i].getAttribute("encoded")== "true" ? encodeURIComponent(value):value;
				args.push(this.namedArguments ?[nodes[i].getAttribute("name"),value]:value);
			}
		}
		else 
		for(var i = 0;i < a.length;i ++)args.push(a[i]);
		
		if(! no_globals)
		for(var i = 0;i < this.globals.length;i ++)args.push([this.globals[i][0],this.globals[i][1]]);
		return args;
	};
	this.setGlobalVar = function(name,data,http,lookup,receive,extra,status){
		if(status != __RPC_SUCCESS__){
			if(receive)self[receive](data,status,extra);
			return;
		};
		
		if(this.vartype == "header" && lookup && http)data = http.getResponseHeader(lookup);
		
		if(lookup.split("\:",2)[0]== "xpath"){
			try{
				var doc = Kernel.getObject("XMLDOM",data).documentElement;
			}
			catch(e){
				throw new Error(1083,Kernel.formErrorString(1083,null,"Receiving global","Returned value is not XML (for global variable lookup with name '" + name + "')"));
			};
			var xmlNode = doc.selectSingleNode(lookup.split("\:",2)[1]);
			var data = xmlNode.nodeValue();
		};
		
		for(var found = false,i = 0;i < this.globals.length;i ++){
			if(this.globals[i][0]== name){
				this.globals[i][1]= data;
				found = true;
			}
		};
		
		if(! found)this.globals.push([name,data]);
		
		if(receive)self[receive](data,__RPC_SUCCESS__,extra);
	};
	this.call = function(name,args){
		if(this.workOffline)return;
		
		if(this.oncall)this.oncall(name,args);
		var receive = typeof this.callbacks[name]== "string" ? self[this.callbacks[name]]:this.callbacks[name];
		
		if(! receive)receive = function(){
		};
		
		if(this.multicall){
			if(! this.stack[this.URL])this.stack[this.URL]= new Array();
			this.stack[this.URL].push({m:name,p:args}
		);
			return true;
		};
		var data = this.serialize(name,args);
		var info = this.get(this.URL,receive,this[name].async,this[name].userdata,true,data,false,null,null,null,this[name].caching);
		return info;
	};
	this.purge = function(modConst,receive,userdata){
		var data = this.serialize("multicall",[this.stack[modConst.URL]]);
		info = this.get(modConst.URL,receive,false,userdata,true,data,false);
		this.stack[modConst.URL]= new Array();
		return info[1];
	};
	this.revert = function(modConst){
		this.stack[modConst.URL]= new Array();
	};
	this.load = function(x){
		this.timeout = parseInt(x.getAttribute("timeout"))|| this.timeout;
		this.URL = x.getAttribute("url-eval")? eval(x.getAttribute("url-eval")):x.getAttribute("url");
		
		if(this.URL)this.server = this.URL.replace(/^(.*\/\/[^\/]*)\/.*$/,"$1")+ "/";
		this.multicall = x.getAttribute("multicall")== "true";
		this.autoroute = x.getAttribute("autoroute")== "true";
		this.workOffline = x.getAttribute("offline")== "true";
		
		if(this.__load)this.__load(x);
		var q = x.childNodes;
		
		for(var i = 0;i < q.length;i ++){
			if(q[i].nodeType != 1)continue;
			
			if(q[i].tagName == "global"){
				this.globals.push([q[i].getAttribute("name"),q[i].getAttribute("value")]);
				continue;
			};
			
			if(IS_IE)var nodes = q[i].getElementsByTagName("j:variable|variable");
			else{
				var nodes = q[i].getElementsByTagNameNS("http://javeline.nl/j","variable");
				
				if(! nodes.length)nodes = q[i].getElementsByTagName("variable");
			};
			
			if(q[i].getAttribute("url"))this.urls[q[i].getAttribute("name")]= q[i].getAttribute("url");
			this.addMethod(q[i].getAttribute("name"),q[i].getAttribute("receive")|| x.getAttribute("receive"),nodes,(q[i].getAttribute("async")== "false" ? false:true),q[i].getAttribute("export"),q[i].getAttribute("type")== "global",q[i].getAttribute("variable"),q[i].getAttribute("lookup"),q[i].getAttribute("caching")== "true");
		}
	}

}
;;function loadIncludes(docElement){
	if(window.opener && window.opener.Application){
		if(document.all)document.body.innerHTML = window.opener.Application.xml.outerHTML;
		LoadData["interface"]=[window.opener.Application.xmlClass,document.all ? document.getElementsByTagName("Application")[0]:window.opener.Application.xml];
		document.body.style.display = "block";
		return;
	};
	document.body.setAttribute("mode","xml");
	
	if((! IS_IE || document.body.getAttribute("mode")== "xml")&& ! docElement){
		return new HTTP().getString(document.body.getAttribute("xml-url")|| location.href,function(xmlString){var str = xmlString.replace(/xmlns\=\"[^"]*\"/g,"").split("\n");
			str.shift();
			str = str.join("\n");
			var xmlNode = Kernel.getObject("XMLDOM",str);
			
			if(Kernel.xmlParseError)Kernel.xmlParseError(xmlNode);
			return loadIncludes(xmlNode);
		}
		,true);
	}
	else 
	if(! docElement)docElement = document;
	AppData = docElement.body ? docElement.body:docElement.selectSingleNode("/html/body");
	loadJMLIncludes(AppData);
	
	if(! self.ERROR_HAS_OCCURRED)Init.interval = setInterval('if(checkLoaded()) initialize()',20);
};

function loadJMLIncludes(xmlNode,oHttp,doSync){
	return true;
};

function loadJMLInclude(node,oHttp,doSync,path){
};

var AppData,IncludeStack =[];

Init.addConditional(loadIncludes,null,['BODY','HTTP','XMLDatabase','TelePort']);

function checkLoaded(){
	for(var i = 0;i < IncludeStack.length;i ++){
		if(! IncludeStack[i]){
			setStatus("Waiting for: [" + i + "] " + IncludeStack[i]);
			return false;
		}
	};
	
	if(! document.body)return false;
	setStatus("Dependencies loaded");
	return true;
};

function initialize(){
	setStatus("Initializing...");
	clearInterval(Init.interval);
	
	if(self._Window)_Window.Init();
	
	if(Kernel.Init)Kernel.Init();
	Init.run();
	
	if(self.Application)Application.Init(AppData);
	
	if(self.loadScreen)loadScreen.style.display = "none";
};

function getAbsolutePath(base,src){
	return src.match(/^\w+\:\/\//)? src:base + src;
};

function removePathContext(base,src){
	if(! src)return "";
	
	if(src.indexOf(base)> - 1)return src.substr(base.length);
	return src;
};


if(document.body)Init.run('BODY');

else window.onload = function(){
	Init.run('BODY');
}
;;