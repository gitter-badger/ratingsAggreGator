<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/

Route::get('/', function() {
    ( League::count() > 0 ) 
        ? $leagues = League::all() 
        : $leagues = null;

    return View::make('home', [
        'players' => Player::mostPopular(),
        'leagues' => $leagues
    ]);
});

Route::get('/register', [
    'as' => 'users.create',
    'uses' => 'UserController@create'
]);

Route::group(['before' => 'env'], function()
{
    Route::get('/admin/{anomaly}', [
        'as' => 'admin',
        'uses' => 'PlayerController@showAnomalousNames'
    ]);

    Route::get('/admin/{anomaly}/delete', [
        'as' => 'anomalousPlayers.delete',
        'uses' => 'PlayerController@deleteAnomalousNames'
    ]);
});

Route::get('test', function() {
    return View::make('magpie');
});

// Auto generate all CRUD routes to your controllers
Route::resource('attributes', 'AttributeController');

Route::resource('leagues', 'LeagueController');

Route::resource('teams', 'TeamController');

Route::resource('players', 'PlayerController');

Route::resource('ratings', 'RatingController');

Route::resource('users', 'UserController');

//Returns a collection of the most recent Tweets posted by the user indicated by the screen_name or user_id parameters.
Route::get('/userTimeLine', function()
{
    return Twitter::getUserTimeline(['screen_name' => 'iamsamgoml', 'count' => 20, 'format' => 'array']);
});


//Returns a collection of the most recent Tweets and retweets posted by the authenticating user and the users they follow.

Route::get('/homeTimeLine', function()
{
    return Twitter::getHomeTimeline(['count' => 20, 'format' => 'json']);
});

//Returns the X most recent mentions (tweets containing a users's @screen_name) for the authenticating user.

Route::get('/mentionsTimeLine', function()
{
    return Twitter::getMentionsTimeline(['count' => 20, 'format' => 'json']);
});

//Updates the authenticating user's current status, also known as tweeting.
Route::get('/postTweet', function()
{
    return Twitter::postTweet(['status' => 'Test Tweet2', 'format' => 'json']);
});

Route::get('/twitter/login', function()
{
    // the SIGN IN WITH TWITTER  button should point to this route

    //Clear any data from the session
    Session::clear();
    $sign_in_twitter = TRUE;
    $force_login = FALSE;

    //Define the callback url
    $callback_url = 'http://' . $_SERVER['HTTP_HOST'] . '/twitter/callback';

    // Make sure we make this request w/o tokens, overwrite the default values in case of login.
    Twitter::set_new_config(['token' => '', 'secret' => '']);
    $token = Twitter::getRequestToken($callback_url);
    if( isset( $token['oauth_token_secret'] ) ) {
        $url = Twitter::getAuthorizeURL($token, $sign_in_twitter, $force_login);

        //Add oauth state and oauth tokens into the session data
        Session::put('oauth_state', 'start');
        Session::put('oauth_request_token', $token['oauth_token']);
        Session::put('oauth_request_token_secret', $token['oauth_token_secret']);

        return Redirect::to($url);
    }
    return Redirect::to('twitter/error');
});

Route::get('/twitter/callback', function() {
    
    // this route is defined on the twitter application settings as the callback route 

    // https://apps.twitter.com/app/YOUR-APP-ID/settings

    //Check we have an oauth request token
    if(Session::has('oauth_request_token')) {
        $request_token = [
            'token' => Session::get('oauth_request_token'),
            'secret' => Session::get('oauth_request_token_secret'),
        ];

        Twitter::set_new_config($request_token);

        $oauth_verifier = FALSE;
        if(Input::has('oauth_verifier')) {
            $oauth_verifier = Input::get('oauth_verifier');
        }

        // getAccessToken() will reset the token for you
        $token = Twitter::getAccessToken( $oauth_verifier );
        
        //check if token contains the oauth token secret
        //if not then redirect user to home page with an error
        if( !isset( $token['oauth_token_secret'] ) ) {
            return Redirect::to('/')->with('flash_error', 'We could not log you in on Twitter.');
        }

        $credentials = Twitter::query('account/verify_credentials');
        if( is_object( $credentials ) && !isset( $credentials->error ) ) {
            // $credentials contains the Twitter user object with all the info about the user.

            // !kint::dump($credentials);return;
          
            //obtain users id (from twitters database)
            $twitterID = $credentials->id;

            // determine if we need to create a new user or not
            // to do this, first check to see if a user with the
            // given twitter id exists in the users table, if so,
            // then just log them in. if not, create a new user.
            if (! ($user = User::whereTwitterID($twitterID)->first() )) {

                $nameTokens = explode(' ', $credentials->name);
                $firstName = array_key_exists(0, $nameTokens)
                    ? $nameTokens[0]
                    : '';
                
                $lastName = array_key_exists(1, $nameTokens)
                    ? $nameTokens[1]
                    : '';                

                $user = User::create(array(
                    'first_name'        => $firstName,
                    'surname'           => $lastName,
                    'username'          => $credentials->screen_name,
                    'password'          => Hash::make( Hash::make(null) ),
                    'twitter_id'        => $twitterID,
                    // 'screen_name'       => $screenName,
                    'oauth_token'       => $token['oauth_token'],
                    'oauth_token_secret'=> $token['oauth_token_secret']
                    // 'access_token'      => $token
                ));
            }

            // only get here if we have either a) found an existing user, or
            // b) created one - time to now log them in!
            Auth::login($user);

            // Redirect user to home page with a success message
            return Redirect::to('/')->with('flash_notice', "Congrats! You've successfully signed in!");
        }
            // Redirect user to home page with an error message
       return Redirect::to('/')->with('flash_error', 'Crab! Something went wrong while signing you up!');
    }
});

Route::get('twitter/error', function(){
    // An error occured
    //TODO: Add some error handling here
});

Route::post('login', [
    'as' => 'users.login',
    'uses' => 'UserController@login'
]);

Route::get('logout', [
    'as' => 'users.logout',
    'uses' => 'UserController@logout'
]);


Route::get('search/{query}', [
    'as' => 'players.search',
    'uses' => 'PlayerController@search'
]);

Route::get('hello', [
    'as' => 'hello',
    'uses' => 'PlayerController@getRandomPlayers'
]);

Route::get('test', [
        'as' => 'test',
        'uses' => 'ScrapeImages2@test'
]);

Route::get('ScrapeImage', 
    ['uses' => 'ScrapeImages2@foo']
);

Route::get('/countries', function()
{
   print_r( ( json_decode( Countries::getList('en', 'json', 'cldr')) ) );
});

Route::post('register', [
    'as' => 'user.store',
    'uses' => 'UserController@store'
]);

// navbar footer routes
Route::get('/about/meet-the-team', [
    'as' => 'meet-the-team',
    function(){
        return View::make('meet-the-team');
    }
]);

Route::get('/help/contact-us', [
    'as' => 'contact-us',
    function(){
        return View::make('contact-us');
    }
]);

Route::get('/tottPlayers', [
    'as' => 'tottPlayers',
    'uses' => 'PlayerController@getAllPlayersOfTeam'
]);
