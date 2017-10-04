(function($) {

    function display_uploader() {

        var form_wrapper = $(document).find('#gm_gallery_submit');

        var form = $(document).find('#gm-gallery');
        form.addClass('gm_js_form');

        var new_input = '<div id="gm_input_wrapper"><input id="gm_file_input" class="gm_full_width" name="image" type="file"></div>';

        var text_inputs  = '<div id="gm_text_input_wrapper">';
            text_inputs += '<input id="gm_js_title" name="title" placeholder="Title" type="text">';
            text_inputs += '<input id="gm_js_submitter" name="name" placeholder="Your name" type="text">';
            text_inputs += '<input id="gm_js_email" name="email" placeholder="Email" type="text">';
            text_inputs += '<textarea id="gm_js_message" name="message" placeholder="Message" style=""></textarea>';
            text_inputs += '</div>';

        var button = '<button id="gm_submit_button" class="" type="button" aria-label="Menu">Upload Image <span id="gm_js_loading_gif"></span></button>';

        var nonce = gm_submit.gm_nonce_field;

        var preview = '<div id="gm_preview_wrapper"><img id="gm_preview_img" src="" alt="Choose a picture"><div id="gm_preview_pane"><i class="fa fa-camera-retro fa-4x" aria-hidden="true"></i></div>' + button + '</div>';

        //var progress = '<progress></progress>';

        form.empty().append(new_input + text_inputs + preview + nonce);

        form_wrapper.append('<div id="gm_js_error_wrapper"></div>');
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

            /*
            if ( gm_button.hasClass('clicked') )
            {
                return false;
            }
            */

            // Prevent multiple submits
            gm_button.addClass('clicked');

            var file = ($(document).find('#gm_file_input'))[0].files[0];

            var inputs = form.serialize();

            var loading_spinner = '<i class="fa fa-circle-o-notch fa-spin fa-fw"></i><span class="sr-only">Loading...</span>';

            var gif_elm = gm_button.find('#gm_js_loading_gif');

            if (gif_elm.html().length < 1)
            {
                gif_elm.append(loading_spinner);
            }

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
                    console.log(resp);
                    process_ajax_response(resp);
                },
                error: function(resp)
                {
                    console.log(resp);
                }
            } );

        });
    }

    function process_ajax_response(resp)
    {
        setTimeout(function () {

            var json_resp = JSON.parse(resp);
            var result = json_resp.result;

            switch ( result )
            {
                case 'success':
                    display_success_msg();
                    break;
                case 'failed':
                    display_error_msg(json_resp.messages);
                    break;
                default:
                    break;
            }
        }, 1800);
    }

    function display_success_msg()
    {
        var success_msg = '<div id="gm_success">Thank you! Your image has been uploaded</div>';

        var form_wrapper = $(document).find('#gm_gallery_submit');
        var form         = form_wrapper.find('form');

        form.fadeOut(500, function() {
            $(this).remove();
        });

        form_wrapper.delay(500).append(success_msg);

    }

    function display_error_msg(msgs) {

        var name_err  = typeof msgs.name  !== 'undefined' ? msgs.name  : '';
        var email_err = typeof msgs.email !== 'undefined' ? msgs.email : '';
        var title_err = typeof msgs.title !== 'undefined' ? msgs.title : '';
        var image_err = typeof msgs.image !== 'undefined' ? msgs.image : '';
        var message_err = typeof msgs.message !== 'undefined' ? msgs.message : '';

        var form_wrapper  = $(document).find('#gm_gallery_submit');
        var form          = form_wrapper.find('form');
        var button        = form.find('#gm_submit_button');
        var error_wrapper = form_wrapper.find('#gm_js_error_wrapper');

        button.removeClass('clicked');
        button.find('#gm_js_loading_gif').empty();

        if ( $(document).find('#gm_error_response').length < 1 )
        {
            error_wrapper.append('<div id="gm_error_response">Please complete the fields in red.</div>');
        } else {
            error_wrapper.effect('shake');
        }

        var bad_elms = [];

        push_bad_elms(form, '#gm_js_submitter', bad_elms, name_err);
        push_bad_elms(form, '#gm_js_email', bad_elms, email_err);
        push_bad_elms(form, '#gm_js_title', bad_elms, title_err);
        push_bad_elms(form, '#gm_file_input', bad_elms, image_err);
        push_bad_elms(form, '#gm_js_message', bad_elms, message_err);

        $.each(bad_elms, function (index, value) {

            if ( !value.hasClass('gm-error') )
            {
                value.addClass('gm-error');
            }
        });
    }

    function push_bad_elms(form, input_id, bad_elms, error_msg)
    {
        if (error_msg !== '')
        {
            var input = form.find(input_id);
            bad_elms.push(input);
        }
    }

    function remove_input_errors() {
        var form_wrapper = $(document).find('#gm_gallery_submit');
        var inputs = form_wrapper.find('input, textarea');

        inputs.each( function () {
            var name = $(this).attr('name');

            if (name !== 'email')
            {
                $(this).on('input', function () {

                    if ( $(this).hasClass('gm-error') )
                    {
                        $(this).removeClass('gm-error');
                    }

                }); // end on change.
            }

            if (name === 'email')
            {
                $(this).on('input', function () {

                    var email_value = $(this).val();

                    if ( validate_email(email_value) && $(this).hasClass('gm-error') )
                    {
                        $(this).removeClass('gm-error');
                    }

                }); // end on change.
            }

            $(this).on('input', function () {

                var inputs_with_errors = form_wrapper.find('.gm-error');

                if ( inputs_with_errors.length < 1 )
                {
                    form_wrapper.find('#gm_js_error_wrapper').empty();
                }
            });
        }); // end each
    }

    function validate_email(email_string) {
        email_string = decodeURIComponent(email_string);
        var pattern = /^([a-z\d!#$%&'*+\-\/=?^_`{|}~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+(\.[a-z\d!#$%&'*+\-\/=?^_`{|}~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+)*|"((([ \t]*\r\n)?[ \t]+)?([\x01-\x08\x0b\x0c\x0e-\x1f\x7f\x21\x23-\x5b\x5d-\x7e\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|\\[\x01-\x09\x0b\x0c\x0d-\x7f\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))*(([ \t]*\r\n)?[ \t]+)?")@(([a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF][a-z\d\-._~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]*[a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])\.)+([a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF][a-z\d\-._~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]*[a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])\.?$/i;
        return pattern.test(email_string);
    }





    display_uploader();
    preview_img();
    img_click();
    img_submit();
    remove_input_errors();


})(jQuery);