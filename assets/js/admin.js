jQuery(function ($) {
    var messageTimer = null;
    var actionInFlight = false;
    var pendingOrderRequests = 0;
    var config = window.wooZoMyacsLite || {};
    var strings = config.i18n || {};
    var $orderWrap = $('#woo-zo-myacs-lite-metabox[data-plugin="woo-zo-myacs-lite"]');

    function ensureConfirmDialog() {
        var $dialog = $('#woo-zo-myacs-lite-confirm');
        if ($dialog.length) {
            return $dialog;
        }

        $('body').append(
            '<div id="woo-zo-myacs-lite-confirm" class="wp-zo-cfl-confirm" style="display:none;">' +
                '<div class="wp-zo-cfl-confirm-backdrop"></div>' +
                '<div class="wp-zo-cfl-confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="wp-zo-cfl-confirm-title">' +
                    '<h3 id="wp-zo-cfl-confirm-title" class="wp-zo-cfl-confirm-title"></h3>' +
                    '<p class="wp-zo-cfl-confirm-text"></p>' +
                    '<div class="wp-zo-cfl-confirm-actions">' +
                        '<button type="button" class="button button-primary wp-zo-cfl-confirm-yes"></button>' +
                        '<button type="button" class="button wp-zo-cfl-confirm-no"></button>' +
                    '</div>' +
                '</div>' +
            '</div>'
        );

        return $('#woo-zo-myacs-lite-confirm');
    }

    function openCancelConfirm(reference, onConfirm) {
        var $dialog = ensureConfirmDialog();
        var message = reference
            ? strings.cancelMessage.replace('%s', reference)
            : strings.cancelMessageEmpty;

        $dialog.find('.wp-zo-cfl-confirm-title').text(strings.cancelTitle);
        $dialog.find('.wp-zo-cfl-confirm-text').text(message);
        $dialog.find('.wp-zo-cfl-confirm-yes').text(strings.confirmYes);
        $dialog.find('.wp-zo-cfl-confirm-no').text(strings.confirmCancel);
        $dialog.fadeIn(120);

        $dialog.off('click.wooZoMyacsLiteConfirm');
        $dialog.on('click.wooZoMyacsLiteConfirm', '.wp-zo-cfl-confirm-no, .wp-zo-cfl-confirm-backdrop', function () {
            $dialog.fadeOut(120);
        });
        $dialog.on('click.wooZoMyacsLiteConfirm', '.wp-zo-cfl-confirm-yes', function () {
            $dialog.fadeOut(120);
            if (typeof onConfirm === 'function') {
                onConfirm();
            }
        });
    }

    function ensureCloseDayDialog() {
        var $dialog = $('#woo-zo-myacs-lite-close-day-modal');
        if ($dialog.length) {
            return $dialog;
        }

        $('body').append(
            '<div id="woo-zo-myacs-lite-close-day-modal" class="wp-zo-cfl-confirm" style="display:none;">' +
                '<div class="wp-zo-cfl-confirm-backdrop"></div>' +
                '<div class="wp-zo-cfl-confirm-dialog wp-zo-cfl-close-day-dialog" role="dialog" aria-modal="true" aria-labelledby="wp-zo-cfl-close-day-title">' +
                    '<h3 id="wp-zo-cfl-close-day-title" class="wp-zo-cfl-confirm-title"></h3>' +
                    '<div class="wp-zo-cfl-close-day-body">' +
                        '<div class="wp-zo-cfl-close-day-message"></div>' +
                        '<p class="wp-zo-cfl-close-day-link-wrap" style="display:none;">' +
                            '<a class="button button-primary wp-zo-cfl-close-day-link" href="" target="_blank" rel="noopener noreferrer"></a>' +
                        '</p>' +
                    '</div>' +
                    '<div class="wp-zo-cfl-confirm-actions">' +
                        '<button type="button" class="button wp-zo-cfl-close-day-close"></button>' +
                    '</div>' +
                '</div>' +
            '</div>'
        );

        return $('#woo-zo-myacs-lite-close-day-modal');
    }

    function openCloseDayDialog(message, isError, linkUrl) {
        var $dialog = ensureCloseDayDialog();

        $dialog.find('.wp-zo-cfl-confirm-title').text(strings.closeDayTitle);
        $dialog.find('.wp-zo-cfl-close-day-close').text(strings.closeAction);
        $dialog.find('.wp-zo-cfl-close-day-message')
            .removeClass('notice notice-success notice-error inline')
            .addClass(isError ? 'notice notice-error inline' : 'notice notice-success inline')
            .html('<p>' + message + '</p>');

        if (linkUrl) {
            $dialog.find('.wp-zo-cfl-close-day-link')
                .attr('href', linkUrl)
                .text(strings.closeDayDownload);
            $dialog.find('.wp-zo-cfl-close-day-link-wrap').show();
        } else {
            $dialog.find('.wp-zo-cfl-close-day-link').attr('href', '');
            $dialog.find('.wp-zo-cfl-close-day-link-wrap').hide();
        }

        $dialog.fadeIn(120);
        $dialog.off('click.wooZoMyacsLiteCloseDay');
        $dialog.on('click.wooZoMyacsLiteCloseDay', '.wp-zo-cfl-close-day-close, .wp-zo-cfl-confirm-backdrop', function () {
            $dialog.fadeOut(120);
        });
    }

    function injectUpdatePageLogo() {
        if (!config.pluginFile || !config.logoUrl) {
            return;
        }

        var $input = $('input[name="checked[]"][value="' + config.pluginFile + '"]');
        if (!$input.length) {
            return;
        }

        var $row = $input.closest('tr');
        var $target = $row.find('.plugin-title strong').first();
        if (!$target.length) {
            $target = $row.find('td').first();
        }
        if (!$target.length || $target.find('.wp-zo-cfl-update-logo').length) {
            return;
        }

        $target.prepend('<img src="' + config.logoUrl + '" alt="" class="wp-zo-cfl-update-logo" />');
    }

    injectUpdatePageLogo();

    function showMessage($container, message, isError) {
        if (!$container.length) {
            return;
        }

        if (messageTimer) {
            clearTimeout(messageTimer);
        }

        $container
            .removeClass('notice-success notice-error')
            .addClass(isError ? 'notice notice-error inline' : 'notice notice-success inline')
            .html('<p>' + message + '</p>');

        messageTimer = setTimeout(function () {
            $container.removeClass('notice notice-success notice-error inline').empty().hide();
        }, 25000);
    }

    function getOrderWrap() {
        return $orderWrap.length ? $orderWrap : $('#woo-zo-myacs-lite-metabox[data-plugin="woo-zo-myacs-lite"]');
    }

    function beginOrderRequest($wrap) {
        pendingOrderRequests += 1;
        $wrap.addClass('is-busy').attr('aria-busy', 'true');
        $wrap.find('.wp-zo-cfl-field, .wp-zo-cfl-action').prop('disabled', true);
        $wrap.find('.wp-zo-cfl-loading').prop('hidden', false);
    }

    function endOrderRequest($wrap) {
        pendingOrderRequests = Math.max(0, pendingOrderRequests - 1);
        if (pendingOrderRequests > 0) {
            return;
        }

        $wrap.removeClass('is-busy').attr('aria-busy', 'false');
        $wrap.find('.wp-zo-cfl-field, .wp-zo-cfl-action').prop('disabled', false);
        $wrap.find('.wp-zo-cfl-loading').prop('hidden', true);
    }

    function setReference($wrap, reference) {
        var $holder = $wrap.find('.wp-zo-cfl-reference-wrap');
        if (!$holder.length) {
            return;
        }

        if (reference) {
            $holder.html(
                '<a class="wp-zo-cfl-reference-link wp-zo-cfl-reference" href="https://a.acssp.gr/track/?k=etr:' + encodeURIComponent(reference) + '" target="_blank" rel="noopener noreferrer">' +
                    $('<div />').text(reference).html() +
                '</a>'
            );
        } else {
            $holder.html('<span class="wp-zo-cfl-reference"></span>');
        }
    }

    function runOrderAction(actionName, $wrap) {
        var map = {
            create_print: 'woo_zo_myacs_lite_create_print',
            cancel: 'woo_zo_myacs_lite_cancel',
            track: 'woo_zo_myacs_lite_track'
        };
        if (actionInFlight) {
            return;
        }

        actionInFlight = true;
        beginOrderRequest($wrap);

        $.post(config.ajaxUrl, {
            action: map[actionName],
            nonce: config.nonce,
            order_id: $wrap.data('order-id')
        }).done(function (response) {
            showMessage($wrap.find('.wp-zo-cfl-message'), response.data.message, !response.success);
            if (response.success && response.data.reference) {
                setReference($wrap, response.data.reference);
            }
            if (response.success && response.data.status) {
                $wrap.find('.wp-zo-cfl-tracking').text(response.data.message);
            }
            if (response.success && response.data.pdf_url) {
                window.open(response.data.pdf_url, '_blank');
            }
            if (response.success && actionName === 'cancel') {
                setReference($wrap, '');
            }
        }).fail(function () {
            showMessage($wrap.find('.wp-zo-cfl-message'), strings.requestFailed, true);
        }).always(function () {
            actionInFlight = false;
            endOrderRequest($wrap);
        });
    }

    getOrderWrap().on('change', '.wp-zo-cfl-field', function (event) {
        event.stopPropagation();

        var $field = $(this);
        var $wrap = getOrderWrap();
        var field = $field.data('field');
        var value = $field.is(':checkbox') ? ($field.is(':checked') ? 1 : 0) : $field.val();
        if (!$wrap.length) {
            return;
        }

        beginOrderRequest($wrap);
        $.post(config.ajaxUrl, {
            action: 'woo_zo_myacs_lite_save_options',
            nonce: config.nonce,
            order_id: $wrap.data('order-id'),
            field: field,
            value: value
        }).done(function (response) {
            showMessage($wrap.find('.wp-zo-cfl-message'), response.data.message, !response.success);
        }).fail(function () {
            showMessage($wrap.find('.wp-zo-cfl-message'), strings.requestFailed, true);
        }).always(function () {
            endOrderRequest($wrap);
        });
    });

    getOrderWrap().on('click', '.wp-zo-cfl-action', function (event) {
        event.preventDefault();
        event.stopImmediatePropagation();
        event.stopPropagation();

        var $wrap = getOrderWrap();
        var actionName = $(this).data('action');
        if (!$wrap.length) {
            return;
        }

        if ('cancel' === actionName) {
            openCancelConfirm($.trim($wrap.find('.wp-zo-cfl-reference').text()), function () {
                runOrderAction(actionName, $wrap);
            });

            return;
        }

        runOrderAction(actionName, $wrap);
    });

    $(document).on('click', '#wp-zo-cfl-clear-pdfs', function () {
        $.post(config.ajaxUrl, {
            action: 'woo_zo_myacs_lite_clear_pdfs',
            nonce: config.nonce
        }).done(function (response) {
            showMessage($('#wp-zo-cfl-settings-message'), response.data.message, !response.success);
            if (response.success) {
                setTimeout(function () { window.location.reload(); }, 800);
            }
        });
    });

    $(document).on('click', '.woo-zo-myacs-lite-close-day-button', function (event) {
        event.preventDefault();
        event.stopPropagation();

        openCloseDayDialog(strings.closeDayLoading, false, '');

        $.post(config.ajaxUrl, {
            action: 'woo_zo_myacs_lite_close_day',
            nonce: config.nonce
        }).done(function (response) {
            openCloseDayDialog(
                response && response.data && response.data.message
                    ? response.data.message
                    : (response && response.success ? strings.closeDaySuccess : strings.requestFailed),
                !response.success,
                response && response.success && response.data ? response.data.document_url : ''
            );
        }).fail(function () {
            openCloseDayDialog(strings.requestFailed, true, '');
        });
    });
});
