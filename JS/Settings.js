var countryId, stateId, cityId
let currentFocus

const firstName = document.getElementById('firstName')
const lastName = document.getElementById('lastName')
const dateOfBirth = document.getElementById('dateOfBirth')
const gender = document.getElementById('gender')
const address = document.getElementById('address')

const country = document.getElementById('country')
const countries = document.getElementById('countries')

const city = document.getElementById('city')
const cities = document.getElementById('cities')

const state = document.getElementById('state')
const states = document.getElementById('states')

const update = document.getElementById('update')

const badCountry = document.getElementById('badCountries')
const badState = document.getElementById('badStates')
const badCity = document.getElementById('badCities')

const emptyFields = document.getElementById('emptyFields')

const file = document.getElementById('file')
const image = document.getElementById('image')
const wrapImage = document.getElementById('wrapImage')

const message = document.getElementById('message')
const messageWrap = document.getElementById('messageWrap')

const allElements = [firstName, lastName, dateOfBirth, gender, address, country, city, state, file, update]

const wait = async time => {
    return await new Promise(resolve => {
        setTimeout(resolve, time)
    })
}

window.onload = async () => {

    for (elem of allElements)
        elem.addEventListener("focus", () => {
            hideErrors()
        })

    if (!country.value)
        return

    const ids = JSON.parse(await Promise.resolve($.post('./API/location.php', { getIds: true, countryName: country.value, stateName: state.value, cityName: city.value } )))

    if (ids.status) {
        countryId = ids.message.countryId
        cityId = ids.message.cityId
        stateId = ids.message.stateId

        $(wrapImage).show()
    }

    if (document.getElementById('pendingApproval')) {

        update.innerHTML = 'Can not update at the moment.'
        
        for (elem of allElements)
            $(elem).prop('disabled', true)
    }
}

const checkInputEmpty = () => {

    const to_verify = [firstName, lastName, dateOfBirth, gender, address, country, city, state, file];
    // const patterns = [ usernamePattern, emailPattern, usernameExists, emailExists];
    let returnVal = 1
    for (let inp of to_verify) {
        if (inp.value.length == 0) {
            $(emptyFields).fadeIn('fast')
            returnVal = 0
        }
    }

    if (!returnVal)
        return 0

    /*
    for (let pattern of patterns) {
        if (pattern.style.display == "block") {
            emptyFields.style.display = "none";
            return 0;
        }
    }

    emptyFields.style.display = "none";
    */
    return 1;
}

const hideErrors = () => {
    for (elem of [badCountry, badState, badCity, emptyFields])
        $(elem).hide()
}

const removeChildren = elem => {
    while (elem.firstChild)
        elem.removeChild(elem.lastChild)
}

const removeChildrenMul = (...elems) => {
    for (let elem of elems)
        removeChildren(elem)
}

const removeAllChildren = () => {
    removeChildrenMul(cities, states, countries)
}

const removeActive = elem => {
    for (let i = 0; i < elem.length; ++i)
        elem[i].classList.remove('autocompleteSelected')
}

const empty = (...elems) => {
    for (let elem of elems) {
        elem.value = ''
        elem.disabled = true
    }
}

const disable = (...elems) => {
    for (let elem of elems)
        elem.disabled = true;
}

const addActive = elem => {
    if (!elem)
        return
    
    removeActive(elem)

    if (currentFocus >= elem.length)
        currentFocus = 0
    else if (currentFocus < 0)
            currentFocus = elem.length - 1

    elem[currentFocus].classList.add('autocompleteSelected')
}

const elemToName = elem => {
    switch(elem) {
        case country:
            return 'countries'
        case state:
            return 'states'
        case city:
            return 'cities'
    }
}

const elemToBad = elem => {
    switch(elem) {
        case country:
            return badCountry;
        case city:
            return badCity;
        case state:
            return badState;
    }
}

const createAutocomplete = (data, from, target, id) => {

    if (!from.value)
        return removeChildren(target)

    removeChildren(target)
    
    currentFocus = -1

    const mainDiv = document.createElement('div')
    mainDiv.className = 'selectElementWrap'
    mainDiv.id = from.id + 'List'

    for (let elem of data) {
        const potential = document.createElement('div')

        potential.className = 'selectElement'

        potential.innerHTML = '<a href = "#" class = "text-white" <b>' + elem.name.substr(0, from.value.length) + '</b>' + elem.name.substr(from.value.length) + '</a>'

        potential.addEventListener("click", async e => {
            from.value = elem.name
            window[id] = elem.id
            removeChildren(target)
        })

        mainDiv.appendChild(potential)
    }

    target.append(mainDiv)
}

const handleSelection = async (e, elem) => {
    let child = document.getElementById(elem.id + 'List')
    
    if (child)
        child = child.getElementsByTagName("div");
    
    switch (e.keyCode) {
        case 40:
            ++currentFocus
            addActive(child)
            break
        case 38:
            --currentFocus
            addActive(child)
            break
        case 13:
        case 9:
            if (currentFocus > -1 && child) {
                child[currentFocus].click()
            }
            else
                if (child && child[0])
                    child[0].click()
            break;
    }
}

const checkInput = async elem => {

    const where = elemToName(elem)

    const verify = JSON.parse(await Promise.resolve($.post('./API/location.php', { where: where, value: elem.value, checkExists: true})))

    return verify
}

const handleBlurEvent = async (elem, next, next2) => {
    await wait(125)

    const ans = (await checkInput(elem)).status
    
    if (!ans) {
        empty(next, next2)
        disable(next, next2)
        $(elemToBad(elem)).fadeIn('fast')
    }

    else {
        $(disabled).prop('disabled', false)
        $(elemToBad(elem)).fadeOut('fast')
    }
}


$(country).on("input", async e => {

    const data = JSON.parse(await Promise.resolve($.post('./API/location.php', { where: 'countries', substr: country.value })))
    
    createAutocomplete(data, country, countries, 'countryId')

    empty(state, city)  
})

$(country).on("keydown", async e => {
    handleSelection(e, country)
})

$(country).on("blur", async e => {
    await handleBlurEvent(country, state, city)
})


$(state).on("input", async e => {
    const data = JSON.parse(await Promise.resolve($.post('./API/location.php', { where: 'states', countryId: countryId, substr: state.value })))
    
    createAutocomplete(data, state, states, 'stateId')
})

$(state).on("keydown", e => {
    handleSelection(e, state)
})

$(state).on("blur", async e => {
    await handleBlurEvent(state, city, city)
})


$(city).on("input", async e => {
    const data = JSON.parse(await Promise.resolve($.post('./API/location.php', { where: 'cities', stateId: stateId, substr: city.value })))
    
    createAutocomplete(data, city, cities, 'cityId')
})

$(city).on("keydown", e => {
    handleSelection(e, city)
})

$(city).on("blur", async e => {
    const dummy = document.createElement('p')
    await handleBlurEvent(city, dummy, dummy)
})

$(document).on('keyup', e => {
    if (e.which == 9)
        removeAllChildren()
})


$(file).change(async e => {
  image.src = URL.createObjectURL(e.target.files[0])
  $(wrapImage).fadeIn('fast')
})


$(update).click(async () => {

    await wait(200)

    const isOk = checkInputEmpty()

    if (!isOk)
        return

        
    for (let elem of [badCountries, badStates, badCities])
        if ($(elem).is(':visible'))
            return

    hideErrors()

    const data = new FormData();
    
    for (let elem of [firstName, lastName, dateOfBirth, gender, address, country, state, city])
        data.append(elem.id, elem.value)

    data.append('file', file.files[0])

    console.log(data)

    const ans = JSON.parse(await Promise.resolve($.ajax({
        type: 'POST',
        url: './API/setData.php',
        data: data,
        processData: false,
        contentType: false
    })))

    $(message).removeClass('alert-danger').addClass('alert-info').html(ans.message)

    for (elem of allElements)
        $(elem).prop('disabled', true)

    return
})

document.addEventListener("click", () => {
    removeAllChildren()
})