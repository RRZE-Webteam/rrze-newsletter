const assert = require( 'node:assert/strict' );
const { execFileSync } = require( 'node:child_process' );
const path = require( 'node:path' );
const { test } = require( 'node:test' );

test( 'production settings register the opt-in design checkbox under the renderer option name', () => {
	// Isolated CLI process: no WordPress bootstrap, database or test schema replacements.
	const config = JSON.parse( execFileSync( 'php', [ '-r', `
		define('ABSPATH', getcwd() . '/');
		function __($value, $domain) { return $value; }
		function get_option($name) { return 'admin@example.test'; }
		require 'vendor/autoload.php';
		require 'config/settings.php';
		echo json_encode([
			'option' => RRZE\\Newsletter\\Config\\getOptionName(),
			'sections' => RRZE\\Newsletter\\Config\\getSections(),
			'fields' => RRZE\\Newsletter\\Config\\getFields(),
		]);
	` ], { cwd: path.join( __dirname, '../..' ), encoding: 'utf8', timeout: 10000 } ) );
	assert.equal( config.option, 'rrze_newsletter' );
	assert.ok( config.sections.some( ( section ) => section.id === 'design' ) );
	const field = config.fields.design.find( ( entry ) => entry.name === 'managed_spacing' );
	assert.equal( field.type, 'checkbox' );
	assert.equal( field.default, 'off' );
} );
