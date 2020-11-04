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

const emptyFields = document.getElementById("emptyFields")

const recoveryData = document.getElementById('usernameEmail')
const recoveryButton = document.getElementById('recoveryButton')
const recoveryMessage = document.getElementById('messageRec')

const signIn = 'Sign-in'
const signUp = 'Sign-up'

window.onload = () => {
    if ($(message).text().replace(/ /g, '').length != 1)
        $(message).fadeIn('fast')
}

const wait = async time => {
    await Promise.resolve(setTimeout(() => {}, time))
}

const checkInputEmpty = () => {

    const to_verify = [username, email, password]
    const patterns = [ usernamePattern, emailPattern, usernameExists, emailExists]

    for (let inp of to_verify) {
        if (inp.value.length == 0) {
            $(emptyFields).fadeIn('fast')
            return 0
        }
    }

    for (let pattern of patterns) {
        if (pattern.style.display == "block") {
            $(emptyFields).fadeIn('fast')
            return 0
        }
    }

    $(emptyFields).hide()

    return 1
}

const checkPattern = password => {
    const missing = ["one capital letter", "6 characters"]
    let password_needs = []

    let upper = false, pattern_invalid = false
    for (let i = 0; i < password.length; ++i) {
        if (/[A-Z]/.test(password[i])) {
            upper = true
            break
        }
    }

    if (!upper) {
        password_needs.push(missing[0])
        pattern_invalid = true
    }

    if (password.length < 6) {
        password_needs.push(missing[1])
        pattern_invalid = true
    }

    return [pattern_invalid, password_needs]
}

const runEffect = async (elem, effect) => {
    $(elem).prop('disabled', true)
    $(elem).toggleClass(effect)

    await wait(200)

    $(elem).toggleClass(effect)
    $(elem).prop('disabled', false)
}

const shake = async elem => {
    await runEffect(elem, 'shake')
}

const resendVerification = async email => {
    const ans = await Promise.resolve($.post('./API/resendVerification.php', { email: email }))

    $(message).html(ans)
}


username.addEventListener("input", () => {

    $(emptyFields).hide()
    $(usernameExists).hide()

    const username_value = username.value
    let correct = true

    if (!(/^.{2,15}$/.test(username_value))) {
        $(usernamePattern).fadeIn('fast')
        correct = false
    }

    if (correct || username_value.length == 0)
        $(usernamePattern).hide()
})

email.addEventListener("input", () => {

    $(emptyFields).hide()
    $(emailExists).hide()

    const email_value = email.value
    let correct = true

    if (!(/\S+@\S+\.\S+/.test(email_value))) {
        $(emailPattern).fadeIn('fast')
        correct = false
    }

    if (correct || email_value.length == 0)
        $(emailPattern).hide()
})

password.addEventListener("input", () => {

    $(emptyFields).hide()

    const confirm_pass = confirmPassword.value
    const actual_pass = password.value
    const verify = checkPattern(actual_pass)
    
    $(passwordShouldContain).fadeIn('fast')

    if (confirm_pass != "" || (confirm_pass == actual_pass)) {
        const event = new CustomEvent("input")
        confirmPassword.dispatchEvent(event)
    }

    if (verify[0] && actual_pass != "") {
        const childs_text = []
        const childs = passwordShouldContain.querySelectorAll('li')

        if (childs != null)
            for (let li of childs)
                childs_text.push(li.innerHTML)

        for (let current_missing_pattern of childs) {
            if (!(verify[1].includes(current_missing_pattern.innerHTML))) {
                childs[0].parentNode.removeChild(current_missing_pattern)
            }
        }

        $(passwordPattern).fadeIn('fast')

        for (let i = 0; i < verify[1].length; ++i) {
            if (!childs_text.includes(verify[1][i])) {
                const li = document.createElement("li")
                li.innerText = verify[1][i]
                li.className += "fade_in"
                passwordShouldContain.appendChild(li)
            }
        }
    }
    else {
        $(passwordPattern).hide()
        passwordShouldContain.innerHTML = ""
    }
    
})

confirmPassword.addEventListener("input", () => {

    $(emptyFields).hide()

    const actual_pass = password.value
    const confirm_pass = confirmPassword.value

    if (actual_pass == confirm_pass)
        $(passwordMatch).hide()
    else
        $(passwordMatch).fadeIn('fast')

})



usernameEmailLogin.addEventListener("input", () => {
    $(message).hide().empty()
})

passwordLogin.addEventListener('input', () => {
    $(message).hide().empty()
})


forgotPassword.addEventListener("click", async () => {

    const currentActive = $('[class*="formTab active"]')
    const currentTabText = $(login).hasClass('active') ? 'Sign-in' : 'Sign-up'
    const buttons = $('[class*="formbtn"]')
    
    buttons.prop('disabled', true)

    currentActive.fadeOut('fast', () => {

        $(currentActive)[0].reset()
        $('[class*="alert"]').hide()

        let currentTab = recoveryForm

        if ($(recoveryForm).hasClass('active')) {
            currentTab = $(forgotPassword).text() === 'Sign-in' ? login : register

            $(forgotPassword).html('Forgot password')
        }
        
        else
            $(forgotPassword).html(currentTabText)

        $(currentTab).fadeIn('fast').addClass('active')
        $(currentActive).removeClass('active')
        buttons.prop('disabled', false)
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

        if (nextTabName == 'Sign-up')
            $('[class*="alert"]').hide()

        const nextTab = nextTabName == 'Sign-in' ? login : register

        $(loginSwitch).html(nextTabName === 'Sign-in' ? 'Sign-up' : 'Sign-in')
        $(nextTab).fadeIn('fast').addClass('active')
        $(currentActive).removeClass('active')
        buttons.prop('disabled', false)
    })
    
})



loginButton.addEventListener("click", async e => {

    e.preventDefault()

    const ans = await Promise.resolve($.post('./Login.php', { data: usernameEmailLogin.value, password: passwordLogin.value, remember: rememberMe.checked }))

    if (ans == 'true')
        return window.location = '/'


    $(loginButton).prop('disabled', true)

    console.log(ans)

    if (!$(message).html | $(message).html != ans)
        $(message).html(ans).fadeIn('fast').removeClass('alert-info').addClass('alert-danger')


    $(loginButton).prop('disabled', false)
})



registerButton.addEventListener("click", async e => {

    e.preventDefault()

    const check = checkInputEmpty()

    if (!check)
        return await shake(registerButton)

    const verify = checkPattern(password.value)
    

    if (verify[0]) {
        const event = new CustomEvent("input")
        password.dispatchEvent(event)
        return await shake(registerButton)
    }

    if (password.value == confirmPassword.value && check) {

        // get rid of try - catch block. testing purpose only

        let ans = await Promise.resolve($.post('./API/Register.php', { username: username.value, email: email.value, password: password.value, confirmPassword: confirmPassword.value }))

        try {
            ans = JSON.parse(ans)
        }

        catch (err) {
            console.log("Eroare")
            console.log(ans)
        }

        if (ans.success) {

            $(register)[0].reset()

            $(message).html('You have succesfully registered! Check your e-mail at ' + ans.email + ' to confirm your account.').removeClass('alert-danger').addClass('alert-success').fadeIn('fast')

            return loginSwitch.click()
        }


        switch(ans.error) {
            case 'userexists':
                $(username).trigger('focusout')
                $(email).trigger('focusout')
                break

            case 'emptyfields':
                $(emptyFields).hide()
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

        await shake(registerButton)
    }

    else {

        if (check)
            passwordMatch.style.display = "block"

        await shake(registerButton)
    }
})

recoveryData.addEventListener('input', () => {
    $(recoveryMessage).hide()
})


recoveryButton.addEventListener("click", async e => {

    e.preventDefault()


    const ans = JSON.parse(await Promise.resolve($.post('./API/recoverPassword.php', { data: recoveryData.value })))


    if (ans.error) {

        $(recoveryMessage).removeClass('alert-success').addClass('alert-danger')

        if ($(recoveryMessage).html == "" || !(recoveryMessage).html != ans.error)
            $(recoveryMessage).html(ans.error)
        
    }

    else {
        $(recoveryMessage).removeClass('alert-danger').addClass('alert-success')
    }


    $(recoveryMessage).fadeIn('fast')
    $(recoveryMessage).html(ans.message)
})

$(() => {
    $('#username').on('focusout', () => {
        $.post('./API/check.php', { data: username.value }, invalid => {
            if (invalid === '1')
                $(usernameExists).fadeIn('fast')
            else
                $(usernameExists).hide()
        })
    })

    $('#email').on('focusout', () => {
        $.post('./API/check.php', { data: email.value }, invalid => {
            if (invalid === '1')
                $(emailExists).fadeIn('fast')
            else
                $(emailExists).hide()
        })
    })
})