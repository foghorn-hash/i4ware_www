jQuery(function($){
    $('#i4ware-contact-form').on('submit', function(e){
        e.preventDefault();
        var $form = $(this);
        var data = $form.serialize();
        data += '&action=i4ware_contact&nonce=' + i4ware_ajax.nonce;
        $form.find('.i4ware-contact-response').text('Lähettää...');
        $.post(i4ware_ajax.ajax_url, data, function(response){
            if(response.success){
                $form.find('.i4ware-contact-response').text(response.data);
                $form[0].reset();
            } else {
                $form.find('.i4ware-contact-response').text(response.data);
            }
        });
    });
});