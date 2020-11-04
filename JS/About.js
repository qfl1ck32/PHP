const home = document.getElementById('home')
const info = document.getElementById('info')
const contact = document.getElementById('contact')

const main = document.getElementById('main')
const whatDoWeDo = document.getElementById('whatDoWeDo')

const loginSystemInfo = document.getElementById('loginSystemInfo')
const loginSystemButton = document.getElementById('loginSystemButton')

const onlineBankingButton = document.getElementById('onlineBankingButton')
const onlineBankingInfo = document.getElementById('onlineBankingInfo')

const settingsButton = document.getElementById('settingsButton')
const settingsInfo = document.getElementById('settingsInfo')

const switchActive = (elem, classPattern) => {
    $('[class*="' + classPattern + '"]').removeClass('active')
    $(elem).addClass('active')
}

const switchFade = (to, spd, tabsSelector, buttonsSelector) => {

    const currentTab = $('[class*="' + tabsSelector + '"]')
    $('[class*="' + buttonsSelector + '"]').addClass('disabled')

    currentTab.fadeOut(spd, () => {
        $(to).fadeIn(spd)
        currentTab.removeClass(tabsSelector)
        $(to).addClass(tabsSelector)
        $('[class*="' + buttonsSelector + '"]').removeClass('disabled')
    })
}

const switchAbout = (button, info) => {
    if ($(button).hasClass('active'))
        return

    switchActive(button, 'infobtn')
    switchFade(info, 'fast', 'currentInfo', 'infobtn')
}

$(loginSystemButton).click(() => {
    switchAbout(loginSystemButton, loginSystemInfo)
})

$(onlineBankingButton).click(() => {
    switchAbout(onlineBankingButton, onlineBankingInfo)
})

$(settingsButton).click(() => {
    switchAbout(settingsButton, settingsInfo)
})

$(home).click(() => {
    window.location.href = '/'
})