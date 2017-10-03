(function($) {

    function display_uploader() {

        var form = $(document).find('#gm-gallery');
        form.addClass('gm_js_form');

        var new_input = '<div id="gm_input_wrapper"><input id="gm_file_input" class="gm_full_width" name="image" type="file"></div>';

        var text_inputs  = '<div id="gm_text_input_wrapper">';
            text_inputs += '<input id="gm_js_title" name="title" placeholder="Title" type="text">';
            text_inputs += '<input id="gm_js_submitter" name="name" placeholder="Your name" type="text">';
            text_inputs += '<input id="gm_js_email" name="email" placeholder="Email" type="text">';
            text_inputs += '<textarea name="message" placeholder="Mesage" style=""></textarea>';
            text_inputs += '</div>';

        var button = '<button id="gm_submit_button" class="" type="button" aria-label="Menu">Upload Image</button>';

        var nonce = gm_submit.gm_nonce_field;

        var preview = '<div id="gm_preview_wrapper"><img id="gm_preview_img" src="" alt="Choose a picture"><div id="gm_preview_pane"><i class="fa fa-camera-retro fa-4x" aria-hidden="true"></i></div>' + button + '</div>';

        var progress = '<progress></progress>';

        form.empty().append(new_input + text_inputs + preview + nonce + progress);
    }

    function preview_img() {

        var input = $(document).find('#gm_file_input');

        input.on('change', function () {

            if (this.files && this.files[0])
            {
                var reader = new FileReader();

                reader.onload = function (e) {

                    var img_elm = $(document).find('#gm_preview_img');

                    var img_wrap = $(document).find('#gm_preview_wrapper');

                    var msg_frame = $(document).find('#gm_message_wrapper');

                    var img_pane = img_wrap.find('#gm_preview_pane');

                    img_elm.css("width","auto");
                    img_elm.attr('src', e.target.result);

                    img_wrap.fadeIn().css("display","inline-block");
                    msg_frame.css("display","inline-block");
                    img_pane.hide();


//                    input.remove();
                };

                reader.readAsDataURL(this.files[0]);
            }
        })
    }

    function img_click() {

//        var img = $(document).find('#gm_preview_wrapper, #gm_preview_pane, #gm_preview_img, #img_wrap');
        var img = $(document).find('#gm_preview_pane, #gm_preview_img, #img_wrap');

        img.on('click', function() {

            var input = $(document).find('#gm_file_input');

            input.click();
        });

    }

    function img_submit() {

        var form       = $(document).find('#gm_gallery_submit').find('form');
        var gm_button  = form.find('#gm_submit_button');

        gm_button.on('click', function (e) {
            e.preventDefault();

            var file = ($(document).find('#gm_file_input'))[0].files[0];

            var inputs = form.serialize();

            var formdata = new FormData();
            formdata.append('inputs', inputs);
            formdata.append('image', file);
            formdata.append('action', 'gm_ajax_submit');

            $.ajax( {
                url: gm_submit.ajaxurl,
                type: 'POST',
                data: formdata,
                contentType:false,
                processData:false,
                success: function(resp)
                {
                    console.log('Success: '+resp)
                },
                error: function(resp)
                {
                    console.log('Fail: '+resp)
                }
            } );

        });
    }

    display_uploader();
    preview_img();
    img_click();
    img_submit();


})(jQuery);