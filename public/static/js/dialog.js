/***
 * 清除对话框内容
 * @param {节点对象} elem 例如 $("#id")的id
 * @returns {undefined}
 */
function clearDialogContent(elem) {
//ajax重新加载会有重复的jquery dialog，所以要删除
    $("div[aria-describedby='" + elem + "']").remove();
    //让dialog显示
    $(elem).removeClass("hidden");
    $(elem).html("正在加载中，请稍等...");
}
/***
 *添加对话框内容
 * @param {节点对象} elem 例如：$("#id")中的"#id"
 * @param {回调函数} callback
 * @returns {undefined}
 */

function addDialogContent(callback) {
    var elem = arguments[1];
    $(elem).html("正在加载中，请稍等...");
    if (callback) {
        callback(arguments);
    } else {
        $(elem).html("加载失败，请重试...");
    }
}
/***
 * 创建dialog对话框
 * @param {节点} elem 例如：$("#id")中的"#id"
 * @param {对话框名称} dialog_name
 * @param {回调函数} callback
 * @returns {undefined}
 */
function createDialog(elem, dialog_name, title, callback, width, height, buttons) {
    clearDialogContent(elem);
    if (!width) {
        width = 900;
    }
    if (!height) {
        height = 500;
    }
    if (buttons) {
        var btn1 = buttons[0];
        var btn2 = buttons[1];
    } else {
        var btn1 = "确定";
        var btn2 = "取消";
    }
    var arguments_1 = arguments;
    dialog_name = $(elem).dialog({
        autoOpen: false,
        width: width,
        height: height,
        modal: true,
        title: title,
        buttons: {
            btn1: function(e) {
                if (callback) {
                    if (callback(arguments_1) == false) {
                        return false;
                    }
                }
                dialog_name.dialog("close");
            },
            btn2: function() {
                dialog_name.dialog("close");
            }
        },
        close: function() {
            dialog_name.dialog("close");
        }
    });
    if (!btn2) {
        $(elem).dialog("option", "buttons", [{text: btn1, click: function() {
                    if (callback) {
                        if (callback(arguments_1) == false) {
                            return false;
                        }
                    }
                    dialog_name.dialog("close");
                }
            }]);
    } else {
        $(elem).dialog("option", "buttons",
                [{text: btn1, click: function() {
                            if (callback) {
                                if (callback(arguments_1) == false) {
                                    return false;
                                }
                            }
                            dialog_name.dialog("close");
                        }
                    }, {text: btn2, click: function() {
                            dialog_name.dialog("close");
                        }
                    }]);
    }
    return dialog_name;
}
/**
 * 显示对话框
 * @param {对话框名称} dialog_name
 * @returns {undefined}
 */
function showDialog(dialog_name) {
    dialog_name.dialog("open");
}

/**
 * 回调函数
 */
function getMenuDialogContent() {
    var elem = arguments[0][1];
    var jqxhr = $.post($("#get-menu-url").val(), {}, function(response) {
        if (response.status == 1) {
            $(elem).html(response.html);
        } else {
            $(elem).html("加载失败，请重试...");
        }
    })
}

function getNewMenuHtml() {
    $.post($("#get-new-menu-url").val(), {'theme_id': $('#theme_id').val()}, function(response) {
        if (response.status == 1) {
			if($('#rightHistory').find("[data-design-rel='menu']").length>0){
				$('#rightHistory').find("[data-design-rel='menu']").html(response.html);
			}else{
				$('#rightHistory').find(".NavBox").html(response.html);
			}
            
        }
    });
}

function selectMenuDialogContent() {
    var elem = arguments[0][1];
    var elem2 = arguments[0][2];
    var jqxhr = $.post($("#select-menu-url").val(), {}, function(response) {
        if (response.status == 1) {
            $(elem).html(response.html);
            $(elem).find("#selectListTitle span").html($(elem2).val());
            $(elem).find("#selectListTitle input").val($(elem2).parents("ul:first").attr("data-id"));
        }
    });
}

//选中的新连接
var newSelectUrl = '';
var newSelectName = '';
function getSelectedMenu() {
    var menu_id = $("#selectListTitle input").val();
    $("#itemList ul[data-id='" + menu_id + "']").find(".list2 input").val(newSelectName);
    $("#itemList ul[data-id='" + menu_id + "']").find(".list2").attr("data-url", newSelectUrl);
    $("#itemList ul[data-id='" + menu_id + "']").find(".list1 input").val(newSelectName);
}


function getBannerDialogContent() {
    var elem = arguments[0][1];
    var url = $(".demo .editor").attr("data-action");
    var jqxhr = $.post($("#get-banner-url").val(), {}, function(response) {
        if (response.status == 1) {
            $(elem).html(response.html);
        } else {
            $(elem).html("加载失败，请重试...");
        }
    })
}

function getNewBannerHtml() {
    $.post($("#get-new-banner-url").val(), {'theme_id': $('#theme_id').val()}, function(response) {
        if (response.status == 1) {
            $("[data-design-rel='banner']").html(response.html);
        }
    });
}



function deleteBanner() {
    var elem = arguments[0][7];
    var id = arguments[0][8];
    $.post($("#delete-banner-url").val(), {id: id}, function(response) {
        if (response.status == 1) {
            $(elem).parents('tr:first').fadeOut("slow", function() {
                $(this).remove();
            });
        } else {
            var editor_third_dialog = createDialog("#editor-third-dialog", "editor_third_dialog", "提示消息", null);
            $('#editor-third-dialog').html("删除失败");
            showDialog(editor_third_dialog);
        }
    });
}

function saveBanner() {
    var id = arguments[0][7];
    var banner_link = $("#banner_link").val();
    var banner_link_window = $("input[name='banner_link_window']:checked").val();
    $.post($("#save-banner-link-url").val(), {id: id, banner_link: banner_link, banner_link_window: banner_link_window}, function(response) {
        if (response.status == 1) {
            var editor_third_dialog = createDialog("#editor-third-dialog", "editor_third_dialog", "提示消息", null);
            $('#editor-third-dialog').html("保存成功");
            showDialog(editor_third_dialog);
        } else {
            var editor_third_dialog = createDialog("#editor-third-dialog", "editor_third_dialog", "提示消息", null);
            $('#editor-third-dialog').html("保存失败");
            showDialog(editor_third_dialog);
        }
    });
}

function getEditBannerContent() {
    var id = arguments[0][2];
    var elem = arguments[0][1];
    var url = $(".demo .editor").attr("data-action");
    var jqxhr = $.post($("#get-banner-link-url").val(), {id: id}, function(response) {
        if (response.status == 1) {
            $(elem).html(response.html);
        } else {
            $(elem).html("加载失败，请重试...");
        }
    })
}

function getAboutDialogContent() {
    var elem = arguments[0][1];
    var jqxhr = $.post($("#get-about-url").val(), {}, function(response) {
        if (response.status == 1) {
            $(elem).html(response.html);
        } else {
            $(elem).html("加载失败，请重试...");
        }
    })
}

function getTplDialogContent() {
    var elem = arguments[0][1];
    var tplId = arguments[0][2];
    var jqxhr = $.post($("#get-tpl-url").val(), {"tplId":tplId}, function(response) {
        if (response.status == 1) {
            $(elem).html(response.html);
        } else {
            $(elem).html("加载失败，请重试...");
        }
    })
}

function saveAboutTitle() {
    var about_title = $("#about_title").val();
    $.post($("#save-about-title-url").val(), {about_title: about_title}, function(response) {
        if (response.status == 1) {
            if (!about_title) {
                about_title = "关于我们";
            }
            $("[data-design-rel='about']").html(about_title);
            var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
            $('#editor-first-dialog').html("保存成功");
            showDialog(editor_first_dialog);
        } else {
            var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
            $('#editor-first-dialog').html("保存失败");
            showDialog(editor_first_dialog);
        }
    });
}

function saveTplTitle() {
    var bg_id = $('input:radio[name="bg_id"]:checked').val();
    var bg_name = $('input:radio[name="bg_id"]:checked').attr('data-bg');
    var gname = $('input[name="gname"]').val();
    $.post($("#save-tpl-title-url").val(), {bg_id: bg_id}, function(response) {
        if (response.status == 1) {
            $('.' + gname).attr('bg_id', bg_id);
            $('.' + gname).attr('style', 'background:url(' + bg_name + ');background-repeat:none;');
            var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
            $('#editor-first-dialog').html("保存成功");
            showDialog(editor_first_dialog);
        } else {
            var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
            $('#editor-first-dialog').html("保存失败");
            showDialog(editor_first_dialog);
        }
    });
}

function getArticleDialogContent() {
    var elem = arguments[0][1];
    var jqxhr = $.post($("#get-article-url").val(), {}, function(response) {
        if (response.status == 1) {
            $(elem).html(response.html);
        } else {
            $(elem).html("加载失败，请重试...");
        }
    })
}

function saveArticleTitle() {
    var article_title = $("#article_title").val();
    $.post($("#save-article-title-url").val(), {article_title: article_title}, function(response) {
        if (response.status == 1) {
            if (!article_title) {
                article_title = "新闻动态";
            }
            $("[data-design-rel='article']").html(article_title);
            var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
            $('#editor-first-dialog').html("保存成功");
            showDialog(editor_first_dialog);
        } else {
            var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
            $('#editor-first-dialog').html("保存失败");
            showDialog(editor_first_dialog);
        }
    });
}

function getArticleLabelDialogContent() {
    var elem = arguments[0][1];
    var jqxhr = $.post($("#get-article-label-url").val(), {}, function(response) {
        if (response.status == 1) {
            $(elem).html(response.html);
        } else {
            $(elem).html("加载失败，请重试...");
        }
    })
}

function saveArticleLabelTitle() {
    var article_label_title = $("#article_label_title").val();
    $.post($("#save-article-label-title-url").val(), {article_label_title: article_label_title}, function(response) {
        if (response.status == 1) {
            if (!article_label_title) {
                article_label_title = "新闻资讯";
            }
            $("[data-design-rel='articleLabel']").html(article_label_title);
            var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
            $('#editor-first-dialog').html("保存成功");
            showDialog(editor_first_dialog);
        } else {
            var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
            $('#editor-first-dialog').html("保存失败");
            showDialog(editor_first_dialog);
        }
    });
}

function getVideoLabelDialogContent() {
    var elem = arguments[0][1];
    var jqxhr = $.post($("#get-video-label-url").val(), {}, function(response) {
        if (response.status == 1) {
            $(elem).html(response.html);
        } else {
            $(elem).html("加载失败，请重试...");
        }
    })
}

function getVideoDialogContent() {
    var elem = arguments[0][1];
    var jqxhr = $.post($("#get-video-url").val(), {}, function(response) {
        if (response.status == 1) {
            $(elem).html(response.html);
        } else {
            $(elem).html("加载失败，请重试...");
        }
    })
}

function saveVideoLabelTitle() {
    var video_label_title = $("#video_label_title").val();
    $.post($("#save-video-label-title-url").val(), {video_label_title: video_label_title}, function(response) {
        if (response.status == 1) {
            if (!video_label_title) {
                video_label_title = "视频类别";
            }
            $("[data-design-rel='videoLabel']").html(video_label_title);
            var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
            $('#editor-first-dialog').html("保存成功");
            showDialog(editor_first_dialog);
        } else {
            var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
            $('#editor-first-dialog').html("保存失败");
            showDialog(editor_first_dialog);
        }
    });
}

function saveVideoTitle() {
    var video_title = $("#video_title").val();
    $.post($("#save-video-title-url").val(), {video_title: video_title}, function(response) {
        if (response.status == 1) {
            if (!video_title) {
                video_title = "视频类别";
            }
            $("[data-design-rel='video']").html(video_title);
            var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
            $('#editor-first-dialog').html("保存成功");
            showDialog(editor_first_dialog);
        } else {
            var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
            $('#editor-first-dialog').html("保存失败");
            showDialog(editor_first_dialog);
        }
    });
}
function getNewProductDialogContent(params) {// "cid":cid
    var cid = params[2];
    var elem = arguments[0][1];
    var themeId = arguments[0][3];
    var jqxhr = $.post($("#get-new-product-url").val(), {"cid": cid, "themeId": themeId}, function(response) {
        if (response.status == 1) {
            $(elem).html(response.html);
        } else {
            $(elem).html("加载失败，请重试...");
        }
    })
}

function saveNewProductTitle() {
    var new_product_title = $("#new_product_title").val();
    var cid = $("#cid").val();
    var rotation = $("#rotation").val();
    $.post($("#save-new-product-title-url").val(), {new_product_title: new_product_title, 'cid': cid, 'rotation': rotation}, function(response) {
        if (response.status == 1) {
            if (!new_product_title) {
                new_product_title = "最新产品";
            }
            $("[data-design-rel='new-product']").html(new_product_title);
            var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
            $('#editor-first-dialog').html("保存成功");
            showDialog(editor_first_dialog);
        } else {
            var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
            $('#editor-first-dialog').html("保存失败");
            showDialog(editor_first_dialog);
        }
    });
}

function getProductClassDialogContent() {
    var elem = arguments[0][1];
    var jqxhr = $.post($("#get-product-class-url").val(), {}, function(response) {
        if (response.status == 1) {
            $(elem).html(response.html);
        } else {
            $(elem).html("加载失败，请重试...");
        }
    })
}

function saveProductClassTitle() {
    var product_class_title = $("#product_class_title").val();
    $.post($("#save-product-class-title-url").val(), {product_class_title: product_class_title}, function(response) {
        if (response.status == 1) {
            if (!product_class_title) {
                product_class_title = "产品分类";
            }
            $("[data-design-rel='product-class']").html(product_class_title);
            var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
            $('#editor-first-dialog').html("保存成功");
            showDialog(editor_first_dialog);
        } else {
            var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
            $('#editor-first-dialog').html("保存失败");
            showDialog(editor_first_dialog);
        }
    });
}

function getContactDialogContent() {
    var elem = arguments[0][1];
    var jqxhr = $.post($("#get-contact-url").val(), {}, function(response) {
        if (response.status == 1) {
            $(elem).html(response.html);
        } else {
            $(elem).html("加载失败，请重试...");
        }
    })
}

function saveContactTitle() {
    var contact_title = $("#contact_title").val();
    $.post($("#save-contact-title-url").val(), {contact_title: contact_title}, function(response) {
        if (response.status == 1) {
            if (!contact_title) {
                contact_title = "联系我们";
            }
            $("[data-design-rel='contact']").html(contact_title);
            var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
            $('#editor-first-dialog').html("保存成功");
            showDialog(editor_first_dialog);
        } else {
            var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
            $('#editor-first-dialog').html("保存失败");
            showDialog(editor_first_dialog);
        }
    });
}


//内页组件编辑标题
function getDetailProductClassTitle() {
    var elem = arguments[0][1];
    var layout_id = $('#layout_id').val();
    var jqxhr = $.post($("#get-detail-component-url").val(), {'layout_id':layout_id,'component':'detailProductClass'}, function(response) {
        if (response.status == 1) {
            $(elem).html(response.html);
        } else {
            $(elem).html("加载失败，请重试...");
        }
    })
}
function setDetailProductClassTitle() {
    var layout_id = $('#layout_id').val();
    var titleVal = $('#detailProductClass').val();
    if($.trim(titleVal) == ''){
        var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
        $('#editor-first-dialog').html("请输入模块名称");
        showDialog(editor_first_dialog);
        return false;
    }
    $.post($("#set-detail-component-url").val(), {'layout_id':layout_id,'title': titleVal,'component':'detailProductClass'}, function(response) {
        if (response.status == 1) {
            if (!titleVal) {
                titleVal = "产品分类";
            }
            $("[data-design-rel='detailProductClass']").html(titleVal);
            var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
            $('#editor-first-dialog').html("保存成功");
            showDialog(editor_first_dialog);
        } else {
            var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
            $('#editor-first-dialog').html("保存失败");
            showDialog(editor_first_dialog);
        }
    });
}

function getDetailNewProductLeftTitle() {
    var elem = arguments[0][1];
    var layout_id = $('#layout_id').val();
    var jqxhr = $.post($("#get-detail-component-url").val(), {'layout_id':layout_id,'component':'detailNewProductLeft'}, function(response) {
        if (response.status == 1) {
            $(elem).html(response.html);
        } else {
            $(elem).html("加载失败，请重试...");
        }
    })
}
function setDetailNewProductLeftTitle() {
    var layout_id = $('#layout_id').val();
    var titleVal = $('#detailNewProductLeft').val();
    if($.trim(titleVal) == ''){
        var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
        $('#editor-first-dialog').html("请输入模块名称");
        showDialog(editor_first_dialog);
        return false;
    }
    $.post($("#set-detail-component-url").val(), {'layout_id':layout_id,'title': titleVal,'component':'detailNewProductLeft'}, function(response) {
        if (response.status == 1) {
            if (!titleVal) {
                titleVal = "最新产品";
            }
            $("[data-design-rel='detailNewProductLeft']").html(titleVal);
            var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
            $('#editor-first-dialog').html("保存成功");
            showDialog(editor_first_dialog);
        } else {
            var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
            $('#editor-first-dialog').html("保存失败");
            showDialog(editor_first_dialog);
        }
    });
}

function getDetailNewProductRightTitle() {
    var elem = arguments[0][1];
    var layout_id = $('#layout_id').val();
    var jqxhr = $.post($("#get-detail-component-url").val(), {'layout_id':layout_id,'component':'detailNewProductRight'}, function(response) {
        if (response.status == 1) {
            $(elem).html(response.html);
        } else {
            $(elem).html("加载失败，请重试...");
        }
    })
}
function setDetailNewProductRightTitle() {
    var layout_id = $('#layout_id').val();
    var titleVal = $('#detailNewProductRight').val();
    if($.trim(titleVal) == ''){
        var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
        $('#editor-first-dialog').html("请输入模块名称");
        showDialog(editor_first_dialog);
        return false;
    }
    $.post($("#set-detail-component-url").val(), {'layout_id':layout_id,'title': titleVal,'component':'detailNewProductRight'}, function(response) {
        if (response.status == 1) {
            if (!titleVal) {
                titleVal = "最新产品";
            }
            $("[data-design-rel='detailNewProductRight']").html(titleVal);
            var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
            $('#editor-first-dialog').html("保存成功");
            showDialog(editor_first_dialog);
        } else {
            var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
            $('#editor-first-dialog').html("保存失败");
            showDialog(editor_first_dialog);
        }
    });
}


function getDetailTopProductLeftTitle() {
    var elem = arguments[0][1];
    var layout_id = $('#layout_id').val();
    var jqxhr = $.post($("#get-detail-component-url").val(), {'layout_id':layout_id,'component':'detailTopProductLeft'}, function(response) {
        if (response.status == 1) {
            $(elem).html(response.html);
        } else {
            $(elem).html("加载失败，请重试...");
        }
    })
}
function setDetailTopProductLeftTitle() {
    var layout_id = $('#layout_id').val();
    var titleVal = $('#detailTopProductLeft').val();
    if($.trim(titleVal) == ''){
        var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
        $('#editor-first-dialog').html("请输入模块名称");
        showDialog(editor_first_dialog);
        return false;
    }
    $.post($("#set-detail-component-url").val(), {'layout_id':layout_id,'title': titleVal,'component':'detailTopProductLeft'}, function(response) {
        if (response.status == 1) {
            if (!titleVal) {
                titleVal = "推荐产品";
            }
            $("[data-design-rel='detailTopProductLeft']").html(titleVal);
            var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
            $('#editor-first-dialog').html("保存成功");
            showDialog(editor_first_dialog);
        } else {
            var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
            $('#editor-first-dialog').html("保存失败");
            showDialog(editor_first_dialog);
        }
    });
}

function getDetailTopProductRightTitle() {
    var elem = arguments[0][1];
    var layout_id = $('#layout_id').val();
    var jqxhr = $.post($("#get-detail-component-url").val(), {'layout_id':layout_id,'component':'detailTopProductRight'}, function(response) {
        if (response.status == 1) {
            $(elem).html(response.html);
        } else {
            $(elem).html("加载失败，请重试...");
        }
    })
}
function setDetailTopProductRightTitle() {
    var layout_id = $('#layout_id').val();
    var titleVal = $('#detailTopProductRight').val();
    if($.trim(titleVal) == ''){
        var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
        $('#editor-first-dialog').html("请输入模块名称");
        showDialog(editor_first_dialog);
        return false;
    }
    $.post($("#set-detail-component-url").val(), {'layout_id':layout_id,'title': titleVal,'component':'detailTopProductRight'}, function(response) {
        if (response.status == 1) {
            if (!titleVal) {
                titleVal = "推荐产品";
            }
            $("[data-design-rel='detailTopProductRight']").html(titleVal);
            var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
            $('#editor-first-dialog').html("保存成功");
            showDialog(editor_first_dialog);
        } else {
            var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
            $('#editor-first-dialog').html("保存失败");
            showDialog(editor_first_dialog);
        }
    });
}



function getDetailHotProductLeftTitle() {
    var elem = arguments[0][1];
    var layout_id = $('#layout_id').val();
    var jqxhr = $.post($("#get-detail-component-url").val(), {'layout_id':layout_id,'component':'detailHotProductLeft'}, function(response) {
        if (response.status == 1) {
            $(elem).html(response.html);
        } else {
            $(elem).html("加载失败，请重试...");
        }
    })
}
function setDetailHotProductLeftTitle() {
    var layout_id = $('#layout_id').val();
    var titleVal = $('#detailHotProductLeft').val();
    if($.trim(titleVal) == ''){
        var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
        $('#editor-first-dialog').html("请输入模块名称");
        showDialog(editor_first_dialog);
        return false;
    }
    $.post($("#set-detail-component-url").val(), {'layout_id':layout_id,'title': titleVal,'component':'detailHotProductLeft'}, function(response) {
        if (response.status == 1) {
            if (!titleVal) {
                titleVal = "热门产品";
            }
            $("[data-design-rel='detailHotProductLeft']").html(titleVal);
            var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
            $('#editor-first-dialog').html("保存成功");
            showDialog(editor_first_dialog);
        } else {
            var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
            $('#editor-first-dialog').html("保存失败");
            showDialog(editor_first_dialog);
        }
    });
}

function getDetailHotProductRightTitle() {
    var elem = arguments[0][1];
    var layout_id = $('#layout_id').val();
    var jqxhr = $.post($("#get-detail-component-url").val(), {'layout_id':layout_id,'component':'detailHotProductRight'}, function(response) {
        if (response.status == 1) {
            $(elem).html(response.html);
        } else {
            $(elem).html("加载失败，请重试...");
        }
    })
}
function setDetailHotProductRightTitle() {
    var layout_id = $('#layout_id').val();
    var titleVal = $('#detailHotProductRight').val();
    if($.trim(titleVal) == ''){
        var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
        $('#editor-first-dialog').html("请输入模块名称");
        showDialog(editor_first_dialog);
        return false;
    }
    $.post($("#set-detail-component-url").val(), {'layout_id':layout_id,'title': titleVal,'component':'detailHotProductRight'}, function(response) {
        if (response.status == 1) {
            if (!titleVal) {
                titleVal = "热门产品";
            }
            $("[data-design-rel='detailHotProductRight']").html(titleVal);
            var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
            $('#editor-first-dialog').html("保存成功");
            showDialog(editor_first_dialog);
        } else {
            var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
            $('#editor-first-dialog').html("保存失败");
            showDialog(editor_first_dialog);
        }
    });
}


//资讯

function getDetailArticleClassTitle() {
    var elem = arguments[0][1];
    var layout_id = $('#layout_id').val();
    var jqxhr = $.post($("#get-detail-component-url").val(), {'layout_id':layout_id,'component':'detailArticleClass'}, function(response) {
        if (response.status == 1) {
            $(elem).html(response.html);
        } else {
            $(elem).html("加载失败，请重试...");
        }
    })
}
function setDetailArticleClassTitle() {
    var layout_id = $('#layout_id').val();
    var titleVal = $('#detailArticleClass').val();
    if($.trim(titleVal) == ''){
        var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
        $('#editor-first-dialog').html("请输入模块名称");
        showDialog(editor_first_dialog);
        return false;
    }
    $.post($("#set-detail-component-url").val(), {'layout_id':layout_id,'title': titleVal,'component':'detailArticleClass'}, function(response) {
        if (response.status == 1) {
            if (!titleVal) {
                titleVal = "资讯分类";
            }
            $("[data-design-rel='detailArticleClass']").html(titleVal);
            var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
            $('#editor-first-dialog').html("保存成功");
            showDialog(editor_first_dialog);
        } else {
            var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
            $('#editor-first-dialog').html("保存失败");
            showDialog(editor_first_dialog);
        }
    });
}


function getDetailNewArticleLeftTitle() {
    var elem = arguments[0][1];
    var layout_id = $('#layout_id').val();
    var jqxhr = $.post($("#get-detail-component-url").val(), {'layout_id':layout_id,'component':'detailNewArticleLeft'}, function(response) {
        if (response.status == 1) {
            $(elem).html(response.html);
        } else {
            $(elem).html("加载失败，请重试...");
        }
    })
}
function setDetailNewArticleLeftTitle() {
    var layout_id = $('#layout_id').val();
    var titleVal = $('#detailNewArticleLeft').val();
    if($.trim(titleVal) == ''){
        var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
        $('#editor-first-dialog').html("请输入模块名称");
        showDialog(editor_first_dialog);
        return false;
    }
    $.post($("#set-detail-component-url").val(), {'layout_id':layout_id,'title': titleVal,'component':'detailNewArticleLeft'}, function(response) {
        if (response.status == 1) {
            if (!titleVal) {
                titleVal = "最新资讯";
            }
            $("[data-design-rel='detailNewArticleLeft']").html(titleVal);
            var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
            $('#editor-first-dialog').html("保存成功");
            showDialog(editor_first_dialog);
        } else {
            var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
            $('#editor-first-dialog').html("保存失败");
            showDialog(editor_first_dialog);
        }
    });
}

function getDetailNewArticleRightTitle() {
    var elem = arguments[0][1];
    var layout_id = $('#layout_id').val();
    var jqxhr = $.post($("#get-detail-component-url").val(), {'layout_id':layout_id,'component':'detailNewArticleRight'}, function(response) {
        if (response.status == 1) {
            $(elem).html(response.html);
        } else {
            $(elem).html("加载失败，请重试...");
        }
    })
}

function setDetailNewArticleRightTitle() {
    var layout_id = $('#layout_id').val();
    var titleVal = $('#detailNewArticleRight').val();
    if($.trim(titleVal) == ''){
        var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
        $('#editor-first-dialog').html("请输入模块名称");
        showDialog(editor_first_dialog);
        return false;
    }
    $.post($("#set-detail-component-url").val(), {'layout_id':layout_id,'title': titleVal,'component':'detailNewArticleRight'}, function(response) {
        if (response.status == 1) {
            if (!titleVal) {
                titleVal = "最新资讯";
            }
            $("[data-design-rel='detailNewArticleRight']").html(titleVal);
            var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
            $('#editor-first-dialog').html("保存成功");
            showDialog(editor_first_dialog);
        } else {
            var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
            $('#editor-first-dialog').html("保存失败");
            showDialog(editor_first_dialog);
        }
    });
}



function getDetailTopArticleLeftTitle() {
    var elem = arguments[0][1];
    var layout_id = $('#layout_id').val();
    var jqxhr = $.post($("#get-detail-component-url").val(), {'layout_id':layout_id,'component':'detailTopArticleLeft'}, function(response) {
        if (response.status == 1) {
            $(elem).html(response.html);
        } else {
            $(elem).html("加载失败，请重试...");
        }
    })
}
function setDetailTopArticleLeftTitle() {
    var layout_id = $('#layout_id').val();
    var titleVal = $('#detailTopArticleLeft').val();
    if($.trim(titleVal) == ''){
        var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
        $('#editor-first-dialog').html("请输入模块名称");
        showDialog(editor_first_dialog);
        return false;
    }
    $.post($("#set-detail-component-url").val(), {'layout_id':layout_id,'title': titleVal,'component':'detailTopArticleLeft'}, function(response) {
        if (response.status == 1) {
            if (!titleVal) {
                titleVal = "推荐资讯";
            }
            $("[data-design-rel='detailTopArticleLeft']").html(titleVal);
            var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
            $('#editor-first-dialog').html("保存成功");
            showDialog(editor_first_dialog);
        } else {
            var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
            $('#editor-first-dialog').html("保存失败");
            showDialog(editor_first_dialog);
        }
    });
}

function getDetailTopArticleRightTitle() {
    var elem = arguments[0][1];
    var layout_id = $('#layout_id').val();
    var jqxhr = $.post($("#get-detail-component-url").val(), {'layout_id':layout_id,'component':'detailTopArticleRight'}, function(response) {
        if (response.status == 1) {
            $(elem).html(response.html);
        } else {
            $(elem).html("加载失败，请重试...");
        }
    })
}
function setDetailTopArticleRightTitle() {
    var layout_id = $('#layout_id').val();
    var titleVal = $('#detailTopArticleRight').val();
    if($.trim(titleVal) == ''){
        var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
        $('#editor-first-dialog').html("请输入模块名称");
        showDialog(editor_first_dialog);
        return false;
    }
    $.post($("#set-detail-component-url").val(), {'layout_id':layout_id,'title': titleVal,'component':'detailTopArticleRight'}, function(response) {
        if (response.status == 1) {
            if (!titleVal) {
                titleVal = "推荐资讯";
            }
            $("[data-design-rel='detailTopArticleRight']").html(titleVal);
            var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
            $('#editor-first-dialog').html("保存成功");
            showDialog(editor_first_dialog);
        } else {
            var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
            $('#editor-first-dialog').html("保存失败");
            showDialog(editor_first_dialog);
        }
    });
}


function getDetailHotArticleLeftTitle() {
    var elem = arguments[0][1];
    var layout_id = $('#layout_id').val();
    var jqxhr = $.post($("#get-detail-component-url").val(), {'layout_id':layout_id,'component':'detailHotArticleLeft'}, function(response) {
        if (response.status == 1) {
            $(elem).html(response.html);
        } else {
            $(elem).html("加载失败，请重试...");
        }
    })
}
function setDetailHotArticleLeftTitle() {
    var layout_id = $('#layout_id').val();
    var titleVal = $('#detailHotArticleLeft').val();
    if($.trim(titleVal) == ''){
        var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
        $('#editor-first-dialog').html("请输入模块名称");
        showDialog(editor_first_dialog);
        return false;
    }
    $.post($("#set-detail-component-url").val(), {'layout_id':layout_id,'title': titleVal,'component':'detailHotArticleLeft'}, function(response) {
        if (response.status == 1) {
            if (!titleVal) {
                titleVal = "热门资讯";
            }
            $("[data-design-rel='detailHotArticleLeft']").html(titleVal);
            var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
            $('#editor-first-dialog').html("保存成功");
            showDialog(editor_first_dialog);
        } else {
            var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
            $('#editor-first-dialog').html("保存失败");
            showDialog(editor_first_dialog);
        }
    });
}

function getDetailHotArticleRightTitle() {
    var elem = arguments[0][1];
    var layout_id = $('#layout_id').val();
    var jqxhr = $.post($("#get-detail-component-url").val(), {'layout_id':layout_id,'component':'detailHotArticleRight'}, function(response) {
        if (response.status == 1) {
            $(elem).html(response.html);
        } else {
            $(elem).html("加载失败，请重试...");
        }
    })
}
function setDetailHotArticleRightTitle() {
    var layout_id = $('#layout_id').val();
    var titleVal = $('#detailHotArticleRight').val();
    if($.trim(titleVal) == ''){
        var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
        $('#editor-first-dialog').html("请输入模块名称");
        showDialog(editor_first_dialog);
        return false;
    }
    $.post($("#set-detail-component-url").val(), {'layout_id':layout_id,'title': titleVal,'component':'detailHotArticleRight'}, function(response) {
        if (response.status == 1) {
            if (!titleVal) {
                titleVal = "热门资讯";
            }
            $("[data-design-rel='detailHotArticleRight']").html(titleVal);
            var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
            $('#editor-first-dialog').html("保存成功");
            showDialog(editor_first_dialog);
        } else {
            var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
            $('#editor-first-dialog').html("保存失败");
            showDialog(editor_first_dialog);
        }
    });
}




function getDetailContactTitle() {
    var elem = arguments[0][1];
    var layout_id = $('#layout_id').val();
    var jqxhr = $.post($("#get-detail-component-url").val(), {'layout_id':layout_id,'component':'detailContact'}, function(response) {
        if (response.status == 1) {
            $(elem).html(response.html);
        } else {
            $(elem).html("加载失败，请重试...");
        }
    })
}
function setDetailContactTitle() {
    var layout_id = $('#layout_id').val();
    var titleVal = $('#detailContact').val();
    if($.trim(titleVal) == ''){
        var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
        $('#editor-first-dialog').html("请输入模块名称");
        showDialog(editor_first_dialog);
        return false;
    }
    $.post($("#set-detail-component-url").val(), {'layout_id':layout_id,'title': titleVal,'component':'detailContact'}, function(response) {
        if (response.status == 1) {
            if (!titleVal) {
                titleVal = "联系我们";
            }
            $("[data-design-rel='detailContact']").html(titleVal);
            var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
            $('#editor-first-dialog').html("保存成功");
            showDialog(editor_first_dialog);
        } else {
            var editor_first_dialog = createDialog("#editor-first-dialog", "editor_first_dialog", "提示消息", null, 300, 200, ['确定']);
            $('#editor-first-dialog').html("保存失败");
            showDialog(editor_first_dialog);
        }
    });
}