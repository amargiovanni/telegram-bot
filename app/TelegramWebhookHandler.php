<?php

declare(strict_types=1);

namespace App;

use App\Models\AutoResponse;
use App\Models\BotCommand;
use App\Models\BotLog;
use App\Models\ShortenedUrl;
use App\Services\FiscalCodeCalculator;
use App\Services\SafeMathCalculator;
use DefStudio\Telegraph\Handlers\WebhookHandler;
use DefStudio\Telegraph\Models\TelegraphChat;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\Writer\PngWriter;
use Exception;
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
        $commands = BotCommand::where('telegraph_bot_id', $this->bot->id)
            ->active()
            ->inMenu()
            ->get();

        $helpText = "📚 <b>Available Commands:</b>\n\n";

        foreach ($commands as $command) {
            $helpText .= "/{$command->command} - {$command->description}\n";
        }

        $this->chat->html($helpText)->send();
    }

    public function shorten(): void
    {
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
}
