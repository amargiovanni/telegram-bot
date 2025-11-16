# Sistema Multi-Bot Telegram con Pannello Admin Filament

Sistema completo e configurabile per gestire multipli bot Telegram attraverso un pannello amministrativo Filament.

## 🚀 Funzionalità Principali

### ✅ Gestione Multi-Bot
- Creazione e gestione di multipli bot Telegram
- Configurazione token bot tramite interfaccia admin
- Setup automatico webhook
- Monitoraggio stato e statistiche bot

### 📡 Feed RSS ✨ COMPLETAMENTE AUTOMATICO
- ✅ **Monitoraggio automatico** via Laravel Scheduler (ogni minuto)
- ✅ **Queue system** per gestione asincrona
- ✅ **Pubblicazione automatica** nuovi contenuti su chat Telegram
- ✅ **Intervallo configurabile** per ogni feed (5-1440 minuti)
- ✅ **Tracking intelligente** - memorizza ultima entry per evitare duplicati
- ✅ **Formattazione automatica** messaggi con emoji e link
- ✅ **Error handling** completo con logging
- ✅ **Comando manuale** disponibile: `php artisan telegram:monitor-rss`

### 🔗 URL Shortener
- ✅ **Accorciamento URL** via comando `/shorten`
- ✅ **Generazione automatica** short code univoco (6 caratteri)
- ✅ **Tracking click** con contatore visualizzazioni
- ✅ **Scadenza URL** configurabile (opzionale)
- ✅ **Gestione admin** completa tramite Filament
- ✅ **Statistiche** per bot e per URL
- ✅ **Logging completo** di creazione e redirect
- ✅ **Copy to clipboard** per short URL
- ✅ **Endpoint pubblico**: `/s/{code}` con redirect automatico

### 🆔 Calcolo Codice Fiscale
- ✅ **Calcolo automatico** codice fiscale italiano
- ✅ **Comando `/cf`** con parametri strutturati
- ✅ **Validazione completa** dei dati in input
- ✅ **Algoritmo ufficiale** con tutti i casi speciali
- ✅ **Help integrato** con esempi d'uso
- ✅ **Supporto codici Belfiore** per comuni italiani
- ✅ **Formato**: `/cf Cognome|Nome|GG/MM/AAAA|M/F|CodiceComune`

### 😄 Comandi Divertenti
Comandi scherzosi e irriverenti per intrattenimento:
- `/barzelletta` - Barzellette a tema tech
- `/insulto` - Insulti friendly e scherzosi
- `/motivazione` - Citazioni motivazionali (con twist)
- `/consiglio` - Consigli (a volte assurdi)
- `/fortuna` - Biscotto della fortuna
- `/decisione [domanda]` - Ti aiuta a decidere
- `/pizza` - Consiglia una pizza random
- `/scusa` - Scuse pronte per sviluppatori

### 🤖 Risposte Automatiche
- Risposte basate su keywords
- Multiple modalità di matching:
  - Esatto
  - Contiene
  - Inizia con
  - Finisce con
  - Regex
- Sistema di priorità
- Supporto per media (foto, video, audio, documenti)
- Restrizioni per chat specifiche
- Cancellazione automatica messaggio trigger

### ⚡ Comandi Bot Personalizzati
- Creazione comandi custom illimitati
- Risposte configurabili (testo e media)
- Visibilità nel menu bot Telegram
- Restrizioni per chat specifiche

### 📊 Log e Monitoraggio
- Log completo di tutte le attività
- Visualizzazione real-time con auto-refresh
- Filtraggio per tipo evento e bot
- Tracking messaggi, comandi, errori

### 🔄 Registrazione Automatica Chat
- Registrazione automatica quando qualcuno scrive al bot
- Registrazione automatica quando bot aggiunto a gruppo
- Aggiornamento nome chat automatico

## 📋 Tabelle Database

### telegraph_bots
Bot Telegram configurati nel sistema.

### telegraph_chats
Chat e gruppi registrati per ogni bot.

### rss_feeds
- `telegraph_bot_id` - Bot associato
- `telegraph_chat_id` - Chat dove pubblicare (nullable)
- `name` - Nome feed
- `url` - URL feed RSS
- `check_interval` - Intervallo controllo (minuti)
- `last_checked_at` - Ultimo controllo
- `last_entry_date` - Data ultima entry
- `is_active` - Attivo/disattivo
- `filters` - Filtri JSON avanzati

### auto_responses
- `telegraph_bot_id` - Bot associato
- `name` - Nome risposta
- `keywords` - Array keywords (JSON)
- `match_type` - Tipo matching
- `case_sensitive` - Case sensitive
- `response_text` - Testo risposta
- `response_type` - Tipo (text/photo/video/audio/document)
- `media_url` - URL media (nullable)
- `is_active` - Attivo/disattivo
- `priority` - Priorità esecuzione
- `delete_trigger_message` - Cancella messaggio trigger
- `allowed_chat_ids` - Chat consentite (JSON array)

### bot_commands
- `telegraph_bot_id` - Bot associato
- `command` - Comando (senza /)
- `description` - Descrizione
- `response_text` - Testo risposta
- `response_type` - Tipo risposta
- `media_url` - URL media (nullable)
- `is_active` - Attivo/disattivo
- `show_in_menu` - Mostra in menu Telegram
- `allowed_chat_ids` - Chat consentite (JSON array)

### shortened_urls
- `telegraph_bot_id` - Bot associato
- `telegraph_chat_id` - Chat che ha creato URL (nullable)
- `original_url` - URL originale lungo
- `short_code` - Codice breve univoco (6 caratteri)
- `click_count` - Contatore click/redirect
- `expires_at` - Data scadenza (nullable)
- `is_active` - Attivo/disattivo

### bot_logs
- `telegraph_bot_id` - Bot associato (nullable)
- `telegraph_chat_id` - Chat associata (nullable)
- `type` - Tipo evento
- `message` - Messaggio log
- `data` - Dati JSON aggiuntivi
- `created_at` - Timestamp (no updated_at)

Tipi evento log:
- `message_received` - Messaggio ricevuto
- `message_sent` - Messaggio inviato
- `command_executed` - Comando eseguito
- `auto_response_triggered` - Risposta automatica attivata
- `rss_check` - Controllo RSS
- `rss_posted` - RSS pubblicato
- `url_shortened` - URL accorciato
- `url_redirect` - Redirect URL eseguito
- `error` - Errore
- `webhook_received` - Webhook ricevuto
- `chat_registered` - Chat registrata
- `bot_added_to_group` - Bot aggiunto a gruppo
- `bot_removed_from_group` - Bot rimosso da gruppo

## 🏗️ Architettura

### Models
- `App\Models\RssFeed` - Feed RSS
- `App\Models\AutoResponse` - Risposte automatiche
- `App\Models\BotCommand` - Comandi bot
- `App\Models\BotLog` - Log sistema
- `App\Models\ShortenedUrl` - URL accorciati
- `DefStudio\Telegraph\Models\TelegraphBot` - Bot (package)
- `DefStudio\Telegraph\Models\TelegraphChat` - Chat (package)

### Filament Resources
- `TelegraphBotResource` - Gestione bot
- `RssFeedResource` - Gestione feed RSS
- `AutoResponseResource` - Gestione risposte automatiche
- `BotCommandResource` - Gestione comandi
- `ShortenedUrlResource` - Gestione URL accorciati
- `BotLogResource` - Visualizzazione log (read-only)

### Controllers
- `UrlRedirectController` - Gestisce redirect da short code a URL originale

### Services
- `FiscalCodeCalculator` - Servizio per calcolo codice fiscale italiano

### Webhook Handler
`App\TelegramWebhookHandler` - Handler principale per webhook Telegram:
- Gestione messaggi in arrivo
- Esecuzione comandi built-in (`/start`, `/help`, `/shorten`, `/cf`)
- Comandi divertenti (`/barzelletta`, `/insulto`, `/motivazione`, `/consiglio`, `/fortuna`, `/decisione`, `/pizza`, `/scusa`)
- Trigger risposte automatiche
- Registrazione automatica chat
- URL shortening e fiscal code calculation
- Logging completo attività

## 🔧 Setup e Configurazione

### 1. Installazione Dipendenze
```bash
composer install
npm install
```

### 2. Configurazione Database
```bash
php artisan migrate
```

### 3. Creazione Admin User
```bash
php artisan shield:super-admin
```

### 4. Configurazione Bot Telegram

1. Accedi al pannello Filament admin
2. Vai su "Telegram Bots" > "Bots"
3. Clicca "Create" e inserisci:
   - Nome bot
   - Token da @BotFather
4. Clicca "Setup Webhook" per registrare il webhook

### 5. URL Webhook
Il webhook è disponibile a:
```
https://your-domain.com/telegraph/{bot_token}/webhook
```

## 📱 Utilizzo

### Creare un Feed RSS
1. Admin > RSS Feeds > Create
2. Seleziona bot
3. Seleziona chat destinazione
4. Inserisci nome e URL feed
5. Configura intervallo controllo

### Creare Risposta Automatica
1. Admin > Auto Responses > Create
2. Seleziona bot
3. Inserisci keywords
4. Configura tipo matching
5. Inserisci testo risposta
6. (Opzionale) Aggiungi media URL
7. Configura priorità

### Creare Comando Custom
1. Admin > Bot Commands > Create
2. Seleziona bot
3. Inserisci comando (es: "help", non "/help")
4. Inserisci descrizione
5. Configura risposta
6. Abilita "Show in Menu" per visibilità in Telegram

### Usare URL Shortener
**Via Bot Telegram:**
1. Scrivi al bot: `/shorten https://example.com/very-long-url`
2. Il bot risponde con l'URL accorciato
3. L'URL è subito utilizzabile e tracciato

**Via Admin Panel:**
1. Admin > Shortened URLs > Create
2. Seleziona bot
3. Inserisci URL originale
4. (Opzionale) Inserisci short code personalizzato
5. (Opzionale) Imposta scadenza
6. Salva e copia l'URL accorciato

**Monitoraggio:**
- Visualizza tutti gli URL accorciati nell'admin
- Controlla statistiche click per ogni URL
- Filtra per bot o stato attivo/scaduto
- Copy to clipboard integrato

### Monitorare Log
1. Admin > Bot Logs
2. Filtra per tipo evento o bot
3. Visualizzazione real-time con auto-refresh ogni 10s

## 🔐 Sicurezza

- Tutti i token bot sono crittografati nel database
- Webhook protetto da token univoco per bot
- Validazione input su tutti i form
- Logs tracciabilità completa
- Permission system con Spatie Shield

## 🚀 Funzionalità Future

Il sistema è progettato per essere estensibile con:
- Convertitori file (PDF, immagini, documenti)
- Liste della spesa condivise
- Scheduling post su canali
- Webcam italiane
- Tracker prezzi (Amazon, AliExpress, voli, hotel)
- Email temporanee
- Statistiche utenti gruppi/canali
- Giochi (Sudoku, Quiz, dadi)
- Ricerca film/serie TV (IMDb, Netflix)
- OCR per estrazione testo da immagini
- Generatore QR code
- Traduttore multi-lingua

## 📝 Note Tecniche

- **Laravel**: 12.x
- **Filament**: 3.x
- **Telegraph**: Latest
- **PHP**: 8.4+
- **Database**: SQLite (può usare MySQL/PostgreSQL)

## 🤝 Contribuire

Questo è un sistema modulare e estensibile. Per aggiungere nuove funzionalità:

1. Crea nuove migrations per tabelle aggiuntive
2. Crea models Eloquent con relationships
3. Crea Filament Resources per interfaccia admin
4. Estendi `TelegramWebhookHandler` per nuovi comandi
5. Aggiungi job Queue per operazioni asincrone

## 📄 Licenza

Questo progetto è sviluppato per uso interno.

## ⏰ Laravel Scheduler Setup

Per abilitare il monitoraggio automatico RSS, aggiungi questo comando al tuo crontab:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

Il sistema verificherà automaticamente ogni minuto quali feed devono essere controllati (in base al `check_interval` configurato).

### Comandi Manuali Disponibili

```bash
# Controlla tutti i feed RSS
php artisan telegram:monitor-rss

# Controlla un feed specifico
php artisan telegram:monitor-rss --feed=1

# Visualizza i job nella queue
php artisan queue:work

# Mostra help del comando
php artisan telegram:monitor-rss --help
```

## 🔄 Queue System

Il sistema usa Laravel Queues per gestire:
- Monitoraggio RSS feeds
- Invio messaggi Telegram
- Operazioni asincrone

### Setup Queue Worker

Per development:
```bash
php artisan queue:work
```

Per production, usa Supervisor o simili per mantenere attivo il worker.

