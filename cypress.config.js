const { defineConfig } = require( 'cypress' );

module.exports = defineConfig( {
	screenshotOnRunFailure: false,
	video: false,
	e2e: {
		specPattern: 'cypress/integration/**/*.js',
		supportFile: 'cypress/support/index.js',
		setupNodeEvents( on, config ) {
			return require( './cypress/plugins/index.js' )( on, config );
		},
	},
} );
