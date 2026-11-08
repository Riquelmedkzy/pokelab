"""Enriquece o JSON local; runtime não depende das APIs. Requer apenas Python padrão."""
import concurrent.futures, datetime, html, json, pathlib, re, urllib.request
ROOT = pathlib.Path(__file__).resolve().parents[1]
CACHE = ROOT / '.private' / 'catalog-cache'
CACHE.mkdir(parents=True, exist_ok=True)

def fetch(url, binary=False):
    import hashlib
    target = CACHE / hashlib.sha256(url.encode()).hexdigest()
    if not target.exists():
        req = urllib.request.Request(url, headers={'User-Agent':'Pokelab-Educational-Catalog/2.0'})
        with urllib.request.urlopen(req, timeout=35) as response: target.write_bytes(response.read())
    result = target.read_bytes()
    return result if binary else result.decode()

catalog_path = ROOT / 'views/assets/js/pokemon.json'
catalog = json.loads(catalog_path.read_text())
additions = ['leafeon','umbreon','espeon','glaceon','delphox','scizor','mew','mewtwo','zoroark','tyranitar','buzzwole','dodrio','sableye','clefable','comfey','goodra','lapras','chandelure','inteleon','ceruledge']
roster = fetch('https://unite.pokemon.com/en-us/pokemon/')
colors = {'fire':'#e86543','water':'#3c9cc9','grass':'#51995e','electric':'#d6b535','ice':'#4faaaa','fighting':'#b96353','poison':'#9a62ad','ground':'#af814b','flying':'#7f9fbd','psychic':'#ca6292','bug':'#819842','rock':'#9f8b56','ghost':'#82639f','dragon':'#7064b8','dark':'#75635d','steel':'#7a959a','fairy':'#ca7daa','normal':'#969388'}
roles = {'attacker':'Attacker','defender':'Defender','all-rounder':'All-rounder','speedster':'Speedster','supporter':'Supporter'}
new_descriptions = {
'leafeon':'Ágil e atento, Leafeon concentra energia solar nas folhas de seu corpo.',
'umbreon':'Umbreon é um parceiro de hábitos noturnos, reconhecido por seus anéis luminosos.',
'espeon':'Com sentidos aguçados, Espeon percebe pequenas mudanças no ambiente.',
'glaceon':'Glaceon pode controlar a temperatura do corpo e formar cristais de gelo.',
'delphox':'Delphox combina poderes psíquicos com as chamas que conduz em seu galho.',
'scizor':'A carapaça metálica e as grandes pinças fazem de Scizor uma presença marcante.',
'mew':'Pequeno e curioso, Mew é conhecido por seu extraordinário potencial.',
'mewtwo':'Mewtwo é um Pokémon de poder psíquico excepcional. A ficha Unite usa a variante de ataque à distância do catálogo oficial.',
'zoroark':'Zoroark é famoso pelas ilusões com que confunde quem cruza seu caminho.',
'tyranitar':'Com sua armadura natural e enorme força, Tyranitar domina a paisagem.',
'buzzwole':'Buzzwole é uma Ultracriatura de músculos impressionantes e presença imponente.',
'dodrio':'Três cabeças e pernas velozes dão a Dodrio uma silhueta inconfundível.',
'sableye':'Sableye se esconde na escuridão e tem olhos que lembram pedras preciosas.',
'clefable':'Clefable é um Pokémon do tipo Fada com passos leves e grande sensibilidade.',
'comfey':'Comfey carrega flores e espalha seu perfume por onde passa.',
'goodra':'Goodra é um dragão de aparência gentil e corpo coberto de uma camada viscosa.',
'lapras':'Lapras percorre as águas e é conhecido por transportar passageiros sobre o casco.',
'chandelure':'Chandelure flutua como um candelabro envolto em chamas misteriosas.',
'inteleon':'Discreto e preciso, Inteleon observa o ambiente antes de agir.',
'ceruledge':'Ceruledge veste uma armadura espectral e transforma suas chamas em lâminas.'}
existing = {p['uri'] for p in catalog}
for slug in additions:
    if slug not in existing:
        catalog.append({'id':max(p['id'] for p in catalog)+1, 'name':slug.title(), 'avatar':slug,'uri':slug,'description':new_descriptions[slug], 'evolutions':[]})

def enrich(p):
    slug = {'ninetales-de-alola':'ninetales-alola','aegislash':'aegislash-shield'}.get(p['uri'],p['uri'])
    api_url = 'https://pokeapi.co/api/v2/pokemon/'+slug
    data = json.loads(fetch(api_url))
    p['types'] = [entry['type']['name'] for entry in data['types']]
    p['national_id'] = data['species']['url'].rstrip('/').split('/')[-1]
    p['sources'] = {'types_and_moves':api_url, 'retrieved_at':datetime.date.today().isoformat()}
    learned=[]
    for move in data['moves']:
        levels=[v['level_learned_at'] for v in move['version_group_details'] if v['move_learn_method']['name']=='level-up']
        if levels: learned.append((max(levels),move['move']['name']))
    p['moves'] = [{'name':name.replace('-',' ').title(),'slug':name} for _,name in sorted(learned,reverse=True)[:3]]
    if not p['moves']:
        p['moves']=[{'name':v['move']['name'].replace('-',' ').title(),'slug':v['move']['name']} for v in data['moves'][:3]]
    if p['uri'] in additions:
        official_url='https://unite.pokemon.com/en-us/pokemon/'+p['uri']+'/'
        page=fetch(official_url)
        anchor=re.search(r'<a[^>]*href="[^" ]*/'+p['uri']+r'/"[^>]*>(.*?)</a>',roster,re.S)
        assert anchor,p['uri']
        role=roles[re.search(r'data-pokemon-battle-type="([^"]+)"',anchor.group(0)).group(1)]
        pills=re.findall(r'<p class="pill">(Melee|Ranged)</p>',page)
        assert pills,p['uri']
        difficulty=re.search(r'Difficulty: (Novice|Intermediate|Expert)',page)
        p['stats_battle']=[role,pills[0]]
        p['level']={'Novice':1,'Intermediate':2,'Expert':3}.get(difficulty.group(1) if difficulty else '',2)
        ratings=re.findall(r'pokemon-stats__stat-name">([^<]+)</span><span class="visually-hidden">\s*([\d.]+)',page)
        values={name:float(value) for name,value in ratings}
        assert len(values)==5,(p['uri'],ratings)
        p['skills']={key:round(values[label]*scale) for key,label,scale in [('attack','Offense',20),('resistance','Endurance',20),('mobility','Mobility',2),('punctuation','Scoring',20),('support','Support',20)]}
        p['color']=colors[p['types'][0]]
        image=re.search(r'src="([^"]+/stat/stat-[^"]+\.png)"',page).group(1)
        image_url=urllib.request.urljoin(official_url,image)
        (ROOT/'views/assets/img/stat'/('stat-'+p['avatar']+'.png')).write_bytes(fetch(image_url,True))
        p['sources']['unite_profile']=official_url
        p['sources']['image']=image_url
        p['sources']['skills_note']='Notas oficiais de 0–5 convertidas para 0–100; mobilidade para 0–10.'
    return p

with concurrent.futures.ThreadPoolExecutor(max_workers=4) as pool:
    enriched=list(pool.map(enrich,catalog))
assert len(enriched)==54
catalog_path.write_text(json.dumps(enriched,ensure_ascii=False,indent=2)+'\n')
print('Catálogo: 54 Pokémon, tipos e movimentos; 20 novas artes e perfis Unite verificados.')
