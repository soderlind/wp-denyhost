module.exports = function(grunt) {
    'use strict';

    grunt.config('phpcs', {
        application: {
            src: [
				//'<%= directories.php %>',
				'<%= files.php %>'
			]
        },
        options: {
            bin: '/usr/local/bin/phpcs',
            standard: 'WordPress-Core',
            ignore: 'database',
            extensions: 'php',
			exclude: [
				'<%= files.exclude %>'
			],
        }
    });

    grunt.loadNpmTasks('grunt-phpcs');

};
