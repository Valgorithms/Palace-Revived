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

$loop = \React\EventLoop\Loop::get();
$logger = new Monolog\Logger('New logger');
$logger->pushHandler(new Monolog\Handler\StreamHandler('php://stdout'));
$discord = new \Discord\Discord([
    'loop' => $loop,
    'logger' => $logger,
    'cache' => new \Discord\Helpers\CacheConfig($interface = new WyriHaximus\React\Cache\Redis((new Clue\React\Redis\Factory($loop))->createLazyClient('127.0.0.1:6379'), 'dphp:cache:'), $compress = true, $sweep = false),
    /*'socket_options' => [
        'dns' => '8.8.8.8', // can change dns
    ],*/
    'token' => $token,
    'loadAllMembers' => true,
    'storeMessages' => true, //Required for rolepicker and other functions
    'intents' => \Discord\WebSockets\Intents::getDefaultIntents() | \Discord\WebSockets\Intents::GUILD_MEMBERS | \Discord\WebSockets\Intents::MESSAGE_CONTENT // default intents as well as guild members
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
	//Optional
	'discord' => $discord, // Pass your own instance of DiscordPHP (https://github.com/discord-php/DiscordPHP)	
	'discord_output' => true, // Output Twitch chat to a Discord server
	
	'loop' => $loop, // (Optional) Pass your own instance of $loop to share with other ReactPHP applications
	'socket_options' => [
		//'dns' => '8.8.8.8', // Can change DNS provider
	],
	'verbose' => true, // Additional output to console (useful for debugging)
	'debug' => true, // Additional output to console (useful for debugging communications with Twitch)
    'logger' => $logger,
	
	//Custom commands
	'commandsymbol' => [ // Process commands if a message starts with a prefix in this array
		"@$nick", //Users can mention your channel instead of using a command symbol prefix
		'!s',
	],
	'whitelist' => [ // Users who are allowed to use restricted functions
        strtolower($nick), //Your channel
        'shriekingechodanica',
        //'smalltowngamingtv',
        //'rattlesire',
        //'shrineplays',
        //'violentvixen_',
        //'linkdrako',
        //'ebonychimera',
    ],
	'badwords' => [ // List of blacklisted words or phrases in their entirety; User will be immediately banned with reason 'badword' if spoken in chat
		'Buy Followers, primes and viewers',
		'bigfollows . com',
		'stearncomminuty',
        'Get viewers, followers and primes on',
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
		'lurk' => 'You have said the magick word to make yourself invisible to all eyes upon you, allowing you to fade into the shadows.',
		'return' => 'You have rolled a Nat 1, clearing your invisibility buff from earlier. You might want to roll for initiative…',
	],
	'functions' => [ // Enabled functions usable by anyone
		'help', // Send a list of commands as a chat message
	],
	'restricted_functions' => [ // Enabled functions usable only by whitelisted users
		'so', //Advertise someone else
        'ban', //Ban someone with or without a reason included after the username
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
	'helix' => [ // REQUIRES a bot application https://dev.twitch.tv/console/apps 
		'bot_id' => $bot_id,  // Obtained from application
		'bot_secret' => $bot_secret,  // Obtained from application
		'bot_token' => $bot_token,  // Obtained from your own server using twitch_oauth.php (see example at https://www.valzargaming.com/twitch_oauth/twitch_oauth_template.html)
		'refresh_token' => $refresh_token,  // Obtained from your own server using twitch_oauth.php (see example at https://www.valzargaming.com/twitch_oauth/twitch_oauth_template.html)
		'expires_in' => $expires_in,  // Obtained from your own server using twitch_oauth.php (see example at https://www.valzargaming.com/twitch_oauth/twitch_oauth_template.html)
	],
    */
	/*
	'browser' => new \React\Http\Browser($options['loop']), //Optionally pass your own browser for use by Helix' async commands
	*/
);
//Discord servers to relay chat for
//Syntax: $twitch_options['channels']['twitch_channel_name']['discord_guild_id'] = 'discord_channel_id';

//$twitch_options['channels']['shriekingechodanica']['923969098185068594'] = '924019611534503996';
$twitch_options['channels']['shriekingechodanica']['999053951670423643'] = '1014429625826414642';
$twitch_options['channels']['valzargaming']['923969098185068594'] = '924019611534503996';
$twitch_options['channels']['rattlesire']['923969098185068594'] = '924019611534503996';
$twitch_options['channels']['silentwingsstudio']['923969098185068594'] = '924019611534503996';

$twitch_options['channels']['valzargaming']['1077144430588469349'] = '1077144433096654934';
//$twitch_options['channels']['seigiva']['923969098185068594'] = '924019611534503996';
//strtolower($nick), // Your channel
//'smalltowngamingtv', // (Optional) Additional channels
//'rattlesire',
//'shrineplays',
//'violentvixen_',
//'linkdrako',
//'ebonychimera',
// Responses that reference other values in options should be declared afterwards
$twitch_options['responses']['social'] = 'Come follow the magick through several dimensions:  Twitter - '.$twitch_options['social']['twitter'].' |  Discord - '.$twitch_options['social']['discord'].' |  YouTube - '.$twitch_options['social']['youtube'];
$twitch_options['responses']['tip'] = 'Wanna help fund the magick?  PayPal - '.$twitch_options['tip']['paypal'];
$twitch_options['responses']['discord'] = $twitch_options['social']['discord'];

//$twitch = new Twitch\Twitch($twitch_options);

include 'db.php'; //$mysqli[$mysqli1, $mysqli2, $mysqli3], //$pdo[$pdo1, $pdo2, $pdo3]
$options = array(
    'loop' => $loop,
    'discord' => $discord,
    //'twitch' => $twitch,
    'twitch_options' => $twitch_options,
    'browser' => $browser,
    'logger' => $logger,
    'stats' => $stats,
    'mysqli' => $mysqli,
    'pdo' => $pdo,
    
    //Filecache
    //'filecache_path' = getcwd() . '/json/', // Manually change where cached files get saved to
    //'filecache_prefix' = 'tutelar;cache;', // Manually change automatically generated filenames to include this prefix (possibly easier to locate/ignore with other programs)
    
    //Configurations
    'command_symbol' => '!s',
    'owner_id' => '116927250145869826', //Valithor#5947
    'owner_guild_id' => '923969098185068594', //ValZarGaming
    'github' => 'https://github.com/VZGCoders/Palace-Revived',
    
    //Configurations
    'files' => [
        'statuslist' => getcwd() . '\status.txt',
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
        'init' => [
            'on_init' => $on_init,
            'status_changer_random' => $status_changer_random,
            'set_ips' => $set_ips,
        ],
        'init_slash' => [
            'slash_init' => $slash_init,
        ],
        'message' => [            
            'on_message' => $on_message,
        ],
        'GUILD_MEMBER_ADD' => [
            //
        ],
        'GUILD_MEMBER_REMOVE' => [
            //
        ],
        'GUILD_MEMBER_UPDATE' => [
            //
        ],
        'GUILD_BAN_ADD' => [
            //
        ],
        'GUILD_BAN_REMOVE' => [
            //
        ],
        'MESSAGE_UPDATE' => [
            //
        ],
        'MESSAGE_DELETE' => [
            //
        ],
        'MESSAGE_DELETE_BULK' => [
            //
        ],
        'userUpdate' => [
            //
        ],
        'misc' => [ //Custom functions
            //DiscordPHP
            'timeout' => $timeout,
            'status_changer' => $status_changer,
            'perm_check' => $perm_check,
            //ReactPHP Browser
            'browser_get' => $browser_get,
            'browser_post' => $browser_post,
            //Message functions
            'debug_direct_message' => $debug_direct_message,
            'debug_guild_message' => $debug_guild_message,
            'any_called_debug_message' => $any_called_debug_message,
            'any_debug_message' => $any_debug_message,
            'owner_message' => $owner_message,
            'manager_message' => $manager_message,
            'admin_message' => $admin_message,
            'moderator_message' => $moderator_message,
            'direct_message' => $direct_message,
            'guild_message' => $guild_message,
            'guild_called_message' => $guild_called_message,
            'any_called_message' => $any_called_message,
            'any_message' => $any_message,
        ],
    ],
);

if (include 'log_functions.php') {
    echo 'Included log functions' . PHP_EOL;
    $options['functions']['message']['log_message'] = $log_message;
    $options['functions']['MESSAGE_UPDATE']['log_MESSAGE_UPDATE'] = $log_MESSAGE_UPDATE;
    $options['functions']['MESSAGE_DELETE']['log_MESSAGE_DELETE'] = $log_MESSAGE_DELETE;
    $options['functions']['GUILD_MEMBER_ADD']['log_GUILD_MEMBER_ADD'] = $log_GUILD_MEMBER_ADD;
    $options['functions']['GUILD_MEMBER_REMOVE']['log_GUILD_MEMBER_REMOVE'] = $log_GUILD_MEMBER_REMOVE;
    $options['functions']['GUILD_MEMBER_UPDATE']['log_GUILD_MEMBER_UPDATE'] = $log_GUILD_MEMBER_UPDATE;
    $options['functions']['GUILD_BAN_ADD']['log_GUILD_BAN_ADD'] = $log_GUILD_BAN_ADD;
    $options['functions']['GUILD_BAN_REMOVE']['log_GUILD_BAN_REMOVE'] = $log_GUILD_BAN_REMOVE;
    $options['functions']['MESSAGE_DELETE_BULK']['log_MESSAGE_DELETE_BULK'] = $log_MESSAGE_DELETE_BULK;
    $options['functions']['userUpdate']['log_userUpdate'] = $log_userUpdate;
}
if (include 'ss13_functions.php') {
    echo 'Included ss13 functions' . PHP_EOL;
    $options['functions']['init_slash']['ss13_slash_init'] = $ss13_slash_init;
    $options['functions']['message']['ss13_on_message'] = $ss13_on_message;
    $options['functions']['misc']['discord2ckey'] = $discord2ckey;
}
if (include 'verifier_functions.php') {
    echo 'Included verifier functions' . PHP_EOL;
}

include 'tutelar.php';
$tutelar = new Tutelar\Tutelar($options);
if (include 'webapi.php') $tutelar->setWebAPI($webapi); //$socket, $webapi, $external_ip, webapiFail(), webapiSnow()
$tutelar->run();