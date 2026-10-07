# Backup

Todo dia às **02:00** (horário de Brasília) o sistema gera um ZIP com:

- o banco PostgreSQL inteiro (`db-dumps/postgresql-<banco>.sql.gz`);
- os arquivos enviados: fotos, anexos, termos e comprovantes (`storage/app/private` e `storage/app/public`);
- o `.env`, que traz o `APP_KEY` usado para decifrar os campos criptografados.

O ZIP é protegido com senha (AES-256) e enviado para um armazenamento compatível com S3, fora da VPS. Às 01:30 os backups antigos são apagados, mantendo:

- todos os dos últimos 7 dias;
- 1 por semana nas 4 semanas seguintes;
- 1 por mês nos 6 meses seguintes.

Se o backup falhar, ou se o último tiver mais de 26 horas, o monitor (`sistema:monitorar`) avisa os super admins por e-mail e pelo WhatsApp da gestão. O card **Saúde do sistema**, no painel da plataforma, mostra quando foi o último backup.

## Configurar (Backblaze B2)

1. Em backblaze.com, crie um bucket **privado**, por exemplo `financeiro-backups`.
2. Em **Application Keys**, crie uma chave com acesso só a esse bucket, com permissão de leitura e escrita.
3. Veja o endpoint S3 do bucket, por exemplo `s3.us-east-005.backblazeb2.com`. A região é o trecho do meio: `us-east-005`.
4. No `.env` da produção, preencha:

   ```
   BACKUP_S3_KEY=<keyID>
   BACKUP_S3_SECRET=<applicationKey>
   BACKUP_S3_REGION=us-east-005
   BACKUP_S3_BUCKET=financeiro-backups
   BACKUP_S3_ENDPOINT=https://s3.us-east-005.backblazeb2.com
   BACKUP_ARCHIVE_PASSWORD=<senha longa>
   ```

5. Guarde a `BACKUP_ARCHIVE_PASSWORD` e o `APP_KEY` também num gerenciador de senhas. Sem a senha, o backup não abre.
6. Confira se o `pg_dump` tem a mesma versão principal do PostgreSQL do servidor: `pg_dump --version` e `psql -c "select version()"`. Se não tiver, instale `postgresql-client-<versão>`.
7. Rode um backup na hora e confira a lista:

   ```bash
   php artisan config:clear
   php artisan backup:run
   php artisan backup:list
   ```

No Cloudflare R2, use `BACKUP_S3_REGION=auto` e `BACKUP_S3_ENDPOINT=https://<account-id>.r2.cloudflarestorage.com`.

## Restaurar

1. Baixe o ZIP pelo painel do Backblaze. Ele fica em `financeiro/AAAA-MM-DD-HH-MM-SS.zip`.
2. Descompacte com a senha. O `unzip` comum não abre AES; use `7z x arquivo.zip`.
3. Coloque o sistema em manutenção e restaure o banco:

   ```bash
   php artisan down
   gunzip -c db-dumps/postgresql-<banco>.sql.gz | psql -U <usuario> -d <banco_vazio>
   ```

   Restaure num banco **vazio**. Para trocar o banco atual, renomeie-o antes e crie um novo com o mesmo nome.
4. Copie `storage/app/private` e `storage/app/public` de volta para `/var/www/financeiro/storage/app/`.
5. Use o `APP_KEY` do `.env` que veio no backup. Com outra chave, os campos criptografados não abrem.
6. Por fim:

   ```bash
   php artisan optimize:clear && php artisan queue:restart && php artisan up
   ```

Teste uma restauração de vez em quando, num banco separado, para ter certeza de que o backup funciona.
