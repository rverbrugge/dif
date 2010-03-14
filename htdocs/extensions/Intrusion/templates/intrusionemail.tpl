Intrusion detected at <?=$domain;?>.

Your website is possibly being attacked.

All service requests from <?=$ip;?> are blocked until <?=strftime("%c", $expire);?> 

--------------------------------------------
ip:      <?=$ip;?> 
host:    <?=$host;?> 
client:  <?=$client;?> 
date:    <?=strftime("%c");?> 


