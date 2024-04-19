/* 
 * 本js只添加完整的function，页面初始化需要添加到$(function({}))中的方法，添加到User/common.js
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/*设置上传的插件*/
function setUploadFile(upParams,buttonId,startFun,afterFun,hiddenVal,previewId,appendDom){
    var url = Date.parse(new Date());
    var useWater = upParams.useWater ? upParams.useWater : 1;//默认都使用水印
    $('#'+buttonId).uploadifive({
        'auto': true,
        'multi': upParams.multi,
        'buttonText': upParams.buttonText,
        'fileSizeLimit': upParams.fileSizeLimit,
        'fileType': upParams.fileType,
        'buttonClass': upParams.buttonClass,
        'queueID': upParams.queueID,
        'formData' : { 'url' : url},
        'uploadScript' : '/Common/uploadPhoto?useWater='+useWater,
        'onUpload': function(filename) {
            if(startFun != ''){
                startFun(filename);
            }
        },
        'onUploadComplete' : function(file, data) {
            try{
                var dataObj = $.parseJSON(data);
                if (dataObj.state == "SUCCESS") {
                    setTimeout(function(){
                        $("#"+upParams.queueID).find(".close").trigger("click");
                    },3000);
                    if(hiddenVal){
                        $("#"+hiddenVal).val(dataObj.url);
                        $("#"+hiddenVal).trigger("click").blur();
                    }
                    if(previewId && previewId != ''){
                        $("#"+previewId).find("img").attr("src",dataObj.url);
                        $("#"+previewId).parent().show();
                        $("#"+previewId).show();
                    }
                    if(appendDom != ''){
                        var size = appendDom.size.split('.'),imgHtml = '',imgSrc='';
                        if(size[0] === size[1] && size[0] === '0'){
                            imgSrc = scaling(dataObj.url,0,0,true);
                        }else{
                            imgSrc = scaling(dataObj.url,size[1],size[0]);
                        }
                        imgHtml = "<a target='_blank' href='" + imgSrc + "'><img src='" + imgSrc + "' /></a>";
                        $(appendDom.obj).html(imgHtml);
                    }
                    if(afterFun != ''){
                        afterFun(dataObj);
                    }
                }else{
                    if(afterFun != ''){
                        afterFun(false);
                    }
                }
            }catch(e){
                console.log(file,data);
            }
        },
        'onError':function(errorType){
            console.log(errorType);
            var fileinfo = $("#"+upParams.queueID+" .uploadifive-queue-item .fileinfo");
            var msg = '上传遇到了一些问题,请稍后再试';
            fileinfo.attr("class","fileinfo error");
            if(errorType=='FILE_SIZE_LIMIT_EXCEEDED'){
                msg = '文件大小不能超过'+upParams.fileSizeLimit;
            }else if(errorType=='FORBIDDEN_FILE_TYPE'){
                msg = '上传文件格式错误';
            }
            fileinfo.html(msg);
            setTimeout(function(){
                $("#"+upParams.queueID).find(".close").trigger("click");
            },5000);
            if(afterFun != ''){
                afterFun(false);
            }
        },
    });
}

/*由于部分url在js异步回调才生成，需要在js中处理图片缩放*/
function scaling(image, height, width, original){
    if(image.indexOf("data:;base64") === -1 || height > 0 || width > 0 || original){
        var resize_image = '';
        image = cutSuffix(image, "?");
        resize_image = original === true ? image : (image + '?x-image-process=image/resize,m_pad,h_' + height + ',w_' + width + ',limit_0');
        return resize_image;
    }else{
        return image;
    }
}

/*去掉多余的后缀部分(如:!h，?)*/
function cutSuffix(image, symbol){
    if(!symbol){
        return image;
    }
    var image_array = new Array();
    try{
        image_array = image.split(symbol);
        return image_array[0];
    }catch(e){
        return image;
    }
}

/*设置通用的表单验证*/
function setValidform(formid,reload){
    $("#"+formid).Validform({
        datatype:{
            "username" : /^[A-Za-z]{1}[A-Za-z0-9\-]{2,14}$/,
            "landline" : /^([0-9]{3,4}-)?[0-9]{7,8}$/,
            "price" : /^[0-9]+(.[0-9]{1,2})?$/,
            "need1":function(gets,obj,curform,regxp){
                    var need=1,
                    numselected=curform.find("input[name='"+obj.attr("name")+"']:checked").length;
                    return  numselected >= need ? true : "请至少选择"+need+"项！";
            },
            "need2":function(gets,obj,curform,regxp){
                    var need=2,
                    numselected=curform.find("input[name='"+obj.attr("name")+"']:checked").length;
                    return  numselected >= need ? true : "请至少选择"+need+"项！";
            },
            "idcard":function(gets,obj,curform,datatype){
                //该方法由佚名网友提供;
                var Wi = [ 7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2, 1 ];// 加权因子;
                var ValideCode = [ 1, 0, 10, 9, 8, 7, 6, 5, 4, 3, 2 ];// 身份证验证位值，10代表X;
                if (gets.length == 15) {   
                    return isValidityBrithBy15IdCard(gets);   
                }else if (gets.length == 18){   
                    var a_idCard = gets.split("");// 得到身份证数组   
                    if (isValidityBrithBy18IdCard(gets)&&isTrueValidateCodeBy18IdCard(a_idCard)) {   
                        return true;   
                    }   
                    return false;
                }
                return false;
                function isTrueValidateCodeBy18IdCard(a_idCard) {   
                    var sum = 0; // 声明加权求和变量   
                    if (a_idCard[17].toLowerCase() == 'x') {   
                        a_idCard[17] = 10;// 将最后位为x的验证码替换为10方便后续操作   
                    }   
                    for ( var i = 0; i < 17; i++) {   
                        sum += Wi[i] * a_idCard[i];// 加权求和   
                    }   
                    valCodePosition = sum % 11;// 得到验证码所位置   
                    if (a_idCard[17] == ValideCode[valCodePosition]) {   
                        return true;   
                    }
                    return false;   
                }
                function isValidityBrithBy18IdCard(idCard18){   
                        var year = idCard18.substring(6,10);   
                        var month = idCard18.substring(10,12);   
                        var day = idCard18.substring(12,14);   
                        var temp_date = new Date(year,parseFloat(month)-1,parseFloat(day));   
                        // 这里用getFullYear()获取年份，避免千年虫问题   
                        if(temp_date.getFullYear()!=parseFloat(year) || temp_date.getMonth()!=parseFloat(month)-1 || temp_date.getDate()!=parseFloat(day)){   
                            return false;   
                        }
                        return true;   
                }
                function isValidityBrithBy15IdCard(idCard15){   
                    var year =  idCard15.substring(6,8);   
                    var month = idCard15.substring(8,10);   
                    var day = idCard15.substring(10,12);
                    var temp_date = new Date(year,parseFloat(month)-1,parseFloat(day));   
                    // 对于老身份证中的你年龄则不需考虑千年虫问题而使用getYear()方法   
                    if(temp_date.getYear()!=parseFloat(year) || temp_date.getMonth()!=parseFloat(month)-1 || temp_date.getDate()!=parseFloat(day)){   
                        return false;   
                    }
                    return true;
                }
            }
        },
        tiptype: 4,
        ajaxPost: true,
        showAllError: true,
        postonce: true,
        beforeSubmit: function (curform) {
            $.Showmsg($.Tipmsg.requestText);
        },
        callback: function (data) {
            if (data.status == 'y' || data.status == 1) {
                if(data.from_url!=''){
                    $.Showmsg(data.info);
                }else{
                    $.Showmsg(data.info);
                }
                setTimeout('$.Hidemsg()',1000);
                setTimeout(function(){
                    if(reload === true){
                        window.location.reload();
                    }
                },1200);
            } else {
                $.Showmsg(data.info);
                setTimeout('$.Hidemsg()',3000);
            }
        }
    })
}

/*初始化地区*/
function initAreas(province_id,city_id,area_id){
    var province = $("#"+province_id);
    province.html("<option value=''>请选择省份</option>");
    if(city_id){
        var city = $("#"+city_id);
        city.html("<option value=''>请选择城市</option>");
    }
    if(area_id){
        var area = $("#"+area_id);
        area.html("<option value=''>请选择区/县</option>");
    }
    ajaxGetSingleArea('',province);
}

/*选择省份*/
function onSelectProvince(province_id,city_id,area_id){
    var city = $("#"+city_id);
    city.html("<option value=''>请选择城市</option>");
    if(area_id){
        var area = $("#"+area_id);
        area.html("<option value=''>请选择区/县</option>");
    }
    ajaxGetSingleArea(province_id,city);
}

/*选择城市*/
function onSelectCity(city_id,area_id){
    var area = $("#"+area_id);
    area.html("<option value=''>请选择区/县</option>");
    ajaxGetSingleArea(city_id,area);
}

/*异步获取区域的方法*/
function ajaxGetSingleArea(id,appendObj){
    var target = '';
    if(id){
        target = $("#"+id).val(); 
    }else{
        target = '001';
    }
    $.ajax({
        type: "POST",
        url: "/Common/getNextArea",
        dataType: "json",
        data: {id: target},
        success: function (data) {
            if (data.status == 1) {
                $.each(data.info, function (n, v) {
                    appendObj.append("<option value='" + v.id + "'>" + v.name + "</option>");
                });
            }
        }
    })
}

function initConfirm(title){
    var html = '<div id="myModalConfirm" class="modal fade bs-example-modal-sm" tabindex="-1" role="dialog" aria-labelledby="myModal" aria-hidden="true" >';
    html += '<div class="modal-dialog modal-sm">';
    html += '<div class="modal-content">';
    html += '<div class="modal-header">';
    html += '<h4 class="modal-title" id="myModalTitle">'+title+'</h4>';
    html += '</div>';
    html += '<div class="modal-body">正在保存中，请稍候...<br/></div>';
    html += '</div>';
    html += '</div>';
    html += '</div>';
    $("body").append(html);
}

/*异步请求确认*/
function ajaxConfirm(obj,reloadObj){
    if(!($("#myModalConfirm").length > 0)){
        initConfirm($(obj).attr("data-title"));
    }
    $("#myModalConfirm .modal-footer").remove();
    $("#myModalConfirm").modal('show'); 
    $("#myModalConfirm .modal-body").html("正在保存中，请稍候...<br/><br/>");
    var url = $(obj).attr("data-url");
    $.ajax({
        async: true,
        type: "POST",
        url: url,
        data: { },
        success:function(response){
            $("#myModalConfirm .modal-body").html("<br/>"+response.info+". Esc[关闭]<br/><br/>");
            var close_button_html = "<div class=\"modal-footer\">";
            close_button_html += "<button type=\"button\" id='success_button' class=\"btn btn-default\" data-dismiss=\"modal\">关闭</button>";
            close_button_html += "</div>";
            $("#myModalConfirm .modal-content").append(close_button_html); 
            $("#myModalConfirm").modal('show'); 
            if (response.status == 1) {
                setTimeout(function(){
                    $("#myModalConfirm #success_button").trigger("click");
                },800);
                setTimeout(function(){
                    $("#myModalConfirm").remove();
                    if(reloadObj){
                        $(reloadObj).trigger("click");
                    }else{
                        window.location.reload();
                    }
                },2000);
            } else {
                setTimeout(function(){
                    $("#myModalConfirm #success_button").trigger("click");
                },2000);
                setTimeout(function(){
                    $("#myModalConfirm").remove();
                },3000);
            }
            
        },
    });
}

function formPageload(obj){
    var url = $(obj).attr("data-url");
    var param = $(obj).closest("form").serialize();
    url += '?' + param;
    $(obj).attr("data-url",url);
    $.pageLink($(obj));
}

/*查询列表*/
function searchReload(obj, idName) {
    var param = $(obj).serializeArray();
    $.dataTbales($("#" + idName), param);
}

/*保存跳转*/
function saveCallBack(result) {
    if (result.status == 1) {
        $.hideValidMsg();
        if (result.checkid) {
            $("#" + result.checkid).click();
        } else {
            $("#indexMain").click();
        }
    }
}

function dateFocus(obj){
    var pickedFunc = function(dp){
        $(obj).focus().blur();
    };
    WdatePicker({
        el:obj,
        onpicked:pickedFunc,
    });
}

/*进入到编辑页面时调用，刷新页面时会提示:当前页面数据未保存,是否需要离开*/
function setEditTip(){
    window.onbeforeunload = function(event) {
        event.returnValue = "该页面内容未保存,是否选择离开?";
    }
}

/*正常保存的时候不需要提示，删除提示*/
function delEditTip(){
    window.document.body.removeAttribute("onbeforeunload");
}

/*页面上调用禁止修改地址栏和无工具栏的弹出窗口*/
function wop(url, name) {
    var location = window.location.protocol + '//' + window.location.hostname;
    url = encodeURI(url + '&location=' + location);
    window.open(url, name, "width=650,height=700,scrollbars=yes,toolbar=no,status=no,resizable=no,menubar=no,location=no,directories=no,top=20,left=20");
}

/*新开页面并跳转到指定页面*/
function openPage(obj){
    var target_page = $(obj).attr("data-url");
    if(target_page.length <= 0){
        alert("链接错误");
        return false;
    }
    var html = '<form method="post" target="_blank">'
    html += '<input type="hidden" name="target_page" value="'+target_page+'">';
    html += '</form>';
    $(obj).append(html);
    $(obj).find("form").submit().remove();
}

/*判断浏览器版本*/
function checkBrowser(){
    var ie = IEVersion();
    switch(ie){
        case 6:
        case 7:
        case 8:
        case 9:
            alert("您当前的浏览器版本为IE"+ie+",系统功能无法正常使用,请升级浏览器版本后再使用(本系统所需浏览器版本最低为IE10)");
            break;
        default:
            break;
    }
}

/*判断IE版本 -1:不是IE 6:IE版本小于6 7:IE7 8:IE8 9:IE9 10:IE10 11:IE11 'edge':edge浏览器*/
function IEVersion() {
    var userAgent = navigator.userAgent; //取得浏览器的userAgent字符串  
    var isIE = userAgent.indexOf("compatible") > -1 && userAgent.indexOf("MSIE") > -1; //判断是否IE<11浏览器  
    var isEdge = userAgent.indexOf("Edge") > -1 && !isIE; //判断是否IE的Edge浏览器  
    var isIE11 = userAgent.indexOf('Trident') > -1 && userAgent.indexOf("rv:11.0") > -1;
    if(isIE) {
        var reIE = new RegExp("MSIE (\\d+\\.\\d+);");
        reIE.test(userAgent);
        var fIEVersion = parseFloat(RegExp["$1"]);
        if(fIEVersion == 7) {
            return 7;
        } else if(fIEVersion == 8) {
            return 8;
        } else if(fIEVersion == 9) {
            return 9;
        } else if(fIEVersion == 10) {
            return 10;
        } else {
            return 6;//IE版本<=7
        }   
    } else if(isEdge) {
        return 'edge';//edge
    } else if(isIE11) {
        return 11; //IE11  
    }else{
        return -1;//不是ie浏览器
    }
}

/*转换图片 base64 => file*/
function dataURItoFile(dataURI, fileName) {
    var byteString = atob(dataURI.split(',')[1]),ab = new ArrayBuffer(byteString.length),ia = new Uint8Array(ab),fileType = '';
    for (var i = 0; i < byteString.length; i++) { 
        ia[i] = byteString.charCodeAt(i); 
    }
    switch(fileName.split('.')[1]){
        case 'png':
            fileType = 'image/png';
            break;
        case 'gif':
            fileType = 'image/gif';
            break;
        default:
            fileType = 'image/jpeg';
            break;
    }
    return new File([ia], fileName, {type: fileType, lastModified: Date.now()});
}

/*随机字符串*/
function randomString(len) {
    len = len || 32;
    var $chars = 'ABCDEFGHJKMNPQRSTWXYZabcdefhijkmnprstwxyz2345678';    /****默认去掉了容易混淆的字符oOLl,9gq,Vv,Uu,I1****/
    var maxPos = $chars.length;
    var pwd = '';
    for (i = 0; i < len; i++) {
        pwd += $chars.charAt(Math.floor(Math.random() * maxPos));
    }
    return pwd;
}

// 批量操作显示方法
// state:1 自动缩放
// state:2 收起
// state:3 放出
function batchOperation(state){
    if(state==1){
      $('.g-batch').fadeIn(250);
    }
    else if(state==2){
      $('.g-batch').fadeOut(250);
    }
    else {
      $('.g-batch').fadeToggle(250);
    }
}

/*单选按钮*/
function checkSingle(obj,btns,target){
    isCheckedAll(obj,target);
    setCheckUrl(obj,btns,target);
}

/*全选按钮*/
function checkAll(obj,btns,target){
    if(target){
        var e = $(target);
    }else{
        var e = $(obj).closest("table");
    }
    if($(obj).prop("checked")){
        e.find(".checkSingle:checkbox").prop("checked",true);
    }else{
        e.find(".checkSingle:checkbox").prop("checked",false);
    }
    setCheckUrl(obj,btns,target);

}

/*验证是否全选*/
function isCheckedAll(obj,target){
    if(target){
        var e = $(target);
    }else{
        var e = $(obj).closest("table");
    }
    var checkboxLen = e.find(".checkSingle:checkbox").length;
    var checkedLen = e.find(".checkSingle:checkbox:checked").length;
    if(checkboxLen == checkedLen){
        e.find(".checkAll:checkbox").prop("checked",true);
    }else{
        e.find(".checkAll:checkbox").prop("checked",false);
    }
}

/*设置选中的按钮上的操作链接*/
function setCheckUrl(obj,btns,target){
    if(target){
        var e = $(target);
    }else{
        var e = $(obj).closest("table");
    }
    var checkedObj = e.find(".checkSingle:checkbox:checked"),temp_id = 0,allId = [];
    if(!btns){
        btns = '#slideBtns';
    }
    if(checkedObj.length > 0){
        $.each(checkedObj,function(n,v){
            temp_id = $(v).val();
            if($.inArray(temp_id,allId) < 0){
                allId.push(temp_id);  
            }
        })
        batchOperation(1)
    }else{
        batchOperation(2)
    }
    $.each($(btns).find("a"),function(n,v){
        setBtnUrl($(v),allId);
    });
}

/*设置按钮组中每个按钮上的url*/
function setBtnUrl(btn,allId){
    var url = btn.attr("data-url");
    if(url){
        if(url.indexOf("?") > 0){
            var temp = url.split("?");
            url = temp[0];
        }
        if(allId.length > 0){
            btn.attr('data-url',url+'?allId='+allId.join(","));
            btn.parent().find("#allIds").val(allId.join(","));
        }else{
            btn.attr('data-url',url);
            btn.parent().find("#allIds").val("");
        }
    }
}