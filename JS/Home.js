const b = document.getElementById('da')

$(b).click(async () => {
    const data = JSON.parse(await Promise.resolve($.post('./API/location.php', { where: 'countries', substr: 'Ro' })))

    for (let elem of data)
        console.log(elem.name)
})