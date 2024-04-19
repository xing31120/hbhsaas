/**
 * 富文本框初始化
 * 不使用setContent，初始化时容易报错
 * @param {type} editor 需要初始化的编辑器id
 * @param {type} content 初始化时的默认文本内容
 */
function initUeditor(editor){
    if($("#edui_fixedlayer").length > 0){
        $("#edui_fixedlayer").remove();
    }
    var ue = UE.getEditor(editor,{
        filterRules: function(){
            return {
                img: function (node) {
                    var src = scaling(node.getAttr('src'),338,450);
                    console.log(src);
                    node.setAttr();
                    node.setAttr({'src':src})
                },
                //黑名单，以下标签及其子节点都会被过滤掉
                '-': 'script style meta iframe embed object',
            };
        }
    });
    ue.ready(function(obj){
        try{
            ue.addListener('blur',function(){
                ue.sync();
            });
        }catch(e){
            console.log(obj,e);
        }
    })
    
    
//    ue.addListener('aftergetcontent',function(){
//        $.ajax({
//            type: 'POST',
//            async : true,
//            url: '/Common/checkSensitive',
//            data: {
//                'param': ue.getContentTxt(),
//            },
//            success:function(rs){
//                if(rs.status=='y'){
//                    $("#"+editor).parent().find(".Validform_checktip").removeClass("Validform_wrong").addClass("Validform_right").html("文本内容检测通过");
//                }else{
//                    $("#"+editor).parent().find(".Validform_checktip").removeClass("Validform_right").addClass("Validform_wrong").html(""+rs.info);
//                }
//            }
//        });
//    })

    return ue;
}

/*翻译富文本框的内容*/
function transferEditor(id){
    var ue = UE.getEditor(id);//获取UEditor对象
    ue.ready(function(editor){
        var translatedContent = '';
        try{
            translatedContent = transferNodes(ue.getContent());//根据节点内容翻译
        }catch(e){
            console.log('transferNodes:'+e);
            translatedContent = ue.getContent();//如果翻译报错则不翻译
        }
        try{
            ue.setContent(translatedContent);  //赋值给UEditor
        }catch(e){
            console.log('setContent:'+e);
        }
    });
}

/*翻译富文本框节点*/
function transferNodes(original){
    var root = UE.htmlparser(original, true);//将原来的文本框内容转换成uNode对象
    try{
        //循环每个节点内容进行文本内容翻译
        root.traversal(function(node){
            if (node.type == 'text') {
                try{
                    node.data = translateStr(node.data);
                }catch(e){
                    console.log('translateStr:'+e);
                }
            }
        });
    }catch(e){
        console.log('traversal:'+e);
    }
    return root.toHtml();//将uNode对象还原成html字符串
}
