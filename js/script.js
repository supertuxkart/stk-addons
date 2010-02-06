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

function addonRequest(page, id, value)
{
	/*
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
   xhr.open( "POST", page + "&id=" + id,  "true"); 
   xhr.send(value);
   */
   $.post(page, { id: id, value: value},
   function(data){
     $("#disAddon").html(data);
   $("#disAddon").scrollTop(0);
   load_jquery();
   });
   
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

$(document).ready(function () {
    $("ul.menu_body li:even").addClass("alt");
    $('a.menu_head').click(function () {
    $('ul.menu_body').slideToggle('medium');
    });
    $('ul.menu_body li a').mouseover(function () {
    $(this).animate({ fontSize: "12px", paddingLeft: "5px" }, 50 );
    });
    $('ul.menu_body li a').mouseout(function () {
    $(this).animate({ fontSize: "12px", paddingLeft: "0px" }, 50 );
    });
});

function load_jquery()
{

    $("div.help-hidden").click(function () {
    $(this).children("div").slideToggle('medium');
    });
    $("span.help-hidden").mouseover(function () {
    document.body.style.cursor='pointer';
    });
    $("span.help-hidden").mouseout(function () {
    document.body.style.cursor='default';
    });
}
