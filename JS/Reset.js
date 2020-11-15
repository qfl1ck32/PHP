const get = elem => {
    return document.getElementById(elem)
}

const   password = get("password"),
        confirmPassword = get("confirmPassword"),
        resetButton = get("reset_button"),

        passwordPattern = get("passwordPattern"),
        passwordMatch = get("passwordMatch"),
        passwordShouldContain = get("passwordShouldContain"),

        message = get('message')


const checkPattern = password => {
    let passwordNeeds = []

    if (password.toLowerCase() === password)
        passwordNeeds.push("one capital letter")
    
    if (password.length < 6)
        passwordNeeds.push("at least 6 characters")

    return passwordNeeds
}

const checkCanReset = () => {

    if ($(password).val() != $(confirmPassword).val())
        $(resetButton).attr('disabled', true)

    for (const elem of [password, confirmPassword])
        if (!$(elem).val() || $(elem).hasClass('is-invalid')) {
            $(resetButton).attr('disabled', true)
            return false
        }

    $(resetButton).attr('disabled', false)
    return true
}

password.addEventListener("input", () => {

    $(confirmPassword).trigger('input')

    const confirmPass = confirmPassword.value
    const actualPass = password.value
    const missingProperties = checkPattern(actualPass)

    if (confirmPass == actualPass)
        $(passwordMatch).hide()

    if (missingProperties.length == 0 || actualPass == "") {
        $(password).removeClass('is-invalid').addClass('is-valid')

        if (actualPass == "")
            $(password).removeClass('is-valid')

        
        checkCanReset()
        return $(passwordPattern).hide()
    }
    
        
    const childNodeProperty = []
    const allChildren = passwordShouldContain.querySelectorAll('li')

    if (allChildren != null)
        for (let li of allChildren)
            childNodeProperty.push(li.innerHTML)

    for (let current_missing_pattern of allChildren) {
        if (!(missingProperties.includes(current_missing_pattern.innerHTML))) {
            allChildren[0].parentNode.removeChild(current_missing_pattern)
        }
    }

    for (let i = 0; i < missingProperties.length; ++i) {
        if (!childNodeProperty.includes(missingProperties[i])) {
            const li = document.createElement("li")
            li.innerText = missingProperties[i]
            li.className += "fade_in"
            passwordShouldContain.appendChild(li)
        }
    }

    $(password).removeClass('is-valid').addClass('is-invalid')
    $(passwordPattern).fadeIn('fast')

    checkCanReset()
})

$(confirmPassword).on("input", () => {

    if (!$(confirmPassword).val()) {
        $(confirmPassword).removeClass('is-invalid').removeClass('is-valid')
        return $(passwordMatch).hide()
    }

    if (password.value == confirmPassword.value) {
        $(confirmPassword).removeClass('is-invalid').addClass('is-valid')
        $(passwordMatch).hide()
    }

    else {
        $(confirmPassword).removeClass('is-valid').addClass('is-invalid')
        $(passwordMatch).fadeIn('fast')
    }

    checkCanReset()

})

resetButton.addEventListener("click", async e => {

    e.preventDefault()

    if (!checkCanReset())
        return

    $(resetButton).attr('disabled', true)

    const ans = JSON.parse(await Promise.resolve($.post('/Reset.php', { password: password.value, confirmPassword: confirmPassword.value })))

    if (ans.status == false) {
        console.log("Dap. Ai eroare.")
        console.log(ans.message)
        console.log(message.innerHTML)
        if (ans.message != message.innerHTML) {
            $(message).html(ans.message).fadeIn('fast')
            $(resetButton).attr('disabled', false)
        }

        else {
            $(message).addClass('shake').on('animationend', () => {
                $(message).removeClass('shake')
                $(resetButton).attr('disabled', false)
            })
        }

        return
    }


    $(message).html(ans.message).removeClass('alert-danger').addClass('alert-success').fadeIn('fast')
    
    await new Promise(resolve => {
        setTimeout(resolve, 2000)
    })

    return window.location.href = '/Login.php';

})