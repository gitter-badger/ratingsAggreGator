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

// this function should be moved to seeds when possible
Route::get('tinker', function () {
    $raw = File::get('simple.json');
    $json = json_decode($raw, true);

    if ($json) {
        foreach ($json['teams'] as $team) {

            // create team model
            if (! Team::whereName($team['name'])->count() )
                $teamModel = Team::create(['name' => $team['name']]);


            echo $team['name'] . "<br>";
            foreach ($team['players'] as $player) {

                $values = array_only($player, ['name']);
                if ( array_key_exists('name', $values) && $values['name']) {
                    $playerModel = Player::create([
                        'name' => $player['name']
                    ]);

                }
            }
        }
    }
});

Route::get('/',  [
    'as' => 'home',
    'uses' => 'RatingController@mostPopular'
]);

Route::get('/register', [
    'as' => 'users.create',
    'uses' => 'UserController@create'
]);

Route::get('profile', function() {
    return View::make('player-profile');
});

// Auto generate all CRUD routes to your controllers
Route::resource('attributes', 'AttributeController');

Route::resource('players', 'PlayerController');

Route::resource('ratings', 'RatingController');

Route::resource('users', 'UserController');

Route::post('login', [
    'as' => 'users.login',
    'uses' => 'UserController@login'
]);

Route::get('logout', [
    'as' => 'users.logout',
    'uses' => 'UserController@logout'
]);


Route::get(
    'search/{query}',
    array(
        'as' => 'players.search',
        'uses' => 'PlayerController@search'
    )
);

Route::get('hello', array(
        'as' => 'hello',
        'uses' => 'PlayerController@getRandomPlayers'
    )
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