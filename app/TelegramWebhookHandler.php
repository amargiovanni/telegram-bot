<?php

declare(strict_types=1);

namespace App;

use App\Models\AutoResponse;
use App\Models\BotCommand;
use App\Models\BotLog;
use App\Models\Reminder;
use App\Models\ShortenedUrl;
use App\Services\BotRateLimiter;
use App\Services\FiscalCodeCalculator;
use App\Services\SafeMathCalculator;
use DefStudio\Telegraph\Handlers\WebhookHandler;
use DefStudio\Telegraph\Models\TelegraphChat;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\Writer\PngWriter;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;

class TelegramWebhookHandler extends WebhookHandler
{
    public function start(): void
    {
        $this->chat->html("👋 <b>Welcome!</b>\n\nI'm a configurable Telegram bot. Use /help to see available commands.")->send();

        BotLog::log(
            'command_executed',
            $this->bot->id,
            $this->chat->id,
            'Start command executed',
            ['user' => $this->message->from()->username()]
        );
    }

    public function help(): void
    {
        // Cache bot commands for 1 hour
        $cacheKey = "bot_commands:{$this->bot->id}";

        $commands = Cache::remember($cacheKey, 3600, function () {
            return BotCommand::where('telegraph_bot_id', $this->bot->id)
                ->active()
                ->inMenu()
                ->get();
        });

        $helpText = "📚 <b>Available Commands:</b>\n\n";

        foreach ($commands as $command) {
            $helpText .= "/{$command->command} - {$command->description}\n";
        }

        $this->chat->html($helpText)->send();
    }

    public function shorten(): void
    {
        // Rate limiting
        if (! $this->checkRateLimit('shorten', 'medium')) {
            return;
        }

        $text = $this->message->text();
        $url = trim(str_replace('/shorten', '', $text));

        // Validate URL
        $validator = Validator::make(['url' => $url], [
            'url' => 'required|url|max:2000',
        ]);

        if ($validator->fails()) {
            $this->chat->html("❌ <b>Invalid URL</b>\n\nPlease provide a valid URL.\n\nExample: <code>/shorten https://example.com</code>")->send();

            return;
        }

        // Create shortened URL
        $shortenedUrl = ShortenedUrl::create([
            'telegraph_bot_id' => $this->bot->id,
            'telegraph_chat_id' => $this->chat->id,
            'original_url' => $url,
            'short_code' => ShortenedUrl::generateUniqueCode(),
            'is_active' => true,
        ]);

        // Log the creation
        BotLog::log(
            'url_shortened',
            $this->bot->id,
            $this->chat->id,
            "URL shortened: {$shortenedUrl->short_code}",
            [
                'short_code' => $shortenedUrl->short_code,
                'original_url' => $url,
                'short_url' => $shortenedUrl->getShortUrl(),
            ]
        );

        // Send response with shortened URL
        $response = "✅ <b>URL Shortened!</b>\n\n";
        $response .= "🔗 Short URL: <code>{$shortenedUrl->getShortUrl()}</code>\n\n";
        $response .= "📊 Original: <i>{$url}</i>";

        $this->chat->html($response)->send();
    }

    public function cf(): void
    {
        // Rate limiting
        if (! $this->checkRateLimit('cf', 'heavy')) {
            return;
        }

        $text = $this->message->text();
        $params = trim(str_replace('/cf', '', $text));

        if (empty($params)) {
            $helpMessage = "🆔 <b>Calcolo Codice Fiscale</b>\n\n";
            $helpMessage .= "Invia i dati nel formato:\n";
            $helpMessage .= "<code>/cf Cognome|Nome|GG/MM/AAAA|M/F|CodiceComune</code>\n\n";
            $helpMessage .= "<b>Esempio:</b>\n";
            $helpMessage .= "<code>/cf Rossi|Mario|15/03/1980|M|H501</code>\n\n";
            $helpMessage .= '📌 Il codice comune (Belfiore) è di 4 caratteri (es: H501 per Roma)';

            $this->chat->html($helpMessage)->send();

            return;
        }

        // Parse parameters
        $parts = explode('|', $params);

        if (count($parts) !== 5) {
            $this->chat->html("❌ <b>Formato errato!</b>\n\nUsa: <code>/cf Cognome|Nome|GG/MM/AAAA|M/F|CodiceComune</code>")->send();

            return;
        }

        [$surname, $name, $birthDate, $gender, $birthPlace] = array_map('trim', $parts);

        // Validate inputs
        $validator = Validator::make([
            'surname' => $surname,
            'name' => $name,
            'birth_date' => $birthDate,
            'gender' => $gender,
            'birth_place' => $birthPlace,
        ], [
            'surname' => 'required|string|min:2',
            'name' => 'required|string|min:2',
            'birth_date' => 'required|date_format:d/m/Y',
            'gender' => 'required|in:M,F,m,f',
            'birth_place' => 'required|string|size:4',
        ]);

        if ($validator->fails()) {
            $errors = implode("\n", $validator->errors()->all());
            $this->chat->html("❌ <b>Dati non validi!</b>\n\n{$errors}")->send();

            return;
        }

        try {
            $calculator = new FiscalCodeCalculator;
            $fiscalCode = $calculator->calculate($surname, $name, $birthDate, $gender, $birthPlace);

            BotLog::log(
                'command_executed',
                $this->bot->id,
                $this->chat->id,
                'Fiscal code calculated',
                [
                    'surname' => $surname,
                    'name' => $name,
                    'birth_date' => $birthDate,
                    'gender' => $gender,
                    'fiscal_code' => $fiscalCode,
                ]
            );

            $response = "✅ <b>Codice Fiscale Calcolato!</b>\n\n";
            $response .= "👤 <b>Dati:</b>\n";
            $response .= "• Cognome: {$surname}\n";
            $response .= "• Nome: {$name}\n";
            $response .= "• Data di nascita: {$birthDate}\n";
            $response .= "• Sesso: {$gender}\n\n";
            $response .= "🆔 <b>Codice Fiscale:</b>\n";
            $response .= "<code>{$fiscalCode}</code>";

            $this->chat->html($response)->send();
        } catch (Exception $e) {
            BotLog::log(
                'error',
                $this->bot->id,
                $this->chat->id,
                'Fiscal code calculation error',
                ['error' => $e->getMessage()]
            );

            $this->chat->html("❌ <b>Errore nel calcolo!</b>\n\nRiprova o verifica i dati inseriti.")->send();
        }
    }

    public function barzelletta(): void
    {
        $jokes = [
            'Perché i programmatori preferiscono il buio? Perché la luce attrae i bug! 🐛',
            'Come si chiama un dinosauro programmatore? T-REX-t Editor! 🦖💻',
            'Perché i developer odiano la natura? Troppi bug! 🌳🐜',
            'Ho chiesto al mio PC di raccontarmi una barzelletta... ha mandato in crash! 💥',
            'Quanti programmatori servono per cambiare una lampadina? Nessuno, è un problema hardware! 💡',
            'Il mio codice non ha bug, ha solo funzionalità non documentate! 📝',
            'There are 10 types of people: quelli che capiscono il binario e quelli che no! 01',
            "Ho un sacco di RAM ma nessun ricordo di dove l'ho messa! 🧠",
            "Perché il programmatore è morto sotto la doccia? L'etichetta dello shampoo diceva: lather, rinse, repeat! 🚿",
            '404: Barzelletta not found! 🔍',
        ];

        $joke = $jokes[array_rand($jokes)];
        $this->chat->html("😂 <b>Barzelletta del giorno:</b>\n\n{$joke}")->send();
    }

    public function insulto(): void
    {
        $insults = [
            "Sei così lento che quando corri all'indietro vai avanti! 🐌",
            'Il tuo QI è talmente basso che serve una scala per raggiungerlo! 🪜',
            "Hai la stessa utilità di un bottone su un'auto senza volante! 🚗",
            'Sei come Internet Explorer: lento, obsoleto e nessuno ti usa più! 🌐',
            'Il tuo codice fa più danni di Godzilla a Tokyo! 🦖🏙️',
            'Sei così confuso che quando guardi una mappa pensi sia un labirinto! 🗺️',
            'Hai meno personalità di un bug report! 🐛📝',
            'Sei come un redirect loop: vai sempre in tondo senza senso! 🔄',
            'Il tuo debugging skill è come cercare un gatto nero in una stanza buia... che non esiste! 🐈‍⬛',
            'Sei più inutile di un floppy disk nel 2025! 💾',
        ];

        $insult = $insults[array_rand($insults)];
        $this->chat->html("😈 <b>Insulto Friendly:</b>\n\n{$insult}\n\n<i>(Scherzo, ti voglio bene! ❤️)</i>")->send();
    }

    public function motivazione(): void
    {
        $motivations = [
            "🌟 Oggi puoi fare grandi cose... oppure no, fa' un po' come ti pare!",
            '💪 Ricorda: anche il sole ha le sue macchie, quindi smetti di preoccuparti dei tuoi bug!',
            "🚀 Il successo è dietro l'angolo... o forse è dall'altra parte della città. Boh!",
            '✨ Credi in te stesso! Almeno uno deve farlo...',
            '🎯 Ogni fallimento è un passo verso il successo. Quindi sei già a metà strada!',
            '🌈 La vita è come il codice: piena di errori ma bellissima quando compila!',
            '⭐ Non mollare mai! O forse sì, dipende quanto sei stanco...',
            '🔥 Sei un campione! Disclaimer: potrebbero esserci campioni migliori.',
            '💎 Sei prezioso come un diamante! Anche se il carbone era più utile...',
            '🏆 Il tuo potenziale è illimitato! Peccato che anche la tua pigrizia lo sia!',
        ];

        $motivation = $motivations[array_rand($motivations)];
        $this->chat->html("<b>Motivazione Quotidiana:</b>\n\n{$motivation}")->send();
    }

    public function consiglio(): void
    {
        $advices = [
            '📌 Quando non sai che fare, premi F5 e vedi cosa succede!',
            '💡 Se il codice non funziona, aggiungi più console.log(). Sempre!',
            "🎲 Quando sei in dubbio: riavvia il server. Funziona l'80% delle volte!",
            '🔧 Backup? Quello che fai 5 minuti DOPO aver perso tutto!',
            '☕ Il caffè non risolve i problemi... ma neanche il tè, quindi tanto vale!',
            "🎯 Non usare mai 'test' come password. Usa 'test123' per più sicurezza!",
            '🌟 Se funziona, non toccarlo. Se non funziona... comunque non toccarlo!',
            '📚 Leggere la documentazione è per i deboli. Vai a tentativi! (disclaimer: pessimo consiglio)',
            '🚀 Deploy on Friday? Solo se ami il weekend emozionante!',
            '🎨 CSS è facile! Disse nessun developer mai...',
        ];

        $advice = $advices[array_rand($advices)];
        $this->chat->html("<b>Consiglio del Giorno:</b>\n\n{$advice}")->send();
    }

    public function fortuna(): void
    {
        $fortunes = [
            '🔮 Il tuo futuro è radioso... o forse è solo il monitor troppo luminoso!',
            '✨ Presto incontrerai qualcuno speciale... probabilmente un altro bug!',
            '🍀 La fortuna ti sorriderà! (Disclaimer: potrebbe essere sarcasmo)',
            '🌠 Una grande opportunità bussa alla tua porta... o forse è solo il postino!',
            '💫 I numeri fortunati di oggi: 404, 500, 502',
            '🎰 Oggi è il tuo giorno fortunato! (Valido fino a mezzanotte)',
            '🌟 Grande successo ti aspetta... nella prossima vita!',
            '🎲 La fortuna è dalla tua parte! (Ma potrebbe cambiare idea)',
            '✨ Un evento straordinario cambierà la tua giornata: la connessione WiFi funzionerà!',
            '🍀 Il tuo codice compilerà al primo tentativo! (Ah no, scusa, mi sbagliavo)',
        ];

        $fortune = $fortunes[array_rand($fortunes)];
        $this->chat->html("<b>Biscotto della Fortuna:</b>\n\n{$fortune}")->send();
    }

    public function decisione(): void
    {
        $text = $this->message->text();
        $question = trim(str_replace('/decisione', '', $text));

        $decisions = [
            '✅ Sì, assolutamente!',
            '❌ No, scordatelo!',
            '🤔 Forse... ma anche no!',
            '💯 Certo, vai tranquillo!',
            '🚫 Pessima idea!',
            '🎲 Tira una moneta, io non decido!',
            '⚠️ A tuo rischio e pericolo!',
            '🌟 È il momento giusto!',
            '⏰ Riprova domani!',
            '🤷 Boh, fa\' come ti pare!',
            '💪 Fallo! YOLO!',
            '🧠 Usa il cervello questa volta!',
            '🔥 Solo se sei pazzo!',
            '❄️ Meglio di no...',
            '🎯 Centro! Vai!',
        ];

        $decision = $decisions[array_rand($decisions)];

        if (empty($question)) {
            $response = "🎯 <b>Aiuto Decisionale</b>\n\n";
            $response .= "Fammi una domanda e deciderò per te!\n\n";
            $response .= "<b>Esempio:</b>\n";
            $response .= '<code>/decisione Devo fare il deploy?</code>';
        } else {
            $response = "❓ <b>Domanda:</b>\n<i>{$question}</i>\n\n";
            $response .= "🎱 <b>Responso:</b>\n{$decision}";
        }

        $this->chat->html($response)->send();
    }

    public function pizza(): void
    {
        $pizzas = [
            '🍕 Margherita - Il classico intramontabile!',
            '🍕 Diavola - Piccante come il tuo codice!',
            '🍕 Quattro Stagioni - Una per ogni sprint!',
            '🍕 Capricciosa - Come i tuoi requisiti del cliente!',
            '🍕 Quattro Formaggi - Debugging a strati!',
            '🍕 Marinara - Minimalista come il tuo primo commit!',
            '🍕 Bufalina - Premium come il tuo server cloud!',
            '🍕 Prosciutto e Funghi - Un mix perfetto!',
            '🍕 Tonno e Cipolla - Controversa ma buona!',
            '🍕 Vegetariana - Per i dev eco-friendly!',
            '🍕 Hawaiana - Polarizzante come tabs vs spaces!',
            '🍕 Rustica - Robusta e affidabile!',
        ];

        $pizza = $pizzas[array_rand($pizzas)];
        $this->chat->html("🍕 <b>Pizza Consigliata:</b>\n\n{$pizza}\n\n<i>Buon appetito! 😋</i>")->send();
    }

    public function scusa(): void
    {
        $excuses = [
            '🤷 "Funzionava sul mio computer!"',
            '⚠️ "È colpa del browser dell\'utente!"',
            '🌐 "Deve essere un problema di rete!"',
            '💾 "Non ho salvato prima del crash!"',
            '🐛 "Non è un bug, è una feature!"',
            '📝 "La documentazione non era chiara!"',
            '⏰ "Non ho avuto abbastanza tempo!"',
            '🔧 "Il framework ha un bug!"',
            '👤 "L\'altro developer ha toccato quel file!"',
            '☕ "Non avevo ancora preso il caffè!"',
            '🌙 "Era tardi e avevo sonno!"',
            '💻 "Il deployment automatico ha fatto casino!"',
            '🎯 "I requisiti cambiano sempre!"',
            '🚀 "Ci pensiamo nel prossimo refactoring!"',
            '📱 "Funziona solo su desktop!"',
        ];

        $excuse = $excuses[array_rand($excuses)];
        $this->chat->html("😅 <b>Scusa Pronta:</b>\n\n{$excuse}\n\n<i>Usa con moderazione! 😉</i>")->send();
    }

    public function qr(): void
    {
        // Rate limiting
        if (! $this->checkRateLimit('qr', 'heavy')) {
            return;
        }

        $text = $this->message->text();
        $data = trim(str_replace('/qr', '', $text));

        if (empty($data)) {
            $helpMessage = "📱 <b>Generatore QR Code</b>\n\n";
            $helpMessage .= "Invia il testo o URL da codificare:\n";
            $helpMessage .= "<code>/qr https://example.com</code>\n\n";
            $helpMessage .= "Oppure un testo qualsiasi:\n";
            $helpMessage .= '<code>/qr Il mio testo segreto</code>';

            $this->chat->html($helpMessage)->send();

            return;
        }

        try {
            $result = Builder::create()
                ->writer(new PngWriter)
                ->data($data)
                ->encoding(new Encoding('UTF-8'))
                ->size(400)
                ->margin(10)
                ->build();

            $filename = 'qr_'.Str::random(10).'.png';
            $path = 'temp/'.$filename;

            Storage::put($path, $result->getString());
            $fullPath = Storage::path($path);

            $this->chat->photo($fullPath)->send();

            Storage::delete($path);

            BotLog::log(
                'command_executed',
                $this->bot->id,
                $this->chat->id,
                'QR code generated',
                ['data' => Str::limit($data, 100)]
            );
        } catch (Exception $e) {
            $this->chat->html("❌ <b>Errore nella generazione del QR code!</b>\n\nRiprova con un testo più breve.")->send();
        }
    }

    public function dado(): void
    {
        $text = $this->message->text();
        $params = trim(str_replace('/dado', '', $text));

        // Parse number of dice (default 1, max 10)
        $numDice = 1;
        if (! empty($params) && is_numeric($params)) {
            $numDice = max(1, min(10, (int) $params));
        }

        $results = [];
        $total = 0;

        for ($i = 0; $i < $numDice; $i++) {
            $roll = random_int(1, 6);
            $results[] = $this->getDiceEmoji($roll);
            $total += $roll;
        }

        $response = '🎲 <b>Lancio '.($numDice === 1 ? 'del dado' : "di {$numDice} dadi").":</b>\n\n";
        $response .= implode(' ', $results)."\n\n";

        if ($numDice > 1) {
            $response .= "📊 Totale: <b>{$total}</b>";
        }

        $this->chat->html($response)->send();
    }

    public function quiz(): void
    {
        $quizzes = [
            ['q' => 'Qual è il linguaggio di programmazione più usato al mondo?', 'a' => 'JavaScript'],
            ['q' => 'Chi ha creato Linux?', 'a' => 'Linus Torvalds'],
            ['q' => 'Cosa significa HTML?', 'a' => 'HyperText Markup Language'],
            ['q' => 'In che anno è nato PHP?', 'a' => '1995'],
            ['q' => 'Qual è la porta di default per HTTP?', 'a' => '80'],
            ['q' => 'Cosa significa CSS?', 'a' => 'Cascading Style Sheets'],
            ['q' => 'Chi ha creato Python?', 'a' => 'Guido van Rossum'],
            ['q' => 'Qual è il protocollo sicuro di HTTP?', 'a' => 'HTTPS'],
            ['q' => 'Cosa significa SQL?', 'a' => 'Structured Query Language'],
            ['q' => 'In che anno è stato rilasciato il primo iPhone?', 'a' => '2007'],
        ];

        $quiz = $quizzes[array_rand($quizzes)];

        $response = "🧠 <b>Quiz Tech!</b>\n\n";
        $response .= "❓ {$quiz['q']}\n\n";
        $response .= "<i>Risposta nascosta qui sotto... </i>\n";
        $response .= "<tg-spoiler>{$quiz['a']}</tg-spoiler>";

        $this->chat->html($response)->send();
    }

    public function password(): void
    {
        // Rate limiting
        if (! $this->checkRateLimit('password', 'medium')) {
            return;
        }

        $text = $this->message->text();
        $params = trim(str_replace('/password', '', $text));

        $length = 16;
        if (! empty($params) && is_numeric($params)) {
            $length = max(8, min(64, (int) $params));
        }

        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+-=[]{}|;:,.<>?';
        $password = '';

        // Use cryptographically secure random_int() instead of rand()
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }

        $strength = $this->evaluatePasswordStrength($password);

        $response = "🔐 <b>Password Generata!</b>\n\n";
        $response .= "Password: <code>{$password}</code>\n\n";
        $response .= "📏 Lunghezza: {$length} caratteri\n";
        $response .= "💪 Sicurezza: {$strength}\n\n";
        $response .= '<i>Clicca per copiare!</i>';

        $this->chat->html($response)->send();
    }

    public function calc(): void
    {
        // Rate limiting
        if (! $this->checkRateLimit('calc', 'heavy')) {
            return;
        }

        $text = $this->message->text();
        $expression = trim(str_replace('/calc', '', $text));

        if (empty($expression)) {
            $helpMessage = "🧮 <b>Calcolatrice</b>\n\n";
            $helpMessage .= "Invia un'espressione matematica:\n";
            $helpMessage .= "<code>/calc 2 + 2</code>\n";
            $helpMessage .= "<code>/calc 10 * 5 + 3</code>\n";
            $helpMessage .= "<code>/calc (100 - 20) / 4</code>\n\n";
            $helpMessage .= 'Operatori: + - * / ( )';

            $this->chat->html($helpMessage)->send();

            return;
        }

        try {
            // Use SafeMathCalculator instead of dangerous eval()
            $calculator = new SafeMathCalculator;
            $result = $calculator->calculate($expression);

            $response = "🧮 <b>Risultato:</b>\n\n";
            $response .= "<code>{$expression}</code>\n";
            $response .= '= <b>'.number_format($result, 2, ',', '.').'</b>';

            $this->chat->html($response)->send();
        } catch (Exception $e) {
            $this->chat->html("❌ <b>Errore nel calcolo!</b>\n\nVerifica l'espressione e riprova.")->send();
        }
    }

    public function moneta(): void
    {
        $result = random_int(0, 1) === 1 ? 'Testa' : 'Croce';
        $emoji = $result === 'Testa' ? '🪙' : '💰';

        $response = "🎲 <b>Lancio della Moneta</b>\n\n";
        $response .= "{$emoji} <b>{$result}!</b>";

        $this->chat->html($response)->send();
    }

    public function indovina(): void
    {
        $number = random_int(1, 100);

        $response = "🎯 <b>Indovina il Numero!</b>\n\n";
        $response .= "Ho pensato a un numero tra 1 e 100.\n";
        $response .= "Prova a indovinarlo!\n\n";
        $response .= "<i>Numero nascosto qui sotto...</i>\n";
        $response .= "<tg-spoiler>{$number}</tg-spoiler>\n\n";
        $response .= '💡 Scoprilo con un click!';

        $this->chat->html($response)->send();
    }

    public function info(): void
    {
        $response = "ℹ️ <b>Informazioni Bot</b>\n\n";
        $response .= "🤖 Bot: {$this->bot->name}\n";
        $response .= "💬 Chat: {$this->chat->name}\n";
        $response .= "🆔 Chat ID: <code>{$this->chat->chat_id}</code>\n\n";
        $response .= "⚡ <b>Comandi Disponibili:</b>\n";
        $response .= "/help - Lista comandi\n";
        $response .= "/qr - Genera QR code\n";
        $response .= "/shorten - Accorcia URL\n";
        $response .= "/cf - Codice fiscale\n";
        $response .= "/password - Genera password\n";
        $response .= "/calc - Calcolatrice\n";
        $response .= "/meteo - Previsioni meteo\n";
        $response .= "/traduci - Traduci testo\n";
        $response .= "/ocr - Estrai testo da immagine\n";
        $response .= "/news - Ultime notizie\n";
        $response .= "/promemoria - Imposta promemoria\n";
        $response .= "/dado - Lancia dadi\n";
        $response .= "/moneta - Lancia moneta\n";
        $response .= "/quiz - Quiz random\n";
        $response .= "/indovina - Indovina numero\n\n";
        $response .= "😄 <b>Fun:</b>\n";
        $response .= "/barzelletta /insulto /motivazione\n";
        $response .= "/consiglio /fortuna /decisione\n";
        $response .= '/pizza /scusa';

        $this->chat->html($response)->send();
    }

    public function ocr(): void
    {
        // Rate limiting
        if (! $this->checkRateLimit('ocr', 'heavy')) {
            return;
        }

        $helpMessage = "📸 <b>OCR - Estrazione Testo</b>\n\n";
        $helpMessage .= "Invia un'immagine con testo e usa il comando:\n";
        $helpMessage .= "<code>/ocr</code>\n\n";
        $helpMessage .= "Supporta: screenshot, documenti, foto, meme\n";
        $helpMessage .= 'Lingue: ITA, ENG, ESP, FRA, DEU';

        // Check if message has photo
        if (! $this->message->photos() || count($this->message->photos()) === 0) {
            $this->chat->html($helpMessage)->send();

            return;
        }

        try {
            // Get the largest photo
            $photos = $this->message->photos();
            $photo = end($photos);

            // Download photo from Telegram
            $fileId = $photo['file_id'];
            $file = $this->bot->getFile($fileId);
            $filePath = $file['result']['file_path'];
            $fileUrl = "https://api.telegram.org/file/bot{$this->bot->token}/{$filePath}";

            // Use OCR.space API (free tier, no key required for basic usage)
            $ch = curl_init('https://api.ocr.space/parse/imageurl');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, [
                'url' => $fileUrl,
                'language' => 'ita',
                'isOverlayRequired' => false,
                'detectOrientation' => true,
                'scale' => true,
                'OCREngine' => 2,
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $result = curl_exec($ch);
            curl_close($ch);

            if (! $result) {
                throw new Exception('OCR service unavailable');
            }

            $data = json_decode($result, true);

            if (! isset($data['ParsedResults']) || empty($data['ParsedResults'])) {
                throw new Exception('No text found in image');
            }

            $text = trim($data['ParsedResults'][0]['ParsedText']);

            if (empty($text)) {
                $this->chat->html("❌ <b>Nessun testo trovato!</b>\n\nL'immagine non contiene testo leggibile.")->send();

                return;
            }

            $response = "📝 <b>Testo Estratto:</b>\n\n";
            $response .= "<code>{$text}</code>\n\n";
            $response .= '💡 <i>Clicca per copiare il testo</i>';

            $this->chat->html($response)->send();

            BotLog::log(
                'command_executed',
                $this->bot->id,
                $this->chat->id,
                'OCR text extracted',
                ['text_length' => strlen($text)]
            );
        } catch (Exception $e) {
            $this->chat->html("❌ <b>Errore OCR!</b>\n\nImpossibile estrarre il testo dall'immagine.")->send();

            BotLog::log(
                'error',
                $this->bot->id,
                $this->chat->id,
                'OCR extraction error',
                ['error' => $e->getMessage()]
            );
        }
    }

    public function news(): void
    {
        // Rate limiting
        if (! $this->checkRateLimit('news', 'medium')) {
            return;
        }

        $text = $this->message->text();
        $category = strtolower(trim(str_replace('/news', '', $text)));

        try {
            // Use NewsAPI (free tier allows limited requests)
            // For production, you'd need an API key from newsapi.org
            // For now, using a public RSS-to-JSON service

            $categories = [
                'tech' => 'https://www.reddit.com/r/technology/.json?limit=5',
                'world' => 'https://www.reddit.com/r/worldnews/.json?limit=5',
                'italia' => 'https://www.reddit.com/r/italy/.json?limit=5',
                '' => 'https://www.reddit.com/r/technology/.json?limit=5',
            ];

            $url = $categories[$category] ?? $categories[''];

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_USERAGENT, 'TelegramBot/1.0');

            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200 || ! $result) {
                throw new Exception('News service unavailable');
            }

            $data = json_decode($result, true);

            if (! isset($data['data']['children']) || empty($data['data']['children'])) {
                throw new Exception('No news found');
            }

            $response = "📰 <b>Ultime Notizie</b>\n";
            $response .= '<i>Categoria: '.($category ?: 'Tech')."</i>\n\n";

            $count = 0;
            foreach ($data['data']['children'] as $item) {
                if ($count >= 5) {
                    break;
                }

                $post = $item['data'];
                $title = Str::limit($post['title'], 100);
                $url = 'https://reddit.com'.$post['permalink'];
                $score = $post['score'];

                $response .= "• <b>{$title}</b>\n";
                $response .= "  👍 {$score} | <a href='{$url}'>Leggi</a>\n\n";

                $count++;
            }

            $response .= "\n💡 <i>Usa: /news tech, /news world, /news italia</i>";

            $this->chat->html($response)->send();

            BotLog::log(
                'command_executed',
                $this->bot->id,
                $this->chat->id,
                'News fetched',
                ['category' => $category ?: 'default']
            );
        } catch (Exception $e) {
            $this->chat->html("❌ <b>Errore nel caricamento notizie!</b>\n\nRiprova più tardi.")->send();

            BotLog::log(
                'error',
                $this->bot->id,
                $this->chat->id,
                'News fetch error',
                ['error' => $e->getMessage()]
            );
        }
    }

    public function promemoria(): void
    {
        // Rate limiting
        if (! $this->checkRateLimit('promemoria', 'medium')) {
            return;
        }

        $text = $this->message->text();
        $params = trim(str_replace('/promemoria', '', $text));

        // Handle list command
        if ($params === 'lista') {
            $reminders = Reminder::where('telegraph_chat_id', $this->chat->id)
                ->pending()
                ->orderBy('remind_at', 'asc')
                ->get();

            if ($reminders->isEmpty()) {
                $this->chat->html("⏰ <b>Nessun promemoria attivo</b>\n\nUsa <code>/promemoria 10m Messaggio</code> per crearne uno.")->send();

                return;
            }

            $response = "⏰ <b>Promemoria Attivi</b>\n\n";
            foreach ($reminders as $reminder) {
                $when = $reminder->remind_at->diffForHumans();
                $response .= "🔔 <b>#{$reminder->id}</b> - {$when}\n";
                $response .= "   {$reminder->message}\n";
                $response .= "   <code>/promemoria cancella {$reminder->id}</code>\n\n";
            }

            $this->chat->html($response)->send();

            return;
        }

        // Handle cancel command
        if (str_starts_with($params, 'cancella ')) {
            $id = (int) trim(str_replace('cancella', '', $params));

            $reminder = Reminder::where('telegraph_chat_id', $this->chat->id)
                ->where('id', $id)
                ->pending()
                ->first();

            if (! $reminder) {
                $this->chat->html("❌ <b>Promemoria non trovato</b>\n\nUsa <code>/promemoria lista</code> per vedere i promemoria attivi.")->send();

                return;
            }

            $reminder->delete();

            $this->chat->html("✅ <b>Promemoria cancellato</b>\n\nIl promemoria #{$id} è stato eliminato.")->send();

            BotLog::log(
                'reminder_cancelled',
                $this->bot->id,
                $this->chat->id,
                "Reminder cancelled: #{$id}",
                ['reminder_id' => $id]
            );

            return;
        }

        // Parse time and message (e.g., "10m Check the oven")
        if (! preg_match('/^(\d+)([mhd])\s+(.+)$/i', $params, $matches)) {
            $helpMessage = "⏰ <b>Imposta Promemoria</b>\n\n";
            $helpMessage .= "📝 <b>Formato:</b>\n";
            $helpMessage .= "<code>/promemoria [tempo] [messaggio]</code>\n\n";
            $helpMessage .= "⏱️ <b>Unità di tempo:</b>\n";
            $helpMessage .= "• <code>m</code> = minuti\n";
            $helpMessage .= "• <code>h</code> = ore\n";
            $helpMessage .= "• <code>d</code> = giorni\n\n";
            $helpMessage .= "📌 <b>Esempi:</b>\n";
            $helpMessage .= "<code>/promemoria 10m Controllare il forno</code>\n";
            $helpMessage .= "<code>/promemoria 1h Riunione importante</code>\n";
            $helpMessage .= "<code>/promemoria 2d Pagare bolletta</code>\n\n";
            $helpMessage .= "📋 <b>Altri comandi:</b>\n";
            $helpMessage .= "<code>/promemoria lista</code> - Vedi promemoria\n";
            $helpMessage .= '<code>/promemoria cancella [id]</code> - Elimina';

            $this->chat->html($helpMessage)->send();

            return;
        }

        $amount = (int) $matches[1];
        $unit = strtolower($matches[2]);
        $message = trim($matches[3]);

        // Calculate remind_at datetime
        $remindAt = now();
        match ($unit) {
            'm' => $remindAt = $remindAt->addMinutes($amount),
            'h' => $remindAt = $remindAt->addHours($amount),
            'd' => $remindAt = $remindAt->addDays($amount),
            default => null,
        };

        // Create reminder
        $reminder = Reminder::create([
            'telegraph_bot_id' => $this->bot->id,
            'telegraph_chat_id' => $this->chat->id,
            'message' => $message,
            'remind_at' => $remindAt,
        ]);

        $when = $remindAt->diffForHumans();

        $response = "✅ <b>Promemoria Impostato!</b>\n\n";
        $response .= "🔔 Ti ricorderò <b>{$when}</b>\n";
        $response .= "📝 Messaggio: <i>{$message}</i>\n\n";
        $response .= "⏰ Data/ora: {$remindAt->format('d/m/Y H:i')}\n";
        $response .= "🆔 ID: <code>{$reminder->id}</code>";

        $this->chat->html($response)->send();

        BotLog::log(
            'reminder_created',
            $this->bot->id,
            $this->chat->id,
            "Reminder created: {$message}",
            [
                'reminder_id' => $reminder->id,
                'remind_at' => $remindAt->toIso8601String(),
            ]
        );
    }

    public function meteo(): void
    {
        // Rate limiting
        if (! $this->checkRateLimit('meteo', 'medium')) {
            return;
        }

        $text = $this->message->text();
        $city = trim(str_replace('/meteo', '', $text));

        if (empty($city)) {
            $helpMessage = "🌤️ <b>Previsioni Meteo</b>\n\n";
            $helpMessage .= "Invia il nome della città:\n";
            $helpMessage .= "<code>/meteo Roma</code>\n";
            $helpMessage .= "<code>/meteo Milano</code>\n";
            $helpMessage .= '<code>/meteo New York</code>';

            $this->chat->html($helpMessage)->send();

            return;
        }

        try {
            // Use wttr.in API (free, no key required)
            $url = 'https://wttr.in/'.urlencode($city).'?format=j1&lang=it';
            $weatherData = file_get_contents($url);

            if ($weatherData === false) {
                throw new Exception('Unable to fetch weather data');
            }

            $data = json_decode($weatherData, true);

            if (! isset($data['current_condition'])) {
                throw new Exception('Invalid weather data');
            }

            $current = $data['current_condition'][0];
            $area = $data['nearest_area'][0] ?? null;

            $temp = $current['temp_C'];
            $feels = $current['FeelsLikeC'];
            $desc = $current['lang_it'][0]['value'] ?? $current['weatherDesc'][0]['value'];
            $humidity = $current['humidity'];
            $wind = $current['windspeedKmph'];
            $pressure = $current['pressure'];

            $location = $area ? ($area['areaName'][0]['value'].', '.$area['country'][0]['value']) : $city;

            $response = "🌤️ <b>Meteo {$location}</b>\n\n";
            $response .= "🌡️ Temperatura: <b>{$temp}°C</b> (percepita {$feels}°C)\n";
            $response .= "☁️ Condizioni: {$desc}\n";
            $response .= "💧 Umidità: {$humidity}%\n";
            $response .= "💨 Vento: {$wind} km/h\n";
            $response .= "🔽 Pressione: {$pressure} hPa\n\n";

            // Next days forecast
            if (isset($data['weather']) && count($data['weather']) > 0) {
                $response .= "<b>📅 Prossimi giorni:</b>\n";
                foreach (array_slice($data['weather'], 0, 3) as $day) {
                    $date = date('d/m', strtotime($day['date']));
                    $maxTemp = $day['maxtempC'];
                    $minTemp = $day['mintempC'];
                    $response .= "• {$date}: {$minTemp}°-{$maxTemp}°C\n";
                }
            }

            $this->chat->html($response)->send();

            BotLog::log(
                'command_executed',
                $this->bot->id,
                $this->chat->id,
                'Weather checked',
                ['city' => $city, 'location' => $location]
            );
        } catch (Exception $e) {
            $this->chat->html("❌ <b>Errore!</b>\n\nCittà non trovata o servizio temporaneamente non disponibile.")->send();

            BotLog::log(
                'error',
                $this->bot->id,
                $this->chat->id,
                'Weather fetch error',
                ['city' => $city, 'error' => $e->getMessage()]
            );
        }
    }

    public function traduci(): void
    {
        // Rate limiting
        if (! $this->checkRateLimit('traduci', 'medium')) {
            return;
        }

        $text = $this->message->text();
        $params = trim(str_replace('/traduci', '', $text));

        if (empty($params)) {
            $helpMessage = "🌐 <b>Traduttore</b>\n\n";
            $helpMessage .= "Invia il testo da tradurre:\n";
            $helpMessage .= "<code>/traduci en:it Hello World</code>\n";
            $helpMessage .= "<code>/traduci it:en Ciao mondo</code>\n";
            $helpMessage .= "<code>/traduci es:it Hola amigo</code>\n\n";
            $helpMessage .= 'Lingue: it, en, es, fr, de, pt, ru, ja, zh';

            $this->chat->html($helpMessage)->send();

            return;
        }

        // Parse format: lang1:lang2 text
        if (! preg_match('/^([a-z]{2}):([a-z]{2})\s+(.+)$/i', $params, $matches)) {
            $this->chat->html("❌ <b>Formato errato!</b>\n\nUsa: <code>/traduci en:it testo</code>")->send();

            return;
        }

        $from = strtolower($matches[1]);
        $to = strtolower($matches[2]);
        $textToTranslate = $matches[3];

        try {
            // Use LibreTranslate API (free, public instance)
            $url = 'https://libretranslate.com/translate';
            $postData = json_encode([
                'q' => $textToTranslate,
                'source' => $from,
                'target' => $to,
                'format' => 'text',
            ]);

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);

            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200 || ! $result) {
                throw new Exception('Translation service unavailable');
            }

            $data = json_decode($result, true);

            if (! isset($data['translatedText'])) {
                throw new Exception('Invalid translation response');
            }

            $translation = $data['translatedText'];

            $response = "🌐 <b>Traduzione</b>\n\n";
            $response .= "📝 Originale ({$from}):\n<i>{$textToTranslate}</i>\n\n";
            $response .= "✅ Tradotto ({$to}):\n<b>{$translation}</b>";

            $this->chat->html($response)->send();

            BotLog::log(
                'command_executed',
                $this->bot->id,
                $this->chat->id,
                'Translation completed',
                ['from' => $from, 'to' => $to, 'length' => strlen($textToTranslate)]
            );
        } catch (Exception $e) {
            $this->chat->html("❌ <b>Errore nella traduzione!</b>\n\nVerifica le lingue e riprova.")->send();

            BotLog::log(
                'error',
                $this->bot->id,
                $this->chat->id,
                'Translation error',
                ['error' => $e->getMessage()]
            );
        }
    }

    public function onChatMemberUpdated(): void
    {
        $update = $this->data->get('my_chat_member');

        if (! $update) {
            return;
        }

        $newStatus = $update['new_chat_member']['status'] ?? null;
        $chatId = $update['chat']['id'] ?? null;
        $chatTitle = $update['chat']['title'] ?? $update['chat']['first_name'] ?? 'Unknown';

        if ($newStatus === 'member' || $newStatus === 'administrator') {
            // Bot was added to a group/chat
            $this->registerOrUpdateChat($chatId, $chatTitle);

            BotLog::log(
                'bot_added_to_group',
                $this->bot->id,
                null,
                "Bot added to: {$chatTitle}",
                ['chat_id' => $chatId, 'status' => $newStatus]
            );
        } elseif ($newStatus === 'left' || $newStatus === 'kicked') {
            BotLog::log(
                'bot_removed_from_group',
                $this->bot->id,
                null,
                "Bot removed from: {$chatTitle}",
                ['chat_id' => $chatId, 'status' => $newStatus]
            );
        }
    }

    protected function handleChatMessage(Stringable $text): void
    {
        // Log incoming message
        BotLog::log(
            'message_received',
            $this->bot->id,
            $this->chat->id,
            'Message received',
            ['text' => $text->toString(), 'user' => $this->message->from()->username()]
        );

        // Check for custom commands first
        if ($text->startsWith('/')) {
            $this->handleCustomCommand($text);

            return;
        }

        // Check for auto-responses
        $this->handleAutoResponses($text->toString());
    }

    protected function handleCustomCommand(Stringable $text): void
    {
        $commandText = $text->after('/')->before(' ')->toString();

        $command = BotCommand::where('telegraph_bot_id', $this->bot->id)
            ->where('command', $commandText)
            ->active()
            ->first();

        if (! $command || ! $command->isAllowedInChat($this->chat->chat_id)) {
            return;
        }

        // Send response based on type
        match ($command->response_type) {
            'photo' => $this->chat->photo($command->media_url)->message($command->response_text)->send(),
            'document' => $this->chat->document($command->media_url)->message($command->response_text)->send(),
            'video' => $this->chat->video($command->media_url)->message($command->response_text)->send(),
            'audio' => $this->chat->audio($command->media_url)->message($command->response_text)->send(),
            default => $this->chat->html($command->response_text)->send(),
        };

        BotLog::log(
            'command_executed',
            $this->bot->id,
            $this->chat->id,
            "Command executed: /{$commandText}",
            ['command_id' => $command->id]
        );
    }

    protected function handleAutoResponses(string $text): void
    {
        $responses = AutoResponse::where('telegraph_bot_id', $this->bot->id)
            ->active()
            ->byPriority()
            ->get();

        foreach ($responses as $response) {
            if (! $response->isAllowedInChat($this->chat->chat_id)) {
                continue;
            }

            if ($response->matches($text)) {
                // Send response
                match ($response->response_type) {
                    'photo' => $this->chat->photo($response->media_url)->message($response->response_text)->send(),
                    'document' => $this->chat->document($response->media_url)->message($response->response_text)->send(),
                    'video' => $this->chat->video($response->media_url)->message($response->response_text)->send(),
                    'audio' => $this->chat->audio($response->media_url)->message($response->response_text)->send(),
                    default => $this->chat->html($response->response_text)->send(),
                };

                // Delete trigger message if configured
                if ($response->delete_trigger_message) {
                    $this->chat->deleteMessage($this->messageId)->send();
                }

                BotLog::log(
                    'auto_response_triggered',
                    $this->bot->id,
                    $this->chat->id,
                    "Auto-response triggered: {$response->name}",
                    ['response_id' => $response->id]
                );

                break; // Only trigger first matching response
            }
        }
    }

    protected function handleUnknownCommand(Stringable $text): void
    {
        BotLog::log(
            'message_received',
            $this->bot->id,
            $this->chat->id,
            'Unknown command received',
            ['command' => $text->toString()]
        );
    }

    protected function registerOrUpdateChat(int $chatId, string $chatName): void
    {
        $chat = TelegraphChat::firstOrCreate(
            [
                'telegraph_bot_id' => $this->bot->id,
                'chat_id' => $chatId,
            ],
            [
                'name' => $chatName,
            ]
        );

        if (! $chat->wasRecentlyCreated) {
            $chat->update(['name' => $chatName]);
        } else {
            BotLog::log(
                'chat_registered',
                $this->bot->id,
                $chat->id,
                "New chat registered: {$chatName}",
                ['chat_id' => $chatId]
            );
        }
    }

    private function getDiceEmoji(int $number): string
    {
        return match ($number) {
            1 => '⚀',
            2 => '⚁',
            3 => '⚂',
            4 => '⚃',
            5 => '⚄',
            6 => '⚅',
            default => '🎲',
        };
    }

    private function evaluatePasswordStrength(string $password): string
    {
        $length = strlen($password);

        if ($length >= 20) {
            return '🟢 Fortissima';
        }

        if ($length >= 16) {
            return '🟡 Forte';
        }

        if ($length >= 12) {
            return '🟠 Media';
        }

        return '🔴 Debole';
    }

    /**
     * Check rate limit for command execution
     */
    private function checkRateLimit(string $command, string $tier = 'medium'): bool
    {
        $rateLimiter = new BotRateLimiter;
        $key = BotRateLimiter::key($this->chat->chat_id, $command);

        if (! $rateLimiter->attempt($key, $tier)) {
            $availableIn = $rateLimiter->availableIn($key);
            $seconds = ceil($availableIn);

            $response = "⏱️ <b>Troppo veloce!</b>\n\n";
            $response .= "Hai raggiunto il limite per questo comando.\n";
            $response .= "Riprova tra <b>{$seconds} secondi</b>.";

            $this->chat->html($response)->send();

            BotLog::log(
                'rate_limit_exceeded',
                $this->bot->id,
                $this->chat->id,
                "Rate limit exceeded for command: {$command}",
                [
                    'command' => $command,
                    'tier' => $tier,
                    'available_in' => $seconds,
                ]
            );

            return false;
        }

        return true;
    }
}
