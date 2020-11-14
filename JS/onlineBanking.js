const get = id => {
    return document.getElementById(id)
}

const   createCreditCard = get('createCreditCard'),
        messageCreateCreditCard = get('messageCreateCreditCard'),
        createCardWithCurrency = get('createCardWithCurrency'),

        closeModal = get('closeModal'),
        
        type = get('type'),
        iban = get('IBAN'),
        currency = get('currency'),
        balance = get('balance'),
        
        creditCardsList = get('creditCardsList'),
        
        transactions = get('transactions')

$(createCreditCard).click(async () => {
    const ans = JSON.parse(await Promise.resolve($.post('./API/createCreditCard.php', { currency: createCardWithCurrency.value })))

    console.log(ans)

    if ($(messageCreateCreditCard).html() != ans.message || ans.status == true)
        $(messageCreateCreditCard).removeClass().html(ans.message).addClass('alert').addClass(ans.status ? 'alert-success' : 'alert-danger').hide().fadeIn('fast')

    else {
        $(messageCreateCreditCard).addClass('shake')
        $(createCreditCard).attr('disabled', true)
        $(messageCreateCreditCard).on('animationend', () => {
            $(messageCreateCreditCard).removeClass('shake')
            $(createCreditCard).attr('disabled', false)
        })
    }
})

$(closeModal).click(() => {
    $(messageCreateCreditCard).hide()
})


window.onload = () => {
    const children = $(creditCardsList).children()

    for (let child of children) {
        const currentIBAN = $(child).find('.IBAN').html()

        
        $(child).on('click', async () => {
            if ($(child).hasClass('active'))
                return

            const IBAN = currentIBAN

            const data = JSON.parse(await Promise.resolve($.post('/onlineBanking.php', { IBAN: IBAN })))

            $(iban).html(currentIBAN)
            $(type).html(data.type)
            $(currency).html(data.currency)
            $(balance).html(data.balance)

            $('[class*="creditCard"]').removeClass('active')
            $(child).addClass('active')
        })

    }
}