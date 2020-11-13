const get = id => {
    return document.getElementById(id)
}

const   createCreditCard = get('createCreditCard'),
        messageCreateCreditCard = get('messageCreateCreditCard'),
        
        type = get('type'),
        iban = get('IBAN'),
        currency = get('currency'),
        balance = get('balance'),
        
        creditCardsList = get('creditCardsList'),
        
        transactions = get('transactions')

$(createCreditCard).click(async () => {
    const ans = JSON.parse(await Promise.resolve($.post('./API/createCreditCard.php', { currency: currency.value })))

    console.log(ans)

    $(messageCreateCreditCard).removeClass().html(ans.message).addClass('alert').addClass(ans.status ? 'alert-success' : 'alert-danger').fadeIn('fast')
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