const get = id => {
    return document.getElementById(id)
}

const   createCreditCard = get('createCreditCard'),
        messageCreateCreditCard = get('messageCreateCreditCard'),
        createCardWithCurrency = get('createCardWithCurrency'),

        closeModal = get('closeModal'),
        modalCenter = get('modalCenter'),
        
        type = get('accountType'),
        iban = get('IBAN'),
        currency = get('currency'),
        balance = get('balance'),
        
        creditCardsList = get('creditCardsList'),
        
        details = get('details'),
        creditCardMainData = get('creditCardMainData'),
        creditCardMainDataSpinner = get('creditCardMainDataSpinner'),

        transactionsButton = get('transactions'),
        creditCardTransactionsData = get('creditCardTransactionsData'),

        transactionsList = get('transactionsList'),

        transactionDate = get('transactionDate'),
        transactionDescription = get('transactionDescription'),
        transactionAmount = get('transactionAmount'),
        transactionBalance = get('transactionBalance'),
        transactionReference = get('transactionReference'),

        missingTransactions = get('missingTransactions')

$(createCreditCard).click(async () => {
    $(createCreditCard).attr('disabled', true)
    
    const ans = JSON.parse(await Promise.resolve($.post('./API/createCreditCard.php', { currency: createCardWithCurrency.value })))

    if ($(messageCreateCreditCard).html() != ans.message || ans.status == true) {
        $(messageCreateCreditCard).removeClass().html(ans.message).addClass('alert').addClass(ans.status ? 'alert-success' : 'alert-danger').hide().fadeIn('fast')
        $(createCreditCard).attr('disabled', false)
    }

    else {
        $(messageCreateCreditCard).addClass('shake')
        $(createCreditCard).attr('disabled', true)
        $(messageCreateCreditCard).on('animationend', () => {
            $(messageCreateCreditCard).removeClass('shake')
            $(createCreditCard).attr('disabled', false)
        })
    }
})

$(modalCenter).on('blur hidden.bs.modal', () => {
    $(messageCreateCreditCard).hide().empty()
})


const switchBetween = (button1, button2, div1, div2) => {
    
    if ($(button1).hasClass('active'))
        return

    $(button2).attr('disabled', true).removeClass('active')
    $(button1).attr('disabled', true).addClass('active')

    $(div1).removeClass('currentDataPage')
    $(div2).addClass('currentDataPage')

    $(div1).fadeOut('fast', () => {
        $(div2).fadeIn('fast')

        $(button1).attr('disabled', false)
        $(button2).attr('disabled', false)
    })
}

$(details).on('click', () => {
    switchBetween(details, transactionsButton, creditCardTransactionsData, creditCardMainData)
})

$(transactionsButton).on('click', () => {
    switchBetween(transactionsButton, details, creditCardMainData, creditCardTransactionsData)
})


window.onload = async () => {

    $('#mainDiv').fadeIn('slow')

    $(creditCardTransactionsData).css('height', $(creditCardMainData).height() + 8)

    const children = $(creditCardsList).children()

    if (!$(children).length)
        return


    for (const child of children) {

        const currentIBAN = $(child).find('.IBAN').html()

        $(child).on('click', async () => {

            const IBAN = currentIBAN

            $('[class*="currentDataPage"]').hide()
            $('[class*="creditCard"]').addClass('disabled')
            $(creditCardMainDataSpinner).fadeIn('fast')
            $(details).attr('disabled', true)
            $(transactionsButton).attr('disabled', true)

            const timeBefore = performance.now()
            const data = JSON.parse(await Promise.resolve($.post('/onlineBanking.php', { IBAN: IBAN })))
            const timeAfter = performance.now()

            if (timeAfter - timeBefore < 500)
                await new Promise(resolve => { setTimeout(resolve, timeBefore - timeAfter + 500) })

            $(iban).html(currentIBAN)
            $(type).html(data.type)
            $(currency).html(data.currency)
            $(balance).html(data.balance)

            $('[class*="creditCard"]').removeClass('active')
            $(child).addClass('active')

            $(creditCardMainDataSpinner).hide()
            $('[class*="currentDataPage"]').fadeIn('fast')
            $('[class*="creditCard"]').removeClass('disabled')
            $(details).attr('disabled', false)
            $(transactionsButton).attr('disabled', false)

            const transactions = data.transactions
            
            $(transactionsList).empty()
            
            if (!transactions.length)
                return $(missingTransactions).html('You have no transactions for this credit card.').fadeIn('slow')
            
            $(missingTransactions).hide()
            $(transactionsList).hide()

            for (const transaction of transactions) {
                const newTransaction = document.createElement('a')
                newTransaction.setAttribute('data-toggle', 'modal')
                newTransaction.setAttribute('data-target', '#modalCenter2')
                newTransaction.setAttribute('href', '#')
                newTransaction.className = 'list-group-item list-group-item-action list-group-item-info border rounded text-center mb-2'
                
                const rowDiv = document.createElement('div')
                rowDiv.className = 'row text'

                const colDiv = document.createElement('div')
                colDiv.className = 'col'
                
                const small = document.createElement('small')
                small.innerHTML = transaction.type

                colDiv.appendChild(small)
                rowDiv.appendChild(colDiv)
                newTransaction.appendChild(rowDiv)

                newTransaction.addEventListener('click', () => {

                    $(transactionDate).html(transaction.date)
                    $(transactionDescription).html(transaction.description)
                    $(transactionAmount).html(transaction.amount)
                    $(transactionBalance).html(transaction.balance)
                    $(transactionReference).html(transaction.reference)
                })

                transactionsList.appendChild(newTransaction)
            }

            $(transactionsList).fadeIn('slow')
        })
    }

    $(creditCardsList).children()[0].click()

}