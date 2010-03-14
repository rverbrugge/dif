/****************************************************************
**	For more information please visit www.javeline.org
**	© 2000-2006 All Rights Reserved Javeline B.V.
** Coded by Ruben Daniëls
**
**	Bootloader for Javeline TelePort(tm)
****************************************************************/

if(!self.BASEPATH) BASEPATH = "";

HOST = self.location.href.replace(/(\/\/[^\/]*)\/.*$/, "$1");
HOST_PATH = location.href.replace(/\/[^\/]*$/, "") + "/";
DEBUG = true;
DEBUG_TYPE = "Memory";
WARNINGS = false;
MAX_RETRIES = 3;

//Uncomment the libraries you need
Modules = [
	"HTTP.js", 		// for simple HTTP transactions
//	"Socket.js", 	// Javeline HTTP Socket Implementation
//	"Poll.js", 		// Javeline Polling Engine
	"RPC.js", 		// RPC Baseclass (needs HTTP class)
	
	//RPC Modules (all need RPC Baseclass)
	"RPC/XMLRPC.js",	// XML-RPC
//	"RPC/SOAP.js", 	// SOAP
//	"RPC/JSON.js", 	// JSON
//	"RPC/JPHP.js", 	// JPHP
	//"RPC/REST.js"  // HTTP (headers / cgi)
	
	//Custom Modules
//	"Custom/Datheon.js", 	 // Datheon
//	"Custom/DeskRun.js", 	 // DeskRun Filesystem Support
];

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
}

function Initialize(){
	clearInterval(Initialize.interval);
	Kernel.TelePort.load();
}

function checkLoaded(){
	for(var i=0;i<Modules.length;i++){
		if(!self[Modules[i]]){
			if(DEBUG == 3) document.title = "Waiting for module " + Modules[i];
			return false;
		}
	}
	
	if(!Kernel.TelePort.isInited) return false;
	
	if(DEBUG == 3) document.title = "Done.";
	
	return true;
}

// Load TelePort Base
include(BASEPATH + "Library/TelePort.js");

// Load TelePort(tm) Modules
for(var i=0;i<Modules.length;i++){
	include(BASEPATH + "Library/" + Modules[i]); //TelePort
	Modules[i] = Modules[i].replace(/(^.*\/|^)([^\/]*)\.js$/, "$2");
}

// Check is modules are loaded
Initialize.interval = setInterval('if(checkLoaded()) Initialize()', 20);
