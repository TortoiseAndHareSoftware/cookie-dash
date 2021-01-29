jQuery('#AcceptCookiesButton').click(function () {
    let storage = window.sessionStorage;
    if(storage.getItem('AcceptedCookies') || storage.getItem('UserOptOut'))
    {
        return;
    }
    let numDays = 30;
    let date = new Date();
    date.setTime(date.getTime() + (numDays * 24 * 60 * 60 * 1000));
    document.cookie = "AcceptedCookies=true; expires=" + date.toGMTString() + ";";
    storage.setItem('AcceptedCookies', 'true');
    jQuery('#ThisSiteUsesCookiesBox').remove();
    alert("Personalization cookies enabled, press OK to reload the page");
    location.reload();
});

jQuery('#DeclineCookiesButton').click(function (e) {
    e.preventDefault();
    let storage = window.sessionStorage;
    if(storage.getItem('AcceptedCookies') || storage.getItem('UserOptOut'))
    {
        return;
    }
    let numDays = 30;
    let date = new Date();
    date.setTime(date.getTime() + (numDays * 24 * 60 * 60 * 1000));
    document.cookie = "UserOptOut=true; expires=" + date.toGMTString() + ";";
    storage.setItem('UserOptOut', 'true');
    jQuery('#ThisSiteUsesCookiesBox').remove();
    alert("Cookies disabled, press OK to reload the page");
    location.reload();
    // let location = window.location.toString();
    // if(location.indexOf("?") > 0){
    //     window.location = location + "&UserOptOutQP";
    // }
    // else{
    //     window.location = location + "?UserOptOutQP";
    // }
    
});

jQuery(document).ready(function(){
    let storage = window.sessionStorage;
    if(!storage.getItem('AcceptedCookies') && !storage.getItem('UserOptOut'))
    {
        jQuery('#ThisSiteUsesCookiesBox').show();
    }
});