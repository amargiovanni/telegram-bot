# Multi-Bot Telegram Management System

Un sistema completo di gestione multi-bot per Telegram con pannello amministrativo Filament, monitoraggio RSS, promemoria, liste della spesa, generazione QR code, calcolatore di codice fiscale e molto altro.

## 📋 Indice

- [Caratteristiche](#-caratteristiche)
- [Tecnologie](#-tecnologie)
- [Requisiti](#-requisiti)
- [Installazione](#-installazione)
- [Configurazione](#-configurazione)
- [Comandi Bot](#-comandi-bot)
- [Pannello Amministrativo](#-pannello-amministrativo)
- [Testing](#-testing)
- [Architettura](#-architettura)
- [Licenza](#-licenza)

## ✨ Caratteristiche

### 🤖 Gestione Multi-Bot
- Supporto per bot Telegram multipli dalla stessa installazione
- Configurazione indipendente per ogni bot
- Gestione centralizzata tramite pannello amministrativo Filament
- Logging completo di tutte le attività

### 📰 Aggregatore di Notizie RSS
- Monitoraggio automatico di feed RSS/Atom
- Pubblicazione automatica di nuovi articoli sui canali Telegram
- Supporto per feed multipli per ogni bot
- Configurazione di intervalli di controllo personalizzati
- Filtri e parsing intelligente dei contenuti

### ⏰ Sistema di Promemoria e Timer
- Creazione di promemoria con data/ora specifica
- Notifiche automatiche via Telegram
- Gestione di promemoria ricorrenti
- Supporto per formato naturale (es. "tra 30 minuti", "domani alle 15:00")
- Tracking di promemoria inviati

### 🛒 Liste della Spesa Condivise
- Creazione e gestione di liste della spesa collaborative
- Aggiunta/rimozione articoli in tempo reale
- Check/uncheck elementi
- Liste multiple per chat
- Visualizzazione organizzata con quantità e unità di misura

### 🔗 URL Shortener
- Creazione di URL brevi personalizzati
- Tracking dei click e statistiche
- Gestione tramite comando /shorten
- Reindirizzamento automatico

### 🖼️ OCR - Estrazione Testo da Immagini
- Riconoscimento testo da immagini (OCR)
- Supporto per multiple lingue
- Processing automatico di immagini inviate al bot
- Estrazione e invio del testo riconosciuto

### 🇮🇹 Calcolatore Codice Fiscale Italiano
- Calcolo del codice fiscale italiano
- Validazione dei dati inseriti
- Supporto per comuni italiani
- Gestione automatica di omocodia

### 🎯 Utilità e Comandi
- **Calcolatrice**: Calcoli matematici avanzati
- **Generatore Password**: Password sicure personalizzabili
- **QR Code**: Generazione di codici QR
- **Meteo**: Previsioni meteo in tempo reale
- **Traduttore**: Traduzione testi tra diverse lingue
- **Info Sistema**: Informazioni sul server e bot

### 🎮 Giochi e Intrattenimento
- **Lancio Dadi**: Simulazione dadi a 6, 20 o 100 facce
- **Cara o Croce**: Lancio di moneta
- **8-Ball**: Magic 8-Ball per risposte casuali
- **Numero Casuale**: Generazione numeri casuali in range
- **Dad Jokes**: Barzellette casuali (in inglese)
- **Quote**: Citazioni motivazionali

### 🔒 Sicurezza e Rate Limiting
- Rate limiting a tre livelli (heavy/medium/light)
- Protezione anti-spam
- Logging di tutte le richieste
- Gestione errori robusta

### 📊 Monitoring e Analytics
- Laravel Pulse per metriche real-time
- Laravel Telescope per debugging (solo in locale)
- Logging completo con Activity Log di Spatie
- Tracking di comandi, messaggi ed errori

### 👥 Auto-Risposte Personalizzate
- Risposte automatiche basate su pattern
- Supporto regex per trigger complessi
- Gestione priorità risposte
- On/off per ogni chat

## 🛠️ Tecnologie

### Backend
- **Laravel 12**: Framework PHP moderno
- **PHP 8.4**: Ultima versione PHP
- **Telegraph**: Libreria per integrazione Telegram
- **SQLite/MySQL**: Database
- **Laravel Sanctum**: Autenticazione API

### Frontend & Admin
- **Filament 4**: Pannello amministrativo moderno
- **Livewire 3**: Interattività real-time
- **Volt**: Componenti Livewire single-file
- **Tailwind CSS 4**: Styling utility-first
- **Alpine.js 3**: JavaScript reattivo

### Testing & Quality
- **Pest 4**: Framework di testing moderno
- **PHPStan (Larastan 3)**: Analisi statica
- **Laravel Pint**: Code formatter
- **Rector 2**: Refactoring automatico

### Monitoring
- **Laravel Pulse**: Performance monitoring
- **Laravel Telescope**: Debugging tools
- **Spatie Activity Log**: User activity tracking

## 📦 Requisiti

- PHP >= 8.4
- Composer
- Node.js >= 18
- NPM/Yarn
- SQLite o MySQL
- Account Telegram Bot (ottenibile da @BotFather)
- (Opzionale) API Keys per servizi esterni (meteo, traduzione, ecc.)

## 🚀 Installazione

### 1. Clona il repository

```bash
git clone https://github.com/your-username/telegram-bot.git
cd telegram-bot
```

### 2. Installa le dipendenze

```bash
composer install
npm install
```

### 3. Configura l'ambiente

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configura il database

Modifica il file `.env` con le tue credenziali database:

```env
DB_CONNECTION=sqlite
# oppure per MySQL:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=telegram_bot
# DB_USERNAME=root
# DB_PASSWORD=
```

### 5. Esegui le migrazioni

```bash
php artisan migrate --seed
```

### 6. Compila gli asset

```bash
npm run build
# oppure per development:
npm run dev
```

### 7. Avvia il server

```bash
php artisan serve
```

### 8. Crea un utente admin

```bash
php artisan make:filament-user
```

## ⚙️ Configurazione

### Configurazione Bot Telegram

1. Crea un bot tramite [@BotFather](https://t.me/botfather)
2. Ottieni il token del bot
3. Accedi al pannello Filament (`http://localhost:8000/admin`)
4. Vai su "Telegraph Bots"
5. Crea un nuovo bot inserendo il token
6. Configura il webhook automaticamente tramite l'interfaccia

### Configurazione Webhook

Il webhook viene configurato automaticamente quando crei o aggiorni un bot nel pannello Filament. L'URL sarà:

```
https://your-domain.com/telegraph/webhook/{bot-token}
```

Assicurati che la tua applicazione sia raggiungibile pubblicamente (usa ngrok per testing locale).

### Configurazione Servizi Esterni

Aggiungi le seguenti variabili al file `.env` per abilitare i servizi esterni:

```env
# OpenWeatherMap per il meteo
OPENWEATHER_API_KEY=your_api_key

# Google Cloud Vision per OCR
GOOGLE_CLOUD_VISION_API_KEY=your_api_key

# LibreTranslate o Google Translate
TRANSLATE_API_KEY=your_api_key
TRANSLATE_API_URL=https://libretranslate.com
```

## 🤖 Comandi Bot

### Comandi Base
- `/start` - Avvia il bot e mostra il messaggio di benvenuto
- `/help` - Mostra l'elenco dei comandi disponibili
- `/info` - Mostra informazioni sul bot e statistiche

### RSS e Notizie
- `/rss` - Gestisci i feed RSS
- `/rss add <url>` - Aggiungi un nuovo feed RSS
- `/rss list` - Mostra tutti i feed attivi
- `/rss remove <id>` - Rimuovi un feed

### Promemoria
- `/promemoria <messaggio> tra <tempo>` - Crea un promemoria
- `/promemoria list` - Mostra tutti i promemoria
- `/promemoria delete <id>` - Elimina un promemoria

Esempi:
```
/promemoria Comprare il pane tra 30 minuti
/promemoria Riunione domani alle 15:00
/promemoria Chiamare Mario tra 2 ore
```

### Liste della Spesa
- `/lista` - Visualizza la lista attiva
- `/lista nuova <nome>` - Crea una nuova lista
- `/lista aggiungi <item>` - Aggiungi un elemento
- `/lista check <id>` - Segna un elemento come completato
- `/lista rimuovi <id>` - Rimuovi un elemento

### Utilità
- `/calc <espressione>` - Calcolatrice
- `/password [length]` - Genera password sicura
- `/qr <testo>` - Genera QR code
- `/meteo <città>` - Ottieni previsioni meteo
- `/traduci <lang> <testo>` - Traduci testo
- `/shorten <url>` - Accorcia URL
- `/cf` - Calcola codice fiscale italiano

### Giochi
- `/dado [facce]` - Lancia un dado (6, 20 o 100 facce)
- `/moneta` - Lancia una moneta
- `/8ball <domanda>` - Chiedi a Magic 8-Ball
- `/random <min> <max>` - Numero casuale
- `/dadjoke` - Barzelletta casuale
- `/quote` - Citazione motivazionale

## 🎛️ Pannello Amministrativo

Accedi al pannello Filament all'indirizzo `/admin` con le credenziali create.

### Risorse Disponibili

#### Telegraph Bots
- Gestione completa dei bot
- Configurazione token e webhook
- Statistiche per bot
- Attivazione/disattivazione

#### Telegraph Chats
- Lista di tutte le chat/gruppi
- Informazioni sui membri
- Configurazioni per chat
- Cronologia messaggi

#### RSS Feeds
- Gestione feed RSS/Atom
- Configurazione intervalli di aggiornamento
- Test feed in tempo reale
- Statistiche articoli pubblicati

#### Reminders
- Visualizzazione tutti i promemoria
- Filtri per stato (pending/sent)
- Modifica/eliminazione
- Statistiche utilizzo

#### Shopping Lists
- Gestione liste e elementi
- Visualizzazione per chat
- Statistiche utilizzo

#### Auto Responses
- Creazione risposte automatiche
- Pattern matching con regex
- Priorità e condizioni
- Attivazione per chat specifica

#### Bot Commands
- Lista comandi personalizzati
- Configurazione descrizioni
- Alias e scorciatoie

#### Bot Logs
- Log completo di tutte le attività
- Filtri per tipo, bot, chat
- Ricerca full-text
- Export dati

#### Shortened URLs
- Gestione URL accorciati
- Statistiche di accesso
- Click tracking

### Dashboard Widgets
- Messaggi ricevuti oggi/settimana/mese
- Comandi eseguiti
- Errori recenti
- RSS articoli pubblicati
- Promemoria inviati
- Grafici attività

## 🧪 Testing

Il progetto include test completi scritti con Pest 4.

### Esegui tutti i test

```bash
php artisan test
```

### Esegui test specifici

```bash
# Test di un file specifico
php artisan test tests/Feature/Models/ReminderTest.php

# Test con filtro sul nome
php artisan test --filter=ReminderTest
```

### Coverage dei test

Il progetto include 34 test con 131 assertions che coprono:

- **Reminder Model** (7 tests): Creazione, relazioni, scope, metodi
- **Shopping List** (8 tests): CRUD, relazioni, filtering
- **Shopping List Items** (6 tests): Operazioni, toggle, scope
- **BotRateLimiter** (7 tests): Rate limiting multi-tier
- **SendDueRemindersCommand** (6 tests): Invio promemoria, error handling

### Analisi statica

```bash
# PHPStan
./vendor/bin/phpstan analyse

# Larastan
composer analyse
```

### Code formatting

```bash
# Laravel Pint
./vendor/bin/pint

# Verifica stile senza modifiche
./vendor/bin/pint --test
```

## 🏗️ Architettura

### Directory Structure

```
app/
├── Actions/              # Azioni riutilizzabili
├── Console/
│   └── Commands/         # Comandi Artisan personalizzati
├── Filament/
│   ├── Resources/        # Risorse Filament CRUD
│   └── Widgets/          # Dashboard widgets
├── Http/
│   └── Controllers/      # Controllers HTTP
├── Models/               # Eloquent Models
├── Services/             # Service classes (BotRateLimiter, ecc.)
└── Traits/               # Traits riutilizzabili

database/
├── factories/            # Model factories per testing
├── migrations/           # Database migrations
└── seeders/              # Database seeders

tests/
├── Feature/              # Test di feature
│   ├── Commands/
│   ├── Models/
│   └── Services/
└── Unit/                 # Unit tests

resources/
└── views/
    ├── components/       # Blade components
    └── livewire/         # Livewire components
```

### Modelli Principali

- **TelegraphBot**: Rappresenta un bot Telegram
- **TelegraphChat**: Rappresenta una chat o gruppo
- **RssFeed**: Feed RSS da monitorare
- **Reminder**: Promemoria programmati
- **ShoppingList**: Lista della spesa
- **ShoppingListItem**: Elemento di una lista
- **AutoResponse**: Risposta automatica configurabile
- **BotCommand**: Comando personalizzato
- **BotLog**: Log di attività del bot
- **ShortenedUrl**: URL accorciato

### Servizi

- **BotRateLimiter**: Gestione rate limiting con 3 tier (heavy/medium/light)
- **RssService**: Parsing e gestione feed RSS
- **OcrService**: Estrazione testo da immagini
- **CodiceFiscaleService**: Calcolo codice fiscale italiano

### Scheduled Tasks

I seguenti task sono schedulati automaticamente (vedi `routes/console.php`):

- `reminders:send` - Ogni minuto, invia promemoria dovuti
- `rss:check` - Ogni 15 minuti, controlla nuovi articoli RSS
- `logs:cleanup` - Ogni giorno, pulisce log vecchi

## 🔐 Sicurezza

### Rate Limiting

Il sistema implementa rate limiting a tre livelli:

```php
// Heavy operations (5 req/min)
'heavy' => ['qr', 'translate', 'ocr', 'weather']

// Medium operations (10 req/min)
'medium' => ['calc', 'password', 'shorten', 'cf']

// Light operations (20 req/min)
'light' => ['dado', 'moneta', '8ball', 'random', 'dadjoke', 'quote']
```

### Validazione Input

Tutti gli input utente sono validati e sanitizzati prima dell'elaborazione.

### Logging

Tutte le operazioni sono loggate per audit e debugging tramite:
- BotLog model per attività specifiche del bot
- Activity Log di Spatie per audit trail
- Laravel Log per errori di sistema

## 📝 Licenza

Questo progetto è sotto licenza MIT. Vedi il file LICENSE per dettagli.

## 🤝 Contributi

I contributi sono benvenuti! Per favore:

1. Fork il progetto
2. Crea un feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit le modifiche (`git commit -m 'Add some AmazingFeature'`)
4. Push al branch (`git push origin feature/AmazingFeature`)
5. Apri una Pull Request

## 📧 Contatti

Per domande o supporto, apri una issue su GitHub.

## 🙏 Ringraziamenti

- [Laravel](https://laravel.com)
- [Filament](https://filamentphp.com)
- [Telegraph](https://github.com/defstudio/telegraph)
- [Pest](https://pestphp.com)
- Tutti i contributori open source delle librerie utilizzate
