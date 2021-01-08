$(function() {

    // Validate the contact form
  $('#contact-form').validate({
    onkeyup: false,
    onclick: false,
    // Specify what the errors should look like
    // when they are dynamically added to the form
    errorElement: "label",
    wrapper: "div",
    errorPlacement: function(error, element) {
        error.wrap("<div class='error-msg'></div>");
        $('#contact-error').html(error);
        $('#contact-error').parent().removeClass('hide');
    },

    // Add requirements to each of the fields
    rules: {
        name: {
            required: true,
            minlength: 2
        },
        email: {
            required: true,
            email: true
        },
        message: {
            required: true,
            minlength: 10
        }
    },

    // Specify what error messages to display
    // when the user does something horrid
    messages: {
        name: {
            required: "Please enter your name.",
            minlength: "Name should be at least 2 characters."
        },
        email: {
            required: "Please enter your email.",
            email: "Please enter a valid email."
        },
        message: {
            required: "Please enter a message.",
            minlength: "Message should be at least 10 characters."
        }
    },

    // Use Ajax to send everything to PHP
    submitHandler: function(form) {
        $("#contact-send").val("Sending...");
        $("#contact-send").prop("disabled",true);
        $(form).ajaxSubmit({
            type: 'POST',
            url: '/php/contact-send.php',
            error: function(xhr,ajaxOptions,thrownError) {
                $("#contact-error").html(xhr.responseText);
                $('#contact-error').parent().removeClass('hide');
                $("#contact-send").val("Send Message");
                $("#contact-send").prop("disabled",false);
            },
            success: function(responseText, statusText, xhr) {
                $(form).slideUp("fast");
                $("#contact-response").html(responseText);
                $("#contact-response").parent().removeClass('hide');
                $("#contact-error").parent().addClass('hide');
            }
        });
        return false;
    }
  });
});
