import requests
from bs4 import BeautifulSoup as bs

URL = 'https://www.countryflags.io'

page = requests.get(URL)

pg = open('flags.html', 'r')

soup = bs(pg, 'html.parser')

divs = soup.findAll('div', {'class': ['item_country', 'cell', 'small-4', 'medium-2', 'large-2'] })


for elem in divs:
    img = elem.findChildren('img')
    print(img[0]['src'])
    p = elem.findChildren('p')
    print(p)
    exit()
