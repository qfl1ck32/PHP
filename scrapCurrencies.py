import requests
from bs4 import BeautifulSoup as bs

URL = 'https://fxtop.com/en/countries-currencies.php'

page = requests.get(URL)

pgC = open('currencies.html', 'r', encoding = 'UTF-8')

soup = bs(pgC, 'html.parser')

soup.prettify()

divs = soup.findAll('td')

i = 0
v = []
cv = []

for elem in divs:

    if i == 1:
        src = elem.find('img')['src'][2:]
        cv.append(src)

    elif i == 2:
        name = elem.string[: elem.string.find('(')][:-1]
        cv.append(name)
        


    i += 1

    if i == 4:
        i = 0
        v.append(cv)
        cv = []

for elem in v:
    url = elem[0]
    name = elem[1]

    del elem[0]
    
    #req = requests.get('http://' + url, allow_redirects = True)

    #open('Images/countryFlags2/%s.png' % name, 'wb').write(req.content)

v.sort()

i = 1
for elem in v:
    elem.insert(0, i)
    i += 1

print(v)

for elem in v:
    print('insert into currencies values (%s, "%s");' % (elem[0], elem[1]))
