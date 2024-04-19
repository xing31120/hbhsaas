/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 * @author liufengcheng@258.com
 */

function logout() {
    $.ajax({
        type: "POST",
        url: "/Public/loginOut",
        dataType: "json",
        data: {logout: 1},
        success: function (data) {
            if (data.s == 1) {
                window.location.replace('http://' + window.location.hostname);
            } else {
                setPageTip('网络异常,请稍后重试！', 3);
            }
        }
    });
}

/*loading页面*/
function showLoading() {
    var html = '<div class="tj-nav"><div class="tj-header"><div class="title">页面加载中...</div></div><div class="content"><div class="loading"></div></div></div>'
    $(".wrap").html(html);
}

/*图表显示*/
function showMyChart(obj, type, time_type, pid) {
    var id = String($(obj).attr("id"));
    if (id === '') {
        console.log('id不能为空');
        return false;
    }
    $(obj).removeAttr("onclick");
    var dom = document.getElementById(id);
    var myChart = echarts.init(dom);
    myChart.showLoading();
    $.ajax({
        type: "POST",
        url: "/Index/loadChart",
        async: true,
        dataType: "json",
        data: {type: type,id:id,time_type:time_type,pid:pid},
        success: function (data) {
            myChart.hideLoading();
            if (data.s == 1) {
                myChart.setOption(mySetOption(data.option,type));
            } else {
                myChart.setOption(mySetOption(data.optionEmpty));
            }
        }
    });

}

/*可以清除定时器的消息提示*/
var myTimeout = [];
function setPageTip(msg, seconds) {
    for (var i in myTimeout) {
        clearTimeout(myTimeout[i]);
    }
    $.Showmsg(msg);
    var a = setTimeout('$.Hidemsg()', (seconds > 0 ? seconds * 1000 : 3000));
    myTimeout.push(a);
}

/*
 * 选项卡切换
 * data-id 加载图表的id，index 时间类型 1月 2季度 3年度，data-function方法名，data-pid产品id
 * data-val图的时候1表示柱状图 2表示南丁格尔玫瑰图 3表示环状图，表格则表示有多少列数据
 * */
function setTab(obj, pid) {
    var index = $(obj).parent().find('li').index($(obj));//获取当前点击的元素是第几个
    $(obj).parent().find('li').removeClass("active");
    $(obj).addClass("active");
    pid = parseInt(pid);
    if (pid > 0) {
        $(".tabUser").find("ul").attr("data-pid",pid);
        $.each($(".tabUser ul"),function(n,v){
            $(v).children().first().trigger("click");
        });
    } else {
        var functionName = $(obj).parent().attr("data-function");
        if(functionName){
            window[functionName]($(obj).parent().attr("data-id"),index + 1,$(obj).parent().attr("data-val"),$(obj).parent().attr("data-pid"));
        }
    }
}

/*echart具体配置*/
function mySetOption(datas,type) {
//    console.log(datas);
    switch(type){
        case 1://柱状图
            if(datas.color.length < 3){
                return false;
            }
            var colors = setColors(datas);//根据需要设置渐变色的数量
            var option = {
                title : {text: datas.title,x:'left'},
                 tooltip: {
                    trigger: 'axis',
                    axisPointer: {
                        type: 'cross'
                    }
                },
                legend: {
                    x : 'center',
                    y : 'bottom',
                    data:[datas['y_value'][0],datas['y_value'][1],datas['y_value'][2]]
                },
                xAxis: {
                    name:datas['x_name'],
                    data: datas['x'],
                    axisLabel: {inside: false,textStyle: {color: '#000'}},
                    axisTick: {show: true,alignWithLabel: true},
                    axisLine: {show: true},
                    z: 10
                },
                yAxis: {
                    name:datas['y_name'],
                    axisLine: {show: true},
                    axisTick: {show: false},
                    axisLabel: {textStyle: {color: '#999'}}
                },
                series: colors
            };
            break;
        case 2://南丁格尔玫瑰图
            var option = {
                title : {text: datas.title,x:'left'},
                tooltip : {trigger: 'item',formatter: "{a} <br/>{b} : {c} ({d}%)"},
                color: datas.color,
                legend: {
                    x : 'center',
                    y : 'bottom',
                    data: datas.x
                },
                calculable : true,
                series : [{
                        name:datas.title,
                        type:datas['show_type'][0],
                        radius : [75, 110],
                        center : ['50%', '50%'],
                        roseType : 'radius',
                        data: datas.y
                    },
                ]
            };
            break;
        case 3://圆环图
            var option = {
                title : {text: datas.title,x:'left'},
                tooltip: {trigger: 'item',formatter: "{a} <br/>{b}: {c} ({d}%)"},
                color: datas.color,
                legend: {
                    orient: 'vertical',
                    x: 'right',
                    y: 'top',
                    data: datas.x
                },
                series: [
                    {
                        name: datas.title,
                        type:'pie',
                        radius: ['50%', '70%'],
                        avoidLabelOverlap: false,
                        label: {
                            normal: {
                                show: false,
                                position: 'center'
                            },
                            emphasis: {
                                show: true,
                                textStyle: {
                                    fontSize: '30',
                                    fontWeight: 'bold'
                                }
                            }
                        },
                        labelLine: {
                            normal: {
                                show: false
                            }
                        },
                        data: datas.y
                    }
                ]
            };
            break;
        default:
            break;
    }
    return option;
}

/*设置渐变色*/
function setColors(datas){
    var option = [];
    var temp = parseInt(datas.color.length/3);
    for(var i = 0;i < temp;i++){
        option[i] = {
            name: datas['y_value'][i],
            type: datas['show_type'][i],
            itemStyle: {
                normal: {
                    color: new echarts.graphic.LinearGradient(0, 0, 0, 1,[{offset: 0, color: datas.color[i*3]},{offset: 0.5, color: datas.color[i*3+1]},{offset: 1, color: datas.color[i*3+2]}])
                },
                emphasis: {
                    color: new echarts.graphic.LinearGradient(0, 0, 0, 1,[{offset: 0, color: datas.color[i*3+2]},{offset: 0.7, color: datas.color[i*3+1]},{offset: 1, color: datas.color[i*3]}])
                }
            },
            data: datas['y'][i]
        };
    }
    return option;
}

/*加载表格内容*/
function reloadTable(id,time_type,tdNum){
    if(!tdNum){
        tdNum = 1;
    }
    if(id=='table5'){
        reloadAll(id,time_type);
    }
    $("#"+id).find("tbody").html("<tr><td style='background: #fff;height: 60px;' colspan='"+tdNum+"'><div class='spinner'><div class='dot1'></div><div class='dot2'></div></div></td></tr>");
    $.ajax({
        type: "POST",
        url: "/Index/loadTable",
        async: true,
        dataType: "json",
        data: {type: id,time_type:time_type},
        success: function (data) {
            if(data.s==1){
                var html = "";
                $.each(data.data,function(n,v){
                    html += "<tr>";
                    for(var i = 0;i<tdNum;i++){
                        html += "<td>"+v.val[i]+"</td>";
                    }
                    html += "</tr>";
                });
                $("#"+id).find("tbody").html(html);
            }else{
                $("#"+id).find("tbody").html("<tr><td colspan='"+tdNum+"'>暂无数据</td></tr>");
            }
        }
    });
    
}

/*加载柱状图、折线图、饼图内容*/
function reloadChart(id,time_type,type,pid){
    pid = parseInt(pid);
    $("#"+id).parent().append("<div id='"+id+"' onclick='showMyChart(this,"+type+","+time_type+","+pid+")' style='height:350px;'></div>");
    $("#"+id).remove();
    $("#"+id).trigger("click");
    if(id == 'container8'){
        reloadTarget(id,time_type);
    }
}

/*加载任务额和达成率内容*/
function reloadTarget(id,time_type){
    $("#"+id+"_real_price").html(0);
    $("#"+id+"_rate").html(0);
    $.ajax({
        type: "POST",
        url: "/Index/getSumTarget",
        async: true,
        dataType: "json",
        data: {time_type:time_type},
        success: function (data) {
            if(data.s==1){
                $("#"+id+"_real_price").html(data.real_price);
                $("#"+id+"_rate").html(data.rate);
            }else{
                $("#"+id+"_real_price").html(0);
                $("#"+id+"_rate").html(0);
            }
        }
    });
}

/*加载全盘数*/
function reloadAll(id,time_type){
    $("#"+id+"_all").html(0);
    $.ajax({
        type: "POST",
        url: "/Index/getAll",
        async: true,
        dataType: "json",
        data: {time_type:time_type},
        success: function (data) {
            if(data.s==1){
                $("#"+id+"_all").html(data.all);
            }else{
                $("#"+id+"_all").html(0);
            }
        }
    });
}