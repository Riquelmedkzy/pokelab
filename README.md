# PokéLab

Catálogo de 54 Pokémon, organizado por 18 elementos, e montador de uma equipe principal de cinco integrantes. PHP MVC, JSON para o catálogo e SQLite para contas e equipes.

## Executar

Requer PHP **8.1+**, com `pdo_sqlite`, JSON e mbstring. Não usa Composer nem Node em produção.

```bash
php -S 127.0.0.1:8087 router.php
```

Neste computador:

```bash
/opt/lampp/bin/php -S 127.0.0.1:8087 router.php
```

Abra http://127.0.0.1:8087. Use o router ao iniciar o servidor PHP: ele publica somente os endpoints e assets permitidos.

A cópia do XAMPP foi atualizada em `/opt/lampp/htdocs/2350/pokemon`. Endereço: http://localhost/2350/pokemon/. As duas cópias de código têm bancos independentes; use sempre o mesmo endereço para continuar uma conta.

## Como usar

1. No catálogo, escolha um elemento. Pokémon com dois tipos aparecem nos dois grupos.
2. Pesquise pelo nome dentro daquele elemento. O botão antigo “Todos os Pokémon” foi removido.
3. Clique na ilustração para animar; clique no nome para abrir a ficha.
4. Abra “Minha equipe” para adicionar, trocar e remover integrantes. A seleção do espaço é mantida ao navegar entre elementos.
5. Crie uma conta ou entre: a equipe passa a ser salva automaticamente no banco. O estado “Salva na sua conta” confirma o salvamento; falhas mostram aviso e opção de tentar novamente.
6. A opção “Animações”, no topo, desativa os movimentos em todas as páginas e permanece salva no navegador. A preferência de movimento reduzido do sistema também é respeitada.

Visitantes podem experimentar sem login. O rascunho local não substitui silenciosamente uma equipe da conta: o botão de importação ocupa somente espaços vazios, sem duplicatas.

## Banco de dados

SQLite é um banco relacional em arquivo, não `localStorage`. O esquema é criado automaticamente por `models/Database.php`:

- `users`: nome, e-mail único, hash da senha e data do cadastro.
- `teams`: equipe vinculada ao usuário, versão e data da alteração.
- `auth_attempts`: limite de tentativas por IP durante 15 minutos.

Padrão: `.private/pokelab.sqlite`. Nunca publique esse arquivo. O `.htaccess` privado bloqueia acesso pelo Apache, e o `router.php` bloqueia o diretório no servidor de desenvolvimento. No Apache, mantenha `AllowOverride` habilitado para essa proteção. Outra opção é definir `POKELAB_DB_PATH` para um arquivo **fora da raiz pública**.

O usuário que executa o PHP precisa poder escrever no diretório do banco. Neste XAMPP, o usuário é `daemon`; ACLs locais foram configuradas no diretório privado, sem abrir permissões para todos. Em outro servidor, configure o proprietário/grupo adequado. Faça backup do banco com a aplicação sem escritas, ou use o comando de backup do SQLite. Copiar apenas o código não transfere as contas.

Senhas usam `password_hash`/`password_verify`; requisições de alteração exigem CSRF. Sessões são renovadas ao entrar e sair. Consultas são parametrizadas. A API valida IDs, proíbe duplicatas e limita a equipe a cinco espaços. Controle de versão impede que uma aba sobrescreva silenciosamente uma equipe modificada em outra. Ações de cadastro/login têm limite de tentativas. Em publicação, configure HTTPS; a verificação de e-mail ainda não faz parte desta versão. Recuperação de senha é descrita abaixo.

## Rotas

- `index.php?route=home`: catálogo de elementos.
- `index.php?route=home&element=fire`: busca no elemento Fogo.
- `index.php?route=team`: montador.
- `index.php?route=team&element=grass`: seleção de parceiros de Planta.
- `index.php?route=pokemon_detalhes&id=35`: ficha de Leafeon.
- `index.php?route=register` / `login`: cadastro e entrada.
- `index.php?route=logout`: saída via POST com CSRF.
- `index.php?route=team_api`: GET/POST da equipe do usuário conectado.

## Dados e fontes

Os 34 registros originais foram preservados e receberam tipos, número nacional e movimentos. Os 20 novos são Leafeon, Umbreon, Espeon, Glaceon, Delphox, Scizor, Mew, Mewtwo, Zoroark, Tyranitar, Buzzwole, Dodrio, Sableye, Clefable, Comfey, Goodra, Lapras, Chandelure, Inteleon e Ceruledge.

- [PokéAPI](https://pokeapi.co/docs/v2): tipos, número nacional e exemplos de movimentos dos jogos principais. Nomes dos golpes são exibidos no idioma original; não representam os movimentos do Unite.
- [Catálogo oficial de Pokémon Unite](https://unite.pokemon.com/en-us/pokemon/): artes completas, função, alcance, dificuldade e notas dos 20 novos perfis. Notas oficiais de 0–5 foram convertidas para 0–100; mobilidade para 0–10, seguindo as escalas do projeto original.

Cada registro contém `sources` com URLs e data de consulta. `scripts/enrich_catalog.py` permite repetir a importação e guarda cache privado. O site funciona sem consultar APIs a cada acesso. Os dados são um retrato de estudo; a análise descreve funções e alcances, não prevê vitórias nem afirma um meta atualizado. Linhas evolutivas não cadastradas são sinalizadas nas fichas.

Ilustrações pertencem aos respectivos titulares de Pokémon. Outfit é hospedada localmente com licença OFL em `views/assets/fonts/OFL.txt`. O projeto é independente e educacional.

## Verificação

```bash
python3 tests/integration.py
```

Os testes iniciam um servidor temporário com um banco isolado e verificam cadastro, login, logout, hashes, CSRF, separação de contas, persistência, limite/validação da equipe, conflitos entre versões, 54 fichas e proteção dos arquivos privados. Não alteram contas reais. Defina `PHP_BINARY` para outro executável PHP, se necessário.


## Perfil e avatar

Entre na conta e clique no avatar/nome no canto superior direito → **Meu perfil**. Também é possível abrir `index.php?route=profile`.

Escolha um dos 54 Pokémon, consulte a prévia e clique em **Salvar perfil**. A busca facilita encontrar seu favorito. O avatar aparece no menu e fica salvo no banco, junto do nome e e-mail. Trocar o e-mail exige informar a senha atual. A seção **Uma nova senha** permite trocar a senha atual, com confirmação; isso encerra as outras sessões e invalida os links de recuperação anteriores. As equipes são preservadas.

A migração de `users` adiciona `avatar_id` (Pikachu para contas antigas) e `session_version` sem recriar o banco ou apagar dados.

## Recuperação de senha local

O usuário escolheu manter o envio **em modo local** nesta etapa. O site não envia e-mail externo nem publica um link que permita tomar a conta de outro treinador.

1. Na tela de entrada, clique em **Esqueci minha senha** e informe o e-mail da conta.
2. A mensagem de teste é gravada na caixa privada `.private/outbox/` da cópia do projeto utilizada.
3. No terminal, leia a mensagem mais recente para o seu e-mail. Para a cópia XAMPP:

```bash
/opt/lampp/bin/php /opt/lampp/htdocs/2350/pokemon/scripts/mailbox.php seu@email.com
```

Para a cópia da pasta de projetos:

```bash
/opt/lampp/bin/php /home/user/Projetos/pokemon/scripts/mailbox.php seu@email.com
```

4. Abra o link exibido e informe/ confirme a nova senha. Depois, entre normalmente. As equipes continuam salvas.

Só o acesso administrativo pelo terminal permite ler essa caixa. Ela fica protegida pelo `.htaccess` e pelo router; `scripts/mailbox.php` também recusa acesso via navegador. Os arquivos representam mensagens locais e contêm links sensíveis: não os versione ou publique.

Tokens aleatórios são armazenados somente como SHA-256 na tabela `password_resets`, vinculados ao e-mail atual. Expiram em **30 minutos**, são de **uso único** e um novo pedido substitui o anterior. Trocar e-mail ou senha revoga os links. O consumo é transacional; ao redefinir, todas as sessões anteriores deixam de autenticar. A resposta pública de pedido é igual para endereços conhecidos e desconhecidos, com limite de tentativas. A tela de redefinição usa `Referrer-Policy: no-referrer` e as páginas não são armazenadas em cache.

### Envio externo no futuro

A integração SMTP opcional usa PHPMailer, com versão fixada e licença em `vendor/PHPMailer/`. Copie `docs/mail.example.php` para `.private/mail.php` e configure servidor, TLS, remetente, usuário, senha e `app_url` HTTPS. As credenciais nunca devem ir para o repositório. `POKELAB_APP_URL` pode definir a URL canônica; ela não é derivada do cabeçalho Host recebido.

O transporte SMTP não foi conectado nem testado com uma conta de e-mail externa nesta entrega. Os testes usam o transporte local e bancos descartáveis. `POKELAB_MAIL_TRANSPORT`, `POKELAB_OUTBOX_DIR` e `POKELAB_DB_PATH` permitem isolar ambientes de teste.

Referências de implementação: [OWASP — recuperação de senha](https://cheatsheetseries.owasp.org/cheatsheets/Forgot_Password_Cheat_Sheet.html) e [PHPMailer](https://github.com/PHPMailer/PHPMailer).
