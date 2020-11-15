const loginSwitch = document.getElementById("loginSwitchButton")
const register = document.getElementById("registerForm")
const login = document.getElementById("loginForm")
const recoveryForm = document.getElementById("recoverForm")

const registerButton = document.getElementById("registerButton")
const loginButton = document.getElementById("loginButton")
const forgotPassword = document.getElementById("forgotPassword")

const username = document.getElementById("username")
const email = document.getElementById("email")
const password = document.getElementById('password')
const confirmPassword = document.getElementById('confirmPassword')

const usernameExists = document.getElementById("usernameExists")
const emailExists = document.getElementById("emailExists")

const passwordPattern = document.getElementById("passwordPattern")
const passwordMatch = document.getElementById("passwordMatch")
const passwordShouldContain = document.getElementById("passwordShouldContain")

const usernamePattern = document.getElementById("usernamePattern")
const emailPattern = document.getElementById("emailPattern")

const usernameEmailLogin = document.getElementById('usernameEmailLogin')
const passwordLogin = document.getElementById('passwordLogin')
const rememberMe = document.getElementById('rememberMe')

const message = document.getElementById('message')

const recoveryData = document.getElementById('usernameEmail')
const recoveryButton = document.getElementById('recoveryButton')
const recoveryMessage = document.getElementById('messageRec')

const signIn = 'Sign-in'
const signUp = 'Sign-up'

let signInReplace = false

window.onload = () => {
    if ($(message).text().replace(/ /g, '').length > 1) {
        $(message).fadeIn('fast')
    }
}

const wait = async time => {
    await Promise.resolve(setTimeout(() => {}, time))
}

const checkCanRegister = () => {
    
    if ($(password).val() != $(confirmPassword).val()) {
        $(resetButton).attr('disabled', true)
        return false
    }

    for (const elem of [username, email, password, confirmPassword])
        if (!elem.value || $(elem).hasClass('is-invalid')) {
            $(registerButton).attr('disabled', true)
            return false
        }
    
    $(registerButton).attr('disabled', false)
    return true
}

const checkCanLogin = () => {
    if(usernameEmailLogin.value.length > 1 && passwordLogin.value.length > 5) {
        $(loginButton).attr('disabled', false)
        return true
    }

    $(loginButton).attr('disabled', true)
    return false
}

const checkCanRecover = () => {
    if ($(recoveryData).val().length > 1) {
        $(recoveryButton).attr('disabled', false)
        return true
    }    
    
    $(recoveryButton).attr('disabled', true)
    return false
}

const checkPattern = password => {
    let passwordNeeds = []

    if (password.toLowerCase() === password)
        passwordNeeds.push("one capital letter")
    
    if (password.length < 6)
        passwordNeeds.push("at least 6 characters")

    return passwordNeeds
}

const resendVerification = async email => {
    const ans = await Promise.resolve($.post('./API/resendVerification.php', { email: email }))

    $(message).html(ans)
}

$(username).on('input', () => {

    $(usernameExists).hide()

    const usernameValue = username.value

    if (!usernameValue.length) {
        $(usernamePattern).hide()
        return $(username).removeClass('is-valid').removeClass('is-invalid')
    }

    if (!(/^.{2,15}$/.test(usernameValue))) {
        $(username).removeClass('is-valid').addClass('is-invalid')
        $(usernamePattern).fadeIn('fast')
    }

    else {
        $(username).removeClass('is-invalid').addClass('is-valid')
        $(usernamePattern).hide()
    }

    checkCanRegister()

})

email.addEventListener("input", () => {
    $(emailExists).hide()

    const emailValue = email.value

    if (!emailValue.length) {
        $(emailPattern).hide()
        return $(email).removeClass('is-valid').removeClass('is-invalid')
    }

    if (!(/\S+@\S+\.\S+/.test(emailValue))) {
        $(email).removeClass('is-valid').addClass('is-invalid')
        $(emailPattern).fadeIn('fast')
    }

    else {
        $(email).removeClass('is-invalid').addClass('is-valid')
        $(emailPattern).hide()
    }

    checkCanRegister()
})

password.addEventListener("input", () => {

    const confirmPass = confirmPassword.value
    const actualPass = password.value
    const missingProperties = checkPattern(actualPass)

    if (confirmPass != "" || (confirmPass == actualPass))
        $(passwordMatch).hide()

    if (missingProperties.length == 0 || actualPass == "") {
        $(password).removeClass('is-invalid').addClass('is-valid')

        if (actualPass == "")
            $(password).removeClass('is-valid')

        
        checkCanRegister()
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
 
    checkCanRegister()
    
})

confirmPassword.addEventListener("input", () => {

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

    checkCanRegister()

})


usernameEmailLogin.addEventListener("input", () => {
    $(message).hide().empty()

    checkCanLogin()
})

passwordLogin.addEventListener('input', () => {
    $(message).hide().empty()
    
    checkCanLogin()
})



forgotPassword.addEventListener("click", async () => {

    const currentActive = $('[class*="formTab active"]')
    const currentTabText = $(login).hasClass('active') ? 'Sign-in' : 'Sign-up'
    const buttons = $('[class*="formbtn"]')
    
    buttons.prop('disabled', true)

    currentActive.fadeOut('fast', () => {

        $(currentActive)[0].reset()
        
        $('[class*="form-control-feedback"]').hide()
        $('[class*="alert"]').hide()
        $('[class*="form-control"]').removeClass('is-valid').removeClass('is-invalid')

        let currentTab = recoveryForm

        if ($(recoveryForm).hasClass('active')) {
            currentTab = $(forgotPassword).text() === 'Sign-in' ? login : register

            $(forgotPassword).html('Forgot password')
        }
        
        else
            $(forgotPassword).html(currentTabText)

        $(currentTab).fadeIn('fast').addClass('active')
        $(currentActive).removeClass('active')
        buttons.not('[class*="mainBtn"]').prop('disabled', false)
    })
})

loginSwitch.addEventListener("click", async () => {


    const nextTabName = $(loginSwitch).text()
    const currentActive = $('[class*="formTab active"]')
    const buttons = $('[class*="formbtn"]')

    buttons.prop('disabled', true)

    $(forgotPassword).html('Forgot password')

    $(currentActive).fadeOut('fast', () => {

        $(currentActive)[0].reset()

        if (nextTabName == 'Sign-up') {
            $('[class*="form-control-feedback alert"]').hide()
            $('[class*="alert"]').hide()
            $('[class*="form-control"]').removeClass('is-valid').removeClass('is-invalid')
        }

        const nextTab = nextTabName == 'Sign-in' ? login : register

        $(loginSwitch).html(nextTabName === 'Sign-in' ? 'Sign-up' : 'Sign-in')
        $(nextTab).fadeIn('fast').addClass('active')
        $(currentActive).removeClass('active')
        buttons.not('[class*="mainBtn"]').prop('disabled', false)
    })
    
})



loginButton.addEventListener("click", async e => {

    e.preventDefault()

    if (!checkCanLogin())
        return

    $(loginButton).prop('disabled', true)

    const ans = JSON.parse(await Promise.resolve($.post('./Login.php', { data: usernameEmailLogin.value, password: passwordLogin.value, signInReplace: signInReplace }))) // remember: rememberMe.checked,

    if (ans.status == true)
        return window.location = '/Index.php'

    signInReplace = ans.status == 2

    if (!$(message).html() || $(message).html() != ans.message) {
        $(message).html(ans.message).fadeIn('fast', () => {
            $(loginButton).prop('disabled', false)
        })
        $(message).removeClass('alert-info').addClass('alert-danger')
    }

    else {
        $(message).show().addClass('shake').on('animationend', () => {
            $(loginButton).prop('disabled', false)
            $(message).removeClass('shake')
        })
    }

})


registerButton.addEventListener("click", async e => {

    e.preventDefault()

    if (!checkCanRegister())
        return

    $(registerButton).attr('disabled', true)

    const ans = JSON.parse(await Promise.resolve($.post('./API/Register.php', { username: username.value, email: email.value, password: password.value, confirmPassword: confirmPassword.value })))

    if (ans.success) {

        $(register)[0].reset()

        $(message).html('You have succesfully registered! Check your e-mail at ' + ans.email + ' to confirm your account.').removeClass('alert-danger').addClass('alert-success').fadeIn('fast', () => {
            $('[class*="form-control"]').removeClass('is-valid')
        })

        return loginSwitch.click()
    }


    switch(ans.error) {
        case 'userexists':
            $(username).trigger('focusout')
            $(email).trigger('focusout')
            break

        case 'usernamePattern':
            $(usernamePattern).css('display', 'block')
            break

        case 'emailPattern':
            $(emailPattern).css('display', 'block')
            break

        case 'differentPasswords':
            $(passwordMatch).css('display', 'block')
            break
    }

    $(registerButton).attr('disabled', false)
})

recoveryData.addEventListener('input', () => {
    $(recoveryMessage).hide()
    
    checkCanRecover()
})


recoveryButton.addEventListener("click", async e => {

    e.preventDefault()

    if (!checkCanRecover())
        return

    $(recoveryButton).attr('disabled', true)

    const ans = JSON.parse(await Promise.resolve($.post('./API/recoverPassword.php', { data: recoveryData.value })))

    if (ans.status == true)
        $(recoveryMessage).removeClass('alert-danger').addClass('alert-success')
    else
        $(recoveryMessage).removeClass('alert-success').addClass('alert-danger')

    if ($(recoveryMessage).html() == "" || $(recoveryMessage).html() != ans.message) {
        $(recoveryMessage).html(ans.message).fadeIn('fast', () => {
            $(recoveryButton).attr('disabled', false)
        })
    }

    else {
        $(recoveryMessage).show().addClass('shake').on('animationend', () => {
            $(recoveryButton).attr('disabled', false)
            $(recoveryMessage).removeClass('shake')
        })
    }

})

$(() => {
    $('#username').on('focusout', () => {
        $.post('./API/check.php', { data: username.value }, invalid => {
            if (invalid === '1') {
                $(username).removeClass('is-valid').addClass('is-invalid')
                $(usernameExists).fadeIn('fast')
            }
            checkCanRegister()
        })
    })

    $('#email').on('focusout', () => {
        $.post('./API/check.php', { data: email.value }, invalid => {
            if (invalid === '1') {
                $(email).removeClass('is-valid').addClass('is-invalid')
                $(emailExists).fadeIn('fast')
            }
            checkCanRegister()
        })
    })
})