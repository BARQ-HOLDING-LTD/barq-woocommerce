var floating_message_timer;
$ = jQuery;
$(document).ready(function () {
    $('#barq-get-token').click(function (e) {
        e.preventDefault();
        $button = $(this);
        if (!$button.hasClass('barq-disabled-button')) {
            $button.addClass('barq-disabled-button');
            $button.next('.spinner').css('visibility', 'visible');
            var username = $('#barq-merchant-username').val();
            var password = $('#barq-merchant-password').val();
            if (username.length > 0 && password.length > 0) {
                var data = {
                    action: 'get_barq_token',
                    username: username,
                    password: password,
                    nonce: Barq.ajax_nonce
                };
                $.post(
                    Barq.ajax_url,
                    data,
                    function (response, textStatus, jqXHR) {
                        $('#barq-merchant-token').val(response['data']['token']);
                        show_floating_message(response.data.message, response.success);
                        $button.removeClass('barq-disabled-button');
                        $button.next('.spinner').css('visibility', 'hidden');
                        if (response.success) {
                            $('.barq-alert-success').show();
                        } else {
                            $('.barq-alert-success').hide();
                        }
                    },
                );
            } else {
                var message = 'Please enter the merchant credentials.';
                show_floating_message(message, false);
                $button.removeClass('barq-disabled-button');
                $button.next('.spinner').css('visibility', 'hidden');
            }
        }
    });
    $('#barq-set-callback').click(function (e) {
        e.preventDefault();
        $button = $(this);
        if (!$button.hasClass('barq-disabled-button')) {
            $button.addClass('barq-disabled-button');
            $button.next('.spinner').css('visibility', 'visible');
            var callback_address = $('#barq-webhook-callback-url').text();
            if (callback_address.length > 0) {
                var data = {
                    action: 'set_barq_callback',
                    callback_url: callback_address,
                    nonce: Barq.ajax_nonce
                };
                $.post(
                    Barq.ajax_url,
                    data,
                    function (response, textStatus, jqXHR) {
                        $('#barq-merchant-token').val(response['data']['token']);
                        show_floating_message(response.data.message, response.success);
                        $button.removeClass('barq-disabled-button');
                        $button.next('.spinner').css('visibility', 'hidden');
                        if (response.success) {
                            $('.barq-alert-success').show();
                        } else {
                            $('.barq-alert-success').hide();
                        }
                    },
                );
            } else {
                var message = 'Please enter the merchant credentials.';
                show_floating_message(message, false);
                $button.removeClass('barq-disabled-button');
                $button.next('.spinner').css('visibility', 'hidden');
            }
        }
    });
    $('#barq-get-hubs').click(function (e) {
        e.preventDefault();
        $button = $(this);
        if (!$button.hasClass('barq-disabled-button')) {
            $button.addClass('barq-disabled-button');
            $button.next('.spinner').css('visibility', 'visible');
            var data = {
                action: 'set_reload_hubs',
                nonce: Barq.ajax_nonce
            };
            $.post(
                Barq.ajax_url,
                data,
                function (response, textStatus, jqXHR) {
                    $('#barq-merchant-token').val(response['data']['token']);
                    show_floating_message(response.data.message, response.success);
                    $button.removeClass('barq-disabled-button');
                    $button.next('.spinner').css('visibility', 'hidden');
                    if (response.success) {
                        $('.barq-alert-success').show();
                    } else {
                        $('.barq-alert-success').hide();
                    }
                },
            );
        }
    });
});


function show_floating_message(message, success) {
    var message_class = 'failed';
    if (success) {
        message_class = 'success';
    }
    if ($('.barq-floating-message').length > 0) {
        $('.barq-floating-message').remove();
    }
    $container = $('<div class="barq-floating-message ' + message_class + '"></div>');
    $close = $('<span class="barq-floating-message-close"></span>');
    $container.append($close);
    $container.append(message);
    $('body').append($container);
    $('.barq-floating-message').fadeIn('fast', function () {
        clearTimeout(floating_message_timer);
        floating_message_timer = setTimeout(() => {
            hide_floating_message();
        }, 5000);
    });
    $('body').on('click', '.barq-floating-message-close', function (event) {
        clearTimeout(floating_message_timer);
        hide_floating_message();
    });
}

function hide_floating_message() {
    $('.barq-floating-message').fadeOut('fast', function () {
        $('.barq-floating-message').remove();
    });
}