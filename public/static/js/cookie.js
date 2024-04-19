/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 * @author liufengcheng@258.com
 */
/*设置cookie*/
function setCookie(name, value){
    var Days = 30;
    var exp = new Date();
    exp.setTime(exp.getTime() + Days * 24 * 60 * 60 * 1000);
    document.cookie = name + "=" + escape(value) + ";expires=" + exp.toGMTString() + ";path=/;domain=.sumaotong.com";
}
/*获取cookie*/
function getCookie(name){
    var arr, reg = new RegExp("(^| )" + name + "=([^;]*)(;|$)");
    if (arr = document.cookie.match(reg)){
        return unescape(arr[2]);
    }else{
        return null;
    }
}
/*删除cookie*/
function delCookie(name){
    var exp = new Date();
    exp.setTime(exp.getTime() + (-1 * 24 * 60 * 60 * 1000));
    var cval = getCookie(name);
    document.cookie = name + "=" + cval + ";expires=" + exp.toGMTString() + ";path=/;domain=.sumaotong.com";
}

