# 24º Jesus te Chama — Landing Page Convite

Landing Page convite oficial para o retiro católico de jovens **24º Jesus te Chama** (*“É necessário nascer de novo”* — João 3,7), realizado nos dias **30 e 31 de outubro e 1º de novembro** em **Pinhais - PR**, voltado para **jovens a partir de 17 anos**.

---

## 1. Objetivo do Projeto

Substituir o envio de vários vídeos separados no WhatsApp por um **único link leve, acolhedor, emocional e rápido no celular**, guiando o jovem convidado pela jornada:

`Recebe o link no WhatsApp` → `Abre a landing` → `Vê o convite` → `Entende o evento` → `Assiste aos depoimentos da equipe` → `Se sente convidado` → `Compartilha ou pede mais informações`.

Além disso, o projeto conta com um sistema próprio de **rastreamento estatístico 100% anônimo** (sem coleta de dados pessoais ou IP) para medir quantas pessoas abriram o convite e até que ponto assistiram a cada vídeo.

---

## 2. Stack e Tecnologia

Priorizando simplicidade máxima, performance mobile e portabilidade para qualquer hospedagem tradicional (como Hostinger):

- **Frontend:** HTML5 semântico + CSS3 puro (Mobile First) + JavaScript Vanilla (ES6+)
- **Backend:** PHP 7.4+ / 8.x (com PDO e Prepared Statements)
- **Banco de Dados:** MySQL / MariaDB (`utf8mb4`)
- **Zero dependências pesadas:** Sem React, Next.js, Vue, jQuery, Bootstrap ou dependência de Node.js em produção.

---

## 3. Estrutura de Diretórios

```text
/
├── index.html                  # Landing page principal do convite
├── privacidade.html            # Página de transparência sobre dados anônimos
├── .gitignore                  # Proteção para não versionar credenciais (api/config.php)
├── README.md                   # Documentação completa do projeto
├── css/
│   └── style.css               # Estilos Mobile First inspirados na arte oficial
├── js/
│   ├── config.js               # Configuração de vídeos, WhatsApp e modo DEBUG
│   ├── tracking.js             # Motor de rastreamento anônimo (visitor_id, session_id, vídeos)
│   └── main.js                 # Renderização dos vídeos, compartilhamento e interações
├── api/
│   ├── config.example.php      # Modelo de configuração do MySQL e senha do Admin
│   ├── config.php              # Configuração ativa (ignorado pelo Git)
│   ├── db.php                  # Conexão segura via PDO (utf8mb4)
│   └── track.php               # Endpoint POST JSON de recebimento de eventos
├── database/
│   ├── schema.sql              # Criação da tabela video_events e índices
│   └── queries.sql             # 15 consultas analíticas prontas para uso
├── admin/
│   ├── auth.php                # Sessão segura (httponly, secure, CSRF e password_verify)
│   ├── login.php               # Tela de login do painel administrativo
│   ├── logout.php              # Encerramento seguro de sessão
│   └── index.php               # Dashboard responsivo de métricas e retenção de vídeos
├── imagens/
│   ├── 24° JTC Tema - Horizontal.jpeg  # Arte horizontal original
│   ├── 24° JTC Tema - Horizontal.jpg   # Cópia de compatibilidade (.jpg)
│   ├── 24° JTC Tema - Vertical.jpeg    # Arte vertical original
│   ├── Inicio.jpeg                     # Arte complementar sem texto
│   └── og-convite-24jtc.jpg            # Imagem otimizada (<180KB) para preview do WhatsApp
└── depoimentos/
    └── WhatsApp Video 2026-10-13 at 09.26.29.mp4 # 1º depoimento em vídeo
```

---

## 4. Como Abrir e Testar Localmente

### Opção A — Sem PHP (Apenas Frontend + Tracking em modo DEBUG)
Enquanto `DEBUG_TRACKING = true` em `js/config.js`, a landing funciona 100% sem precisar de PHP ou MySQL instalados:

1. Usando Python (já instalado no Windows):
   ```powershell
   python -m http.server 8080
   ```
   Acesse no navegador: `http://localhost:8080`
2. Ou abra o arquivo `index.html` diretamente no navegador / extensão *Live Server* do VS Code.

### Opção B — Com PHP Local (Para testar API e `/admin/` localmente)
Caso tenha PHP instalado na máquina (XAMPP, Laragon ou PHP CLI):
```powershell
php -S localhost:8080
```
Acesse:
- Landing Page: `http://localhost:8080/`
- Painel Admin: `http://localhost:8080/admin/`

---

## 5. Como Configurar o Banco MySQL e Executar `schema.sql`

1. No painel da hospedagem (**hPanel da Hostinger** → *Bancos de Dados MySQL*), crie um novo banco de dados e usuário.
2. Abra o **phpMyAdmin** referente ao banco criado.
3. Clique na aba **Importar** (ou **SQL**), selecione o arquivo `/database/schema.sql` e execute.
4. Isso criará a tabela `video_events` com todos os índices otimizados (`visitor_id`, `session_id`, `event_name`, `video_id`, `created_at`).

Via linha de comando (opcional):
```bash
mysql -u usuario_banco -p nome_banco < database/schema.sql
```

---

## 6. Como Configurar `api/config.php`

1. Copie o arquivo `/api/config.example.php` para `/api/config.php`.
2. Edite `/api/config.php` com as credenciais do MySQL da hospedagem:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'u123456789_jtc');
   define('DB_USER', 'u123456789_user');
   define('DB_PASSWORD', 'SuaSenhaDoBancoAqui');
   ```
3. Em `/js/config.js`, quando publicar em produção com o banco ativo, altere:
   ```javascript
   const DEBUG_TRACKING = false;
   ```

> **Importante:** O arquivo `/api/config.php` já está no `.gitignore` para nunca ser enviado ao GitHub com senhas reais.

---

## 7. Como Adicionar Novos Vídeos, Nomes e Posters

Toda a configuração dos depoimentos fica centralizada em **`/js/config.js`** no array `testimonials`:

```javascript
const testimonials = [
  {
    id: "depoimento_01",
    name: "Maria Clara",
    role: "Equipe de Acolhida",
    file: "depoimentos/WhatsApp Video 2026-10-13 at 09.26.29.mp4",
    poster: "imagens/poster-01.jpg",
    caption: "Um convite preparado de coração por quem está organizando cada detalhe para receber você.",
    tracks: []
  },
  {
    id: "depoimento_02",
    name: "Lucas",
    role: "Coordenação Jovem",
    file: "depoimentos/depoimento-02.mp4",
    poster: "",
    caption: "Venha viver esses dias com a gente!",
    tracks: []
  }
];
```

- **Como adicionar novos vídeos (4 a 5 vídeos):** Coloque o arquivo `.mp4` na pasta `/depoimentos/` e adicione um novo objeto no array `testimonials` com um `id` único (`depoimento_02`, `depoimento_03`, etc.).
- **Como colocar nome e função nos depoimentos:** Preencha os campos `name` e `role`. Se deixados vazios (`""`), o card exibirá automaticamente *"Convite da nossa equipe"* e *"Equipe 24º Jesus te Chama"*.
- **Como configurar poster (thumbnail):** Coloque uma imagem leve (`.jpg` ou `.webp`) em `/imagens/` e informe o caminho relativo no campo `poster`. Se deixado vazio (`""`), o player usa automaticamente `#t=0.1` com `preload="metadata"` para exibir o primeiro quadro do vídeo.
- **Como adicionar legendas (WebVTT):** Salve o arquivo `.vtt` em `/depoimentos/` e adicione no array `tracks` do vídeo:
  ```javascript
  tracks: [
    { src: "depoimentos/depoimento_01.vtt", label: "Português", srclang: "pt-BR", default: true }
  ]
  ```

---

## 8. Como Alterar o Número do WhatsApp

Abra `/js/config.js` e preencha a constante `WHATSAPP_NUMBER` apenas com números (DDI 55 + DDD + número):

```javascript
const WHATSAPP_NUMBER = "5541999999999";
```

- Quando preenchido, o botão **“Quero saber mais”** abre diretamente a conversa no WhatsApp com a mensagem configurada em `WHATSAPP_DEFAULT_MESSAGE`.
- Enquanto estiver vazio (`""`), clicar no botão registra o evento `whatsapp_click` normalmente e exibe um aviso acolhedor orientando o jovem a responder para a pessoa que lhe enviou o link no WhatsApp.

---

## 9. Como Funciona o Rastreamento Anônimo (`visitor_id`, `session_id` e Eventos)

### `visitor_id`
- No primeiro acesso, verifica `localStorage.getItem("jtc_visitor_id")`.
- Se não existir, gera um UUID v4 aleatório via `crypto.randomUUID()` (com fallback seguro para navegadores antigos) e salva no `localStorage`.
- Permite contar **visitantes únicos** e **visitantes que retornaram** sem identificar a pessoa nem coletar dados pessoais.

### `session_id`
- Verifica `sessionStorage.getItem("jtc_session_id")`.
- Se não existir, gera um novo UUID válido durante aquela sessão/aba aberta do navegador.

### Eventos Disponíveis (Whitelist)
Todos os eventos passam por `trackEvent(eventName, data)` e são validados no backend (`/api/track.php`):
- `page_view` — Disparado 1 única vez ao abrir a landing
- `video_start` — Primeiro play do vídeo na sessão
- `video_25` — Vídeo atingiu 25% da duração
- `video_50` — Vídeo atingiu 50% da duração (**Métrica principal de visualização relevante**)
- `video_75` — Vídeo atingiu 75% da duração
- `video_90` — Vídeo atingiu 90% da duração
- `video_complete` — Vídeo chegou até o final (`ended`)
- `share_click` — Clique no botão “Compartilhar este convite”
- `whatsapp_click` — Clique no botão “Quero saber mais”

### Prevenção de Duplicidade
O objeto `videoMilestones` (em memória + `sessionStorage`) garante que cada marco (`video_start`, `25%`, `50%`, `75%`, `90%`, `complete`) seja enviado **apenas uma vez por vídeo por sessão**, mesmo que o visitante volte o vídeo e assista novamente.

### Como Testar em Modo DEBUG
Com `DEBUG_TRACKING = true` em `/js/config.js`:
1. Abra a landing no navegador e pressione `F12` (aba **Console**).
2. Ao carregar a página, dar play no vídeo ou clicar nos botões, você verá os logs detalhados:
   `[TRACK] page_view {...}`
   `[TRACK] video_start {...}`
   `[TRACK] video_25 {...}`

---

## 10. Painel Administrativo (`/admin/`) e Consultas SQL

### Como Acessar o Admin
Acesse `https://seudominio.com.br/admin/` (ou `http://localhost:8080/admin/`).
O painel exibe:
- **Topo:** Visitantes Únicos, Visualizações da Landing, Sessões, Compartilhamentos e Cliques no WhatsApp.
- **Cards por Vídeo:** Iniciaram, 25%, **50% (destaque)**, 75%, 90%, Concluíram e Taxa de Conclusão (`%`).
- **Tabela Diária:** Acessos por dia.

### Como Configurar Usuário e Senha do Admin
A senha nunca fica em texto puro; ela é validada com `password_verify()` no PHP.
1. Gere o hash da sua senha executando no terminal com PHP (ou em um arquivo PHP temporário):
   ```bash
   php -r "echo password_hash('SuaSenhaSecretaAqui', PASSWORD_DEFAULT) . PHP_EOL;"
   ```
2. Copie o hash gerado (`$2y$10$...`) e cole na constante `ADMIN_PASSWORD_HASH` dentro de `/api/config.php`:
   ```php
   define('ADMIN_USERNAME', 'coord_jtc');
   define('ADMIN_PASSWORD_HASH', '$2y$10$SEU_HASH_GERADO_AQUI');
   ```

### Consultas SQL Diretas
O arquivo `/database/queries.sql` traz 15 consultas prontas para rodar no phpMyAdmin (visitantes únicos, funil de cada vídeo, taxa de conclusão, visitantes que viram mais de um vídeo, acessos por dia e visitantes recorrentes).

---

## 11. Como Comprimir Vídeos para Web (Recomendação Mobile)

Para manter o carregamento rápido no 4G/5G dos jovens sem perder qualidade visual:
- **Formato:** MP4 (Codec de vídeo **H.264**, áudio **AAC**)
- **Resolução ideal para celular:** `720x1280` (vertical 9:16) ou `1280x720` (horizontal 16:9)
- **Bitrate equilibrado:** CRF 23 a 26 (`~1.2 Mbps` a `2 Mbps`) + flag `-movflags +faststart` (permite iniciar o play instantaneamente antes de baixar todo o arquivo).

Comando sugerido com **FFmpeg** (ou equivalente no **HandBrake** marcando *"Web Optimized"*):
```bash
ffmpeg -i video-original.mp4 -vcodec libx264 -crf 24 -preset medium -vf "scale='min(720,iw)':-2" -acodec aac -b:a 128k -movflags +faststart depoimento-otimizado.mp4
```

---

## 12. Open Graph / Preview no WhatsApp

A prévia do link no WhatsApp utiliza as meta tags Open Graph configuradas no `<head>` do `index.html`, apontando para `imagens/og-convite-24jtc.jpg` (~173 KB, `1200x675`), garantindo carregamento imediato da imagem no WhatsApp mesmo em conexões móveis lentas.

Se mudar de domínio no futuro, basta atualizar a URL nas tags `og:url`, `og:image` e `twitter:image` no `index.html`.

---

## 13. Como Fazer Deploy e Migrar de Domínio / Servidor

### Publicação Inicial (Ex: `https://convitejesustechama.9digitaltec.com.br`)
1. No hPanel da Hostinger, crie o subdomínio `convitejesustechama.9digitaltec.com.br` e verifique se o **SSL (HTTPS)** está ativo.
2. Crie o banco MySQL no hPanel e importe `/database/schema.sql` pelo phpMyAdmin.
3. Altere `DEBUG_TRACKING = false` em `/js/config.js`.
4. Envie os arquivos para a pasta `public_html` do subdomínio (via Git Deploy ou File Manager / FTP).
5. Crie o arquivo `/api/config.php` no servidor com os dados do banco e o `ADMIN_PASSWORD_HASH`.

### Como Migrar para Outro Domínio ou Servidor
Como todos os ativos da página (`css/`, `js/`, `imagens/`, `depoimentos/`, `api/`) utilizam **caminhos relativos**, migrar para um domínio definitivo é imediato:
1. Exporte o banco MySQL atual pelo phpMyAdmin (aba **Exportar** → Rápido → SQL).
2. Importe o `.sql` no novo banco de dados.
3. Copie todos os arquivos da pasta pública para o novo domínio.
4. Atualize `/api/config.php` com os dados do novo banco e atualize a URL nas meta tags `og:url` e `og:image` do `index.html`.
