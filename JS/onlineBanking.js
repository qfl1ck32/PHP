const currency = document.getElementById('currency')
const createCreditCard = document.getElementById('createCreditCard')
const messageCreateCreditCard = document.getElementById('messageCreateCreditCard')

$(createCreditCard).click(async () => {
    const ans = JSON.parse(await Promise.resolve($.post('./API/createCreditCard.php', { currency: currency.value })))

    console.log(ans)

    $(messageCreateCreditCard).removeClass().html(ans.message).addClass('alert').addClass(ans.status ? 'alert-success' : 'alert-danger').fadeIn('fast')
})