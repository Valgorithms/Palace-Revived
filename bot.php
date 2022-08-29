<?php

/*
 * This file is a part of the Tutelar project.
 *
 * Copyright (c) 2022-present Valithor Obsidion <valithor@valzargaming.com>
 */

set_time_limit(0);
ignore_user_abort(1);
ini_set('max_execution_time', 0);
ini_set('memory_limit', '-1'); //Unlimited memory usage
define('MAIN_INCLUDED', 1); //Token and SQL credential files may be protected locally and require this to be defined to access
require getcwd() . '/token.php'; //$token
require getcwd() . '/secret.php'; //twitchphp helix secrets
include getcwd() . '/vendor/autoload.php';

$loop = React\EventLoop\Factory::create();
$logger = new Monolog\Logger('New logger');
$logger->pushHandler(new Monolog\Handler\StreamHandler('php://stdout'));
$discord = new \Discord\Discord([
    'loop' => $loop,
    'logger' => $logger,
    /*'socket_options' => [
        'dns' => '8.8.8.8', // can change dns
    ],*/
    'token' => "$token",
    'loadAllMembers' => true,
    'storeMessages' => true, //Required for rolepicker and other functions
    'intents' => Discord\WebSockets\Intents::getDefaultIntents() | Discord\WebSockets\Intents::GUILD_MEMBERS, // default intents as well as guild members
]);
include 'stats_object.php'; 
$stats = new Stats();
$stats->init($discord);
$browser = new \React\Http\Browser($loop);
include 'functions.php'; //execInBackground()
include 'variable_functions.php';

$nick = 'ValZarGaming'; // Twitch username (Case sensitive)
$twitch_options = array(
	//Required
	'secret' => $secret, // Client secret
	'nick' => $nick, 
	'channels' => [
		strtolower($nick), // Your channel
		'smalltowngamingtv', // (Optional) Additional channels
		'rattlesire',
		'shrineplays',
		'violentvixen_',
		'linkdrako',
		'ebonychimera',
	],
	
	//Optional
	'discord' => $discord, // Pass your own instance of DiscordPHP (https://github.com/discord-php/DiscordPHP)	
	'discord_output' => true, // Output Twitch chat to a Discord server
	'guild_id' => '923969098185068594', //ID of the Discord server
	'channel_id' => '924019611534503996', //ID of the Discord channel to output messages to
	
	'loop' => $loop, // Pass your own instance of $loop to share with other ReactPHP applications
	'socket_options' => [
		'dns' => '8.8.8.8', // Can change DNS provider
	],
	'verbose' => true, // Additional output to console (useful for debugging)
	'debug' => true, // Additional output to console (useful for debugging communications with Twitch)
	
	//Custom commands
	'commandsymbol' => [ // Process commands if a message starts with a prefix in this array
		"@$nick", //Users can mention your channel instead of using a command symbol prefix
		'!',
		';',
	],
	'whitelist' => [ // Users who are allowed to use restricted functions
		strtolower($nick), //Your channel
		'smalltowngamingtv',
		'rattlesire',
		'shrineplays',
		'violentvixen_',
		'linkdrako',
		'ebonychimera',
	],
	'badwords' => [ // List of blacklisted words or phrases in their entirety; User will be immediately banned with reason 'badword' if spoken in chat
		'Buy followers, primes and viewers',
		'bigfollows . com',
		'stearncomminuty',
	],
	'social' => [ //NYI
		'twitter' => 'https://twitter.com/valzargaming',
		'discord' => 'https://discord.gg/NU4BS5P36g',
		'youtube' => 'https://www.youtube.com/valzargaming',
	],
	'tip' => [ //NYI
		'paypal' => 'https://www.paypal.com/paypalme/valithor',
		'cashapp' => '$Valithor',
	],
	'responses' => [ // Whenever a message is sent matching a key and prefixed with a command symbol, reply with the defined value
		'ping' => 'Pong!',
		'github' => 'https://github.com/VZGCoders/TwitchPHP',
		//'lurk' => 'You have said the magick word to make yourself invisible to all eyes upon you, allowing you to fade into the shadows.',
		//'return' => 'You have rolled a Nat 1, clearing your invisibility buff from earlier. You might want to roll for initiative…',
	],
	'functions' => [ // Enabled functions usable by anyone
		'help', // Send a list of commands as a chat message
	],
	'restricted_functions' => [ // Enabled functions usable only by whitelisted users
		//'so', //Advertise someone else
	],
	'private_functions' => [ // Enabled functions usable only by the bot owner sharing the same username as the bot
		'php', //Outputs the current version of PHP as a message
		'join', //Joins another user's channel
		'leave', //Leave the current user's channel
		'stop', //Kills the bot
	],
	/*
	`HelixCommandClient => [
		$HelixCommandClient, // Optionally pass your own instance of the HelixCommandClient class
	],
	*/
	'helix' => [ // REQUIRES a bot application https://dev.twitch.tv/console/apps 
		'bot_id' => $bot_id,  // Obtained from application
		'bot_secret' => $bot_secret,  // Obtained from application
		'bot_token' => $bot_token,  // Obtained from your own server using twitch_oauth.php (see example at https://www.valzargaming.com/twitch_oauth/twitch_oauth_template.html)
		'refresh_token' => $refresh_token,  // Obtained from your own server using twitch_oauth.php (see example at https://www.valzargaming.com/twitch_oauth/twitch_oauth_template.html)
		'expires_in' => $expires_in,  // Obtained from your own server using twitch_oauth.php (see example at https://www.valzargaming.com/twitch_oauth/twitch_oauth_template.html)
	],
	/*
	'browser' => new \React\Http\Browser($options['loop']), //Optionally pass your own browser for use by Helix' async commands
	*/
);
$twitch = new Twitch\Twitch($twitch_options);

$options = array(
    'loop' => $loop,
    'discord' => $discord,
    'twitch' => $twitch,
    'browser' => $browser,
    'logger' => $logger,
    'stats' => $stats,
    
    //Configurations
    'command_symbol' => '!s',
    'owner_id' => '116927250145869826', //Valithor#5947
    'tutelar_guild_id' => '923969098185068594', //ValZarGaming
    'files' => [
        'status_path' => getcwd() . '\status.txt',
        'serverinfo' => 'V:\WinNMP2021\WWW\vzg.project\servers\serverinfo.json'
    ],
    'channel_ids' => [
        '923969098185068594' => [
            //'nomads_ooc_channel' => '636644156923445269',
        ],
    ],
    'role_ids' => [
        '923969098185068594' => [
            //'admiral' => '468980650914086913',
        ],
    ],
    'functions' => [
        'ready' => [
            'on_ready' => $on_ready,
            'status_changer_random' => $status_changer_random,
        ],
        'message' => [            
            'on_message' => $on_message,
        ],
        'GUILD_MEMBER_ADD' => [
            //
        ], 
        'misc' => [ //Custom functions
            //DiscordPHP
            'timeout' => $timeout,
            'slash_init' => $slash_init,
            'status_changer' => $status_changer,
            'perm_check' => $perm_check,
            //ReactPHP Browser
            'browser_get' => $browser_get,
            'browser_post' => $browser_post,
            //Message functions
            'debug_direct_message' => $debug_direct_message,
            'debug_guild_message' => $debug_guild_message,
            'debug_any_called_message' => $debug_any_called_message,
            'debug_any_message' => $debug_any_message,
            'owner_message' => $owner_message,
            'admin_message' => $admin_message,
            'moderator_message' => $moderator_message,
            'direct_message' => $direct_message,
            'guild_message' => $guild_message,
            'any_called_message' => $any_called_message,
            'any_message' => $any_message
        ],
    ],
);
include 'tutelar.php';
$tutelar = new Tutelar\Tutelar($options);
include 'webapi.php'; //$socket, $webapi, webapiFail(), webapiSnow();
$tutelar->run();