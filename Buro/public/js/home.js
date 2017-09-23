/**
 * Created by hh on 9/13/2017.
 */
advisor_list();
function advisor_list() {
    var $ = jQuery;
    var postData 		= {"name":"user","value": 'advisor'},
        formURL 		= "/advisor_list";

    $.ajax(
        {
            url : formURL,
            type: "POST",
            data : postData,
            dataType: "json",
            success:function(data)
            {
                var html_val = list(data);
                $('#advisor_list').html(html_val);
            },
            error: function(jqXHR, textStatus, errorThrown){
                alert(errorThrown);

            },
        });
}

function list(json) {
    var $ = jQuery;
    var  html = '';
    $.each(json, function(k, v) {
            html += '<div class="col-md-3 col-sm-6 col-xs-6 service-box fadeIn" data-animation="fadeIn" data-animation-delay="01">';
            html +='<div class="thumbnail">';
            html+='<div class="advisor-img">';
            if(v.image =='') html+='<img src="/images/service-01.jpg"  >';
            else    html+='<img src="/admin/images/users/'+v.image+'"  >';
            html+='<span class="play-overlay">';
            html+='<a href="#"><img alt="" src="/images/play-icon.png"></a>';
            html+='</span>';
            html+='</div>';
            html+='<div class="caption">';
            html+='<h3>'+v.name+'</h3>';
            html+='<div class="service-title"><i class="fa fa-heart" aria-hidden="true"></i>Love advisor<i class="fa fa-heart" aria-hidden="true"></i></div>';
            html+='<div class="rating">';
            html+='<span class="fa fa-star"></span>';
            html+='<span class="fa fa-star"></span>';
            html+='<span class="fa fa-star"></span>';
            html+='<span class="fa fa-star"></span>';
            html+='<span class="fa fa-star"></span>';
            html+='</div>';
            html+='<div class="service-description">please understand 1 question  will be answered  within 12 to 24 ';
            html+='hours....Purple ocean top psychic and thank you for choosing purple ocean my name is Rose I have...';
            html+='</div>';
            html+='</div>';
            html+='</div>';
            html+='</div>';
    });
    return html;
}