var oldElSub ="";
var oldSub ="";
var oldRoot ="";
var oldDiv ="";
function loadSub(newSub)
{
	newSub = "sub" + newSub;
	if(oldSub !="")	document.getElementById(oldSub).style.display = "none";
	document.getElementById(newSub).style.display = "block"; 
	oldSub = newSub;
}

function loadAddon(id, page)
{
	addonRequest(page, id);
}

function addonRequest(page, id)
{
	
	document.getElementById("disAddon").innerHTML = '<img src="image/loader.gif" alt="loader" />';
	document.getElementById("disAddon").style.display="block";
	if(oldDiv !="")	document.getElementById(oldDiv).style.display = "none";
    var xhr; 
    try {  xhr = new ActiveXObject('Msxml2.XMLHTTP');   }
    catch (e) 
    {
        try {   xhr = new ActiveXObject('Microsoft.XMLHTTP');    }
        catch (e2) 
        {
          try {  xhr = new XMLHttpRequest();     }
          catch (e3) {  xhr = false;   }
        }
     }
 
    xhr.onreadystatechange  = function()
    { 
         if(xhr.readyState  == 4)
         {
              if(xhr.status  == 200)
              {
                 document.getElementById("disAddon").innerHTML=xhr.responseText;
				}
              else 
                 document.ajax.dyn="Error code " + xhr.status;
         }
    };
   xhr.open( "GET", page + "&id=" + id,  "true"); 
   xhr.send(null);
   
}
function loadDiv(newDiv)
{
	newDiv = "disp" + newDiv;
	if(oldDiv !="")	document.getElementById(oldDiv).style.display = "none"; 
	document.getElementById(newDiv).style.display = "block"; 
	oldDiv = newDiv;
	document.getElementById("disAddon").innerHTML ="";
	document.getElementById("disAddon").style.display="none";
}
function changeClassSub(obj)
{
	if(oldElSub !="") oldElSub.className="sub";
	obj.className = "subSelected";
	oldElSub=obj;
}
function changeClassRoot(obj)
{
	if(oldRoot !="")oldRoot.className="root";
	obj.className = "rootSelected";
	oldRoot=obj;
}
function verify(codeSent)
{
	if (confirm("Do you want remove this add-ons") == true)
	{
	eval(codeSent);
	location.reload();
	}
}
