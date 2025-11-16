# Sistema Multi-Bot Telegram con Pannello Admin Filament

Sistema completo e configurabile per gestire multipli bot Telegram attraverso un pannello amministrativo Filament.

## 🚀 Funzionalità Principali

### ✅ Gestione Multi-Bot
- Creazione e gestione di multipli bot Telegram
- Configurazione token bot tramite interfaccia admin
- Setup automatico webhook
- Monitoraggio stato e statistiche bot

### 📡 Feed RSS
- Monitoraggio automatico feed RSS
- Pubblicazione automatica nuovi contenuti su chat Telegram
- Intervallo configurabile per ogni feed
- Filtri avanzati per contenuti

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
- `DefStudio\Telegraph\Models\TelegraphBot` - Bot (package)
- `DefStudio\Telegraph\Models\TelegraphChat` - Chat (package)

### Filament Resources
- `TelegraphBotResource` - Gestione bot
- `RssFeedResource` - Gestione feed RSS
- `AutoResponseResource` - Gestione risposte automatiche
- `BotCommandResource` - Gestione comandi
- `BotLogResource` - Visualizzazione log (read-only)

### Webhook Handler
`App\TelegramWebhookHandler` - Handler principale per webhook Telegram:
- Gestione messaggi in arrivo
- Esecuzione comandi custom
- Trigger risposte automatiche
- Registrazione automatica chat
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
- Calcolo codice fiscale
- Accorciamento URL
- Liste della spesa condivise
- Scheduling post su canali
- Webcam italiane
- Tracker prezzi (Amazon, AliExpress)
- Email temporanee
- Statistiche utenti gruppi/canali
- Giochi (Sudoku, Quiz, dadi)
- Ricerca film/serie TV (IMDb, Netflix)

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
