function CardPayment(url, pubkey) {
    this.url = url;
    this.pubkey = pubkey;
    $("#card_payment_form").attr("action", url);
    var numberInput = $('#card_number'),
        expiryInput = $('#expiry_date'),
        cvcInput = $('#cvc'),
        nameInput = $('#c_name'),
        emailInput = $('#c_email'),
        termsOfServiceInput = $('#tpay-cards-accept-regulations-checkbox');
    const TRIGGER_EVENTS = 'input change blur';

    function getShortOrigin(maxLength) {
        var fallbackOrigin = document.location.protocol + '//' + document.location.host,
            origin = document.location.origin || fallbackOrigin,
            MAX_ORIGIN_LENGTH = 60,
            hasCustomLimit = typeof maxLength === 'number' && isFinite(maxLength),
            limit = hasCustomLimit ? maxLength : MAX_ORIGIN_LENGTH;

        if (limit < 0) {
            limit = 0;
        }

        // RSA payload has a strict size limit (key size - 11 bytes). Truncate
        // the origin to make sure encrypting the concatenated card payload does
        // not exceed the limit on stores with very long host names.
        return origin.substring(0, limit);
    }

    function getMaxPayloadLengthFromKey(key) {
        if (!key || !key.n || typeof key.n.bitLength !== 'function') {
            return Infinity;
        }

        var bitLength = key.n.bitLength(),
            byteLength = Math.ceil(bitLength / 8);

        return Math.max(0, byteLength - 11);
    }

    function toggleProcessingState(isProcessing) {
        if (isProcessing) {
            $("#card_continue_btn").fadeOut();
            $("#loading_scr").fadeIn();
        } else {
            $("#loading_scr").fadeOut();
            $("#card_continue_btn").fadeIn();
        }
    }

    function showEncryptionError(message) {
        toggleProcessingState(false);
        alert(message || 'Nie można zaszyfrować danych karty. Skontaktuj się z obsługą sklepu.');
    }

    function SubmitPayment() {
        var cardNumber = numberInput.val().replace(/\s/g, ''),
            expiry = expiryInput.val().replace(/\s/g, ''),
            cvc = cvcInput.val().replace(/\s/g, ''),
            basePayload = cardNumber + '|' + expiry + '|' + cvc,
            encrypt = new JSEncrypt(),
            decoded = Base64.decode(pubkey),
            encrypted,
            key,
            maxPayloadLength,
            payload,
            originBudget;
        toggleProcessingState(true);
        encrypt.setPublicKey(decoded);
        key = encrypt.getKey();
        maxPayloadLength = getMaxPayloadLengthFromKey(key);
        if (isFinite(maxPayloadLength) && basePayload.length > maxPayloadLength) {
            console.error('RSA key is too small for the payment payload.');
            showEncryptionError('Nie można zaszyfrować danych karty. Klucz publiczny jest nieprawidłowy lub zbyt krótki.');

            return;
        }
        originBudget = isFinite(maxPayloadLength)
            ? Math.max(0, maxPayloadLength - basePayload.length - 1)
            : undefined;
        payload = basePayload + '|' + getShortOrigin(originBudget);
        try {
            encrypted = encrypt.encrypt(payload);
        } catch (error) {
            console.error('Encrypting card data failed.', error);
            showEncryptionError();

            return;
        }
        if (!encrypted) {
            console.error('Encrypting card data returned an empty result.');
            showEncryptionError();

            return;
        }
        $("#carddata").val(encrypted);
        $("#card_vendor").val($.payment.cardType(cardNumber));
        numberInput.val('');
        expiryInput.val('');
        cvcInput.val('');
        $('#card_payment_form').submit();
    }

    function setWrong($elem) {
        $elem.addClass('wrong').removeClass('valid');
    }

    function setValid($elem) {
        $elem.addClass('valid').removeClass('wrong');
    }

    function validateCcNumber($elem) {
        var isValid = false,
            ccNumber = $.payment.formatCardNumber($elem.val()),
            supported = ['mastercard', 'maestro', 'visa'],
            type = $.payment.cardType(ccNumber),
            notValidNote = $('#info_msg_not_valid'),
            cardTypeHolder = $('.tpay-card-icon'),
            notSupportedNote = $('#info_msg_not_supported');
        $elem.val($.payment.formatCardNumber($elem.val()));
        cardTypeHolder.attr('class', 'tpay-card-icon');
        if (supported.indexOf(type) < 0 && type !== null && ccNumber.length > 1) {
            showElem(notSupportedNote);
            hideElem(notValidNote);
            setWrong($elem);
        } else if (supported.indexOf(type) > -1 && $.payment.validateCardNumber(ccNumber)) {
            setValid($elem);
            hideElem(notSupportedNote);
            hideElem(notValidNote);
            isValid = true;
        } else if (ccNumber.length < 4) {
            hideElem(notSupportedNote);
            hideElem(notValidNote);
            setWrong($elem);
        } else {
            setWrong($elem);
            showElem(notValidNote);
            hideElem(notSupportedNote);
        }
        if (type !== '') {
            cardTypeHolder.addClass('tpay-' + type + '-icon');
        }

        return isValid;
    }

    function hideElem($elem) {
        $elem.css('display', 'none');
    }

    function showElem($elem) {
        $elem.css('display', 'block');
    }

    function validateExpiryDate($elem) {
        var isValid = false, expiration;
        $elem.val($.payment.formatExpiry($elem.val()));
        expiration = $elem.payment('cardExpiryVal');
        if (!$.payment.validateCardExpiry(expiration.month, expiration.year)) {
            setWrong($elem);
        } else {
            setValid($elem);
            isValid = true;
        }

        return isValid;
    }

    function validateCvc($elem) {
        var isValid = false;
        if (!$.payment.validateCardCVC($elem.val(), $.payment.cardType(numberInput.val().replace(/\s/g, '')))) {
            setWrong($elem);
        } else {
            setValid($elem);
            isValid = true;
        }

        return isValid;
    }

    function validateName($elem) {
        var isValid = false;
        if ($elem.val().length < 3) {
            setWrong($elem);
        } else {
            isValid = true;
            setValid($elem);
        }

        return isValid;
    }

    function validateEmail($elem) {
        var isValid = false;
        if (!$elem.formance('validate_email')) {
            setWrong($elem);
        } else {
            isValid = true;
            setValid($elem);
        }

        return isValid;
    }

    function checkName() {
        if (nameInput.length > 0) {
            return validateName(nameInput);
        }

        return true;
    }

    function checkEmail() {
        if (emailInput.length > 0) {
            return validateEmail(emailInput);
        }

        return true;
    }

    function validateTos($elem) {
        if ($elem.is(':checked')) {
            setValid($elem);

            return true;
        } else {
            setWrong($elem);

            return false;
        }
    }

    function checkForm() {
        var isValidForm = false;
        if (
            validateCcNumber(numberInput)
            && validateExpiryDate(expiryInput)
            && validateCvc(cvcInput)
            && checkName()
            && checkEmail()
            && validateTos(termsOfServiceInput)
        ) {
            isValidForm = true;
        }

        return isValidForm;
    }

    $('#card_continue_btn').click(function () {
        var savedId = $('input[name=savedId]:checked').val();
        if ((savedId === 'new' || !$('input[name=savedId]').length) && checkForm()) {
            SubmitPayment();
        }
        if ($.isNumeric(savedId) && validateTos(termsOfServiceInput)) {
            $('#saved_card_payment_form').submit();
        }
    });
    numberInput.on(TRIGGER_EVENTS, function () {
        validateCcNumber($(this));
    });
    expiryInput.on(TRIGGER_EVENTS, function () {
        validateExpiryDate($(this));
    });
    cvcInput.on(TRIGGER_EVENTS, function () {
        validateCvc($(this));
    });
    nameInput.on(TRIGGER_EVENTS, function () {
        validateName($(this));
    });
    emailInput.on(TRIGGER_EVENTS, function () {
        validateEmail($(this));
    });
    termsOfServiceInput.on(TRIGGER_EVENTS, function () {
        validateTos($(this));
    });

}

$('document').ready(function () {
    $('input[name=savedId]').first().prop('checked', "checked");
    handleForm();
});

function handleForm() {
    $('input[name=savedId]').each(function () {
        $(this).click(function () {
            if ($(this).is(":checked")) {
                if ($(this).val() !== 'new') {
                    $('#card_form').css({opacity: 1.0}).animate({opacity: 0.0}, 500);
                    setTimeout(
                        function () {
                            $('#card_form').css({display: "none"})
                        }, 500
                    );
                }
            }

        });
    });
    $('#newCard').click(function () {
        if ($(this).is(":checked")) {
            $('#card_form').css({opacity: 0.0, display: "block"}).animate({opacity: 1.0}, 500);
        }
    });
}
