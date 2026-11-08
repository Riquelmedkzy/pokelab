"""Testes HTTP em banco SQLite temporário. Não altera contas ou equipes reais."""
import http.cookiejar, json, os, pathlib, re, sqlite3, subprocess, tempfile, time, unittest, urllib.error, urllib.parse, urllib.request
ROOT = pathlib.Path(__file__).resolve().parents[1]
PHP = os.environ.get('PHP_BINARY', '/opt/lampp/bin/php')
PORT = 18087
BASE = f'http://127.0.0.1:{PORT}/index.php?route='

class Client:
    def __init__(self): self.client = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
    def request(self, route, data=None, token=None, raw=False):
        headers = {}
        if raw: headers['Content-Type']='application/json'; body=json.dumps(data).encode()
        elif data is not None: body=urllib.parse.urlencode(data).encode()
        else: body=None
        if token: headers['X-CSRF-Token']=token
        req=urllib.request.Request(BASE+route,data=body,headers=headers)
        try:
            with self.client.open(req) as r:return r.status,r.read().decode()
        except urllib.error.HTTPError as e:return e.code,e.read().decode()
    def csrf(self,route='register'):
        status,html=self.request(route)
        assert status==200,(status,html[:200])
        return re.search(r'name="csrf" value="([a-f0-9]+)"',html).group(1)
    def register(self,email):
        token=self.csrf()
        status,html=self.request('register',{'csrf':token,'name':'Treinador Teste','email':email,'password':'test-password-7291'})
        assert status==200 and 'Seu time. Sua assinatura.' in html,(status,html[:500])
        return json.loads(re.search(r'id="session-data">(.*?)</script>',html,re.S).group(1))

class Integration(unittest.TestCase):
    def test_account_and_team(self):
        a,b=Client(),Client()
        self.assertEqual(a.request('team_api')[0],401)
        self.assertEqual(a.request('register',{'name':'Test','email':'a@test.invalid','password':'valid-password'})[0],403)
        session=a.register('one@test.invalid')
        self.assertTrue(session['authenticated'])
        payload={'members':[18,3,35,None,None],'revision':0}
        self.assertEqual(a.request('team_api',payload,raw=True)[0],403)
        self.assertEqual(a.request('team_api',payload,token=session['csrf'],raw=True)[0],200)
        saved=json.loads(a.request('team_api')[1]); self.assertEqual(saved['members'],payload['members']); self.assertEqual(saved['revision'],1)
        self.assertEqual(a.request('team_api',payload,token=session['csrf'],raw=True)[0],409)
        for bad in [[18,18,None,None,None],[99999,None,None,None,None],['18',None,None,None,None],[18]*6,{'0':18}]:
            self.assertEqual(a.request('team_api',{'members':bad,'revision':1},token=session['csrf'],raw=True)[0],422)
        other=b.register('two@test.invalid')
        self.assertEqual(json.loads(b.request('team_api')[1])['members'],[None]*5)
        self.assertEqual(b.request('team_api',{'members':[1,None,None,None,None],'revision':0},token=other['csrf'],raw=True)[0],200)
        self.assertEqual(json.loads(a.request('team_api')[1])['members'],payload['members'])
        self.assertEqual(a.request('logout')[0],405)
        self.assertEqual(a.request('logout',{'csrf':'invalid'})[0],403)
        self.assertEqual(a.request('logout',{'csrf':session['csrf']})[0],200)
        self.assertEqual(a.request('team_api')[0],401)
        csrf=a.csrf('login')
        self.assertEqual(a.request('login',{'csrf':csrf,'email':'one@test.invalid','password':'wrong-password'})[0],422)
        status,html=a.request('login',{'csrf':csrf,'email':'one@test.invalid','password':'test-password-7291'})
        self.assertEqual(status,200)
        self.assertEqual(json.loads(a.request('team_api')[1])['members'],payload['members'])
        with sqlite3.connect(DB) as db:
            hashed=db.execute('SELECT password_hash FROM users LIMIT 1').fetchone()[0]
            self.assertNotEqual(hashed,'test-password-7291'); self.assertTrue(hashed.startswith('$2y$'))
    def test_profile_and_recovery(self):
        a, other, guest = Client(), Client(), Client()
        session = a.register('profile@test.invalid')
        self.assertIn('Bom ter você de volta.', guest.request('profile')[1])
        self.assertIn('Escolha seu avatar', a.request('profile')[1])
        current = 'test-password-7291'
        self.assertEqual(a.request('team_api',{'members':[18,3,None,None,None],'revision':0},token=session['csrf'],raw=True)[0],200)
        fields = {'action':'details','name':'Treinador Perfil','email':'profile@test.invalid','avatar_id':'31'}
        self.assertEqual(a.request('profile', fields)[0], 403)
        fields['csrf'] = a.csrf('profile')
        self.assertEqual(a.request('profile',dict(fields,avatar_id='999999'))[0],422)
        self.assertEqual(a.request('profile',dict(fields,email='novo-profile@test.invalid'))[0],422)
        status,html = a.request('profile',dict(fields,current_password=current,email='novo-profile@test.invalid'))
        self.assertEqual(status,200)
        self.assertIn('Perfil atualizado.',html)
        with sqlite3.connect(DB) as db:
            row=db.execute('SELECT name,email,avatar_id FROM users WHERE email=?',('novo-profile@test.invalid',)).fetchone()
            self.assertEqual(row,('Treinador Perfil','novo-profile@test.invalid',31))
        token=other.csrf('login')
        self.assertEqual(other.request('login',{'csrf':token,'email':'novo-profile@test.invalid','password':current})[0],200)
        # Um e-mail desconhecido recebe a mesma mensagem pública, mas nenhuma mensagem privada.
        def forgot(email):
            csrf=guest.csrf('forgot')
            return guest.request('forgot',{'csrf':csrf,'email':email})
        self.assertEqual(guest.request('forgot',{'email':'novo-profile@test.invalid'})[0],403)
        self.assertIn('Pedido recebido.',forgot('novo-profile@test.invalid')[1])
        files=list(pathlib.Path(OUTBOX).glob('*.json'))
        self.assertEqual(len(files),1)
        mail=json.loads(files[0].read_text())
        self.assertEqual(mail['to'],'novo-profile@test.invalid')
        link=re.search(r'http[^\s]+route=reset[^\s]+',mail['body']).group(0)
        reset_token=urllib.parse.parse_qs(urllib.parse.urlparse(link).query)['token'][0]
        self.assertNotIn(reset_token, guest.request('forgot')[1])
        with sqlite3.connect(DB) as db:
            stored=db.execute('SELECT token_hash FROM password_resets').fetchone()[0]
            self.assertNotEqual(stored,reset_token)
        self.assertIn('Pedido recebido.',forgot('unknown@test.invalid')[1])
        self.assertEqual(len(list(pathlib.Path(OUTBOX).glob('*.json'))),1)
        # Segundo pedido revoga o primeiro; links expirados não abrem o formulário.
        self.assertIn('Pedido recebido.',forgot('novo-profile@test.invalid')[1])
        self.assertNotIn('name="new_password"',guest.request('reset&token='+reset_token)[1])
        files=sorted(pathlib.Path(OUTBOX).glob('*.json'),key=lambda f:f.stat().st_mtime_ns)
        old_link=re.search(r'http[^\s]+route=reset[^\s]+',json.loads(files[-1].read_text())['body']).group(0)
        old_token=urllib.parse.parse_qs(urllib.parse.urlparse(old_link).query)['token'][0]
        with sqlite3.connect(DB) as db: db.execute('UPDATE password_resets SET expires_at = 0')
        self.assertNotIn('name="new_password"',guest.request('reset&token='+old_token)[1])
        self.assertIn('Pedido recebido.',forgot('novo-profile@test.invalid')[1])
        files=sorted(pathlib.Path(OUTBOX).glob('*.json'),key=lambda f:f.stat().st_mtime_ns)
        mail=json.loads(files[-1].read_text())
        link=re.search(r'http[^\s]+route=reset[^\s]+',mail['body']).group(0)
        reset_token=urllib.parse.parse_qs(urllib.parse.urlparse(link).query)['token'][0]
        status,html=guest.request('reset&token='+reset_token)
        self.assertEqual(status,200)
        csrf=re.search('name="csrf" value="([a-f0-9]+)"',html).group(1)
        reset={'token':reset_token,'new_password':'new-password-5729','confirm_password':'new-password-5729'}
        self.assertEqual(guest.request('reset',reset)[0],403)
        self.assertEqual(guest.request('reset',dict(reset,csrf=csrf,confirm_password='different-password'))[0],422)
        status,html=guest.request('reset',dict(reset,csrf=csrf))
        self.assertEqual(status,200)
        self.assertIn('Sua senha foi atualizada',html)
        self.assertNotIn('name="new_password"',guest.request('reset&token='+reset_token)[1])
        self.assertEqual(a.request('team_api')[0],401)
        self.assertEqual(other.request('team_api')[0],401)
        token=a.csrf('login')
        self.assertEqual(a.request('login',{'csrf':token,'email':'novo-profile@test.invalid','password':current})[0],422)
        self.assertEqual(a.request('login',{'csrf':token,'email':'novo-profile@test.invalid','password':'new-password-5729'})[0],200)
        self.assertEqual(json.loads(a.request('team_api')[1])['members'],[18,3,None,None,None])
        other_token=other.csrf('login')
        self.assertEqual(other.request('login',{'csrf':other_token,'email':'novo-profile@test.invalid','password':'new-password-5729'})[0],200)
        csrf=a.csrf('profile')
        status,html=a.request('profile',{'csrf':csrf,'action':'password','current_password':'new-password-5729','new_password':'profile-password-5729','confirm_password':'profile-password-5729'})
        self.assertEqual(status,200)
        self.assertIn('Senha alterada.',html)
        self.assertEqual(a.request('team_api')[0],200)
        self.assertEqual(other.request('team_api')[0],401)
        # O link consumido não pode voltar a alterar a senha.
        self.assertEqual(guest.request('reset',dict(reset,csrf=guest.csrf('forgot')))[0],400)
        self.assertIn('Muitos pedidos.',forgot('novo-profile@test.invalid')[1])

    def test_catalog_and_routes(self):
        c=Client();catalog=json.loads((ROOT/'views/assets/js/pokemon.json').read_text())
        self.assertEqual(len(catalog),54);self.assertEqual(len({p['id'] for p in catalog}),54)
        self.assertEqual(c.request('home')[0],200)
        self.assertNotIn('Todos os Pokémon',c.request('home')[1])
        for p in catalog:
            self.assertTrue(p['types']);self.assertTrue(p['moves']); self.assertTrue((ROOT/f"views/assets/img/stat/stat-{p['avatar']}.png").exists())
            status,html=c.request('pokemon_detalhes&id='+str(p['id']))
            self.assertEqual(status,200,p['name']); self.assertIn('<h1>'+p['name']+'</h1>',html)
        for route in ['unknown','pokemon_detalhes&id=9999','pokemon_detalhes&id[]=3']:
            self.assertEqual(c.request(route)[0],404)
        html=c.request('home&element=fire')[1]
        self.assertIn('Pesquisar no elemento Fogo',html);self.assertIn('Charizard',html);self.assertNotIn('Adicionar Charizard à equipe',html)
        self.assertIn('Adicionar Charizard à equipe',c.request('team&element=fire')[1])
        for path in ['/.private/pokelab.sqlite','/config.php','/models/Database.php','/scripts/enrich_catalog.py']:
            with self.assertRaises(urllib.error.HTTPError) as context:urllib.request.urlopen(f'http://127.0.0.1:{PORT}'+path)
            self.assertEqual(context.exception.code,404)

if __name__=='__main__':
    with tempfile.TemporaryDirectory(prefix='pokelab-test-') as temp:
        DB=str(pathlib.Path(temp)/'test.sqlite')
        OUTBOX=str(pathlib.Path(temp)/'outbox')
        env=dict(os.environ,POKELAB_DB_PATH=DB,POKELAB_OUTBOX_DIR=OUTBOX,POKELAB_MAIL_TRANSPORT='local',POKELAB_APP_URL=f'http://127.0.0.1:{PORT}')
        with open(pathlib.Path(temp)/'server.log','w') as log:
            server=subprocess.Popen([PHP,'-S',f'127.0.0.1:{PORT}','-t',str(ROOT),str(ROOT/'router.php')],env=env,stdout=log,stderr=log)
            try:
                for _ in range(40):
                    try:urllib.request.urlopen(BASE+'home',timeout=1);break
                    except urllib.error.URLError:time.sleep(.1)
                result=unittest.main(exit=False)
                if not result.result.wasSuccessful(): raise SystemExit(1)
            finally:server.terminate();server.wait(timeout=5)
