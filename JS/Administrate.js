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
        noCreditCards = get('noCreditCards'),

        filterTransactionsByDateDiv = get('filterTransactionsByDateDiv'),

        transactionsFromDate = get('transactionsFromDate'),
        transactionsToDate = get('transactionsToDate'),

        filterTransactionsByDateButton = get('filterTransactionsByDate'),

        exportTransactions = get('exportTransactions'),

        usersList = get('usersList'),

        modify = get('modify'),

        creditCardModifyData = get('creditCardModifyData'),

        modifyBalance = get('modifyBalance'),
        modifyBalanceButton = get('modifyBalanceButton'),

        modifyAlert = get('modifyAlert'),
        
        switchToBank = get('switchToBank'),

        switchToPersonal = get('switchToPersonal'),
        
        personalDataDiv = get('personalDataDiv'),
        bankData = get('bankData'),

        missingCreditCards = get('missingCreditCards'),
        missingPersonalData = get('missingPersonalData'),

        personalDataOld = get('personalDataOld'),
        personalDataNew = get('personalDataNew'),

        rejectPersonalDataChange = get('rejectPersonalDataChange'),
        acceptPersonalDataChange = get('acceptPersonalDataChange')

$(createCreditCard).click(async () => {
    $(createCreditCard).attr('disabled', true)

    const ID = $('.user.active').find('.ID').html()

    const ans = JSON.parse(await Promise.resolve($.post('./API/createCreditCard.php', { currencyId: createCardWithCurrency.value, ID: ID })))

    if (ans.status == true) {
        const ID = $('.user.active').find('.ID').html()
        appendCreditCard(ans.arg0, false, ID)
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

const getPage = name => {

    switch (name) {
        case 'creditCardMainData':
            return creditCardMainData

        case 'creditCardTransactionsData':
            return creditCardTransactionsData
        case 'creditCardModifyData':
           return creditCardModifyData
        case 'personalDataDiv':
            return personalDataDiv
        case 'bankData':
            return bankData
        case 'sendMoneyContainer':
            return sendMoneyContainer
        case 'buyItemContainer':
            return buyItemContainer
    }

}

const switchPages = (button, buttonClassID, pageClassID) => {
    
    if ($(button).hasClass('active'))
        return

    const activeButton = $(buttonClassID + '.active')
    const associatedPage = $(activeButton).attr('associatedPage')
    const actualPage = getPage(associatedPage)

    $(activeButton).removeClass('active')

    $(button).addClass('active')

    const newAssociatedPage = $(button).attr('associatedPage')
    const newActualPage = getPage(newAssociatedPage)
    
    const otherButtons = $(buttonClassID)

    for (let i = 0; i < otherButtons.length; ++i) {
        $(otherButtons[i]).attr('disabled', true)
    }

    $(actualPage).fadeOut('fast', () => {
        $(newActualPage).fadeIn('fast')

        for (let i = 0; i < otherButtons.length; ++i) {
            $(otherButtons[i]).attr('disabled', false)
        }
    })

    $(actualPage).removeClass(pageClassID)
    $(newActualPage).addClass(pageClassID)
}

$(details).on('click', () => {
    switchPages(details, '.ccDataButtons', 'currentDataPage')
    // switchBetween(details, transactionsButton, creditCardTransactionsData, creditCardMainData)

    // $(creditCardTransactionsData).removeClass('currentDataPage')
    // $(creditCardMainData).addClass('currentDataPage')
})

$(transactionsButton).on('click', () => {
    switchPages(transactionsButton, '.ccDataButtons', 'currentDataPage')
    // switchBetween(transactionsButton, details, creditCardMainData, creditCardTransactionsData)

    // $(creditCardMainData).removeClass('currentDataPage')
    // $(creditCardTransactionsData).addClass('currentDataPage')
})

$(modify).on('click', () => {
    switchPages(modify, '.ccDataButtons', 'currentDataPage')
})

$(sendMoney).on('click', () => {
    switchPages(sendMoney, '.sendBuyButtons', 'currentContainer')
    // switchBetween(sendMoney, buyItem, buyItemContainer, sendMoneyContainer)
    
    $(simulateTransactionButton).attr('disabled', true)

    // $(sendMoneyContainer).addClass('currentContainer')
    // $(buyItemContainer).removeClass('currentContainer')
})

$(buyItem).on('click', () => {
    switchPages(buyItem, '.sendBuyButtons', 'currentContainer')
    // switchBetween(buyItem, sendMoney, sendMoneyContainer, buyItemContainer)
    
    $(simulateTransactionButton).attr('disabled', false)

    // $(buyItemContainer).addClass('currentContainer')
    // $(sendMoneyContainer).removeClass('currentContainer')
})

$(switchToBank).on('click', () => {
    switchPages(switchToBank, '.switchContentButton')
})

$(switchToPersonal).on('click', () => {
    switchPages(switchToPersonal, '.switchContentButton')
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

        const ID = $('.user.active').find('.ID').html()

        const ans = JSON.parse(await Promise.resolve($.post('/Administrate.php', { buyItem: true, IBAN: $(transactionSimIBANFrom).val(), itemId: itemId, ID: ID})))

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

    const ID = $('.user.active').find('.ID').html()

    const ans = JSON.parse(await Promise.resolve($.post('/Administrate.php', { 
                                                                            simulateTransaction: true, 
                                                                            toIBAN: $(transactionSimIBANTo).val(), 
                                                                            fromIBAN: $(transactionSimIBANFrom).val(), 
                                                                            description: $(transactionSimDescription).val(), 
                                                                            amount: $(transactionSimAmount).val(),
                                                                            ignoreCurrencyConvert: ignoreCurrencyConvert,
                                                                            ID: ID
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

    const ID = $('.user.active').find('.ID').html()

    const ans = JSON.parse(await Promise.resolve($.post('/Administrate.php', { checkIBANExists: true, IBAN: $(transactionSimIBANTo).val(), ID: ID })))

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

const appendCreditCard = (data, active, ID) => {

    if (!creditCardsList)
        return

    const a = document.createElement('a')
    $(a).attr('href', '#')
    $(a).addClass('creditCard list-group-item list-group-item-action list-group-item-info border rounded text-center mr-2')

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
        //$(a).trigger('click')
    }

    creditCardOnClick(a, ID)
}

const appendUser = (data, active) => {

    if (!usersList)
        return

    const a = document.createElement('a')
    $(a).attr('href', '#')
    $(a).addClass('user list-group-item list-group-item-action list-group-item-info border rounded text-center mr-2')

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
            $(small1).addClass('pre').html(data.username)
            row1.appendChild(small1)


        const row2 = document.createElement('div')
        $(row2).addClass('row')

            const small2 = document.createElement('small')
            $(small2).addClass('text-muted').html(data.email)
            row2.appendChild(small2)


    col9.appendChild(row1)
    col9.appendChild(row2)

    mainDiv.appendChild(col9)

    a.appendChild(mainDiv)

    const ID = document.createElement('div')
    $(ID).addClass('row justify-content-start mt-2 text-break-lg')

        const small4 = document.createElement('small')
        $(small4).addClass('ID').html(data.ID)
        ID.appendChild(small4)

    a.appendChild(ID)

    usersList.appendChild(a)

    userOnClick(a)

    if ($(usersList).children().length == 1) {
        // $('#missingCreditCards').hide()
        // $('#allCreditCards').show()
        // $(simulateTransaction).show()
        
        $(a).trigger('click')
    }

}

const userOnClick = child => {
    const ID = $(child).find('.ID').html()

    $(child).on('click', async (e, arg) => {

        const switchButtons = $(child).find('.switchContextButton')

        $('[class*="user"]').addClass('disabled')

        await loadPage(ID)

        $('.switchContextButton').removeClass('active')
        $(switchButtons[0]).addClass('active')

        $('[class*="user"]').removeClass('disabled')
        $('[class*="user"]').removeClass('active')
        $(child).addClass('active')
    })
}

const creditCardOnClick = (child, ID) => {
    const currentIBAN = $(child).find('.IBAN').html()

    $(child).on('click', async (e, arg) => {

        $(transactionsFromDate).val('')
        $(transactionsToDate).val('')

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
        const data = JSON.parse(await Promise.resolve($.post('/Administrate.php', { IBAN: IBAN, ID: ID })))

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

        $(modifyBalance).val(data.balance.substr(0, data.balance.length - 4))

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

        addTransactions(data, false)
    })
}

$(modifyBalanceButton).on('click', async () => {
    const IBAN = $('.creditCard.active').find('.IBAN').html()

    const ans = JSON.parse(await Promise.resolve($.post('/Administrate.php', { IBAN: IBAN, modifyBalance: $(modifyBalance).val() })))

    if (ans.status) {
        $(modifyAlert).removeClass('alert-danger').addClass('alert-success')
    }
    
    else {
        $(modifyAlert).removeClass('alert-success').addClass('alert-danger')
    }

    $(modifyAlert).hide().html(ans.message).fadeIn('slow', () => {
        setTimeout(() => {
            $(modifyAlert).fadeOut('slow')
        }, 2000)
    })
})

const addTransactions = (data, isFilter) => {

    const transactions = data.transactions

    $(transactionsList).empty()
        
    if (!transactions.length) {
        $(exportTransactions).hide()
        if (isFilter)
            return $(missingTransactions).html('There are no transactions between the given dates.').hide().fadeIn('slow')
    
        return $(filterTransactionsByDateDiv).hide(), $(missingTransactions).html('You have no transactions for this credit card.').fadeIn('slow')
    }
    
    $(missingTransactions).hide()
    $(transactionsList).hide()

    let lastDate
    let writeDate = true

    try {
        lastDate = new Date(transactions[0].date).getDate()
    }

    catch (e) {

    }

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

        if (new Date(transaction.date).getDate() < lastDate) {
            lastDate = new Date(transaction.date).getDate()
            const hr = document.createElement('div')
            $(hr).addClass('border-top my-3')
            transactionsList.appendChild(hr)
            writeDate = true
        }

        newTransaction.addEventListener('click', () => {
            $(transactionDate).html(transaction.date)
            $(transactionDescription).html(transaction.description)
            $(transactionAmount).html(transaction.amount + ' ' + data.currency)
            $(transactionBalance).html(transaction.balance + ' ' + data.currency)
            $(transactionReference).html(transaction.reference)
        })

        if (writeDate) {
            const date = document.createElement('div')
            $(date).addClass('text-left text-white mb-2')
                const small = document.createElement('small')

                const data = new Date(transaction.date)

                $(small).html(data.getUTCFullYear() + '-' + (data.getUTCMonth() + 1) + '-' + data.getDate())

                date.appendChild(small)

            transactionsList.appendChild(date)
            writeDate = false
        }

        transactionsList.appendChild(newTransaction)
    }

    $(transactionsList).fadeIn('slow')
    $(filterTransactionsByDateDiv).fadeIn('slow')
    $(exportTransactions).fadeIn('slow')
}

$(filterTransactionsByDateButton).on('click', async () => {
    const fromDate = $(transactionsFromDate).val(), toDate = $(transactionsToDate).val()

    const IBAN = $('.creditCard.active').find('.IBAN').html()
    const ID = $('.user.active').find('.ID').html()

    $(filterTransactionsByDateButton).attr('disabled', true)

    const data = JSON.parse(await Promise.resolve($.post('/Administrate.php', { IBAN: IBAN, filterByDate: true, fromDate: fromDate, toDate: toDate, ID: ID })))

    addTransactions(data, true)

    $(filterTransactionsByDateButton).attr('disabled', false)
})

$(exportTransactions).on('click', async () => {
    const fromDate = $(transactionsFromDate).val(), toDate = $(transactionsToDate).val()
    const IBAN = $('.creditCard.active').find('.IBAN').html()

    $(exportTransactions).attr('data-content', 'Loading...')

    $(exportTransactions).attr('disabled', true)

    const ID = $('.user.active').find('.ID').html()

    const ans = JSON.parse(await Promise.resolve($.post('/exportTransactions.php', { IBAN: IBAN, fromDate: fromDate, toDate: toDate, ID: ID })))

    $(exportTransactions).attr('data-content', ans.message)

    $(exportTransactions).popover('show')
    $(exportTransactions).attr('disabled', false)

    await new Promise(resolve => {
        setTimeout(resolve, 2500)
    })

    $(exportTransactions).popover('hide')
})

const loadPersonalData = async ID => {

    const personalData = JSON.parse(await Promise.resolve($.post('/Administrate.php', { getPersonalData: true, ID: ID })))

        if (personalData.arg0) {
            $(personalDataOld).fadeIn('slow')
            putPersonalData('Old', personalData.arg0)
        }

        else
            $(personalDataOld).hide()

        if (personalData.arg1) {
            $(personalDataNew).fadeIn('slow')
            putPersonalData('New', personalData.arg1)
        }

        else
            $(personalDataNew).hide()
}

const loadPage = async ID => {

    // $('#allCreditCards').hide()

    if (!ID)
        $('#mainDiv').hide()

    if (ID) {

        const creditCards = JSON.parse(await Promise.resolve($.post('/Administrate.php', { getCards: true, ID: ID })))

        if (creditCards.message) {
            $('#missingCreditCards').hide()
            $(missingPersonalData).hide()
            $('#allCreditCards').fadeIn('slow')
            $(createCreditCardButton).fadeIn('slow')
            $(simulateTransaction).fadeIn('slow')
            $(creditCardsList).empty()
            for (const creditCard of creditCards.message) {
                appendCreditCard(creditCard, false, ID)
            }
            $(creditCardsList).children()[0].click()
            $(creditCardTransactionsData).css('height', $(creditCardMainData).height() + 8)
        }

        else {

            if (!creditCards.status)
                $(missingPersonalData).fadeIn('slow')

            else
                $('#missingCreditCards').fadeIn('slow')

            $(simulateTransaction).hide()
            $('#allCreditCards').hide()
            $(creditCardsList).empty()
        }


        await loadPersonalData(ID)


        $('#mainDiv').fadeIn('slow')
    }

    else {
        const users = JSON.parse(await Promise.resolve($.post('/Administrate.php', { getUsers: true })))

        if (users.message) {
            for (const user of users.message) {
                appendUser(user)
            }
        }
    }

    const children = $(creditCardsList).children()

    if (!$(children).length)
        return $('#bankData').removeClass('mx-2')
}

$(acceptPersonalDataChange).on('click', async () => {
    const ID = $('.user.active').find('.ID').html()
    
    await Promise.resolve($.post('/Administrate.php', { ID: ID, acceptChange: true }))
    await loadPersonalData(ID)
})

$(rejectPersonalDataChange).on('click', async () => {
    const ID = $('.user.active').find('.ID').html()
    
    await Promise.resolve($.post('/Administrate.php', { ID: ID, rejectChange: true }))
    await loadPersonalData(ID)
})

const putPersonalData = (type, data) => {
    $('#firstName' + type).val(data.firstName)
    $('#lastName' + type).val(data.lastName)
    $('#dateOfBirth' + type).val(data.dateOfBirth)
    $('#gender' + type).val(data.gender)
    $('#address' + type).val(data.address)
    $('#country' + type).val(data.country)
    $('#state' + type).val(data.state)
    $('#city' + type).val(data.city)
    $('#image' + type).attr('src', data.image)

}

window.onload = async () => {

    loadPage()

}

$(function () {
    $('[data-toggle="popover"]').popover()
})