jQuery(document).ready(function($){
    var custom_uploader;
    /*##############################*/
    /* 画像選択ボタンがクリックされた場合の処理。*/
    /*##############################*/
    $('#paka3media').click(function(e) {
        e.preventDefault();
        if (custom_uploader) {
            custom_uploader.open();
            return;
        }
        custom_uploader = wp.media({
            title: 'Choose Image',
            // 以下のコメントアウトを解除すると画像のみに限定される。
 
            library: {
                type: 'image'
            }, 
 
            button: {
                text: 'Choose Image'
            },
            multiple: false // falseにすると画像を1つしか選択できなくなる
        });
        custom_uploader.on('select', function() {
            var images = custom_uploader.state().get('selection');
            var date = new Date().getTime();
 
            images.each(function(file){
                img_id = file.toJSON().id+"_"+date;
                $('#paka3images').empty();
                $('#paka3images').append('<div id=img_'+ img_id +'></div>')
                .find('div:last').append('<a href="#" class="paka3image_remove">削除する</a><br />'
                     +'<input type="hidden" name="tasktweet[post_thumbnail_id]" value="'+file.toJSON().id+'" />'
                     +'<img src="'+file.toJSON().sizes.thumbnail.url+'" />');
            });
        });
        custom_uploader.open();
    });
    /*##############################*/
    /* 削除がクリックされた場合の処理。*/
    /*##############################*/
    $( ".paka3image_remove" ).live( 'click', function( e ) {
        
        e.preventDefault();
        e.stopPropagation();
        
        img_obj = $(this).parent();
        if(img_obj.length >0){
            img_obj.remove();
        }
    });
});