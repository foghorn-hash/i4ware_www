jQuery(function($){
    $('#contact-form').on('submit', function(e){
        e.preventDefault();
        var $form = $(this);

        if (typeof grecaptcha !== 'undefined' && $form.find('.g-recaptcha').length) {
            var recaptchaResponse = grecaptcha.getResponse();
            if (!recaptchaResponse) {
                $form.find('.contact-response').text('Please verify the reCAPTCHA.');
                return;
            }
        }

        var data = $form.serialize();
        data += '&action=i4ware_contact&nonce=' + i4ware_ajax.nonce;
        $form.find('.contact-response').text('Sending...');
        $.post(i4ware_ajax.ajax_url, data, function(response){
            if(response.success){
                $form.find('.contact-response').text(response.data);
                $form[0].reset();
                if (typeof grecaptcha !== 'undefined') { grecaptcha.reset(); }
            } else {
                $form.find('.contact-response').text(response.data);
                if (typeof grecaptcha !== 'undefined') { grecaptcha.reset(); }
            }
        });
    });
});