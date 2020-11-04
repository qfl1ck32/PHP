const password = document.getElementById("password")
const confirmPassword = document.getElementById("confirm_password")
const resetButton = document.getElementById("reset_button")

const passwordPattern = document.getElementById("password_pattern")
const passwordMatch = document.getElementById("password_match")
const passwordShouldContain = document.getElementById("password_should_contain")

const emptyFields = document.getElementById('emptyFields')
const message = document.getElementById('message')

const checkInputEmpty = () => {

    const to_verify = [password, confirmPassword]
    const patterns = [passwordPattern]

    for (let inp of to_verify) {
        if (inp.value.length == 0) {
            $(emptyFields).fadeIn('fast')
            return 0
        }
    }

    for (let pattern of patterns) {
        if (pattern.style.display == "block") {
            $(emptyFields).hide()
            return 0
        }
    }

    $(emptyFields).hide()

    return 1
}

const checkPattern = password => {
    const missing = ["one capital letter", "6 characters"]
    let password_needs = []

    let upper = false, pattern_invalid = false;
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

password.addEventListener("input", () => {

    $(emptyFields).hide()


    const confirm_pass = confirmPassword.value;
    const actual_pass = password.value;
    const verify = checkPattern(actual_pass);

    if (confirm_pass != "" || (confirm_pass == actual_pass)) {
        const event = new CustomEvent("input");
        confirmPassword.dispatchEvent(event);
    }

    if (verify[0] && actual_pass != "") {
        const childs_text = [];
        const childs = passwordShouldContain.querySelectorAll('li');

        if (childs != null)
            for (let li of childs)
                childs_text.push(li.innerHTML);

        for (let current_missing_pattern of childs) {
            if (!(verify[1].includes(current_missing_pattern.innerHTML))) {
                childs[0].parentNode.removeChild(current_missing_pattern);
            }
        }

        $(passwordPattern).fadeIn('fast')

        for (let i = 0; i < verify[1].length; ++i) {
            if (!childs_text.includes(verify[1][i])) {
                const li = document.createElement("li");
                li.innerText = verify[1][i];
                li.className += "fade_in";
                passwordShouldContain.appendChild(li);
            }
        }
    }
    else {
        $(passwordPattern).hide()
        $(passwordShouldContain).html("")
    }
    
})

confirmPassword.addEventListener("input", () => {

    $(emptyFields).hide()

    const actual_pass = password.value;
    const confirm_pass = confirmPassword.value;

    if (actual_pass == confirm_pass)
        $(passwordMatch).hide()
    else
        $(passwordMatch).fadeIn('fast')

})

function check_input_empty() {

    const to_verify = [password, confirm_password];

    for (let inp of to_verify) {
        if (inp.value.length == 0) {
            $(reset_button).prop('disabled', true)
            return 0;
        }
    }

    $(reset_button).prop('disabled', false)

    return 1;
}

resetButton.addEventListener("click", async e => {

    e.preventDefault()

    const check = checkInputEmpty()

    if (!check)
        return

    const verify = checkPattern(password.value)
    

    if (verify[0]) {
        const event = new CustomEvent("input")
        password.dispatchEvent(event)
        return
    }

    if (password.value == confirmPassword.value && check) {
        let ans = JSON.parse(await Promise.resolve($.post('/Reset.php', { password: password.value, confirmPassword: confirmPassword.value })))

        if (ans.error) {
            if (ans.error != message.innerHTML)
                message.innerHTML = ans.error

            return
        }


        $(message).html(ans.message).removeClass('alert-danger').addClass('alert-success').fadeIn('fast')
        
        await new Promise(resolve => {
            setTimeout(resolve, 2000)
        })

        return window.location.href = '/Login.php';
    }
})