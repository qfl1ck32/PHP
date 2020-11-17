var countryId = -1, stateId = -1, cityId = -1
let currentFocus

const get = elem => {
    return document.getElementById(elem)
}

const   firstName = get('firstName'),
        lastName = get('lastName'),
        dateOfBirth = get('dateOfBirth'),
        gender = get('gender'),
        address = get('address'),

        country = get('country'),
        countries = get('countries'),

        city = get('city'),
        cities = get('cities'),

        state = get('state'),
        states = get('states'),

        update = get('update'),

        badCountry = get('badCountries'),
        badState = get('badStates'),
        badCity = get('badCities'),

        file = get('file'),
        image = get('image'),
        wrapImage = get('wrapImage'),

        message = get('message'),
        messageWrap = get('messageWrap'),

        allElements = [firstName, lastName, dateOfBirth, gender, address, country, city, state, file]

const wait = async time => {
    return await new Promise(resolve => {
        setTimeout(resolve, time)
    })
}

window.onload = async () => {

    for (const elem of [firstName, lastName, address, file]) {
        $(elem).on('input', () => {
            if (!$(elem).val().length)
                return $(elem).removeClass('is-invalid').removeClass('is-valid')

            if ($(elem).val().length < 2)
                $(elem).removeClass('is-valid').addClass('is-invalid')
            else
                $(elem).removeClass('is-invalid').addClass('is-valid')

            checkCanUpdate()
        })
    }

    $(gender).on('input', () => {

        if ($(gender).val() === 'Choose here')
            $(gender).removeClass('is-valid').addClass('is-invalid')

        else
            $(gender).removeClass('is-invalid').addClass('is-valid')

        checkCanUpdate()
    })

    $(dateOfBirth).on('input', () => {
        if ($(dateOfBirth).val() === '')
            $(dateOfBirth).removeClass('is-valid').addClass('is-invalid')
        else
            $(dateOfBirth).removeClass('is-invalid').addClass('is-valid')

        checkCanUpdate()
    })

    if (!country.value)
        return $('#mainDiv').fadeIn('slow')

    for (const elem of [firstName, lastName, dateOfBirth, gender, address, country, state, city])
        $(elem).addClass('is-valid')

    const ids = JSON.parse(await Promise.resolve($.post('./API/location.php', { getIds: true, countryName: country.value, stateName: state.value, cityName: city.value } )))

    if (ids.status) {
        countryId = ids.message.countryId
        cityId = ids.message.cityId
        stateId = ids.message.stateId

        $(wrapImage).show()
    }

    if (document.getElementById('pendingApproval')) {

        update.innerHTML = 'Can not update at the moment.'
        
        for (elem of allElements) {
            $(elem).prop('disabled', true)
        }
    }

    $('#mainDiv').fadeIn('slow')

}

const checkCanUpdate = () => { 
    for (const elem of allElements)
        if (!$(elem).val() || $(elem).hasClass('is-invalid')) {
            $(update).attr('disabled', true)
            return false
        }
    
    $(update).attr('disabled', false)

    return true
}

const removeChildren = elem => {
    while (elem.firstChild)
        elem.removeChild(elem.lastChild)
}

const removeChildrenMul = (...elems) => {
    for (let elem of elems) {
        removeChildren(elem)
        $(elem).removeClass('border rounded')
    }
}

const removeAllChildren = () => {
    removeChildrenMul(cities, states, countries)
}

const removeActive = elem => {
    for (let i = 0; i < elem.length; ++i)
        elem[i].classList.remove('bg-primary')
}

const empty = (...elems) => {
    for (let elem of elems) {
        $(elem).val('')
        $(elem).removeClass('is-valid').removeClass('is-invalid')
        $(elemToBad(elem)).hide()
    }
}

const disable = (...elems) => {
    for (let elem of elems)
        $(elem).attr('disabled', true)
}

const addActive = elem => {
    if (!elem)
        return
    
    removeActive(elem)

    if (currentFocus >= elem.length)
        currentFocus = 0

    else if (currentFocus < 0)
            currentFocus = elem.length - 1

    elem[currentFocus].classList.add('bg-primary')
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

    $(target).removeClass('border rounded')
    removeChildren(target)

    if (!(from.value && data.length))
        return

    $(target).addClass('border rounded')
    
    currentFocus = -1

    const mainDiv = document.createElement('div')
    mainDiv.className = 'selectElementWrap'
    mainDiv.id = from.id + 'List'

    for (let elem of data) {
        const potential = document.createElement('div')

        potential.className = 'selectElement rounded'

        potential.innerHTML = '<a href = "#" class = "text-white text-center font-italic" <b>' + elem.name.substr(0, from.value.length) + '</b>' + elem.name.substr(from.value.length) + '</a>'

        $(potential).click(() => {
            $(this).next().focus()
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

    if (!elem.value)
        return $(elemToBad(elem)).fadeOut('fast')

    const ans = (await checkInput(elem)).status
    
    if (!ans) {
        empty(next, next2)
        $(elemToBad(elem)).fadeIn('fast')

        $(elem).removeClass('is-valid').addClass('is-invalid')
    }

    else {
        $(elem).removeClass('is-invalid').addClass('is-valid')

        $(elemToBad(elem)).fadeOut('fast')
    }
}


$(firstName).on('input', () => {

})


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

    checkCanUpdate()
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

    checkCanUpdate()
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

    checkCanUpdate()
})

$(document).on('keyup', e => {
    if (e.which == 9)
        removeAllChildren()
})


$(file).change(async e => {
    const file = e.target.files[0]
    if (file.size > 5000000)
        return alert('The file you have uploaded is larger than 5MB.')

    if (!file.type.match('image*'))
        return alert('You should enter an image (.jpeg / .png).')

    image.src = URL.createObjectURL(file)
    $(wrapImage).fadeIn('fast')

    checkCanUpdate()
})


$(update).click(async () => {

    await wait(200)

    if(!checkCanUpdate())
        return

    const data = new FormData();
    
    for (let elem of [firstName, lastName, dateOfBirth, gender, address])
        data.append(elem.id, elem.value)

    data.append("countryId", countryId)
    data.append("stateId", stateId)
    data.append("cityId", cityId)

    data.append('file', file.files[0])

    const ans = JSON.parse(await Promise.resolve($.ajax({
        type: 'POST',
        url: './API/setData.php',
        data: data,
        processData: false,
        contentType: false
    })))

    if (!ans.status) {
        console.log(ans)
        return
    }
    
    $(message).removeClass('alert-danger').addClass('alert-info').html(ans.message)

    $(update).prop('disabled', true)

    for (elem of allElements)
        $(elem).prop('disabled', true)

    return
})

document.addEventListener("click", () => {
    removeAllChildren()
})