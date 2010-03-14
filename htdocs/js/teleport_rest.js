//    Javeline® TelePort™ : an Open Source server communication layer.
//
//    Copyright (C)2006 Javeline BV http://wwww.javeline.nl
//    Westeinde 38  1334 Almere  The Netherlands info@javeline.nl
//
//    This program is free software; you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation; either version 2 of the License, or
//    (at your option) any later version.
//
//    This program is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//   You should have received a copy of the GNU General Public License
//    along with this program; if not, write to the Free Software
//    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

/****************************************************************
**	© 2000-2006 All Rights Reserved Javeline B.V.
**  Coded by Ruben Daniëls
**
**	Bootloader for Javeline TelePort(tm)
****************************************************************/

if(!self.BASEPATH) BASEPATH = "";

HOST = self.location.href.replace(/(\/\/[^\/]*)\/.*$/, "$1");
HOST_PATH = location.href.replace(/\/[^\/]*$/, "") + "/";
DEBUG = true;
DEBUG_TYPE = "Memory";
WARNINGS = false;
PACKAGED = true;
MAX_RETRIES = 3;

function include(sourceFile){
	//Safari Special Case
	if(navigator.vendor == "Apple Computer, Inc." || navigator.vendor == "KDE")
		document.write("<script src='" + sourceFile + "'></script>");

	//Other browsers
	else{
		var head = document.documentElement.getElementsByTagName("head")[0];
		var elScript = document.createElement("script");
		elScript.src = sourceFile;
		head.appendChild(elScript);
	}
}//    Javeline® TelePort™ : an Open Source server communication layer.
//
//    Copyright (C)2006 Javeline BV http://wwww.javeline.nl
//    Westeinde 38  1334 Almere  The Netherlands info@javeline.nl
//
//    See license.txt for the full license.

function HTTP(){
	this.queue = [];
	this.timeout = 10000; //default 10 seconds
	if(!this.uniqueId) this.uniqueId = Kernel.all.push(this) - 1;

	this.getXML = function(url, receive, async, userdata, nocache){
		return this.get(url, receive, async, userdata, nocache, "", true);
	}

	this.getString = function(url, receive, async, userdata, nocache){
		return this.get(url, receive, async, userdata, nocache, "");
	}

	this.get = function(url, receive, async, userdata, nocache, data, useXML, id, autoroute){
		if(Kernel.isFalse(id)){
			var http = Kernel.getObject("HTTP");
			id = this.queue.push([http, receive, null, null, userdata, null, [url, async, data, nocache], useXML, 0])-1;
		}
		else{
			var http = this.queue[id][0];
			http.abort();
		}

		this.queue[id][3] = new Date();
		this.queue[id][2] = new Function('var HTTP=Kernel.lookup(' + this.uniqueId + ');var id="' + id + '";var dt = new Date(new Date().getTime() - HTTP.queue[id][3].getTime());diff = parseInt(dt.getSeconds()*1000 + dt.getMilliseconds());if(diff > HTTP.timeout){HTTP.dotimeout(id); return};if(HTTP.queue[id][0].readyState == 4){HTTP.queue[id][0].onreadystatechange = function(){};HTTP.receive(id);}');
		this.queue[id][5] = setInterval('Kernel.lookup(' + this.uniqueId + ').queue[' + id + '][2]()', 20);

		if(!autoroute) autoroute = this.shouldAutoroute;
		var srv = autoroute ? this.routeServer : url;

		// #ifdef __DEBUG
		Kernel.debugMsg("<strong>Making request[" + id + "] to " + url + (autoroute ? "<br /><span style='color:green'>[via: " + srv + (nocache ? (srv.match(/(\.asp|\.aspx|\.ashx)$/) ? "/" : (srv.match(/\?/) ? "&" : "?")) + Math.random() : "") + "]</span>" : "") + "</strong> with data:<br />" + new String(data.xml ? data.xml : data).replace(/</g, "&lt;") + "<hr />");
		// #endif
		
		try{
			http.open(this.protocol || "GET", srv + (nocache ? (srv.match(/(\.asp|\.aspx|\.ashx)$/) ? "/" : (srv.match(/\?/) ? "&" : "?")) + Math.random() : ""), async);
			http.setRequestHeader("User-Agent", "Javeline TelePort 0.8.9");
			http.setRequestHeader("Content-type", this.useXML || useXML ? "text/xml" : "text/plain");
			//nochache? xmlhttp.setRequestHeader("If-Modified-Since","0");
			
			if(autoroute){
				http.setRequestHeader("X-Route-Request", url);
				http.setRequestHeader("X-Proxy-Request", url);
				http.setRequestHeader("X-Compress-Response", "gzip");
			}
		}catch(e){
			// Retry request by routing it
			if(this.autoroute && !autoroute){
				if(!Kernel.isFalse(id)){
					clearInterval(this.queue[id][5]);
					//this.queue[id] = null;
				}
				this.shouldAutoroute = true;
				return this.get(url, receive, async, userdata, nocache, data, useXML, id, true);
			}
			
			// #ifdef __DEBUG
			Kernel.debugMsg("<strong>Permission denied accessing remote source " + url + "</strong><hr />");
			// #endif

			//Routing didn't work either... Throwing error
			var noClear = receive ? receive(null, __RPC_ERROR__, {
				userdata : userdata,
				http : http,
				tpModule : this,
				id : id,
				message : "---- Javeline Error ----\nMessage : Permission denied accessing remote resource: " + url
			}) : false;
			if(!noClear) this.queue[id] = null;
			
			return;
		}

		if(this.__HeaderHook) this.__HeaderHook(http);

		// #ifdef __DEBUG

		try{
			// Sending
			http.send(data);
		}catch(e){
			
			// #ifdef __DEBUG
			Kernel.debugMsg("<strong>File or Resource not available " + arguments[0] + "</strong><hr />");
			// #endif
			
			// File not found
			var noClear = receive ? receive(null, __RPC_ERROR__, {
				userdata : userdata,
				http : http,
				tpModule : this,
				id : id,
				message : "---- Javeline Error ----\nMessage : File or Resource not available: " + arguments[0]
			}) : false;
			if(!noClear) this.queue[id] = null;
			
			return;
		}

		if(!async) return this.receive(id);
	}

	this.receive = function(id){
		if(!this.queue[id]) return false;

		clearInterval(this.queue[id][5]);

		var message;
		var http = this.queue[id][0];

		// Test if HTTP object is ready
		try{if(http.status){}}catch(e){return setTimeout('Kernel.lookup(' + this.uniqueId + ').receive(' + id + ')', 10);}

		var callback = this.queue[id][1];
		var useXML = this.queue[id][7];
		var userdata = this.queue[id][4];
		var retries = this.queue[id][8];
		var from_url = this.queue[id][6][0];

		// #ifdef __DEBUG
		Kernel.debugMsg("<strong>Receiving [" + id + "] from " + from_url + "<br /></strong>" + http.responseText.replace(/\</g, "&lt;").replace(/\n/g, "<br />") + "<hr />");
		// #endif

		try{
			var msg = "";

			// Check HTTP Status
			if(http.status != 200 && http.status != 0)
				throw new Error(0, "HTTP error [" + id + "]:" + http.status);

			// Check for XML Errors
			if(useXML || this.useXML){
				if(http.responseText.replace(/^[\s\n\r]+|[\s\n\r]+$/g, "") == "") throw new Error("Empty Document");
				
				msg = "Received invalid XML:";
				var xmlDoc = http.responseXML && http.responseXML.documentElement ? http.responseXML : Kernel.getObject("XMLDOM", http.responseText);
				if(Kernel.isIE) xmlDoc.setProperty("SelectionLanguage", "XPath");
				var xmlNode = xmlDoc.documentElement;
			}

			// Get content
			var content = useXML || this.useXML ? xmlNode : http.responseText;

			// Check RPC specific Error messages
			if(this.isRPC){
				msg = "RPC result did not validate: ";
				message = this.checkErrors(content, http);
			}
		}
		catch(e){
			// Send callback error state
			var noClear = callback ? callback(content, __RPC_ERROR__, {
				userdata : userdata,
				http : http,
				tpModule : this,
				id : id,
				message : msg + e.message,
				retries : retries
			}) : false;
			if(!noClear){
				http.abort();
				this.queue[id] = null;
			}

			return;
		}

		var data = this.isRPC ? this.unserialize(message) : content;

		var noClear = callback ? callback(data, __RPC_SUCCESS__, {
			userdata : userdata,
			http : http,
			tpModule : this,
			id : id,
			retries : retries
		}) : false;
		if(!noClear) this.queue[id] = null;

		return xmlNode;
	}

	this.dotimeout = function(id){
		if(!this.queue[id]) return false;

		clearInterval(this.queue[id][5]);
		var http = this.queue[id][0];
		
		// Test if HTTP object is ready
		try{if(http.status){}}catch(e){return setTimeout('HTTP.dotimeout(' + id + ')', 10);}
		
		var callback = this.queue[id][1];
		var useXML = this.queue[id][7];
		var userdata = this.queue[id][4];

		http.abort();

		// #ifdef __DEBUG
		Kernel.debugMsg("<strong>HTTP Timeout [" + id + "]<br /></strong><hr />");
		// #endif

		var noClear = callback ? callback(null, __RPC_TIMEOUT__, {
			userdata : userdata,
			http : http,
			tpModule : this,
			id : id,
			message : "HTTP Call timed out",
			retries : this.queue[id][8]
		}) : false;
		if(!noClear) this.queue[id] = null;
	}

	this.retry = function(id){
		if(!this.queue[id]) return false;

		var q = this.queue[id];
		var a = q[6];

		// #ifdef __DEBUG
		Kernel.debugMsg("<strong>Retrying request...<br /></strong><hr />");
		// #endif

		q[8]++;

		//this.get(a[0], null, a[1], null, null, a[3], a[2], id);
		this.get(a[0], q[1], a[1], q[4], a[3], a[2], q[7], id);
		
		return true;
	}

	this.cancel = function(id){
		if(!this.queue[id]) return false;

		this.queue[id][0].abort();
	}

	if(!this.load){
		this.load = function(x){
			var receive = x.getAttribute("receive");
			
			for(var i=0;i<x.childNodes[i].length;i++){
				var useXML = x.childNodes[i].getAttribute("type") == "XML";
				var url = x.childNodes[i].getAttribute("url");
				var receive = x.childNodes[i].getAttribute("receive") || receive;
				var async = x.childNodes[i].getAttribute("async") != "false";
				
				this[x.childNodes[i].getAttribute("name")] = function(data, userdata){
					return this.get(url, self[receive], async, userdata, false, data, useXML);
				}
			}
		}
	}
}

if(!self.PACKAGED && self.Kernel && !Kernel.TelePort.inited) Kernel.TelePort.Init();
//    Javeline® TelePort™ : an Open Source server communication layer.
//
//    Copyright (C)2006 Javeline BV http://wwww.javeline.nl
//    Westeinde 38  1334 Almere  The Netherlands info@javeline.nl
//
//    See license.txt for the full license.

function Poll(server){
	this.uniqueId = Kernel.all.push(this) - 1;

	//Options
	this.server = "";
	this.connectRetries = 4;
	this.lastTime = "";
	this.mplexmode = 0;
	this.mplexdata = "";
	this.interval = 10;

	/*********************************************************************
										PUBLIC METHODS
	*********************************************************************/

	this.start = function(interval){
		clearInterval(this.timer);

		if(interval) this.interval = interval;
		this.timer = setTimeout('Kernel.lookup(' + this.uniqueId + ').poll()', this.interval * 1000);

		this.isPolling = true;
	}

	this.stop = function(){
		clearInterval(this.timer);
		this.isPolling = false;
	}

	/*********************************************************************
										PRIVATE METHODS
	*********************************************************************/

	// NOT NEEDED
	/*this.doubleCheckPoll = function(){
		if(this.isPolling && (this.lastPoll - new Date().getTime()) > this.interval * 1000) this.poll();
	}
	setInterval('Kernel.lookup(' + this.uniqueId + ').doubleCheckPoll()', 10000);
	*/

	/************************
			RECEIVE
	************************/
	this.rfunc = new Function('data', 'status', 'extra', 'Kernel.lookup(' + this.uniqueId + ').receive(data, status, extra)');
	this.poll = function(){
		this.lastPoll = new Date().getTime();

		if(this.connect == 0)
			HTTP.getXML(this.URL + "&time=" + this.lastTime, this.rfunc, true, null, true);
		else if(this.connect == 1)
			this.oRPC[this.oMethod]();
	}

	this.isPotentialDisconnect = 0;
	this.isInited = false;
	this.receive = function(xmlData, status, extra){
		var id;

		if(extra.http.status == 200 && extra.http.responseText.replace(/\n/g, "") == '') return this.start();

		if(status != __RPC_SUCCESS__){
			if(extra.retries < this.connectRetries) return HTTP.retry(extra.id);
			else if(this.doConnectError(extra.http)) this.start();

			return;
		}

		// Check for custom errors
		if(this.onerrorcheck) this.onerrorcheck(xmlData, status, extra);

		// #ifdef __DEBUG
		Kernel.debugMsg("<strong>Polling received " + (xmlData.xml ? xmlData.xml : xmlData).replace(/</g, "&lt;") + "</strong>");
		// #endif

		// Multiplexing modes
		if(this.mplexmode == 0) id = 0;
		else if(this.mplexmode == 1) id = xmlData.getAttribute(this.mplexdata);
		else if(this.mplexmode == 2) id = self[this.mplexdata]();

		// Call Method
		var success = this.process[id](xmlData);

		success ? this.poll() : this.start();
	}

	this.doConnectError = function(http, force){
		if(DEBUG) document.title = "message didn't arrive (" + chatlist.isPotentialDisconnect + ") ";
		this.stop();
	}

	this.process = [];
	this.addProcess = function(func, id){
		if(!id) id = this.process.length;
		this.process[id] = func;
	}

	this.load = function(x){
		this.mplexmode = x.getAttribute("mplexmode");
		this.interval = x.getAttribute("interval");

		if(this.mplexmode == 1) this.mplexdata = x.getAttribute("xml-attr");
		if(this.mplexmode == 2) this.mplexdata = x.getAttribute("method");

		if(x.getAttribute("url")){
			this.connect = 0;
			this.oURL = x.getAttribute("url");
		}
		else if(x.getAttribute("rpc")){
			this.connect = 1;
			this.oRPC = self[this.connect[0]];
			this.oMethod = this.connect[1];
			this.oRPC.setCallback(this.oMethod, this.rfunc);
		}

		return this;
	}
}//    Javeline® TelePort™ : an Open Source server communication layer.
//
//    Copyright (C)2006 Javeline BV http://wwww.javeline.nl
//    Westeinde 38  1334 Almere  The Netherlands info@javeline.nl
//
//    See license.txt for the full license.

Array.prototype.dataType = "array";
Number.prototype.dataType = "number";
Date.prototype.dataType = "date";
Boolean.prototype.dataType = "boolean";
String.prototype.dataType = "string";

function RPC(){
	if(!this.supportMulticall) this.multicall = false;

	this.stack = {};
	this.callbacks = {};
	this.globals = [];
	this.names = [];
	this.urls = [];

	this.isRPC = true;
	this.useHTTP = true;

	this.routeServer = HOST + "/cgi-bin/rpcproxy.cgi";
	this.autoroute = false;
	
	this.namedArguments = false;

	/* ADD METHODS */

	this.addMethod = function(name, receive, names, async, vexport, is_global, global_name, global_lookup){
		if(is_global) this.callbacks[name] = new Function('data', 'status', 'extra', 'Kernel.lookup(' + this.uniqueId + ').setGlobalVar("' + global_name + '"' + ', data, extra.http, "' + global_lookup + '", "' + receive + '", extra, status)');
		else if(receive) this.callbacks[name] = receive;

		this.setName(name, names);
		if(vexport) this.vexport = vexport;
		this[name] = new Function('return this.call("' + name + '"' + ', this.fArgs(arguments, this.names["' + name + '"], ' + (this.vartype != "cgi" && this.vexport == "cgi") + '));');
		this[name].async = async;

		return true;
	}

	this.setName = function(name, names){
		this.names[name] = names;
	}

	this.setCallback = function(name, func){
		this.callbacks[name] = func;
	}

	this.fArgs = function(a, nodes, no_globals){
		var args = [];
		
		if(!no_globals) for(var i=0;i<this.globals.length;i++) args.push([this.globals[i][0], this.globals[i][1]]);

		if(nodes && nodes.length){
			for(var value, j=0,i=0;i<nodes.length;i++){
				// Determine value
				if(nodes[i].getAttribute("value")) value = nodes[i].getAttribute("value");
				else if(nodes[i].getAttribute("method")) value = self[nodes[i].getAttribute("method")](args);
				else if(!Kernel.isFalse(a[j])) value = a[j++];
				else value = nodes[i].getAttribute("default");
				
				//Encode string optionally
				value = nodes[i].getAttribute("encoded") == "true" ? encodeURIComponent(value) : value;
				
				//Set arguments
				args.push(this.namedArguments ? [nodes[i].getAttribute("name"), value] : value);
			}
		}
		else
			for(var i=0;i<a.length;i++) args.push(a[i]);

		if(!no_globals) for(var i=0;i<this.globals.length;i++) args.push([this.globals[i][0], this.globals[i][1]]);

		return args;
	}

	/* GLOBALS */

	this.setGlobalVar = function(name, data, http, lookup, receive, extra, status){
		if(status != __RPC_SUCCESS__){
			// #ifdef __DEBUG
			Kernel.debugMsg("Could not get Global Variable<br />");
			// #endif

			if(receive) self[receive](data, status, extra);
			return;
		}

		if(this.vartype == "header" && lookup && http) data = http.getResponseHeader(lookup);
		if(lookup.split("\:", 2)[0] == "xpath"){

			try{
				var doc = XMLDatabase.getObject("XMLDOM", data).documentElement;
			}
			catch(e){
				throw new Error(0, "---- Javeline Error ----\nMessage : Returned value is not XML (for global variable lookup with name '" + name + "')")
			}

			var xmlNode = doc.selectSingleNode(lookup.split("\:", 2)[1]);
			var data = xmlNode.nodeValue();
		}

		for(var found=false,i=0;i<this.globals.length;i++){
			if(this.globals[i][0] == name){
				this.globals[i][1] = data;
				found = true;
			}
		}
		if(!found) this.globals.push([name, data]);

		if(receive) self[receive](data, __RPC_SUCCESS__, extra);
	}

	/* CALL */

	this.call = function(name, args){
		if(this.oncall) this.oncall(name, args);

		// #ifdef __DEBUG
		if(!this[name]){throw new Error(1602, "---- Javeline Error ----\nProcess :  RPC Send\nMessage : Callback method is not declared: '" + name + "'")}
		// #endif

		var receive = typeof this.callbacks[name] == "string" ? eval(this.callbacks[name]) : this.callbacks[name];

		// Set up multicall
		if(this.multicall){
			if(!this.stack[this.URL]) this.stack[this.URL] = new Array();
			this.stack[this.URL].push({m : name, p : args});
			return true;
		}

		// Get Data
		var data = this.getSerialized(name, args); //function of module

		// Sent the request
		var info = this.get(this.URL, receive, this[name].async, this[name].userdata, true, data);

		return info;
	}

	/* PURGE MULTICALL */

	this.purge = function(modConst, receive, userdata){
		// Get Data
		var data = this.getSerialized("multicall", [this.stack[modConst.URL]]); //function of module

		info = this.get(modConst.URL, receive, false, userdata, true, data, false);
		this.stack[modConst.URL] = new Array();

		return info[1];
	}

	this.revert = function(modConst){
		this.stack[modConst.URL] = new Array();
	}

	/* Load XML Definitions */

	this.load = function(x){
		this.timeout = parseInt(x.getAttribute("timeout")) || this.timeout;
		this.URL = x.getAttribute("url-type") == "eval" ? eval(x.getAttribute("url")) : x.getAttribute("url");
		this.server = this.URL.replace(/^(.*\/\/[^\/]*)\/.*$/, "$1") + "/";
		this.multicall = x.getAttribute("multicall") == "true";
		this.autoroute = x.getAttribute("autoroute") == "true";
		
		if(this.__load) this.__load(x);

		var q = x.childNodes;
		for(var i=0;i<q.length;i++){
			if(q[i].nodeType != 1) continue;

			if(q[i].tagName == "global"){
				this.globals.push([q[i].getAttribute("name"), q[i].getAttribute("value")]);
				continue;
			}

			var nodes = q[i].getElementsByTagName("variable");

			if(q[i].getAttribute("url")) this.urls[q[i].getAttribute("name")] = q[i].getAttribute("url");

			//Add Method
			this.addMethod(
				q[i].getAttribute("name"),
				q[i].getAttribute("receive") || x.getAttribute("receive"),
				nodes,
				(q[i].getAttribute("async") == "false" ? false : true),
				q[i].getAttribute("export"),
				q[i].getAttribute("type") == "global",
				q[i].getAttribute("variable"),
				q[i].getAttribute("lookup")
			);
		}
	}
}
//    Javeline® TelePort™ : an Open Source server communication layer.
//
//    Copyright (C)2006 Javeline BV http://wwww.javeline.nl
//    Westeinde 38  1334 Almere  The Netherlands info@javeline.nl
//
//    See license.txt for the full license.

__RPC_SUCCESS__ = 1;
__RPC_TIMEOUT__ = 2;
__RPC_ERROR__ = 3;

Kernel = {
	toString : function(){
		return "[Javeline (Kernel)]";
	},

	all : [],
	isIE : document.all ? true : false,
	isSafari : (navigator.vendor == "Apple Computer, Inc." || navigator.vendor == "KDE"),
	isGecko : navigator.userAgent.indexOf("Firefox") !=-1,
	useDeskRun : (window.external && window.external.GetVersion && window.external.GetRuntimeType() == 2) ? true : false,

	lookup : function(uniqueId){
		return this.all[uniqueId];
	},

	inherit : function(obj, classRef){
		obj.s = classRef;
		obj.s();
		obj.s = null;
	},

	isFalse : function(c){
		return c == null || !c && c != false && c != "" || (typeof c == "number" && !isFinite(c));
	},

	DEBUG : self.DEBUG,
	DEBUG_TYPE : self.DEBUG_TYPE || "Memory", //Window
	DEBUG_INFO : "",

	debugMsg : function(msg){
		if(!this.DEBUG) return;

		if(this.DEBUG_TYPE == "Window" || this.win && !this.win.closed){
			this.win = window.open("", "debug");
			this.win.document.write(msg);
		}
		else this.DEBUG_INFO += msg;

		if(this.ondebug) this.ondebug(msg);
	},

	showDebug : function(){
		this.win = window.open("", "debug");
		this.win.document.write(this.DEBUG_INFO);
	},

	decodeBase64 : function(sEncoded){
		// Input must be dividable with 4.
		if(!sEncoded || (sEncoded.length % 4) > 0)
		  return sEncoded;

		/* Use NN's built-in base64 decoder if available.
		   This procedure is horribly slow running under NN4,
		   so the NN built-in equivalent comes in very handy. :) */

		else if(typeof(atob) != 'undefined')
		  return atob(sEncoded);

	  	var nBits, i, sDecoded = '';
	  	var base64 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=';
		sEncoded = sEncoded.replace(/\W|=/g, '');

		for(i=0; i < sEncoded.length; i += 4){
			nBits =
				(base64.indexOf(sEncoded.charAt(i))   & 0xff) << 18 |
				(base64.indexOf(sEncoded.charAt(i+1)) & 0xff) << 12 |
				(base64.indexOf(sEncoded.charAt(i+2)) & 0xff) <<  6 |
				base64.indexOf(sEncoded.charAt(i+3)) & 0xff;
			sDecoded += String.fromCharCode(
				(nBits & 0xff0000) >> 16, (nBits & 0xff00) >> 8, nBits & 0xff);
		}

		// not sure if the following statement behaves as supposed under
		// all circumstances, but tests up til now says it does.

		return sDecoded.substring(0, sDecoded.length -
		 ((sEncoded.charCodeAt(i - 2) == 61) ? 2 :
		  (sEncoded.charCodeAt(i - 1) == 61 ? 1 : 0)));
	},

	getObject : function(type, message){
		if(type == "HTTP"){
			return document.all ? (Kernel.useDeskRun?window.external.CreateComponent("microsoft.XMLHTTP"):(new ActiveXObject("microsoft.XMLHTTP"))) : new XMLHttpRequest();
		}
		else if(type == "XMLDOM"){
			if(document.all){
				xmlParser = new ActiveXObject("microsoft.XMLDOM");
				xmlParser.setProperty("SelectionLanguage", "XPath");
				if(message) xmlParser.loadXML(message);
			}
			else{
				xmlParser = new DOMParser();
				if(message) xmlParser = xmlParser.parseFromString(message, "text/xml");
			}

			return xmlParser;
		}
	},

	TelePort : {
		modules : new Array(),
		named : {},

		register : function(obj){
			var id = false, data = {
				name : obj.SmartBindingHook[0],
				args : obj.SmartBindingHook[1],
				obj : obj
			};
	
			this.named[obj.SmartBindingHook[0]] = data;
			return this.modules.push(data) - 1;
		},
	
		getModules : function(){
			return this.modules;
		},
	
		getModuleByName : function(defname){
			return this.named[defname]
		},
		
		hasLoadRule : function(xmlNode){
			for(mod in this.named){
				if(!this.named[mod].args) continue;
				
				if(xmlNode.getAttribute(this.named[mod].name)){
					this.lastRuleFound = this.named[mod];
					return true;
				}
			}
			return false;
		},

		// Set Communication
		Init : function(){
			this.inited = true;
	
			var comdef = document.documentElement.getElementsByTagName("head")[0].getElementsByTagName(Kernel.isIE ? "teleport" : "j:teleport")[0];
			if(!comdef && document.documentElement.getElementsByTagNameNS) comdef = document.documentElement.getElementsByTagNameNS("http://javeline.nl/j", "j:teleport")[0];
			if(!comdef){
				this.isInited = true;
				return IssueWarning(1006, "---- Javeline Warning ----\nMessage : Could not find TelePort(tm) Definition")
			}
	
			new HTTP().getXML(HOST_PATH + comdef.getAttribute("url"), function(xmlNode, status, extra){
				if(status != __RPC_SUCCESS__){
					if(extra.retries < MAX_RETRIES) return HTTP.retry(extra.id);
					else throw new Error(1007, "---- Javeline Error ----\nMessage : Could not load TelePort(tm) Definition:\n\n" + extra.message);
				}
	
				Kernel.TelePort.xml = xmlNode;
				Kernel.TelePort.isInited = true;
				
				if(self.PACKAGED) Kernel.TelePort.load();
			}, true);
		},

		// Load TelePort Definition
		load : function(){
			if(!this.xml) return;
			
			var nodes = this.xml.childNodes;
			if(!nodes.length) return;
	
			for(var i=0;i<nodes.length;i++)
				this.initComm(nodes[i]);
	
			this.loaded = true;
			if(this.onload) this.onload();
		},

		/********* INITCOMM ***********
			Initialize Communication Protocols

			INTERFACE:
			this.initComm(xmlNode);
		****************************/
		initComm : function(x){
			if(x.nodeType != 1) return;

			//Socket Communication
			if(x.tagName == "Socket"){
				var o = new Socket();
				this.setReference(x.getAttribute("id"), o);
				o.load(x);

				return;
			}

			//Polling Engine
			if(x.tagName == "Poll"){
				this.setReference(x.getAttribute("id"), new Poll().load(x));

				return;
			}

			//Initialize Communication Component
			this.setReference(x.getAttribute("id"), new CommBaseClass(x));
		},

		/********* SETREFERENCE ***********
			Set Reference to an object by name

			INTERFACE:
			this.setReference(name, o, global);
		****************************/
		setReference : function(name, o, global){
			if(self[name]) return;
			return (self[name] = o);
		}
	}
}

function CommBaseClass(xml){
	this.xml = xml;
	this.uniqueId = Kernel.all.push(this) - 1;

	this.toString = function(){
		return "[Javeline TelePort(tm) Component : " + (this.name || "") + " (" + this.type + ")]";
	}

	this.inherit = function(classRef){
		this.s = classRef;
		this.s();
		this.s = null;
	}

	if(this.xml){
		this.name = xml.getAttribute("id");
		this.type = xml.tagName;

		// Inherit from the specified baseclass
		if(!self[this.type]) throw new Error(0, "Could not find TelePort(tm) Component '" + this.type + "'");
		this.inherit(self[this.type]);

		if(this.useHTTP){
			// Inherit from HTTP Module
			if(!self.HTTP) throw new Error(0, "Could not find TelePort(tm) HTTP Component");
			this.inherit(HTTP);
		}

		if(this.xml.getAttribute("protocol")){
			// Inherit from Module
			if(!self[this.xml.getAttribute("protocol")]) throw new Error(0, "Could not find TelePort(tm) RPC Component '" + this.xml.getAttribute("protocol") + "'");
			this.inherit(self[this.xml.getAttribute("protocol")]);
		}
	}

	// Load Comm definition
	if(this.xml) this.load(this.xml);
}

if(!self.PACKAGED && self.HTTP && !Kernel.TelePort.inited) Kernel.TelePort.Init();
//    Javeline® TelePort™ : an Open Source server communication layer.
//
//    Copyright (C)2006 Javeline BV http://wwww.javeline.nl
//    Westeinde 38  1334 Almere  The Netherlands info@javeline.nl
//
//    See license.txt for the full license.

function REST(){
	this.supportMulticall = false;
	this.protocol = "GET";
	this.vartype = "cgi";
	this.isXML = true;
	this.namedArguments = true;

	// Register Communication Module
	this.SmartBindingHook = ["http", "variables"];
	Kernel.TelePort.register(this);

	// Stand Alone
	if(!this.uniqueId){
		Kernel.inherit(this, CommBaseClass);
		this.inherit(HTTP);
		this.inherit(RPC);
	}

	this.unserialize = function(str){
		return str;
	}

	// Create message to send
	this.getSerialized = function(functionName, args){
		//HTTP HEADERS - THIS IS NOT GONNA WORK, USE HEADER HOOK
		if(this.vartype == "header"){
			for(var i=0;i<args.length;i++){
		   	if(args[i][0] && args[i][1]){

		   		// #ifdef __DEBUG
				if(self.DEBUG == 3){
					win.document.write("<strong>" + args[i][0] + ":</strong> " + args[i][1] + "<br />");
				}
				// #endif

		   		http.setRequestHeader(args[i][0], args[i][1]);
		   	}
		   }
		}

		//HTTP GET
		/*for(var i=0;i<args.length;i++){
			var obj = args[i][1];
			args[i] = args[i][0] + "=" + (obj && obj.nodeType ? obj.xml : obj.toString());
		}
		serverAddress += encodeURI(functionName + "&" + args.join("&"));*/

		if(this.vartype == "cgi" || this.vexport == "cgi"){
			var cgiargs = this.vartype == "cgi" ? args : this.globals;

			for(var vars=[],i=0;i<cgiargs.length;i++)
				vars.push(cgiargs[i][0] + "=" + (cgiargs[i][1] || ""));

			this.protocol = "GET";
		}

		if(!this.BaseURL) this.BaseURL = this.URL;
		var nUrl = this.urls[functionName] ? this.urls[functionName] : this.BaseURL;
		this.URL = nUrl + (nUrl.match(/\?/) ? "&" : "?") + vars.join("&");

		return "";
	}

	// Check Received Data for errors
	this.checkErrors = function(data, http){
		return data;
	}
	
	this.__load = function(x){
		if(x.getAttribute("var-type")) this.vartype = x.getAttribute("var-type");
		
		if(x.getAttribute("m-name")){
			var mName = x.getAttribute("m-name");
			var nodes = x.getElementsByTagName("method");

			for(var i=0;i<nodes.length;i++){
				var y = nodes[i];
				var v = y.insertBefore(x.ownerDocument.createElement("variable"), y.firstChild);
				v.setAttribute("name", mName);
				v.setAttribute("value", y.getAttribute("name"));
			}
		}
	}
}
Kernel.TelePort.Init();