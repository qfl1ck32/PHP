const b = document.getElementById('testdestroy')

$(b).click(async () => {
    const data = JSON.parse(await Promise.resolve($.post('./API/forceDestroy.php', { sessId: "47392chvs8t00lvuicgk71m5ce" })))

    alert(data.message)
})