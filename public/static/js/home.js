$(function(){
    jQuery.extend({
        "goToPage": function(page, e) {
            var url = window.location.href;
            var index = url.indexOf("-p-");
            var start_url = '';
            if (index == -1) {
                start_url = url.replace(".html","");
            } else {
                start_url = url.substring(0, index);
            }
            start_url += "-p-" + page + ".html";
            window.location.href = start_url;
        }
    });
})


