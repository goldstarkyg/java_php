/**
 * Created by hh on 9/13/2017.
 */

function Test(val) {

    var $ = jQuery;
    var postData 		= {"name":"condition","value": 'sql'},
        formURL 		= "/testsql";
    if(val == 'sql') formURL = "/testsql";
    $.ajax(
        {
            url : formURL,
            type: "GET",
            data : postData,
            dataType: "text",
            success:function(data)
            {
                // var html_val = list(data);
                $('#test_list').text(data);
            },
            error: function(jqXHR, textStatus, errorThrown){
                alert(errorThrown);

            },
        });
}

var $ = jQuery;
function list(json) {
    var  html = '<div>';
    $.each(json, function(k, v) {
        if($.type(v) == 'array') {
            $.each(v, function (k1, v1) {
                if($.type(v1) == 'array') {
                    $.each(v1, function (k2, v2) {
                        html += "<div style=\'margin-left: 30px;\'>" + k2 + " : " + v2 + "</div>";
                    });
                }else {
                    html += "<div style=\'margin-left: 30px;\'>" + k1 + " : " + v1 + "</div>";
                }
            });
        }else {
            html += "<div>" + k + " : " + v + "<div>";
        }
    });
    html+='</div>';
    return html;
}
