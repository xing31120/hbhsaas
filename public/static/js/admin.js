/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
jQuery.fn.extend({
    "loading": function() {
        $(this).html('<div class=\"slide-loading\">正在加载，请稍候...</div>');
        return this;
    },
    "error": function() {
        var msg_obj = $(this).html("<div class=\"slide-error\">网络请求失败，请稍后重试！</div>")
    },
    "validform": function(form_id, callback) {
        if (form_id) {
            var form = $("#" + form_id);
        }
        else {
            var form = $(this).find("form:first");
        }
        $.validform(form, callback);
        return this;
    },
    "category": function(category_json) {
        /*生成编辑易行业选择*/
        var category_dom = $(this);
        category_dom.html('<span class="Validform_checktip Validform_loading">正在加载...</span>');
        if (category_json) {
            var value = parseInt(category_json[0])
        } else {
            var value = 0;
        }
        $.ajax({
            data: "pid=001",
            type: "GET",
            dataType: "json",
            url: "/Public/getPDCCategory",
            success: function(data) {
                category_dom.html('');
                var select_dom = $('<select class="form-control form-category"  name="category[]"><option value="">请选择</option></select>');
                $(data).each(function(k, v) {
                    if (v.id == value) {
                        select_dom.append('<option value="' + v.categoryID + '" selected>' + v.name + '</option>');
                    } else {
                        select_dom.append('<option value="' + v.categoryID + '">' + v.name + '</option>');
                    }
                })
                select_dom.bind("change", $.category);
                category_dom.html('');
                category_dom.append(select_dom).append('<span class="Validform_checktip"></span>');
                if (category_json) {
                    for (i = 0; i < category_json.length - 1; i++) {
                        $.category(category_json[i], eval(category_json[i + 1]), category_dom);
                    }
                }
            },
            error: function(response, status) {
                alert('行业输入出错，请稍后重试');
            },
        })

    }
})
jQuery.extend({
    "category": function(pid, value, category_dom) {
        if (category_dom) {
            var parent_dom = $(category_dom).find("select:last");
        } else {
            var parent_dom = $(this);
        }
        if ($.type(pid) == "object") {
            var pid = parent_dom.val();
        }
        $(parent_dom).nextAll("select").remove();
        if (!pid)
            return false;
        $(parent_dom).nextAll(".Validform_checktip").removeClass("Validform_right Validform_wrong").addClass("Validform_loading").html("正在检索下级分类，请稍候...");
        $.ajax({
            type: "GET",
            url: "/Public/getPDCCategory",
            data: "pid=" + pid,
            dataType: "json",
            error: function(response, status) {
                alert('行业输入出错，请稍后重试');
            },
            success: function(data) {
                if (data == null || data == '') {
                    $(parent_dom).nextAll(".Validform_checktip").removeClass("Validform_loading").addClass("Validform_right").html('无下级分类');
                    return false;
                }
                var select_dom = $('<select class="form-control form-category" datatype="n" name="category[]"><option value="">请选择</option></select>');
                $(data).each(function(k, v) {
                    if (v.categoryID == value) {
                        select_dom.append('<option value="' + v.categoryID + '" selected>' + v.name + '</option>');
                    } else {
                        select_dom.append('<option value="' + v.categoryID + '">' + v.name + '</option>');
                    }
                })
                select_dom.bind("change", $.category);
                $(parent_dom).nextAll(".Validform_checktip").removeClass("Validform_right Validform_wrong Validform_loading").html('');
                parent_dom.after(select_dom);
                parent_dom.next().focus();
            }
        })
    },
    "ValidformCallback": function(data) {
        // 定义默认表单提交成功回调
        if (data.status == 1) {
            $("#Validform_msg").delay(500).fadeOut();
        }
        else if (data.status == 0) {
            $("#Validform_msg").delay(3000).fadeOut();
        }
    },
    "hideValidMsg": function() {
        $("#Validform_msg").delay(500).fadeOut();
    },
    "confirm": function(msg, e, callback) {
        var confirm = $("#confirm_modal");
        if (confirm.length <= 0) {
            var confirm = $('<div class="modal fade132" id="confirm_modal" tabindex="-1" role="dialog" aria-hidden="true"><div class="modal-dialog"><div class="modal-content"></div></div></div>');
        }
        var url = $(e).attr("data-url");
        if (!url) {
            alert('无效的操作地址');
            return false;
        }
        var success_reload = $(e).attr("data-reload");
        var callbacks = $.Callbacks();
        if (callback) {
            if ($.isFunction(callback) || $.isFunction(eval(callback))) {
                callbacks.add(eval(callback));
            }
            else {
                alert('回调函数不存在');
                return false;
            }
        } else {
            callbacks.add($.confirmCallback);
        }
        var confirm_content = $(confirm).find(".modal-content");
        confirm_content.html('');
        confirm_content.append('<div class="modal-header"><button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button><h4 class="modal-title">操作确认</h4></div>');
        confirm_content.append('<div class="modal-body">' + msg + '</div>');
        confirm_content.append('<div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">关闭</button><button type="button" class="btn btn-primary" id="confirm_button" data-dismiss="modal">确认</button></div>');
        confirm_content.find("#confirm_button").bind("click", function() {
            callbacks.fire(url, e, success_reload);
        })
        $("body").append(confirm);
        $(confirm).modal("show");
    },
    "confirmonly": function(msg, e, el) {
        var confirm = $("#confirm_modal");
        if (confirm.length <= 0) {
            var confirm = $('<div class="modal fade" id="confirm_modal" tabindex="-1" role="dialog" aria-hidden="true"><div class="modal-dialog"><div class="modal-content"></div></div></div>');
        }
        var url = $(e).attr("data-url");
        if (!url) {
            alert('无效的操作地址');
            return false;
        }

        var confirm_content = $(confirm).find(".modal-content");
        confirm_content.html('');
        confirm_content.append('<div class="modal-header"><button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button><h4 class="modal-title">操作确认</h4></div>');
        confirm_content.append('<div class="modal-body">' + msg + '</div>');
        confirm_content.append('<div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">关闭</button><button type="button" class="btn btn-primary" id="confirm_button" data-dismiss="modal">确认</button></div>');
        confirm_content.find("#confirm_button").bind("click", function() {
            $.get(url);
            $(el).click();
        })
        $("body").append(confirm);
        $(confirm).modal("show");
    },
    "confirmCallback": function(url, e, success_reload) {
        $("#confirm_modal .modal-body").loading();
        $.ajax({
            type: "GET",
            dataType: "json",
            url: url,
            success: function(msg) {
                $("#confirm_modal .modal-body").html(msg.info);
                if (msg.status == 1) {
                    $("#confirm_modal").modal("hide");
                } else {
                    $("#confirm_button").remove();
                    $("#confirm_modal").modal("show");
                }
                if (success_reload) {
                    $.dataTbales($(success_reload))
                }
            },
            error: function(request, status, errorThrown) {
                $("#confirm_modal .modal-body").error();
            }
        });
    },
    "dataTbales": function(e, param) {
        // 为datatables表格触发数据加载
        var url = $(e).attr("data-url");
        if (!url) {
            alert("无效链接11");
            return false;
        }
        $(e).html('<div class=\"datatable-loading\">正在加载，请稍候...</div>');
        $(e).load(url, param, function(respone, status) {
            if (status == 'error' || status == 'timeout') {
                $(this).error();
                return false;
            }
        })
    },
    "validform": function(f, callback) {
        // 初始化表单验证插件
        var option = {};
        option.url = $(f).attr("data-action");
        if (!option.url) {
            return false;
        }
        option.ajaxPost = true;
        option.showAllError = true;
        option.tiptype = 3;
        if (callback) {
            option.callback = callback;
        }
        else {
            option.callback = function(result) {
                $.ValidformCallback(result);
            }
        }
        return $(f).Validform(option);
    },
    "ueditor": function(e) {
        var um_id = e.id;
        UE.delEditor(um_id);
        UE.getEditor(um_id);
    },
    "slideLink": function(e) {
        // 主体内容链接
        var id = $(e).attr("data-id");
        var url = $(e).attr("data-url");
        if (!url) {
            alert("无效链接");
            return false;
        }
        var effect = ["blind", "clip", "drop", "fold", "scale", "slide"];
        var raund_effect = effect[Math.floor(Math.random() * effect.length)]
        if ($("#" + id).length > 0) {
            var obj = $("#" + id);
        }
        else {
            var obj = $("#slide_content");
        }
        $(".slide").hide();
        $(obj).addClass("slide-mask").loading();
        $(obj).show(raund_effect, {}, 500, function() {
            $(this).load(url, function(respone, status) {
                if (status == 'error' || status == 'timeout') {
                    $(this).error();
                    return false;
                }
                $(this).removeClass("slide-mask");
                $(this).find("div[data-type='ajax']").each(function() {
                    $.dataTbales(this);
                });
                $(this).find("textarea[data-type='umeditor']").each(function() {
                    $.ueditor(this);
                });
                $(this).find("form").each(function() {
                    var form_callback = $(this).attr("callback");
                    if (typeof (form_callback) != "undefined" && $.isFunction(eval(form_callback))) {
                        $.validform(this, eval(form_callback))
                    }
                    else {
                        $.validform(this)
                    }
                });
            });
        });
        if ($(".ui-dialog[aria-describedby='editor-dialog']").size()) {
            $("#editor-dialog").dialog("close");
        }
    },
    "pageLink": function(e) {
        var url = $(e).attr("data-url");
        if (!url) {
            alert("无效链接");
            return false;
        }
        var obj = $(e).parents(".slide:first").find("#page_content");
        if (obj.lenth <= 0) {
            alert("无法获取page_content对象");
            return false;
        }
        else {
            $(obj).html('<div class=\"slide-loading\">正在加载，请稍候...</div>');
            $(obj).load(url, function(respone, status) {
                if (status == 'error' || status == 'timeout') {
                    $(this).error();
                    return false;
                }
                $(this).find("div[data-type='ajax']").each(function() {
                    $.dataTbales(this);
                });
                $(this).find("textarea[data-type='umeditor']").each(function() {
                    $.ueditor(this);
                });
                $(this).find("form").each(function() {
                    var form_callback = $(this).attr("callback");
                    if ($.isFunction(eval(form_callback))) {
                        $.validform(this, eval(form_callback))
                    } else {
                        $.validform(this)
                    }
                })
            });
        }
    },
    "goToPage": function(page, e) {
        var $_dom = $(e).parents("#page_content");
        var param = $_dom.find("form").serializeArray();
        param.push({"name": "p", "value": page});
        $.dataTbales($_dom.find(".datatables"), param);
    },
    "slideLink": function(e) {
        // 主体内容链接
        var id = $(e).attr("data-id");
        var url = $(e).attr("data-url");
        if (!url) {
            alert("无效链接");
            return false;
        }
        var effect = ["blind", "clip", "drop", "fold", "scale", "slide"];
        var raund_effect = effect[Math.floor(Math.random() * effect.length)]
        if ($("#" + id).length > 0) {
            var obj = $("#" + id);
        }else {
            var obj = $("#slide_content");
        }
        $(".slide").hide();
        $(obj).addClass("slide-mask").loading();
        $(obj).show(raund_effect, {}, 500, function() {
            $(this).load(url, function(respone, status) {
                if (status == 'error' || status == 'timeout') {
                    $(this).error();
                    return false;
                }
                $(this).removeClass("slide-mask");
                $(this).find("div[data-type='ajax']").each(function() {
                    $.dataTbales(this);
                });
                $(this).find("textarea[data-type='umeditor']").each(function() {
                    $.ueditor(this);
                });
                $(this).find("form").each(function() {
                    var form_callback = $(this).attr("callback");
                    if (typeof (form_callback) != "undefined" && $.isFunction(eval(form_callback))) {
                        $.validform(this, eval(form_callback))
                    }
                    else {
                        $.validform(this)
                    }
                });
            });
        });
        if ($(".ui-dialog[aria-describedby='editor-dialog']").size()) {
            $("#editor-dialog").dialog("close");
        }
    },
})

$(function(){
    u_s_h = $(window).height();
    $("#site-content").bind("load", function() {
        u_site = $("iframe").contents();
        $("#site,#slide-mask").height(u_s_h + 'px');
    })
    // 监听浏览器大小
    $(window).resize(function() {
        u_s_h = $(window).height();
        $("#site,.slide").height(u_s_h + 'px');
    });
    $("#site,.slide").height(u_s_h + 'px');
    $("body").delegate(".slide-link", "click", function() {
        //移除掉设计中心产生的多余div
        if ($("#design_container").length > 0) {
            $("div[id='design_container']").remove();
        }
        // 判断是否二级菜单
        $(".nav li").removeClass("active");
        var second_menu = $(".nav .dropdown.open");
        if (second_menu.length > 0) {
            second_menu.addClass("active");
        }
        $(this).parents("li:first").addClass("active");
        $.slideLink($(this));
    });
    $("body").delegate(".page-link", "click", function() {
        //判断是否有产品分类才让添加产品  资讯一样
        if ($(this).attr('data')) {
            $("#myModal").modal('show');
            return false;
        }
        $.pageLink($(this));
        $(this).siblings().removeClass("active");
        $(this).addClass("active");
    });
    $('#h-maggess').click(function(){
        $('#h-msg').slideToggle();
    })
    $('body').mousedown(function(e) {
        if($(e.target).closest("#h-maggess").length == 0){
            $('#h-msg').slideUp();
        }
    });
})