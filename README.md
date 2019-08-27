MetaMorphosis Clinical Data Conversion Tool 
 
 
Getting Started The MetaMorphosis Clinical Data Conversion Tool External Module allows REDCap Administrators to take a collection of exported patient identifiers from a source system (TriNetX, i2b2, other data warehouse) and convert them into an identified patient cohort in a standard demographics REDCap template. 

Requirements 
 • REDCap Version 8.10.2 or later • You must be a REDCap Administrator to use this module. The REDCap Administrator will serve as an honest broker of health care data for this module. • This module assumes that a valid IRB-approved protocol is in place.
 • This module assumes that there will be a Service Account used for the Data Warehouse connection. You will need an account, password and host. 
 • You may need to rewrite the Script to suit your data warehouse design. The one provided was designed to work with an i2b2 staging server. 
 
Installation 
• Obtain this module from the Consortium [REDCap Repo] (https://redcap.vanderbilt.edu/consortium/modules/index.php) from the control center. 
 
Configuration 
 
1.Enable module on all Projects by default  - As marked in the example, this option does not automatically show as a useable module. If a normal user goes into External Modules, they will not see this  
2.Make module discoverable – As marked in the example, allows the green Enable a Module to find MetaMorphosis by a REDCap Administrator 
3.Module configuration permissions in projects –  
4.The remainder of the options are related to configuring the server that you will be connecting to as the source system. You will need to enter the Username, password, host , Port, and SID.   The SID is server id. 
5.Still need to add the ability to select database 

Project	Setup	
1. The Redcap Administrator	will create a	new	project	using	the	Green	“New	Project”	Tab	using	an institutional Demographics form that must be pre-setup and marked to be used as a project template.	
2. Go	to the	External project	page	and	click	the	green	Enable	a	Module	button.	
3. Enable	the	MetaMorphosis	External	Module	by	clicking	Enable	
4. Go	to the	External Modules	link	in	the	left	navigation	and	select	Metamorphosis	
5. Go	to the	document field	and	choose	the	file	that	the	user	provided	from	either	TriNetX	or	i2b2	or	other	data	source.		You	may	add	other	data	on	this	screen	for	auditing	purposes.		
6. Click	Submit.	
7. The	External	Module	will	go	to	the	data	warehouse	and	grab	identifiable	demographics	and	place	them	as	records	in	the	REDCap	project.	
	
