# Evolution API v2 com HTTPS

Troca da Evolution v1.8.2 pela v2.3.7. Com a v2:

- os contatos que o WhatsApp esconde (`…@lid`) chegam também com o número, e o lead já é criado com o telefone;
- o sistema consegue responder esses contatos.

A conexão passa a usar HTTPS no endereço `https://evolution.skinflow.com.br`.

A v2 sobe **ao lado** da v1, na porta 8081. A v1 só é desligada no fim, depois que tudo estiver funcionando. Até lá, voltar atrás é trocar duas linhas do `.env` (veja **Voltar atrás**).

Tempo: uns 30 minutos. O WhatsApp fica fora só entre o passo 6 e o passo 8.

> Não mande para ninguém (nem para o Claude) as chaves e senhas geradas abaixo.

---

## 0. Antes de começar

```bash
# O que está rodando hoje (anote o nome do container da Evolution v1)
docker ps --format 'table {{.Names}}\t{{.Image}}\t{{.Ports}}'

# A porta 8081 precisa estar livre (não deve aparecer nada)
ss -ltnp | grep ':8081 ' || echo "8081 livre"

# Backup do banco do sistema
cd /var/www/financeiro && php artisan backup:run --only-db
```

## 1. DNS

No painel onde o domínio `skinflow.com.br` está registrado, crie um registro:

| Tipo | Nome | Valor |
|---|---|---|
| A | `evolution` | `31.97.82.45` |

Espere até este comando responder `31.97.82.45` (costuma levar de 5 a 30 minutos):

```bash
dig +short evolution.skinflow.com.br
```

## 2. Pasta, chaves e configuração da v2

```bash
sudo mkdir -p /opt/evolution-v2 && cd /opt/evolution-v2

# Gera a chave mestra da API e a senha do banco da Evolution
CHAVE=$(openssl rand -hex 24)
SENHA_BD=$(openssl rand -hex 16)

sudo tee .env >/dev/null <<EOF
SERVER_TYPE=http
SERVER_PORT=8080
SERVER_URL=https://evolution.skinflow.com.br
LANGUAGE=pt-BR
DEL_INSTANCE=false

AUTHENTICATION_API_KEY=${CHAVE}
AUTHENTICATION_EXPOSE_IN_FETCH_INSTANCES=true

DATABASE_PROVIDER=postgresql
DATABASE_CONNECTION_URI=postgresql://evolution:${SENHA_BD}@evolution-postgres:5432/evolution?schema=evolution_api
DATABASE_CONNECTION_CLIENT_NAME=evolution_v2
DATABASE_SAVE_DATA_INSTANCE=true
DATABASE_SAVE_DATA_NEW_MESSAGE=true
DATABASE_SAVE_MESSAGE_UPDATE=true
DATABASE_SAVE_DATA_CONTACTS=true
DATABASE_SAVE_DATA_CHATS=true

POSTGRES_DATABASE=evolution
POSTGRES_USERNAME=evolution
POSTGRES_PASSWORD=${SENHA_BD}

CACHE_REDIS_ENABLED=true
CACHE_REDIS_URI=redis://evolution-redis:6379/6
CACHE_REDIS_PREFIX_KEY=evolution
CACHE_REDIS_SAVE_INSTANCES=false
CACHE_LOCAL_ENABLED=false

CONFIG_SESSION_PHONE_CLIENT=Financeiro
CONFIG_SESSION_PHONE_NAME=Chrome
WEBHOOK_GLOBAL_ENABLED=false
EOF
sudo chmod 600 .env

# Guarde a chave mestra num lugar seguro (gerenciador de senhas)
echo "Chave mestra da Evolution v2: ${CHAVE}"
```

## 3. Docker Compose da v2

```bash
cd /opt/evolution-v2
sudo tee docker-compose.yml >/dev/null <<'EOF'
services:
  api:
    container_name: evolution_v2_api
    image: evoapicloud/evolution-api:v2.3.7
    restart: always
    depends_on: [evolution-redis, evolution-postgres]
    ports:
      - "127.0.0.1:8081:8080"   # só o nginx do próprio servidor acessa
    volumes:
      - evolution_instances:/evolution/instances
    env_file: .env

  evolution-redis:
    container_name: evolution_v2_redis
    image: redis:7
    restart: always
    command: redis-server --appendonly yes
    volumes:
      - evolution_redis:/data

  evolution-postgres:
    container_name: evolution_v2_postgres
    image: postgres:15
    restart: always
    environment:
      POSTGRES_DB: ${POSTGRES_DATABASE}
      POSTGRES_USER: ${POSTGRES_USERNAME}
      POSTGRES_PASSWORD: ${POSTGRES_PASSWORD}
    volumes:
      - postgres_data:/var/lib/postgresql/data

volumes:
  evolution_instances:
  evolution_redis:
  postgres_data:
EOF

sudo docker compose up -d
sleep 20
curl -s http://127.0.0.1:8081/ | head -c 300; echo
```

A última linha deve mostrar algo como `{"status":200,"message":"Welcome to the Evolution API…","version":"2.3.7"…}`.

## 4. HTTPS no nginx

```bash
sudo tee /etc/nginx/sites-available/evolution.skinflow.com.br >/dev/null <<'EOF'
server {
    listen 80;
    server_name evolution.skinflow.com.br;

    client_max_body_size 50M;

    location / {
        proxy_pass http://127.0.0.1:8081;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout 120s;
    }
}
EOF
sudo ln -sf /etc/nginx/sites-available/evolution.skinflow.com.br /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx

# Certificado gratuito (Let's Encrypt); escolha redirecionar HTTP para HTTPS se perguntar
sudo certbot --nginx -d evolution.skinflow.com.br

curl -s https://evolution.skinflow.com.br/ | head -c 200; echo
```

Se o `certbot` não estiver instalado: `sudo apt install -y certbot python3-certbot-nginx`.

## 5. Criar a instância na v2

```bash
CHAVE=$(sudo grep ^AUTHENTICATION_API_KEY= /opt/evolution-v2/.env | cut -d= -f2)

curl -s -X POST https://evolution.skinflow.com.br/instance/create \
  -H "apikey: ${CHAVE}" -H "Content-Type: application/json" \
  -d '{"instanceName":"financeiro","integration":"WHATSAPP-BAILEYS","qrcode":true}' \
  | python3 -c "import sys,json; d=json.load(sys.stdin); print('TOKEN DA INSTÂNCIA:', d.get('hash'))"
```

Guarde o **TOKEN DA INSTÂNCIA** (vai no passo 7).

Cadastre o webhook. Ele aponta para o mesmo endereço de hoje, com "Webhook by Events" desligado:

```bash
curl -s -X POST https://evolution.skinflow.com.br/webhook/set/financeiro \
  -H "apikey: ${CHAVE}" -H "Content-Type: application/json" \
  -d '{"webhook":{"enabled":true,"url":"https://financeiro.skinflow.com.br/api/whatsapp/webhook","byEvents":false,"base64":false,"events":["MESSAGES_UPSERT","MESSAGES_UPDATE"]}}'
echo
```

## 6. Ligar o WhatsApp na v2 (QR Code)

1. Abra o QR Code. A forma mais fácil é pelo gerenciador que vem na v2:
   - entre em `https://evolution.skinflow.com.br/manager`;
   - informe a URL `https://evolution.skinflow.com.br` e a chave mestra;
   - abra a instância **financeiro** e clique em conectar.
2. No celular da clínica, abra o WhatsApp, vá em **Aparelhos conectados**, toque em **Conectar um aparelho** e escaneie o QR Code.
3. Na mesma tela de Aparelhos conectados, **desconecte o aparelho antigo** (o da v1).

Confira:

```bash
curl -s https://evolution.skinflow.com.br/instance/connectionState/financeiro -H "apikey: ${CHAVE}"; echo
```

Deve aparecer `"state":"open"`.

## 7. Apontar o sistema para a v2

```bash
cd /var/www/financeiro
cp .env .env.antes-evolution-v2

# Troca (ou acrescenta) as duas linhas
grep -q '^EVOLUTION_URL=' .env && sed -i 's#^EVOLUTION_URL=.*#EVOLUTION_URL=https://evolution.skinflow.com.br#' .env || echo 'EVOLUTION_URL=https://evolution.skinflow.com.br' >> .env
grep -q '^EVOLUTION_VERSAO=' .env && sed -i 's#^EVOLUTION_VERSAO=.*#EVOLUTION_VERSAO=2#' .env || echo 'EVOLUTION_VERSAO=2' >> .env

# Se existir token do webhook, ele passa a ser o token da instância
grep '^WHATSAPP_WEBHOOK_TOKEN=' .env && echo ">> Troque o valor acima pelo TOKEN DA INSTÂNCIA do passo 5"

php artisan optimize:clear && php artisan config:cache && php artisan queue:restart
```

No sistema, abra **Dados da clínica**:
- **Instância:** `financeiro`.
- **Chave:** o **TOKEN DA INSTÂNCIA** do passo 5.

Salve.

## 8. Testar

1. **Mensagem de um número novo:** mande "oi" de um celular que não é paciente. O contato deve aparecer em **Leads → Novo**, com o telefone preenchido.
2. **Resposta pelo sistema:** na ficha desse lead, responda pela **Conversa no WhatsApp**. A mensagem deve chegar no celular.
3. **Resposta pelo celular da clínica:** responda o lead pelo celular. A mensagem deve aparecer na ficha em até 15 segundos.

Se algo falhar:

```bash
tail -n 50 storage/logs/laravel.log | grep -i whatsapp
sudo docker logs --tail 50 evolution_v2_api
```

## 9. Desligar a v1 (só depois que tudo funcionar)

```bash
# Use o nome anotado no passo 0
sudo docker stop NOME_DO_CONTAINER_V1
sudo docker update --restart=no NOME_DO_CONTAINER_V1
```

Deixe o container parado por uns dias antes de apagar.

---

## Voltar atrás

```bash
cd /var/www/financeiro
cp .env.antes-evolution-v2 .env
php artisan optimize:clear && php artisan config:cache && php artisan queue:restart
sudo docker start NOME_DO_CONTAINER_V1
```

Depois disso:
- volte a instância e a chave antigas em **Dados da clínica**;
- escaneie o QR Code da v1 de novo, se ela tiver sido desconectada.
