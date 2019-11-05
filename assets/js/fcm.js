    $(function(){

            $('.three-ds-container').remove()
            $('#charge_token').remove()
			$('body').prepend('<div class="three-ds-container" hidden><div id="three-ds-popup" class="three-ds-popup"><div class="lds-dual-ring"></div></div></div>')
			$('.woocommerce-checkout').append('<input name="charge_token" id="charge_token" hidden>')
            $('#viva-credit-card-form').trigger('reset')
            Card_Logos = {
                "visa": FCM_Plugin_Image_Base_Url + "visa.svg",
                "mastercard": FCM_Plugin_Image_Base_Url + "mastercard.svg",
                "maestro": FCM_Plugin_Image_Base_Url + "maestro.svg",
                "amex": FCM_Plugin_Image_Base_Url + "amex.svg",
                "diners_club_carte_blanche": FCM_Plugin_Image_Base_Url + "diners.svg",
                "none": FCM_Plugin_Image_Base_Url + "credit-card.svg"
            }

    })

    var Validate_Card_Form = function(){

        var card_form_isvalid = true
        var credit_card_number = $('form#viva-credit-card-form .cardnumber').validateCreditCard()

        if (credit_card_number.card_type !== null && Card_Logos[credit_card_number.card_type.name]) $('form#viva-credit-card-form .cardnumber').css('background', 'url(' + Card_Logos[credit_card_number.card_type.name] + ') no-repeat 95% center')
        else $('form#viva-credit-card-form .cardnumber').css('background', 'url(' + Card_Logos["none"] + ') no-repeat 95% center');
        if (credit_card_number.card_type && ['visa','maestro','mastercard'].includes(credit_card_number.card_type.name)) {
            if (!credit_card_number.luhn_valid && credit_card_number.length_valid && $('form#viva-credit-card-form .cardnumber').val().length > 18) card_form_isvalid = false
        } else {
            if (!credit_card_number.luhn_valid && credit_card_number.length_valid && $('form#viva-credit-card-form .cardnumber').val().length > 14) card_form_isvalid = false
        }

        if ($('form#viva-credit-card-form .cardnumber').is(":focus") && credit_card_number.luhn_valid && credit_card_number.length_valid) $('form#viva-credit-card-form .expiry-month').focus()

        if ($('form#viva-credit-card-form .expiry-month').val().length == 2 && $('form#viva-credit-card-form .expiry-year').val().length == 2) {
            var now = new Date()
            var mm = Number(String(now.getMonth() + 1).padStart(2, "0"))
            var yy = Number(now.getFullYear().toString().substr(-2))

            if ($('form#viva-credit-card-form .expiry-month').val() > 12) card_form_isvalid = false
            if ($('form#viva-credit-card-form .expiry-year').val() < yy) card_form_isvalid = false
            else {
                if ($('form#viva-credit-card-form .expiry-year').val() == yy) {
                    if (!($('form#viva-credit-card-form .expiry-month').val() > mm)) card_form_isvalid = false
                }
            }
        }

        if (card_form_isvalid) {
            if ($('form#viva-credit-card-form .cvv').val().length > 2 && $('form#viva-credit-card-form .cardholder').val()) {
                $(".viva-credit-card-form").css({
                    "border": "1px solid #00ff00"
                })
            } else $(".viva-credit-card-form").css({
                "border": "1px solid #ddd"
            })
        } else $(".viva-credit-card-form").css({
            "border": "1px solid #ff0022"
        })
    }