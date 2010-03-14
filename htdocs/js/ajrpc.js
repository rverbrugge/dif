/**
 * AJ-RPC: An XML-RPC client for Javascript
 *
 * Copyright (Adam Delves) adam@sccode.com
 *
 * The code is prodvided as is with no warrenty. You may use or modify this code as you see fit 
 * provided the above copyright notice is included.
 */

/**
 * XMLRPCClient Object
 *
 * Sends and receives XML-RPC messages ad repsonses to a given URI
 *
 * @param uri The fully qualified uri of the XML-RPC server. Only one client per server.
 * @version 1.1(beta)
 */
function XMLRPCClient(uri)
{
	var currentClient = this;
	var xmlHTTP = getXMLHTTP();
	var functions = [];

	if (! xmlHTTP) {
		throw "XML HTTP Not Supported";
	}

	/**
	 * Function to execute when a response is received.
	 * 
 	 * For synchronous requests. Leave as null.
	 */
	this.onresponse = null;
	
	/**
	 * Adds a function to the client.
	 *
	 * After the function has been added it can be called as a function of the current
	 * client.	
	 *
	 * @param newFunction New function to add, either an XMLRPCFunction object or the function name.
	 * @return XMLRPCFunction object of the function added
	 */
	this.addFunction = function(newFunction)
	{
		if (newFunction.constructor != XMLRPCFunction) {
			newFunction = new XMLRPCFunction(newFunction);
		}
		
		var functionName = newFunction.getFunctionName().replace('.', '_');

		if (functionName == 'addFunction') functionName = 'add_function';

		functions[functionName] = newFunction;
		currentClient[functionName] = function()
		{
			newFunction.clearParameters();
			
			for(var x = 0; x < arguments.length; x++) {
				newFunction.addParameter(arguments[x]);		
			}

			// an XMLRPCResponse object is returned here
			var response = send(newFunction);

			if (response) {
				if (! response.isFault()) {
					return response.getReturnValue().getValue();
				} else {
					throw response;
				}	
			}
		}

		return newFunction;
	}
	
	/**
	 *  Add all supported server functions to the client function list.
	 *
	 *  
	 */
	this.aqquireFunctions = function()
	{
		// always send suynchronous
		var oldonresponse = currentClient.onresponse;
		currentClient.onresponse = null;
		
		var system_listMethods = new XMLRPCFunction('system.listMethods');

		var response = send(system_listMethods);

		if (response && (! response.isFault())) {
			var funcs = response.getReturnValue().getValue() 
			for(func in funcs) {
				currentClient.addFunction(String(funcs[func]));		
			}
		}

		currentClient.onresponse = oldonresponse;
		return true;
	}	

	this.getResponse = function()
	{
		return currentResponse;
	}
	
	this.getFunction = function(functionName)
	{
		if (functions[functionName]) {
			return functions[functionName];
		} else {
			return null;
		}
	}

	var send = function(invokeFunction)
	{	
		var message = invokeFunction.createMessage();
		var async = (invokeFunction.onresponse || currentClient.onresponse);
		
		xmlHTTP.open('post',uri, async);
		xmlHTTP.setRequestHeader('Content-Type', 'text/xml');
		
		if (async) {
			// todo set timeout here for no response
			
			xmlHTTP.onreadystatechange = function()
			{
				if (xmlHTTP.readyState != 4) return;

				if (xmlHTTP.status != 200) {
					response = new XMLRPCResponse('',5);
				} else {
					response = new XMLRPCResponse(xmlHTTP.responseText);
				}
				
				//todo send an XMLHTTPResponse here
				invokeFunction.setResponse(response);
				if (invokeFunction.onresponse) {
					invokeFunction.onresponse(response);
				} else {
					currentClient.onresponse(response);
				}
			}
		
		}
		xmlHTTP.send(message);

		if (! async) {
			var response;
			
			//todo send an XMLHTTPResponse here
			if (xmlHTTP.status != 200) {
				response = new XMLRPCResponse('',5);
			} else {
				response = new XMLRPCResponse(xmlHTTP.responseText);
			}
				
			invokeFunction.setResponse(response);
			response.setCaller(invokeFunction);
			return response;
		}
	}
}

/**
 * XMLRPCFunction
 *
 * Hold information and parameters pertaining to secific XML-RPC function.
 *
 */
function XMLRPCFunction(functionName)
{
	var params = [];
	var currentResponse = null;

	/**
	 * Function to be called when this specific function receives a response.
	 */
	this.onresponse = null;

	/**
	 * Adds a parameter to the parameter list.
	 *
	 * The order in which the parameters are passed in the RPC calls is dicated by the order
	 * in which the parameters are added using this function.
	 *
	 * @param param XMLRPCValue object for the parameter.
 	 */
	this.addParameter = function(param)
	{
		if (param.constructor != XMLRPCValue) {
			param = new XMLRPCValue(param);
		}
		
		params.push(param);	
	}
	
	/**
	 * Clears the parameter list, ready for another call.
	 */
	this.clearParameters = function()
	{
		params = [];	
	}
	
	/**
	 * Creats an XML-RPC Payload
	 *
	 * Creates an XML-RPC message payload ready to be sent to the server.
	 */
	this.createMessage = function()
	{
		var sReturn = '<?xml version="1.0" encoding="UTF-8" ?>\n';
		sReturn += '\t<methodCall>\n\t\t<methodName>' + xmlescape(functionName) + '</methodName>\n';
		sReturn += '\t<params>\n';

		for(var x = 0; x < params.length; x++) {
			sReturn += '\t\t<param>\n\t\t\t' + params[x].serialize() + '\n\t\t</param>\n';
		}

		sReturn += '\t</params>\n</methodCall>';

		return sReturn;
	}

	/**
	 * Returns the function name.
	 */
	this.getFunctionName = function()
	{
		return functionName;
	}

	/**
	 * Returns the last received response or null if there isn't one.
	 */
	this.getResponse = function()
	{
		return currentResponse;
	}

	/**
	 * Sets the last received response.
	 */
	this.setResponse =  function(response)
	{
		if (response.constructor == XMLRPCResponse) {
			currentResponse = response;
		}
	}
}

/**
 * XMLRPCResponse
 *
 * Parses and holds information about an XML-RPC response.#
 *
 * @param responseMessage Valid XML Response
 * @param faultCode Fault Code - set this when returning a local error.
 * @param faultDescription Set the fault description string.
 */
function XMLRPCResponse(responseMessage, faultCode, faultDescription)
{
	var FAULT_CODES = [];

	FAULT_CODES[1] = 'Unknown function.';
	FAULT_CODES[2] = 'Invalid / Corrupt Response';
	FAULT_CODES[3] = 'Incorrect Parameters passed to function.';
	FAULT_CODES[4] = 'Can\'t introspect: function unknown';
	FAULT_CODES[5] = 'Server did not return an OK (200) response';
	FAULT_CODES[100] = 'XML Parse Error';


	var returnValue = null;
	var responseText = '';	
	var callerFunction = null;	

	/**
	 * Get the function which generated this response.
	 */
	this.getCaller = function()
	{
		return callerFunction;
	}

	/**
	 * Get fault code.
	 * 
	 * Zero if no fault.
	 */
	this.getFaultCode = function()
	{
		return faultCode;
	}

	/**
	 * Get fault description.
	 */
	this.getFaultDescription = function()
	{
		if ((faultCode in FAULT_CODES) && (! faultDescription)) {	
			faultDescription = FAULT_CODES[faultCode];
		}
		
		return faultDescription;
	}

	/**
	 * Get response text/XML string.
	 */
	this.getResponseText = function()
	{
		return responseText;
	}

	/**
	 * Get the XMLRPCValue object for the return value.
	 */
	this.getReturnValue = function()
	{
		return returnValue;
	}

	/**
	 * Indicates whether the response is fault response.
	 */
	this.isFault = function()
	{
		return (faultCode != 0);
		
	}

	/**
	 * Sets the caller function which generated this response. 
	 */
	this.setCaller = function(newFunction)
	{
		if (! newFunction.constructor == XMLRPCFunction) {
			throw "Not an XMLRPCFunction";
		}

		callerFunction = newFunction;
	}

	/**
	 * Convert response to string.
	 *
	 * If response is not a fault, a string representation of the return value is returned.
	 * If response is a fault, the fault description is returned.
	 */
	this.toString = function()
	{
		if (faultCode == 0) {
			return returnValue.toString();
		} else {
			return this.getFaultDescription();
		}
	}

	var parseFault = function(fault)
	{
		var value = fault.getElementsByTagName('value');

		if (value.length > 0) {
			try {
				value = new XMLRPCValue(value[0]);
			} catch (e) {
				faultCode = 2;
				return;
			}
		}
		
		var returnObject = value.getValue();

		if (returnObject.faultCode && returnObject.faultString) {
			faultCode = returnObject.faultCode;
			faultDescription = returnObject.faultString;
		} else {
			faultCode = 2;	
		}	
	}
	
	if (faultCode) {
		return;	
	}
	
	// remove any junk before and afterthe <methodResponse> tag

	//responseMessage = responseMessage.replace(/(\s|\S)*<?xml/m, '<?xml');
	
	responseMessage = responseMessage.replace(/<\/methodResponse>(\s|\S)*/m, '</methodResponse>');


	// load into a DOMDocumnet
	var dom = loadXmlFromString(responseMessage);
	responseText = responseMessage;
	
	if (! dom.documentElement) {
		faultCode = 2;
		return;
	}	

	// look for fault tag
	var fault = dom.getElementsByTagName('fault');

	if (fault.length > 0) {
		parseFault(fault[0]);
		return;
	}
		
        // parse a valid response and return vlaue
	var methodResponse;
	var params;
	var param;
	var value;
	
	if (((methodResponse = dom.getElementsByTagName('methodResponse')).length == 0) ||
	    ((params = methodResponse[0].getElementsByTagName('params')).length== 0) ||
	    ((param = params[0].getElementsByTagName('param')).length == 0) ||
	    ((value = param[0].getElementsByTagName('value')).length == 0) ) {
		    faultCode = 2;
		    return;
	}

	try {
		returnValue = new XMLRPCValue(value[0]);
		faultCode = 0;
	} catch(e){
		faultCode = 2;	
	}

	return;
}

/**
 * Parses / Stores an XML-RPC representation of a value.
 *
 * @param domElement Element object containing the value, or the primitive JS value
 * @param type Force a specific type. Only used if a primitive JS value is passed.
 */
function XMLRPCValue(domElement, type)
{
	var value = new String();

	//get the value node Element passed
	if (domElement.firstChild) {

		var typeTag;
		for(var x =0; x < domElement.childNodes.length; x++) {

			var child = domElement.childNodes[x];
		
			if (child.nodeType == 1) { // ELEMENT NODE
				typeTag = child;
			}
		}

		if (! typeTag) {
			throw "Invalid Data Format";
		}
		
		type = typeTag.tagName;
		
		if (typeTag.firstChild) {
			value = typeTag.firstChild.nodeValue;
		} else {
			value = '';
		}	

		switch (type) {
			case 'int':
			case 'i4':
				value = parseInt(value);
				break;	
			case 'boolean':	
				value = parseInt(value)?true:false;	
				break;
			case 'double':	
				value = parseFloat(value);
				break;
			case 'dateTime.iso8601':
				value = parseISO8601(value);
	
				break;
			case 'array':
				value = new Array();

				var data = domElement.getElementsByTagName('data');
	
				if(data.length > 0) {
					var values = data[0].getElementsByTagName('value');

					for(var x = 0; x < values.length; x++) {
						value.push((new XMLRPCValue(values[x])).getValue());
					}
				}

				break;
			case 'struct':
				var members = domElement.getElementsByTagName('member');
				value = new Object();
				
				if(members.length > 0) {
					for(var x = 0; x < members.length; x++) {
						var name = members[x].getElementsByTagName('name');
						var mValue =  members[x].getElementsByTagName('value');
	
						if (name.length > 0) {
							name = name[0].firstChild.nodeValue;
						} else {
							throw "Ivalaid Data Format";
						}

						if (mValue.length > 0) {
							mValue = mValue[0];
						} else {
							throw "Ivalaid Data Format";
						}

						value[name] = (new XMLRPCValue(mValue)).getValue();
					}
				}
			
				break;
			default:
				type = 'string';	
			case 'string':	
				value = xmlunescape(value);
		}
	} else { // Javascript type passed
		value = domElement;
		
		if (! type) {
			type = typeof (value.valueOf());
		}		       
			
		switch (type) {
			case 'number':
			if (parseInt(value) == value) {
					type ='int';
				} else {
					type = 'double';
				}
			case 'int':
			case 'double':
			case 'int4':
				break;
				
			case 'object':
			case 'function':
				if (value.constructor == Array) {
					type = 'array';
				} else {			
					type = 'struct';
				}

			case 'array':		
				for(var x = 0; x < value.length; x++) {
					value[x] = new XMLRPCValue(value[x]).getValue();			
				}
				break;

				
			case 'struct':
				
				for(var prop in value) {
					value[prop] = new XMLRPCValue(value[prop]).getValue();
				}
				break
				
			case 'dateTime.iso8601':

			case 'boolean':
				break;
			default:
				type = 'string';	

		}

		value = domElement;

			
	}
	
	/**
	 * Gets the data type of the value.
	 */
	this.getType = function ()
	{
		return type;
	}

	/**
	 * Gets the actual Javascript value.
	 */
	this.getValue = function()
	{
		
		return value;
	}

	/**
	 * Converts the value to an XML-RPC string for inclusion in an XML-RPC message.
	 */
	this.serialize = function()
	{
		var sValue = value;

		switch (type) {
			case 'int':
				sValue = parseInt(value);
				break;

 			case 'dateTime.iso8601':
				sValue = serializeToISO8601(value);

				break;
			case 'string':
				sValue = xmlescape(sValue);	
				break;
				
			case 'array':
				sValue = '<data>\n';
				
				for(var x = 0; x < value.length; x++) {
					sValue += (new XML_RPC_Value(value[x])).serialize();
				}

				sValue += '</data>\n\n';
				break;
				
			case 'struct':	
				sValue = '';
			
				for (var prop in value) {
					sValue += '<member>\n\t<name>' + xmlescape(prop) + '</name>\n\t';
					sValue += (new XML_RPC_Value(value[prop])).serialize();
					sValue += '\n</member>\n\n';
				}

				break;
					
		}
		
		return '<value><' + type + '>' + sValue + '</' + type + '></value>\n';
	}

	/**
	 * Converts value to string.
	 */
	this.toString = function()
	{
		return value.toString();
	}
}

/**
 * Escapes <'s "'s and >'s inside a string with their entity equivilents.
 *
 * @param string to Escape
 */
function xmlescape(string)
{
	return string.replace(/&/g, '&amp;').replace(/"/g, '&quot;');
}

/**
 * Converts &gt;, &lt; and &quot; to their string equivilents
 *
 * @param string String to unescape
 */
function xmlunescape(string)
{
	return string.replace(/&amp;/g, '&').replace(/&quot;/g, '"');
}

/**
 * Converts a stirng representation of a date in ISO8601 format in to a Javascript
 * date object.
 *
 * @param String to convert.
 */
function parseISO8601(date)
{
	var ISO8601match = /(\d{4})(\d{2})(\d{2})T(\d{2}):(\d{2}):(\d{2})/;
	var result = date.match(ISO8601match);				

	if (result) {
		return new Date(result[1], result[2]-1, result[3], result[4], result[5], result[6]);
	} else {
		return new Date();
	}
	
}

/**
 * Converts a Javascript Date object to an ISO8601 DAte String
 *
 * @param Date object to convert.
 */
function serializeToISO8601(date)
{
	if (! (date instanceof Date)) return '';

	var year = String(date.getFullYear());
	var month = String(date.getMonth()+1).length==2?String(date.getMonth()+1):'0' + String(date.getMonth()+1);
	var day = String(date.getDate()).length==2?String(date.getDate()):'0' + String(date.getDate());

	return year + month + day + 'T' + date.getHours() + ':' + date.getMinutes() + ':' + date.getSeconds();
}

function getXMLHTTP()
{
	if ((typeof XMLHttpRequest) != "undefined") {
		/* XMLHTTPRequest present, use that */
		return new XMLHttpRequest();
	} else if (window.ActiveXObject) {
		/*  there are several versions of IE's Active X control, use the most recent one available */
		var xmlVersions = ["MSXML2.XMLHttp.5.0",
				   "MSXML2.XMLHttp.4.0",
				   "MSXML2.XMLHttp.3.0",
				   "MSXML2.XMLHttp",
				   "Microsoft.XMLHTTP"];

		for (var x =0; x < xmlVersions.length; x++) {
			try {
				var xmlHTTP = new ActiveXObject(xmlVersions[x]);
				return xmlHTTP;
			} catch (e) {
				//continue looping
			}
		}
	}

	/* if none of that worked, return false, to indicate failure */
	return false;
}

function loadXmlFromString(sXml)
{

	var oXml = getXML();

	if (window.ActiveXObject) { // Internet Explorer
		oXml.loadXML(sXml);
		return oXml;	
	} else if ((typeof DOMParser) != "undefined") { // w3c Compliant Browsers
		var parser = new DOMParser();
		var ret = parser.parseFromString(sXml, 'text/xml');

		return ret;
	}

	throw 'Cannot load XMl from string';
	
}

function getXML(freeThreaded)
{
	if (document.implementation.createDocument) {
		return document.implementation.createDocument('', '', null);
	} else if (window.ActiveXObject) {
		if (freeThreaded) {
			return new  ActiveXObject("MSXML2.FreeThreadedDOMDocument.3.0");
		}

		var xmlVersions = ["MSXML2.DOMDocument.5.0",
				   "MSXML2.DOMDocument.4.0",
				   "MSXML2.DOMDocument.3.0",
				   "MSXML2.DOMDocument",
				   "Microsoft.XmlDom"];
		
		for (var x =0; x < xmlVersions.length; x++) {
			try {
				var oXml = new ActiveXObject(xmlVersions[x]);
				return oXml;
			} catch (e) {
				//continue looping
			}
		}
	} 

	throw 'Cannot load XML';
}

