(function($) {

    /**
     * Empties the HTML image submit form and replaces it with a cool JS form.
     */
    function display_uploader() {

        var form_wrapper = $(document).find('#gm_gallery_submit');

        var form = $(document).find('#gm-gallery');
        form.addClass('gm_js_form');

        var new_input = '<div id="gm_input_wrapper">'+'<div>Images should be less than ' + gm_submit.max_img_kb +'kbs</div>'+'<input id="gm_file_input" class="gm_full_width" name="image" type="file"></div>';

        var text_inputs  = '<div id="gm_text_input_wrapper">';
            text_inputs += '<input id="gm_js_title" name="title" placeholder="Title" type="text" maxlength="100">';
            text_inputs += '<input id="gm_js_submitter" name="name" placeholder="Your name" type="text" maxlength="100">';
            text_inputs += '<input id="gm_js_email" name="email" placeholder="Email" type="text" maxlength="100">';
            text_inputs += '<textarea id="gm_js_message" name="message" placeholder="Message" style="" maxlength="600"></textarea>';
            text_inputs += '</div>';

        var button = '<button id="gm_submit_button" class="" type="button" aria-label="Menu">Upload Image <span id="gm_js_loading_gif"></span></button>';

        var nonce = gm_submit.gm_nonce_field;

        var preview = '<div id="gm_preview_wrapper"><img id="gm_preview_img" src="" alt="Choose a picture"><div id="gm_preview_pane"><i class="fa fa-camera-retro fa-4x" aria-hidden="true"></i></div>' + button + '</div>';

        form.empty().append(new_input + text_inputs + preview + nonce);

        form_wrapper.append('<div id="gm_js_error_wrapper"></div>');
    }

    /**
     * Displays the image being uploaded to the user
     */
    function preview_img() {

        var file_input = $(document).find('#gm_file_input');

        file_input.on('change', function () {

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
                };

                reader.readAsDataURL(this.files[0]);
            }
        }); // end change
    }

    /**
     * Simulates a click on the file input if the image preview area is clicked.
     */
    function img_click() {

        var img = $(document).find('#gm_preview_pane, #gm_preview_img, #img_wrap');

        img.on('click', function() {

            var input = $(document).find('#gm_file_input');

            input.click();
        }); // end click
    }

    /**
     * Begins an Ajax submit.
     */
    function img_submit() {

        var form       = $(document).find('#gm_gallery_submit').find('form');
        var gm_button  = form.find('#gm_submit_button');

        gm_button.on('click', function (e) {
            e.preventDefault();

            // Prevent multiple submissions. The '.gm_clicked' class is removed in add_input_errors().
            if ( gm_button.hasClass('gm_clicked') )
            {
                return false;
            }

            // Prevent multiple submits
            gm_button.addClass('gm_clicked');

            if ( add_input_errors() === false )
            {
                // If validation fails, do not submit to Ajax.
                return false;
            }

            show_spinner(gm_button);

            var file    = ($(document).find('#gm_file_input'))[0].files[0];
            var inputs  = form.serialize();
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
                    process_ajax_response(resp);
                },
                error: function () {
                    display_error_message('There was a problem submitting your image. Please try again later!')
                }
            }); // end ajax

        }); // end on click
    }

    function show_spinner(gm_button) {
        var loading_spinner = '<i class="fa fa-circle-o-notch fa-spin fa-fw"></i><span class="sr-only">Loading...</span>';

        var gif_elm = gm_button.find('#gm_js_loading_gif');

        if (gif_elm.html().length < 1)
        {
            gif_elm.append(loading_spinner);
        }
    }

    /**
     * Processes the response from the Ajax submit. The response is generated by the PHP function gm_ajax_submit()
     * which is used for the Ajax handler.
     *
     * @param resp {string} Response from Ajax call. Parsed into JSON object.
     */
    function process_ajax_response(resp) {

        setTimeout( function () {

            var json_resp = JSON.parse(resp);
            var result = json_resp.result;
            console.log(json_resp);

            switch ( result )
            {
                case 'success':
                    process_success_msg();
                    break;
                case 'failed':
                    process_error_msg(json_resp.messages);
                    break;
                default:
                    break;
            }
        }, 1800); // end timeout
    }

    /**
     * Displays an error message. Shakes the error message if it's already present.
     *
     * @param error_msg {string}    The message that will appear in the #gm_error_response element.
     */
    function display_error_message(error_msg) {

        var form_wrapper  = $(document).find('#gm_gallery_submit');
        var error_wrapper = form_wrapper.find('#gm_js_error_wrapper');
        var error_display = form_wrapper.find('#gm_error_response');

        if ( error_display.length < 1 )
        {
            var insert = '<div id="gm_error_response">' + error_msg + '</div>';
            error_wrapper.append(insert);
        } else {

            if (error_msg.localeCompare( error_display.text() ) !== 0) {
                error_display.empty().append(error_msg);
            }
            error_wrapper.effect('shake');
        }
    }

    /**
     * Displays a thank you message
     */
    function process_success_msg() {

        var success_msg  = '<div id="gm_success">Thank you! Your image has been uploaded</div>';

        var form_wrapper = $(document).find('#gm_gallery_submit');
        var form         = form_wrapper.find('form');

        form.fadeOut(500, function() {
            $(this).remove();
        });

        form_wrapper.delay(600).append(success_msg);
    }

    /**
     * Populates validation errors in the form based on Ajax response.
     *
     * @param msgs {Object} Messages from the Ajax response.
     */
    function process_error_msg(msgs) {

        var name_err  = typeof msgs.name  !== 'undefined' ? msgs.name  : '';
        var email_err = typeof msgs.email !== 'undefined' ? msgs.email : '';
        var title_err = typeof msgs.title !== 'undefined' ? msgs.title : '';
        var image_err = typeof msgs.image !== 'undefined' ? msgs.image : '';
        var message_err = typeof msgs.message !== 'undefined' ? msgs.message : '';

        var form_wrapper  = $(document).find('#gm_gallery_submit');
        var form          = form_wrapper.find('form');
        var button        = form.find('#gm_submit_button');

        button.removeClass('clicked');
        button.find('#gm_js_loading_gif').empty();

        if ( typeof msgs.image !== 'undefined') {
            console.log(msgs.image);
            display_error_message( msgs.image );
        } else {
            display_error_message('Please complete the fields in red');
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

    /**
     * If 'error_msg' is not whitespace, find the appropriate form input for the message and push it to the 'bad_elms'
     * array.
     *
     * @param form      {HTMLElement}   Submit form element.
     * @param input_id  {string}        ID for the input being examined.
     * @param bad_elms  {Array}         Array that is populated with input elements.
     * @param error_msg {string}        The validation message for the input.
     */
    function push_bad_elms(form, input_id, bad_elms, error_msg)
    {
        if (error_msg !== '')
        {
            var input = form.find(input_id);
            bad_elms.push(input);
        }
    }

    /**
     * Validates the file input, text inputs and textarea. Adds the 'gm-error' class to inputs that fail validation.
     * Also displays general error in a #gm_error_response element. Shakes the #gm_error_response if it's already present.
     *
     * @returns {boolean}   If any errors are found, return false.
     */
    function add_input_errors() {
        var form_wrapper = $(document).find('#gm_gallery_submit');
        var form = form_wrapper.find('form');
        var inputs = form_wrapper.find('input, textarea');
        var gm_button = form.find('#gm_submit_button');

        inputs.each( function () {
            var name  = $(this).attr('name');

            var elm = $(this);

            switch (name) {
                case 'email':
                    check_input(elm, validate_email);
                    break;
                case 'image':
                    check_input(elm, validate_extension);
                    break;
                default:
                    check_input(elm, true);
                    break;
            }

        }); // end each

        gm_button.removeClass('gm_clicked');

        if ( form.find('.gm-error').length > 0 )
        {
            display_error_message('Please complete the fields in red.');

            return false;
        }
    }

    /**
     * Validates input values while the form user is inputting data. Empties the #gm_js_error_wrapper element if no
     * errors are present.
     */
    function remove_input_errors() {
        var form_wrapper = $(document).find('#gm_gallery_submit');
        var inputs = form_wrapper.find('input, textarea');

        inputs.each( function () {
            var elm   = $(this);
            var name  = elm.attr('name');

            // For email, add/remove .gm-error class on blur .
            if (name === 'email') {
                elm.on('blur', function () {
                    check_input( elm, validate_email);
                });
            }

            $(this).on('input', function () {
                switch (name) {
                    case 'email':
                        // Don't validate unless it's to remove the .gm-error class.
                        if ( $(this).hasClass('gm-error') ) {
                            check_input(elm, validate_email);
                        }
                        break;
                    case 'image':
                        check_input(elm, validate_extension);
                        break;
                    default:
                        check_input(elm, true);
                        break;
                }

                if ( form_wrapper.find('.gm-error').length < 1 ) {
                    form_wrapper.find('#gm_js_error_wrapper').empty();
                }

            }); // end on input

        }); // end each
    }

    /**
     * Validates the value from argument elm. If the 'callback' argument is a function, that function will be used
     * to validate the input value. Else, just check to see if the input value is not whitespace.
     *
     * @param   elm         {HTMLElement}   Text/file input or textarea being checked.
     * @param   callback    {function|*}    Function used to validate in input value. Or any value.
     * @returns {boolean}                   Returns true if input value passes validation, else false.
     */
    function check_input(elm, callback) {

        var value = elm.val();
        var has_error = elm.hasClass('gm-error');

        if ( typeof callback === 'function' ) {

            if ( callback(value) && has_error) {
                elm.removeClass('gm-error');
                return true;
            }

            if ( !callback(value) && !has_error ) {
                elm.addClass('gm-error');
                return false;
            }
        } else {

            if ( value !== '' && has_error) {
                elm.removeClass('gm-error');
                return true;
            }

            if ( value === '' && !has_error) {
                elm.addClass('gm-error');
                return false;
            }
        }
    }

    /**
     * Checks to make sure the 'email_string' argument is a valid email address.
     *
     * @param   email_string     {string}   Value being checked.
     * @returns {boolean}                   Returns true if valid, else false.
     */
    function validate_email(email_string) {
        email_string = decodeURIComponent(email_string);
        var pattern = /^([a-z\d!#$%&'*+\-\/=?^_`{|}~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+(\.[a-z\d!#$%&'*+\-\/=?^_`{|}~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+)*|"((([ \t]*\r\n)?[ \t]+)?([\x01-\x08\x0b\x0c\x0e-\x1f\x7f\x21\x23-\x5b\x5d-\x7e\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|\\[\x01-\x09\x0b\x0c\x0d-\x7f\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))*(([ \t]*\r\n)?[ \t]+)?")@(([a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF][a-z\d\-._~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]*[a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])\.)+([a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF][a-z\d\-._~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]*[a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])\.?$/i;
        return pattern.test(email_string);
    }

    /**
     * Checks to make sure the 'file_name' argument includes an allowed file extension.
     *
     * @param   file_name   {string}    Value being checked.
     * @returns {string|boolean}        If extension is valid, return extension. Else return false.
     */
    function validate_extension(file_name) {
        var allowed_exts = ['.jpg', '.jpeg', '.png', '.gif'];

        file_name = file_name.toLowerCase();

        var reg_ex = (new RegExp('(' + allowed_exts.join('|').replace(/\./g, '\\.') + ')$')).test(file_name);

        if ( reg_ex === true )
        {
            return file_name.split('.').pop();
        }

        return false;
    }

    /* *********************
     * - Do submit things
     * *********************/

    display_uploader();
    preview_img();
    img_click();
    img_submit();
    remove_input_errors();

})(jQuery);