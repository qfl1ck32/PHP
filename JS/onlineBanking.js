var ignoreCurrencyConvert = false

const get = id => {
    return document.getElementById(id)
}

const   createCreditCard = get('createCreditCard'),
        messageCreateCreditCard = get('messageCreateCreditCard'),
        createCardWithCurrency = get('createCardWithCurrency'),

        closeModal = get('closeModal'),
        modalCenter = get('modalCenter'),

        modalCenterSimulateTransaction = get('modalCenter3'),
        
        type = get('accountType'),
        iban = get('IBAN'),
        currency = get('currency'),
        balance = get('balance'),
        
        creditCardsList = get('creditCardsList'),
        
        details = get('details'),
        creditCardMainData = get('creditCardMainData'),
        creditCardMainDataSpinner = get('creditCardMainDataSpinner'),

        createCreditCardButton = get('createCard'),

        transactionsButton = get('transactions'),
        creditCardTransactionsData = get('creditCardTransactionsData'),

        transactionsList = get('transactionsList'),

        transactionDate = get('transactionDate'),
        transactionDescription = get('transactionDescription'),
        transactionAmount = get('transactionAmount'),
        transactionBalance = get('transactionBalance'),
        transactionReference = get('transactionReference'),

        missingTransactions = get('missingTransactions'),


        simulateTransaction = get('simulateTransaction'),
        simulateTransactionButton = get('simulateTransactionButton'),

        messageSimulateTransaction = get('messageSimulateTransaction'),

        transactionSimIBANFrom = get('transactionSimIBANFrom'),
        transactionSimImg = get('transactionSimImage'),
        transactionSimBalance = get('transactionSimBalance'),
        transactionSimReceiverName = get('transactionSimReceiverName'),
        transactionSimAmount = get('transactionSimAmount'),
        transactionSimCurrency = get('transactionSimCurrency'),
        transactionSimDescription = get('transactionSimDescription'),

        sendMoney = get('sendMoney'),
        buyItem = get('buyItem'),

        sendMoneyContainer = get('sendMoneyContainer'),
        buyItemContainer = get('buyItemContainer'),

        chosenItem = get('chosenItem'),


        transactionSimIBANTo = get('transactionSimIBANTo'),


        allCreditCards = get('allCreditCards'),
        noCreditCards = get('noCreditCards')


$(createCreditCard).click(async () => {
    $(createCreditCard).attr('disabled', true)

    const ans = JSON.parse(await Promise.resolve($.post('./API/createCreditCard.php', { currencyId: createCardWithCurrency.value })))

    if (ans.status == true) {
        appendCreditCard(ans.arg0)
    }

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

    $(div1).fadeOut('fast', () => {
        $(div2).fadeIn('fast')

        $(button1).attr('disabled', false)
        $(button2).attr('disabled', false)
    })
}

$(details).on('click', () => {
    switchBetween(details, transactionsButton, creditCardTransactionsData, creditCardMainData)

    $(creditCardTransactionsData).removeClass('currentDataPage')
    $(creditCardMainData).addClass('currentDataPage')
})

$(transactionsButton).on('click', () => {
    switchBetween(transactionsButton, details, creditCardMainData, creditCardTransactionsData)

    $(creditCardMainData).removeClass('currentDataPage')
    $(creditCardTransactionsData).addClass('currentDataPage')
})

$(sendMoney).on('click', () => {
    switchBetween(sendMoney, buyItem, buyItemContainer, sendMoneyContainer)
    $(simulateTransactionButton).attr('disabled', true)

    $(sendMoneyContainer).addClass('currentContainer')
    $(buyItemContainer).removeClass('currentContainer')
})

$(buyItem).on('click', () => {
    switchBetween(buyItem, sendMoney, sendMoneyContainer, buyItemContainer)
    $(simulateTransactionButton).attr('disabled', false)

    $(buyItemContainer).addClass('currentContainer')
    $(sendMoneyContainer).removeClass('currentContainer')
})


const checkCanSimulate = () => {
    for (const elem of [transactionSimIBANTo, transactionSimDescription, transactionSimAmount]) {
        if ($(elem).val() === '' || $(elem).hasClass('is-invalid')) {
            $(simulateTransactionButton).attr('disabled', true)
            return false
        }
    }

    if (transactionSimDescription.length > 32) {
        $(simulateTransactionButton).attr('disabled', true)
        return false
    }

    $(simulateTransactionButton).attr('disabled', false)
    return true
}


$(modalCenterSimulateTransaction).on('blur hidden.bs.modal', () => {
    $(messageSimulateTransaction).hide().empty()
})

$(simulateTransactionButton).on('click', async () => {

    const simulatePage = $('[class*="currentContainer"]')

    if ($(simulatePage).attr('id') === 'buyItemContainer') {
        const itemId = $(chosenItem).val()

        $(simulateTransactionButton).attr('disabled', true)


        const ans = JSON.parse(await Promise.resolve($.post('/onlineBanking.php', { buyItem: true, IBAN: $(transactionSimIBANFrom).val(), itemId: itemId })))

        if (ans.status == true) {

            $(messageSimulateTransaction).removeClass('alert-danger').removeClass('alert-warning').addClass('alert').addClass('alert-success').html(ans.message).fadeIn('fast')

            const children = $(creditCardsList).children()

            for (const child of children) {
                if ($(child).hasClass('active')) {
                    $(child).trigger('click', [true])
                    break
                }
            }

        }

        else {
            $(messageSimulateTransaction).removeClass('alert-success').removeClass('alert-warning').addClass('alert').addClass('alert-danger').html(ans.message).fadeIn('fast', () => {
                $(simulateTransactionButton).attr('disabled', false)
            })
        }

        return
    }

    if (!checkCanSimulate())
        return

    $(simulateTransactionButton).attr('disabled', true)

    $(messageSimulateTransaction).hide()

    const ans = JSON.parse(await Promise.resolve($.post('/onlineBanking.php', { 
                                                                            simulateTransaction: true, 
                                                                            toIBAN: $(transactionSimIBANTo).val(), 
                                                                            fromIBAN: $(transactionSimIBANFrom).val(), 
                                                                            description: $(transactionSimDescription).val(), 
                                                                            amount: $(transactionSimAmount).val(),
                                                                            ignoreCurrencyConvert: ignoreCurrencyConvert
                                                                        })))

    if (ans.status == true) {
        $(messageSimulateTransaction).removeClass('alert-danger').removeClass('alert-warning').addClass('alert').addClass('alert-success').html(ans.message).fadeIn('fast', () => {
            $(simulateTransactionButton).attr('disabled', false)
        })

        const children = $(creditCardsList).children()

        for (const child of children) {
            if ($(child).hasClass('active')) {
                $(child).trigger('click', [true])
                break
            }
        }

        ignoreCurrencyConvert = false
    }

    else {

        if (ans.status == -1) {
            $(messageSimulateTransaction).removeClass('alert-success').removeClass('alert-danger').addClass('alert').addClass('alert-warning').html(ans.message).fadeIn('fast', () => {
                const divOtherIbans = document.createElement('div')
                $(divOtherIbans).css('display', 'none')
                const otherIbans = ans.arg0

                for (const otherIban of otherIbans) {
                    const wrapper = document.createElement('div')
                    $(wrapper).addClass('container')

                    const newChild = document.createElement('a')
                    $(newChild).html(otherIban.IBAN)
                    $(newChild).attr('href', '#')

                    $(newChild).on('click', () => {
                        $(messageSimulateTransaction).hide()

                        if (ans.arg1)
                            $(transactionSimIBANTo).val($(newChild).html())

                        else {
                            const ccChildren = $(creditCardsList).children()

                            for (const child of ccChildren) {
                                const thisIban = $(child).find('.IBAN').html()

                                if (thisIban == $(newChild).html())
                                    $(child).trigger('click')
                            }
                        }
                    })

                    wrapper.appendChild(newChild)
                    divOtherIbans.appendChild(wrapper)
                }

                messageSimulateTransaction.appendChild(divOtherIbans)
                $(divOtherIbans).fadeIn('fast')

                $(simulateTransactionButton).attr('disabled', false)
            })

        }

        else
            $(messageSimulateTransaction).removeClass('alert-success').removeClass('alert-warning').addClass('alert').addClass('alert-danger').html(ans.message).fadeIn('fast', () => {
                $(simulateTransactionButton).attr('disabled', false)
            })
        
        ignoreCurrencyConvert = true
    }
})

$(simulateTransaction).on('click', () => {
    const simulatePage = $('[class*="currentContainer"]')

    if ($(simulatePage).attr('id') === 'buyItemContainer')
        $(simulateTransactionButton).attr('disabled', false)
})

$(transactionSimIBANTo).on('change', async () => {

    if (!$(transactionSimIBANTo).val()) {
        $(transactionSimIBANTo).removeClass('is-valid').removeClass('is-invalid')
        $(transactionSimReceiverName).val('')
        return checkCanSimulate()
    }

    const ans = JSON.parse(await Promise.resolve($.post('/onlineBanking.php', { checkIBANExists: true, IBAN: $(transactionSimIBANTo).val() })))

    if (ans.status == true) {
        $(transactionSimIBANTo).removeClass('is-invalid').addClass('is-valid')
        $(transactionSimReceiverName).val(ans.message)
    }

    else {
        $(transactionSimIBANTo).removeClass('is-valid').addClass('is-invalid')
        $(transactionSimReceiverName).val(ans.message)
    }

    checkCanSimulate()
})

$(transactionSimDescription).on('input', () => {
    const val = $(transactionSimDescription).val()

    if (val == '') {
        $(transactionSimDescription).removeClass('is-valid').removeClass('is-invalid')
        return checkCanSimulate()
    }

    if (val.length < 4 || val.length > 32)
        $(transactionSimDescription).removeClass('is-valid').addClass('is-invalid')
    else
        $(transactionSimDescription).removeClass('is-invalid').addClass('is-valid')

    checkCanSimulate()
})

$(transactionSimAmount).on('input', () => {

    const val = $(transactionSimAmount).val()

    if (val == '') {
        $(transactionSimAmount).removeClass('is-valid').removeClass('is-invalid')
        return checkCanSimulate()
    }

    const currentBalanceAux = $(transactionSimBalance).html()
    const currentBalance = parseFloat(currentBalanceAux.substr(0, currentBalanceAux.length - 4))

    if (isNaN(parseFloat(val)) || val <= 0 || val > currentBalance)
        $(transactionSimAmount).removeClass('is-valid').addClass('is-invalid')
    else
        $(transactionSimAmount).removeClass('is-invalid').addClass('is-valid')

    checkCanSimulate()
})

const appendCreditCard = (data, active) => {
    const a = document.createElement('a')
    $(a).attr('href', '#')
    $(a).addClass('creditCard list-group-item list-group-item-action list-group-item-info border rounded text-center mr-lg-0 mr-2')

    if (active)
        $(a).addClass('active')
    else
        $(a).addClass('mt-lg-2 mt-sm-0')

    const mainDiv = document.createElement('div')
    
    $(mainDiv).addClass('row text-left')

    const col9 = document.createElement('div')
    $(col9).addClass('col-9')

        const row1 = document.createElement('div')
        $(row1).addClass('row')
    
            const small1 = document.createElement('small')
            $(small1).addClass('pre').html(data.type)
            row1.appendChild(small1)


        const row2 = document.createElement('div')
        $(row2).addClass('row')

            const small2 = document.createElement('small')
            $(small2).addClass('creditCardListBalance text-muted').html(data.currency + ' [ ' + data.balance + ' ]')
            row2.appendChild(small2)


    col9.appendChild(row1)
    col9.appendChild(row2)

    const colTextRight = document.createElement('div')
    $(colTextRight).addClass('col text-right')

        const small3 = document.createElement('small')
            const img = document.createElement('img')
            $(img).css('width', '15').css('height', '15').addClass('rounded').attr('src', './Images/countryFlags/' + data.currency.substr(0, 2) + '.png')
            small3.appendChild(img)


    colTextRight.appendChild(small3)


    mainDiv.appendChild(col9)
    mainDiv.appendChild(colTextRight)

    a.appendChild(mainDiv)


    const iban = document.createElement('div')
    $(iban).addClass('row justify-content-start mt-2 text-break-lg')

        const small4 = document.createElement('small')
        $(small4).addClass('IBAN').html(data.IBAN)
        iban.appendChild(small4)

    a.appendChild(iban)

    creditCardsList.appendChild(a)

    if ($(creditCardsList).children().length == 1) {
        $('#missingCreditCards').hide()
        $('#allCreditCards').show()
        $(simulateTransaction).show()
        creditCardOnClick(a)
        $(a).trigger('click')
    }

    else
        creditCardOnClick(a)


}

const creditCardOnClick = child => {
    const currentIBAN = $(child).find('.IBAN').html()

    $(child).on('click', async (e, arg) => {

        const IBAN = currentIBAN

        $('[class*="currentDataPage"]').hide()
        $('[class*="creditCard"]').addClass('disabled')
        $(creditCardMainDataSpinner).fadeIn('fast')
        $(details).attr('disabled', true)
        $(transactionsButton).attr('disabled', true)
        $(createCreditCardButton).attr('disabled', true)
        $(simulateTransaction).attr('disabled', true)
        $(simulateTransactionButton).attr('disabled', true)

        const timeBefore = performance.now()
        const data = JSON.parse(await Promise.resolve($.post('/onlineBanking.php', { IBAN: IBAN })))
        const timeAfter = performance.now()

        if (timeAfter - timeBefore < 200)
            await new Promise(resolve => { setTimeout(resolve, timeBefore - timeAfter + 200) })

        $(transactionSimIBANFrom).val(IBAN)
        $(transactionSimImg).attr('src', './' + data.img)
        $(transactionSimBalance).html(data.balance)
        $(transactionSimCurrency).html(data.currency)

        const actualBalance = data.balance.substr(0, data.balance.length - 3)

        $(child).find('.creditCardListBalance').html(data.currency + ' [ ' + actualBalance + ' ]')

        $(iban).html(IBAN)
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
        $(createCreditCardButton).attr('disabled', false)
        $(simulateTransaction).attr('disabled', false)
        $(simulateTransactionButton).attr('disabled', false)

        if (arg == null) {
            $(transactionSimAmount).trigger('input')
        }

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
            
            const mainDiv = document.createElement('div')
            $(mainDiv).addClass('row align-items-center')

                const col0 = document.createElement('div')
                $(col0).addClass = 'col-0';

                    const icon = document.createElement('i')
                    $(icon).addClass('fas fa-' + (transaction.type.indexOf('Received') != -1 ? 'plus' : 'minus') + '-circle')
                
                col0.appendChild(icon)
                mainDiv.appendChild(col0)

                const colSnd = document.createElement('div')
                $(colSnd).addClass('col')

                    const fstRow = document.createElement('div')
                    $(fstRow).addClass('row text-left')
                    
                        const nxtCol = document.createElement('div')
                        $(nxtCol).addClass('col')

                            const small = document.createElement('small')
                            $(small).html(transaction.type)

                        nxtCol.appendChild(small)
                    
                    fstRow.appendChild(nxtCol);
                    colSnd.appendChild(fstRow)

                    const sndRow = document.createElement('div')
                    $(sndRow).addClass('row text-left')

                        const nxtCol2 = document.createElement('div')
                        $(nxtCol2).addClass('col font-weight-bold font-italic')

                            const small2 = document.createElement('small')
                            $(small2).html(transaction.amount + ' ' + data.currency)

                        nxtCol2.appendChild(small2)
                    
                    sndRow.appendChild(nxtCol2)
                    colSnd.appendChild(sndRow)

                mainDiv.appendChild(colSnd)
            

            newTransaction.appendChild(mainDiv)

            newTransaction.addEventListener('click', () => {
                $(transactionDate).html(transaction.date)
                $(transactionDescription).html(transaction.description)
                $(transactionAmount).html(transaction.amount + ' ' + data.currency)
                $(transactionBalance).html(transaction.balance + ' ' + data.currency)
                $(transactionReference).html(transaction.reference)
            })

            transactionsList.appendChild(newTransaction)
        }

        $(transactionsList).fadeIn('slow')
    })
}


window.onload = async () => {

    $('#mainDiv').fadeIn('slow')

    $(creditCardTransactionsData).css('height', $(creditCardMainData).height() + 8)

    $('#allCreditCards').hide()
    $('#mainDiv').hide()

    const creditCards = JSON.parse(await Promise.resolve($.post('/onlineBanking.php', { getCards: true })))

    $('#mainDiv').fadeIn('slow')

    if (creditCards.message) {
        $('#missingCreditCards').hide()
        $('#allCreditCards').fadeIn('slow')
        for (const creditCard of creditCards.message) {
            appendCreditCard(creditCard)
        }
    }


    const children = $(creditCardsList).children()

    if (!$(children).length)
        return


    $(creditCardsList).children()[0].click()

}