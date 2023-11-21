<?php
/**
 * Initializes all database profiles, and sets the desired database profile
 * to be the active profile.
 *
 * @package minPHP
 */

// Lazy connecting will only establish a connection to the database if one is
// needed. If disabled, a connection will be attempted as soon as a Model is
// requested and a Database profile exists. Some models may not require a DB
// connection so it is recommended to leave this enabled.
Configure::set('Database.lazy_connecting', true);
Configure::set('Database.fetch_mode', PDO::FETCH_OBJ);
Configure::set('Database.reuse_connection', true);

Configure::load('blesta');

// Set the database profile
Configure::set('Database.profile', Configure::get('Blesta.database_info'));
