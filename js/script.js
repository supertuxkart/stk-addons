var oldElSub ="";
var oldSub ="";
var oldRoot ="";
var oldDiv ="";

function confirm_delete(url)
{
    if (confirm("Really delete this item?")) {
        window.location = url;
    }
}

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
function loadFrame(id, page, value)
{
    $.post(page, {id: id, value: value},
    function(data) {
        $("#right-content_body").html(data);
        $("#right-content_body").scrollTop(0);
        load_jquery();
    });
}

function addonRequest(page, id, value)
{
    $.post(page, {id: id, value: value},
    function(data){
        $("#content-addon_body").html(data);
        $("#content-addon_body").scrollTop(0);
        load_jquery();
    });
}
function loadDiv(newDiv)
{
    newDiv = "disp" + newDiv;
    if(oldDiv !="")	document.getElementById(oldDiv).style.display = "none";
    document.getElementById(newDiv).style.display = "block";
    oldDiv = newDiv;
    document.getElementById("content-addon_body").innerHTML ="";
    document.getElementById("content-addon_body").style.display="none";
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
    $("#news-messages").newsTicker();
    $('a.menu_head').click(function () {
        $('ul.menu_body').slideToggle('medium');
    });
    $('#advance_button').click(function () {
    //if($("#advanced").css("margin-right")!= "279px") $("#advanced").animate({ marginRight: "279px" }, 150 );
    //else  $("#advanced").animate({ marginRight: "1px" }, 150 );
    if($("#content_advanced").css("width")!= "300px") $("#content_advanced").animate({width: "300px"}, 150 );
    else  $("#content_advanced").animate({width: "0px"}, 150 );
    });

    $('a.addon-list').click(function() {
      history.pushState({ path: this.path }, '', this.href);
      var url = this.href;
      var addonType = getUrlVars(url)['type'];
      var addonId = getUrlVars(url)['name'];
      loadFrame(addonId,'addons-panel.php?type=' + addonType);
      return false;
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

function textLimit(field, num) {
    if (field.value.length > num) {
        field.value = field.value.substring(0, num);
    }
}
function addRating(rating,addonId,sel_storage,disp_storage) {
    loadHTML('./include/addRating.php?rating='+encodeURI(rating)+'&addonId='+encodeURI(addonId),sel_storage);
    loadHTML('./include/addRating.php?addonId='+encodeURI(addonId),disp_storage);
}

// AJAX functions
function createXHR()
{
    var xmlhttp = false;
    try
    {// code for IE7+, Firefox, Chrome, Opera, Safari
        xmlhttp=new XMLHttpRequest();
    }
    catch(e)
    {// code for IE6, IE5
        try
        {
            xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
        }
        catch(e)
        {
            xmlhttp = false;
        }
    }
    return xmlhttp;
}
/**
 * Loads an HTML page
 * Put the content of the body tag into the current page.
 * @param url URL of the HTML page to load
 * @param storage ID of the tag that gets to hold the output
 */
function loadHTML(url, storage)
{
    var storage_elem = document.getElementById(storage);
    var xhr = createXHR();
    xhr.onreadystatechange=function()
    {
        if(xhr.readyState == 4)
        {
            if (storage_elem.innerHTML == "undefined")
            {
                storage_elem = xhr.responseText;
            } else {
                storage_elem.innerHTML = xhr.responseText;
            }
        }
    };
    xhr.open("GET", url , true);
    xhr.send(null);
}

// Read a page's GET URL variables and return them as an associative array.
function getUrlVars(url)
{
    if (url === undefined)
        url = window.location.href;
    
    var vars = [], hash;
    var hashes = url.slice(url.indexOf('?') + 1).split('&');
    for(var i = 0; i < hashes.length; i++)
    {
        hash = hashes[i].split('=');
        vars.push(hash[0]);
        vars[hash[0]] = hash[1];
    }
    return vars;
}
