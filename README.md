# discord-twitch-bot
Quick Discord bot to notify a server when your friends go live!

# Why?
There are loads of Discord Bots out there which will notify you when your favourite streamers go live, but most of them are paid or very limited until you pay. My friends and I were sick of it.
So here, host it yourself! Pay nobody.

# Requirements
PHP >= 8.2
PHP pdo, sqlite, json (events will also speed it up)

# Setup
```bash
git clone git@github.com:AdamHebby/discord-twitch-bot.git
composer install -o
cp .env.example .env
```

Modify .env with all of your secrets, deploy it to your server (or dont) and run it with `php bin/bot.php`.

Make the bot in Discord with the following permissions https://discordlookup.com/permissions-calculator/2147503168

# Usage
## Prerequisites
Assuming at this point:
 - You've cloned this repo and followed the setup guide
 - Made your bot in Discord with the correct permissions

`php bin/bot.php` will start the bot, it will run until you stop it.

The bot will register several commands, take a look in `src/Command` for the list of commands.

NOTE: These are subject to change, but currently the following commands are available:

```txt
Commands:
 - /help - Show this message.
 - /add <streamer> <channel> [custom message] - Add a streamer to watch.
 - /remove <streamer> - Remove a streamer from being watched.
 - /list - List all streamers being watched.
 - /clipswatch <streamer> <channel> - Watch for new clips for a particular streamer and post them in a channel.
 - /clipsunwatch <streamer> - Stop watching for new clips for a particular streamer.
```

# Disclaimer
I am not responsible for what you do with this bot, be careful with rate limits and terms of service. Ensure you read the ToS of both Discord and Twitch before using.

Feel free to look over the code, it's not perfect but it's currently only intended for personal use.
